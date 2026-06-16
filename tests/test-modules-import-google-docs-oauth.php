<?php

use Pressbooks\Modules\Import\GoogleDocs\CredentialsStore;
use Pressbooks\Modules\Import\GoogleDocs\OAuthClient;
use Pressbooks\Modules\Import\GoogleDocs\Storage\BrokerBackedStorage;
use Pressbooks\Modules\Import\GoogleDocs\Storage\DirectEncryptedStorage;
use Pressbooks\Modules\Import\GoogleDocs\Storage\SodiumCipher;
use Pressbooks\Modules\Import\GoogleDocs\Storage\StoredToken;
use Pressbooks\Modules\Import\GoogleDocs\Storage\TokenMode;
use Firebase\JWT\JWT;

class Modules_ImportGoogleDocsOAuthTest extends \WP_UnitTestCase {

	private CredentialsStore $store;

	private int $user_id;

	private static ?string $broker_public_key = null;

	private static ?string $broker_private_key = null;

	private static string $encryption_key;

	private static string $state = 'test-state';

	public static function wpSetUpBeforeClass(): void {
		$config = [
			'digest_alg' => 'sha256',
			'private_key_bits' => 2048,
			'private_key_type' => OPENSSL_KEYTYPE_RSA,
		];
		$key = openssl_pkey_new( $config );
		if ( $key ) {
			openssl_pkey_export( $key, $priv_pem );
			self::$broker_private_key = $priv_pem;
			$details = openssl_pkey_get_details( $key );
			self::$broker_public_key = $details['key'];
		}

		self::$encryption_key = sodium_bin2base64(
			random_bytes( SODIUM_CRYPTO_SECRETBOX_KEYBYTES ),
			SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING
		);
		if ( ! defined( 'PRESSBOOKS_GOOGLE_DOCS_ENCRYPTION_KEY' ) ) {
			define( 'PRESSBOOKS_GOOGLE_DOCS_ENCRYPTION_KEY', self::$encryption_key );
		}
		if ( ! defined( 'PRESSBOOKS_AUTH_BROKER_NETWORK_SECRET' ) ) {
			define( 'PRESSBOOKS_AUTH_BROKER_NETWORK_SECRET', sodium_bin2base64( random_bytes( 32 ), SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING ) );
		}
	}

	public function set_up(): void {
		parent::set_up();
		$this->store = new CredentialsStore();
		$this->store->saveClientCredentials( 'test-client-id', 'test-client-secret' );
		$this->user_id = self::factory()->user->create();
	}

	public function tear_down(): void {
		delete_site_option( CredentialsStore::NETWORK_OPTION_KEY );
		delete_user_meta( $this->user_id, DirectEncryptedStorage::META_KEY );
		delete_user_meta( $this->user_id, BrokerBackedStorage::META_KEY );
		parent::tear_down();
	}

	private function make_oauth_client( bool $broker_mode ): OAuthClient {
		$cipher = new SodiumCipher();
		$storage = $broker_mode
			? new BrokerBackedStorage( $cipher, self::$encryption_key )
			: new DirectEncryptedStorage( $cipher, self::$encryption_key );
		return new OAuthClient(
			$storage,
			new CredentialsStore()
		);
	}

	/**
	 * @group import
	 */
	public function test_get_authorize_url_contains_required_params(): void {
		$oauth = $this->make_oauth_client( false );
		$url = $oauth->getAuthorizeUrl( 'https://example.com/return' );
		$this->assertStringContainsString( 'accounts.google.com', $url );
		$this->assertStringContainsString( 'client_id=test-client-id', $url );
		$this->assertStringContainsString( 'access_type=offline', $url );
		$this->assertStringContainsString( 'documents.readonly', urldecode( $url ) );
		$this->assertStringContainsString( 'drive.readonly', urldecode( $url ) );
	}

	/**
	 * @group import
	 */
	public function test_get_authorize_url_stores_state_transient(): void {
		$oauth = $this->make_oauth_client( false );
		$url = $oauth->getAuthorizeUrl( 'https://example.com/return' );
		parse_str( parse_url( $url, PHP_URL_QUERY ), $params );
		$state = $params['state'] ?? '';
		$this->assertNotEmpty( $state );
		$return = get_site_transient( 'pb_gdocs_state_' . $state );
		$this->assertSame( 'https://example.com/return', $return );
	}

