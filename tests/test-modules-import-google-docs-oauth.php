<?php

use Pressbooks\Modules\Import\GoogleDocs\CredentialsStore;
use Pressbooks\Modules\Import\GoogleDocs\OAuthClient;
use Firebase\JWT\JWT;

class Modules_ImportGoogleDocsOAuthTest extends \WP_UnitTestCase {

	private CredentialsStore $store;
	private int $user_id;

	private static ?string $broker_public_key = null;
	private static ?string $broker_private_key = null;

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
	}

	public function set_up(): void {
		parent::set_up();
		$this->store = new CredentialsStore();
		$this->store->saveClientCredentials( 'test-client-id', 'test-client-secret' );
		$this->user_id = self::factory()->user->create();
	}

	public function tear_down(): void {
		delete_site_option( CredentialsStore::NETWORK_OPTION_KEY );
		delete_user_meta( $this->user_id, CredentialsStore::USER_META_KEY );
		parent::tear_down();
	}

	/**
	 * @group import
	 */
	public function test_get_authorize_url_contains_required_params(): void {
		$oauth = new OAuthClient( $this->store );
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
		$oauth = new OAuthClient( $this->store );
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
		$oauth = new OAuthClient( $this->store );
		$oauth->getAuthedClient( $this->user_id );
	}

	/**
	 * @group import
	 */
	public function test_get_authed_client_returns_client_when_token_valid(): void {
		$this->store->saveUserToken( $this->user_id, [
			'access_token'  => 'valid-token',
			'refresh_token' => 'rt',
			'expires_at'    => time() + 3600,
			'expires_in'    => 3600,
			'created'       => time(),
		] );
		$oauth = new OAuthClient( $this->store );
		$client = $oauth->getAuthedClient( $this->user_id );
		$this->assertInstanceOf( \Google\Client::class, $client );
		$this->assertSame( 'valid-token', $client->getAccessToken()['access_token'] );
	}

	/**
	 * @group import
	 */
	public function test_is_broker_mode_returns_false_when_not_configured(): void {
		$oauth = new OAuthClient( $this->store );
		$this->assertFalse( $oauth->isBrokerMode() );
	}

	private function skipWithoutBrokerKeys(): void {
		if ( ! self::$broker_public_key || ! self::$broker_private_key ) {
			$this->markTestSkipped( 'Broker keys not found at expected path.' );
		}
	}

	private function createBrokerJwt( array $overrides = [] ): string {
		$host = parse_url( home_url(), PHP_URL_HOST );
		$payload = array_merge( [
			'iss' => 'https://auth-broker.example.com',
			'aud' => $host,
			'exp' => time() + 300,
			'iat' => time(),
			'jti' => bin2hex( random_bytes( 16 ) ),
			'wp_state' => 'test-state',
			'tokens' => [
				'access_token' => 'broker-access-token',
				'refresh_token' => 'broker-refresh-token',
				'expires_in' => 3600,
				'token_type' => 'Bearer',
			],
		], $overrides );
		return JWT::encode( $payload, self::$broker_private_key, 'RS256' );
	}

	/**
	 * @group import
	 */
	public function test_get_authorize_url_uses_broker_when_configured(): void {
		$this->skipWithoutBrokerKeys();

		$broker_url = 'https://auth-broker.example.com';
		$public_key = self::$broker_public_key;

		if ( ! defined( 'PRESSBOOKS_AUTH_BROKER_URL' ) ) {
			define( 'PRESSBOOKS_AUTH_BROKER_URL', $broker_url );
		}
		if ( ! defined( 'PRESSBOOKS_AUTH_BROKER_PUBLIC_KEY' ) ) {
			define( 'PRESSBOOKS_AUTH_BROKER_PUBLIC_KEY', $public_key );
		}

		$oauth = new OAuthClient( $this->store );
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

		$broker_url = 'https://auth-broker.example.com';
		$public_key = self::$broker_public_key;

		if ( ! defined( 'PRESSBOOKS_AUTH_BROKER_URL' ) ) {
			define( 'PRESSBOOKS_AUTH_BROKER_URL', $broker_url );
		}
		if ( ! defined( 'PRESSBOOKS_AUTH_BROKER_PUBLIC_KEY' ) ) {
			define( 'PRESSBOOKS_AUTH_BROKER_PUBLIC_KEY', $public_key );
		}

		$oauth = new OAuthClient( $this->store );
		$this->assertTrue( $oauth->isBrokerMode() );

		$state = 'test-state-value';
		set_site_transient( 'pb_gdocs_state_' . $state, 'https://example.com/return', 600 );

		$jwt = $this->createBrokerJwt( [ 'wp_state' => $state ] );

		$return_url = $oauth->handleCallback( $jwt, $state, $this->user_id );
		$this->assertSame( 'https://example.com/return', $return_url );

		$stored = $this->store->getUserToken( $this->user_id );
		$this->assertNotNull( $stored );
		$this->assertSame( 'broker-access-token', $stored['access_token'] );
		$this->assertSame( 'broker-refresh-token', $stored['refresh_token'] );
		$this->assertArrayHasKey( 'expires_at', $stored );

		$this->assertFalse( get_site_transient( 'pb_gdocs_state_' . $state ) );
	}

	/**
	 * @group import
	 */
	public function test_handle_callback_rejects_expired_jwt(): void {
		$this->skipWithoutBrokerKeys();

		$broker_url = 'https://auth-broker.example.com';
		$public_key = self::$broker_public_key;

		if ( ! defined( 'PRESSBOOKS_AUTH_BROKER_URL' ) ) {
			define( 'PRESSBOOKS_AUTH_BROKER_URL', $broker_url );
		}
		if ( ! defined( 'PRESSBOOKS_AUTH_BROKER_PUBLIC_KEY' ) ) {
			define( 'PRESSBOOKS_AUTH_BROKER_PUBLIC_KEY', $public_key );
		}

		$oauth = new OAuthClient( $this->store );
		$state = 'expired-state';
		set_site_transient( 'pb_gdocs_state_' . $state, 'https://example.com/return', 600 );

		$jwt = $this->createBrokerJwt( [
			'wp_state' => $state,
			'exp' => time() - 300,
		] );

		$this->expectException( \RuntimeException::class );
		$oauth->handleCallback( $jwt, $state, $this->user_id );
	}

	/**
	 * @group import
	 */
	public function test_handle_callback_rejects_wrong_issuer(): void {
		$this->skipWithoutBrokerKeys();

		$broker_url = 'https://auth-broker.example.com';
		$public_key = self::$broker_public_key;

		if ( ! defined( 'PRESSBOOKS_AUTH_BROKER_URL' ) ) {
			define( 'PRESSBOOKS_AUTH_BROKER_URL', $broker_url );
		}
		if ( ! defined( 'PRESSBOOKS_AUTH_BROKER_PUBLIC_KEY' ) ) {
			define( 'PRESSBOOKS_AUTH_BROKER_PUBLIC_KEY', $public_key );
		}

		$oauth = new OAuthClient( $this->store );
		$state = 'wrong-issuer-state';
		set_site_transient( 'pb_gdocs_state_' . $state, 'https://example.com/return', 600 );

		$jwt = $this->createBrokerJwt( [
			'wp_state' => $state,
			'iss' => 'https://evil.example.com',
		] );

		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'Invalid JWT issuer.' );
		$oauth->handleCallback( $jwt, $state, $this->user_id );
	}

	/**
	 * @group import
	 */
	public function test_handle_callback_rejects_wrong_audience(): void {
		$this->skipWithoutBrokerKeys();

		$broker_url = 'https://auth-broker.example.com';
		$public_key = self::$broker_public_key;

		if ( ! defined( 'PRESSBOOKS_AUTH_BROKER_URL' ) ) {
			define( 'PRESSBOOKS_AUTH_BROKER_URL', $broker_url );
		}
		if ( ! defined( 'PRESSBOOKS_AUTH_BROKER_PUBLIC_KEY' ) ) {
			define( 'PRESSBOOKS_AUTH_BROKER_PUBLIC_KEY', $public_key );
		}

		$oauth = new OAuthClient( $this->store );
		$state = 'wrong-aud-state';
		set_site_transient( 'pb_gdocs_state_' . $state, 'https://example.com/return', 600 );

		$jwt = $this->createBrokerJwt( [
			'wp_state' => $state,
			'aud' => 'evil-host.example.com',
		] );

		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'Invalid JWT audience.' );
		$oauth->handleCallback( $jwt, $state, $this->user_id );
	}

	/**
	 * @group import
	 */
	public function test_handle_callback_rejects_state_mismatch(): void {
		$this->skipWithoutBrokerKeys();

		$broker_url = 'https://auth-broker.example.com';
		$public_key = self::$broker_public_key;

		if ( ! defined( 'PRESSBOOKS_AUTH_BROKER_URL' ) ) {
			define( 'PRESSBOOKS_AUTH_BROKER_URL', $broker_url );
		}
		if ( ! defined( 'PRESSBOOKS_AUTH_BROKER_PUBLIC_KEY' ) ) {
			define( 'PRESSBOOKS_AUTH_BROKER_PUBLIC_KEY', $public_key );
		}

		$oauth = new OAuthClient( $this->store );
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
	public function test_handle_callback_rejects_replayed_jwt(): void {
		$this->skipWithoutBrokerKeys();

		$broker_url = 'https://auth-broker.example.com';
		$public_key = self::$broker_public_key;

		if ( ! defined( 'PRESSBOOKS_AUTH_BROKER_URL' ) ) {
			define( 'PRESSBOOKS_AUTH_BROKER_URL', $broker_url );
		}
		if ( ! defined( 'PRESSBOOKS_AUTH_BROKER_PUBLIC_KEY' ) ) {
			define( 'PRESSBOOKS_AUTH_BROKER_PUBLIC_KEY', $public_key );
		}

		$oauth = new OAuthClient( $this->store );
		$jti = bin2hex( random_bytes( 16 ) );

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

		$broker_url = 'https://auth-broker.example.com';
		$public_key = self::$broker_public_key;

		if ( ! defined( 'PRESSBOOKS_AUTH_BROKER_URL' ) ) {
			define( 'PRESSBOOKS_AUTH_BROKER_URL', $broker_url );
		}
		if ( ! defined( 'PRESSBOOKS_AUTH_BROKER_PUBLIC_KEY' ) ) {
			define( 'PRESSBOOKS_AUTH_BROKER_PUBLIC_KEY', $public_key );
		}

		$oauth = new OAuthClient( $this->store );
		$state = 'no-transient-state';

		$jwt = $this->createBrokerJwt( [ 'wp_state' => $state ] );

		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'Invalid or expired OAuth state.' );
		$oauth->handleCallback( $jwt, $state, $this->user_id );
	}
}
