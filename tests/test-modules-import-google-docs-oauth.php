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
		$this->store = CredentialsStore::fromEnvironment();
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
			CredentialsStore::fromEnvironment()
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

		$oauth = new OAuthClient( $storage, CredentialsStore::fromEnvironment() );
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
				'aud'       => parse_url( network_home_url(), PHP_URL_HOST ),
				'tokens'    => [
					'access_token'   => 'broker-access-token',
					'expires_at'     => time() + 3600,
					'token_type'     => 'Bearer',
				],
				'session_handle' => 'broker-session-handle',
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
		$oauth = new OAuthClient( $storage, CredentialsStore::fromEnvironment() );
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
		$oauth = new OAuthClient( $storage, CredentialsStore::fromEnvironment() );

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

	// ─── Direct mode helpers ──────────────────────────────────────────────

	private function makeDirectOAuth( \Pressbooks\Modules\Import\GoogleDocs\Storage\TokenStorage $storage ): array {
		$creds = $this->createMock( CredentialsStore::class );
		$creds->method( 'isBrokerMode' )->willReturn( false );
		$creds->method( 'getClientCredentials' )->willReturn( [
			'client_id'     => 'test_client_id',
			'client_secret' => 'test_client_secret',
		] );

		$mock_google = $this->createMock( \Google\Client::class );

		$oauth = $this->getMockBuilder( OAuthClient::class )
			->setConstructorArgs( [ $storage, $creds ] )
			->onlyMethods( [ 'buildClient' ] )
			->getMock();
		$oauth->method( 'buildClient' )->willReturn( $mock_google );

		return [ $oauth, $mock_google ];
	}

	/**
	 * @group import
	 */
	public function test_disconnect_is_noop_when_no_token(): void {
		$storage = $this->createMock( \Pressbooks\Modules\Import\GoogleDocs\Storage\TokenStorage::class );
		$storage->method( 'load' )->willReturn( null );
		$storage->expects( $this->never() )->method( 'delete' );

		[ $oauth ] = $this->makeDirectOAuth( $storage );
		$oauth->disconnect( 1 );
		$this->assertTrue( true ); // must not throw
	}

	/**
	 * @group import
	 */
	public function test_disconnect_deletes_token_in_direct_mode(): void {
		$token = new StoredToken(
			[ 'access_token' => 'tok', 'expires_at' => time() + 3600 ],
			TokenMode::Direct
		);

		$storage = $this->createMock( \Pressbooks\Modules\Import\GoogleDocs\Storage\TokenStorage::class );
		$storage->method( 'load' )->willReturn( $token );
		$storage->expects( $this->once() )->method( 'delete' )->with( 42 );

		[ $oauth, $mock_google ] = $this->makeDirectOAuth( $storage );
		$mock_google->method( 'revokeToken' )->willReturn( null );

		$oauth->disconnect( 42 );
	}

	/**
	 * @group import
	 */
	public function test_disconnect_throws_when_google_revoke_fails(): void {
		$token = new StoredToken(
			[ 'access_token' => 'tok', 'expires_at' => time() + 3600 ],
			TokenMode::Direct
		);

		$storage = $this->createMock( \Pressbooks\Modules\Import\GoogleDocs\Storage\TokenStorage::class );
		$storage->method( 'load' )->willReturn( $token );

		[ $oauth, $mock_google ] = $this->makeDirectOAuth( $storage );
		$mock_google->method( 'revokeToken' )
			->willThrowException( new \Exception( 'revoke failed' ) );

		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessageMatches( '/Failed to revoke token at Google/' );
		$oauth->disconnect( 1 );
	}

	/**
	 * @group import
	 */
	public function test_handle_callback_direct_mode_stores_token_and_returns_url(): void {
		$user_id    = $this->factory->user->create();
		$state      = 'state_direct_123';
		$return_url = 'https://example.com/return';
		set_site_transient( 'pb_gdocs_state_' . $state, $return_url, 600 );

		$saved_token = null;
		$storage     = $this->createMock( \Pressbooks\Modules\Import\GoogleDocs\Storage\TokenStorage::class );
		$storage->method( 'save' )->willReturnCallback( function ( $uid, $tok ) use ( &$saved_token ) {
			$saved_token = $tok;
			return true;
		} );

		[ $oauth, $mock_google ] = $this->makeDirectOAuth( $storage );
		$mock_google->method( 'fetchAccessTokenWithAuthCode' )->willReturn( [
			'access_token'  => 'access_123',
			'refresh_token' => 'refresh_456',
			'expires_in'    => 3600,
			'token_type'    => 'Bearer',
		] );

		$result = $oauth->handleCallback( 'auth_code', $state, $user_id );

		$this->assertSame( $return_url, $result );
		$this->assertNotNull( $saved_token );
		$this->assertSame( 'access_123', $saved_token->accessToken() );
		$this->assertSame( 'refresh_456', $saved_token->refreshToken() );
	}

	/**
	 * @group import
	 */
	public function test_handle_callback_direct_mode_throws_on_token_error(): void {
		$state = 'state_error_456';
		set_site_transient( 'pb_gdocs_state_' . $state, 'https://example.com', 600 );

		$storage = $this->createMock( \Pressbooks\Modules\Import\GoogleDocs\Storage\TokenStorage::class );
		[ $oauth, $mock_google ] = $this->makeDirectOAuth( $storage );
		$mock_google->method( 'fetchAccessTokenWithAuthCode' )->willReturn( [
			'error'             => 'invalid_grant',
			'error_description' => 'Token has been expired or revoked.',
		] );

		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessageMatches( '/Token exchange failed/' );
		$oauth->handleCallback( 'bad_code', $state, 1 );
	}

	/**
	 * @group import
	 */
	public function test_get_authed_client_refreshes_expired_direct_token(): void {
		$user_id        = $this->factory->user->create();
		$original_token = new StoredToken(
			[
				'access_token'  => 'old_access',
				'refresh_token' => 'my_refresh',
				'expires_at'    => time() - 100,
			],
			TokenMode::Direct
		);

		$saved_token = null;
		$storage     = $this->createMock( \Pressbooks\Modules\Import\GoogleDocs\Storage\TokenStorage::class );
		$storage->method( 'load' )->willReturn( $original_token );
		$storage->method( 'save' )->willReturnCallback( function ( $uid, $tok ) use ( &$saved_token ) {
			$saved_token = $tok;
			return true;
		} );

		[ $oauth, $mock_google ] = $this->makeDirectOAuth( $storage );
		$mock_google->method( 'isAccessTokenExpired' )->willReturn( true );
		$mock_google->method( 'fetchAccessTokenWithRefreshToken' )
			->with( 'my_refresh' )
			->willReturn( [
				'access_token' => 'new_access',
				'expires_in'   => 3600,
				'token_type'   => 'Bearer',
			] );

		$client = $oauth->getAuthedClient( $user_id );

		$this->assertInstanceOf( \Google\Client::class, $client );
		$this->assertNotNull( $saved_token );
		$this->assertSame( 'new_access', $saved_token->accessToken() );
		$this->assertSame( 'my_refresh', $saved_token->refreshToken() );
	}

	/**
	 * @group import
	 */
	public function test_get_authed_client_throws_when_no_refresh_token(): void {
		$user_id = $this->factory->user->create();
		$token   = new StoredToken(
			[ 'access_token' => 'old', 'expires_at' => time() - 100 ],
			TokenMode::Direct
		);

		$storage = $this->createMock( \Pressbooks\Modules\Import\GoogleDocs\Storage\TokenStorage::class );
		$storage->method( 'load' )->willReturn( $token );
		$storage->expects( $this->once() )->method( 'delete' )->with( $user_id );

		[ $oauth, $mock_google ] = $this->makeDirectOAuth( $storage );
		$mock_google->method( 'isAccessTokenExpired' )->willReturn( true );

		$this->expectException( \Pressbooks\Modules\Import\GoogleDocs\ReauthorizationRequiredException::class );
		$oauth->getAuthedClient( $user_id );
	}

	/**
	 * @group import
	 */
	public function test_get_authed_client_throws_when_direct_refresh_returns_error(): void {
		$user_id = $this->factory->user->create();
		$token   = new StoredToken(
			[
				'access_token'  => 'old',
				'refresh_token' => 'stale_refresh',
				'expires_at'    => time() - 100,
			],
			TokenMode::Direct
		);

		$storage = $this->createMock( \Pressbooks\Modules\Import\GoogleDocs\Storage\TokenStorage::class );
		$storage->method( 'load' )->willReturn( $token );
		$storage->expects( $this->once() )->method( 'delete' )->with( $user_id );

		[ $oauth, $mock_google ] = $this->makeDirectOAuth( $storage );
		$mock_google->method( 'isAccessTokenExpired' )->willReturn( true );
		$mock_google->method( 'fetchAccessTokenWithRefreshToken' )->willReturn( [
			'error'             => 'invalid_grant',
			'error_description' => 'Token has been expired or revoked.',
		] );

		$this->expectException( \Pressbooks\Modules\Import\GoogleDocs\ReauthorizationRequiredException::class );
		$oauth->getAuthedClient( $user_id );
	}
}