	/**
	 * @group import
	 */
	public function test_extract_doc_id_from_url(): void {
		$this->assertSame(
			'1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgVE2upms',
			OAuthClient::extractDocId( 'https://docs.google.com/document/d/1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgVE2upms/edit' )
		);
		$this->assertSame(
			'1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgVE2upms',
			OAuthClient::extractDocId( 'https://docs.google.com/document/d/1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgVE2upms/edit#heading=h.abc' )
		);
		$this->assertNull( OAuthClient::extractDocId( 'https://docs.google.com/spreadsheets/d/abc/edit' ) );
		$this->assertNull( OAuthClient::extractDocId( 'not-a-url' ) );
	}

	/**
	 * @group import
	 */
	public function test_get_authed_client_throws_when_no_token(): void {
		$this->expectException( \Pressbooks\Modules\Import\GoogleDocs\ReauthorizationRequiredException::class );
		$oauth = $this->make_oauth_client( false );
		$oauth->getAuthedClient( $this->user_id );
	}

	/**
	 * @group import
	 */
	public function test_get_authed_client_returns_client_when_token_valid(): void {
		$cipher = new SodiumCipher();
		$storage = new DirectEncryptedStorage( $cipher, self::$encryption_key );
		$storage->save(
			$this->user_id,
			new StoredToken(
				[
					'access_token'  => 'valid-token',
					'refresh_token' => 'rt',
					'expires_at'    => time() + 3600,
					'expires_in'    => 3600,
					'created'       => time(),
				],
				TokenMode::Direct
			)
		);

		$oauth = new OAuthClient( $storage, new CredentialsStore() );
		$client = $oauth->getAuthedClient( $this->user_id );
		$this->assertInstanceOf( \Google\Client::class, $client );
		$this->assertSame( 'valid-token', $client->getAccessToken()['access_token'] );
	}

	/**
	 * @group import
	 */
	public function test_is_broker_mode_returns_false_when_not_configured(): void {
		$oauth = $this->make_oauth_client( false );
		$this->assertFalse( $oauth->isBrokerMode() );
	}

	private function skipWithoutBrokerKeys(): void {
		if ( ! self::$broker_public_key || ! self::$broker_private_key ) {
			$this->markTestSkipped( 'Broker keys not found at expected path.' );
		}
	}

	private function defineBrokerConstants(): void {
		if ( ! defined( 'PRESSBOOKS_AUTH_BROKER_URL' ) ) {
			define( 'PRESSBOOKS_AUTH_BROKER_URL', 'https://auth-broker.example.com' );
		}
		if ( ! defined( 'PRESSBOOKS_AUTH_BROKER_PUBLIC_KEY' ) ) {
			define( 'PRESSBOOKS_AUTH_BROKER_PUBLIC_KEY', self::$broker_public_key );
		}
	}

	private function createBrokerJwt( array $overrides = [] ): string {
		$payload = array_merge(
			[
				'iss'       => PRESSBOOKS_AUTH_BROKER_URL,
				'aud'       => parse_url( home_url(), PHP_URL_HOST ),
				'tokens'    => [
					'access_token'   => 'broker-access-token',
					'expires_at'     => time() + 3600,
					'token_type'     => 'Bearer',
					'session_handle' => 'broker-session-handle',
				],
				'google_sub' => 'broker-google-sub',
				'wp_state'  => self::$state,
				'jti'       => wp_generate_password( 32, false ),
				'iat'       => time(),
				'exp'       => time() + 60,
			],
			$overrides
		);
		return JWT::encode( $payload, self::$broker_private_key, 'RS256' );
	}

	/**
	 * @group import
	 */
	public function test_get_authorize_url_uses_broker_when_configured(): void {
		$this->skipWithoutBrokerKeys();
		$this->defineBrokerConstants();

		$oauth = $this->make_oauth_client( true );
		$this->assertTrue( $oauth->isBrokerMode() );

		$url = $oauth->getAuthorizeUrl( 'https://example.com/return' );
		$this->assertStringContainsString( 'auth-broker.example.com', $url );
		$this->assertStringContainsString( 'oauth/start', $url );

		$parsed = parse_url( $url );
		parse_str( $parsed['query'] ?? '', $params );
		$this->assertArrayHasKey( 'origin', $params );
		$this->assertArrayHasKey( 'wp_state', $params );
		$this->assertNotEmpty( $params['wp_state'] );

		$state = $params['wp_state'];
		$this->assertSame( 'https://example.com/return', get_site_transient( 'pb_gdocs_state_' . $state ) );
	}

	/**
	 * @group import
	 */
	public function test_handle_callback_verifies_jwt_and_stores_tokens(): void {
		$this->skipWithoutBrokerKeys();
		$this->defineBrokerConstants();

		$cipher = new SodiumCipher();
		$storage = new BrokerBackedStorage( $cipher, self::$encryption_key );
		$oauth = new OAuthClient( $storage, new CredentialsStore() );
		$this->assertTrue( $oauth->isBrokerMode() );

		$state = 'test-state-value';
		set_site_transient( 'pb_gdocs_state_' . $state, 'https://example.com/return', 600 );

		$jwt = $this->createBrokerJwt( [ 'wp_state' => $state ] );

		$return_url = $oauth->handleCallback( $jwt, $state, $this->user_id );
		$this->assertSame( 'https://example.com/return', $return_url );

		$stored = $storage->load( $this->user_id );
		$this->assertNotNull( $stored );
		$this->assertSame( 'broker-access-token', $stored->accessToken() );
		$this->assertSame( 'broker-session-handle', $stored->brokerSessionHandle() );
		$this->assertSame( 'broker-google-sub', $stored->googleSub() );
		$this->assertNull( $stored->refreshToken(), 'Broker mode must never persist a refresh token' );

		$this->assertFalse( get_site_transient( 'pb_gdocs_state_' . $state ) );
	}

	/**
	 * @group import
	 */
	public function test_handle_callback_rejects_expired_jwt(): void {
		$this->skipWithoutBrokerKeys();
		$this->defineBrokerConstants();

		$oauth = $this->make_oauth_client( true );
		$state = 'expired-state';
		set_site_transient( 'pb_gdocs_state_' . $state, 'https://example.com/return', 600 );

		$jwt = $this->createBrokerJwt(
			[
				'wp_state' => $state,
				'exp'      => time() - 300,
			]
		);

		$this->expectException( \RuntimeException::class );
		$oauth->handleCallback( $jwt, $state, $this->user_id );
	}

	/**
	 * @group import
	 */
	public function test_handle_callback_rejects_wrong_issuer(): void {
		$this->skipWithoutBrokerKeys();
		$this->defineBrokerConstants();

		$oauth = $this->make_oauth_client( true );
		$state = 'wrong-issuer-state';
		set_site_transient( 'pb_gdocs_state_' . $state, 'https://example.com/return', 600 );

		$jwt = $this->createBrokerJwt(
			[
				'wp_state' => $state,
				'iss'      => 'https://evil.example.com',
			]
		);

		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'Invalid JWT issuer.' );
		$oauth->handleCallback( $jwt, $state, $this->user_id );
	}

	/**
	 * @group import
	 */
	public function test_handle_callback_rejects_wrong_audience(): void {
		$this->skipWithoutBrokerKeys();
		$this->defineBrokerConstants();

		$oauth = $this->make_oauth_client( true );
		$state = 'wrong-aud-state';
		set_site_transient( 'pb_gdocs_state_' . $state, 'https://example.com/return', 600 );

		$jwt = $this->createBrokerJwt(
			[
				'wp_state' => $state,
				'aud'      => 'evil-host.example.com',
			]
		);

		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'Invalid JWT audience.' );
		$oauth->handleCallback( $jwt, $state, $this->user_id );
	}

	/**
	 * @group import
	 */
	public function test_handle_callback_rejects_state_mismatch(): void {
		$this->skipWithoutBrokerKeys();
		$this->defineBrokerConstants();

		$oauth = $this->make_oauth_client( true );
		$state = 'good-state';
		set_site_transient( 'pb_gdocs_state_' . $state, 'https://example.com/return', 600 );

		$jwt = $this->createBrokerJwt( [ 'wp_state' => 'wrong-state' ] );

		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'JWT state mismatch.' );
		$oauth->handleCallback( $jwt, $state, $this->user_id );
	}

	/**
	 * @group import
	 */
	public function test_handle_callback_rejects_refresh_token_in_jwt(): void {
		$this->skipWithoutBrokerKeys();
		$this->defineBrokerConstants();

		$oauth = $this->make_oauth_client( true );
		$state = 'refresh-token-state';
		set_site_transient( 'pb_gdocs_state_' . $state, 'https://example.com/return', 600 );

		$jwt = $this->createBrokerJwt(
			[
				'wp_state' => $state,
				'tokens'   => [
					'access_token'   => 'broker-access-token',
					'expires_at'     => time() + 3600,
					'token_type'     => 'Bearer',
					'session_handle' => 'broker-session-handle',
					'refresh_token'  => 'should-not-be-here',
				],
			]
		);

		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'must not contain a refresh_token' );
		$oauth->handleCallback( $jwt, $state, $this->user_id );
	}

	/**
	 * @group import
	 */
	public function test_handle_callback_rejects_replayed_jwt(): void {
		$this->skipWithoutBrokerKeys();
		$this->defineBrokerConstants();

		$oauth = $this->make_oauth_client( true );
		$jti = wp_generate_password( 32, false );

		$state1 = 'replay-state-1';
		set_site_transient( 'pb_gdocs_state_' . $state1, 'https://example.com/return', 600 );
		$jwt = $this->createBrokerJwt( [ 'wp_state' => $state1, 'jti' => $jti ] );
		$oauth->handleCallback( $jwt, $state1, $this->user_id );

		$state2 = 'replay-state-2';
		set_site_transient( 'pb_gdocs_state_' . $state2, 'https://example.com/return', 600 );
		$jwt2 = $this->createBrokerJwt( [ 'wp_state' => $state2, 'jti' => $jti ] );

		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'JWT has already been used.' );
		$oauth->handleCallback( $jwt2, $state2, $this->user_id );
	}

	/**
	 * @group import
	 */
	public function test_handle_callback_rejects_invalid_state(): void {
		$this->skipWithoutBrokerKeys();
		$this->defineBrokerConstants();

		$oauth = $this->make_oauth_client( true );
		$state = 'no-transient-state';

		$jwt = $this->createBrokerJwt( [ 'wp_state' => $state ] );

		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'Invalid or expired OAuth state.' );
		$oauth->handleCallback( $jwt, $state, $this->user_id );
	}

	/**
	 * @group import
	 */
	public function test_get_authed_client_calls_broker_refresh_when_expired(): void {
		$this->skipWithoutBrokerKeys();
		$this->defineBrokerConstants();

		$cipher = new SodiumCipher();
		$storage = new BrokerBackedStorage( $cipher, self::$encryption_key );
		$oauth = new OAuthClient( $storage, new CredentialsStore() );

		$stub = $this->getMockBuilder( \Pressbooks\Modules\Import\GoogleDocs\Broker\BrokerRefreshClient::class )
			->disableOriginalConstructor()
			->getMock();
		$stub->method( 'refresh' )
			->willReturnCallback( function ( $user_id ) use ( $storage ) {
				$storage->save( $user_id, new StoredToken(
					[
						'session_handle' => 'sh-abc',
						'access_token'   => 'fresh-at',
						'expires_at'     => time() + 3600,
						'google_sub'     => 'sub-001',
					],
					TokenMode::Broker
				) );
				return $storage->load( $user_id );
			} );
		$oauth->setBrokerRefreshClient( $stub );

		$storage->save( $this->user_id, new StoredToken(
			[
				'session_handle' => 'sh-abc',
				'access_token'   => 'expired-at',
				'expires_at'     => time() - 60,
				'google_sub'     => 'sub-001',
			],
			TokenMode::Broker
		) );

		$client = $oauth->getAuthedClient( $this->user_id );
		$this->assertInstanceOf( \Google\Client::class, $client );
		$this->assertSame( 'fresh-at', $client->getAccessToken()['access_token'] );
	}
}
