<?php

class Modules_ImportGoogleDocsBrokerClientTest extends \WP_UnitTestCase {

	private static string $brokerPrivateKey;
	private static string $brokerPublicKey;
	private static string $encryptionKey;
	private static string $networkSecret;
	private string $captured_request_url = '';
	private array $captured_request_args = [];
	private array $http_response = [];

	public static function wpSetUpBeforeClass( $factory ): void {
		$key_resource = openssl_pkey_new( [ 'private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA ] );
		openssl_pkey_export( $key_resource, $private_key_pem );
		self::$brokerPrivateKey = $private_key_pem;
		self::$brokerPublicKey = openssl_pkey_get_details( $key_resource )['key'];

		self::$encryptionKey = sodium_bin2base64( random_bytes( SODIUM_CRYPTO_SECRETBOX_KEYBYTES ), SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING );
		self::$networkSecret = sodium_bin2base64( random_bytes( 32 ), SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING );

		if ( ! defined( 'PRESSBOOKS_GOOGLE_DOCS_ENCRYPTION_KEY' ) ) {
			define( 'PRESSBOOKS_GOOGLE_DOCS_ENCRYPTION_KEY', self::$encryptionKey );
		}
	}

	public function set_up(): void {
		parent::set_up();
		$this->captured_request_url = '';
		$this->captured_request_args = [];
		$this->http_response = [ 'response' => [ 'code' => 500, 'message' => 'No response stub set' ], 'body' => '' ];
		add_filter( 'pre_http_request', [ $this, 'capture_http_request' ], 10, 3 );
	}

	public function tear_down(): void {
		remove_filter( 'pre_http_request', [ $this, 'capture_http_request' ] );
		parent::tear_down();
	}

	public function capture_http_request( $preempt, $args, $url ) {
		$this->captured_request_url = $url;
		$this->captured_request_args = $args;
		return $this->http_response;
	}

	protected function set_http_response( int $code, string $body ): void {
		$this->http_response = [ 'response' => [ 'code' => $code, 'message' => '' ], 'body' => $body ];
	}

	protected function make_client(): \Pressbooks\Modules\Import\GoogleDocs\Broker\BrokerRefreshClient {
		$storage = new \Pressbooks\Modules\Import\GoogleDocs\Storage\BrokerBackedStorage(
			new \Pressbooks\Modules\Import\GoogleDocs\Storage\SodiumCipher(),
			self::$encryptionKey
		);
		return new \Pressbooks\Modules\Import\GoogleDocs\Broker\BrokerRefreshClient(
			'https://broker.example.test',
			self::$brokerPublicKey,
			self::$networkSecret,
			$storage
		);
	}

	protected function seed_broker_token( int $user_id ): void {
		$storage = new \Pressbooks\Modules\Import\GoogleDocs\Storage\BrokerBackedStorage(
			new \Pressbooks\Modules\Import\GoogleDocs\Storage\SodiumCipher(),
			self::$encryptionKey
		);
		$storage->save(
			$user_id,
			new \Pressbooks\Modules\Import\GoogleDocs\Storage\StoredToken(
				[
					'session_handle' => 'sh-abc',
					'access_token'   => 'expired-at',
					'expires_at'     => time() - 60,
					'google_sub'     => 'sub-001',
				],
				\Pressbooks\Modules\Import\GoogleDocs\Storage\TokenMode::Broker
			)
		);
	}

	protected function sign_broker_response( array $payload ): string {
		$payload['iat'] = time();
		$payload['exp'] = time() + 30;
		$payload['iss'] = 'https://broker.example.test';
		$payload['aud'] = parse_url( home_url(), PHP_URL_HOST );
		return \Firebase\JWT\JWT::encode( $payload, self::$brokerPrivateKey, 'RS256' );
	}

	/**
	 * @group import
	 */
	public function test_refresh_builds_signed_post_to_broker(): void {
		$client = $this->make_client();
		$user_id = self::factory()->user->create();
		$this->seed_broker_token( $user_id );

		$this->set_http_response( 200, $this->sign_broker_response( [
			'access_token' => 'fresh-at',
			'expires_at'   => time() + 3600,
			'token_type'   => 'Bearer',
		] ) );

		try {
			$client->refresh( $user_id );
		} catch ( \Throwable $e ) {
			// Assertion is about the request, not the response handling.
		}

		$this->assertSame( 'https://broker.example.test/oauth/refresh', $this->captured_request_url );
		$this->assertSame( 'POST', $this->captured_request_args['method'] );

		$body = json_decode( $this->captured_request_args['body'], true );
		$this->assertSame( 'sh-abc', $body['handle'] );
		$this->assertSame( 'sub-001', $body['google_sub'] );
		$this->assertSame( parse_url( home_url(), PHP_URL_HOST ), $body['origin'] );
		$this->assertNotEmpty( $body['jti'] );
		$this->assertNotEmpty( $body['iat'] );

		$this->assertArrayHasKey( 'X-Network-Signature', $this->captured_request_args['headers'] );
		$expected_sig = hash_hmac( 'sha256', $this->captured_request_args['body'], self::$networkSecret );
		$this->assertSame( $expected_sig, $this->captured_request_args['headers']['X-Network-Signature'] );
	}

	/**
	 * @group import
	 */
	public function test_refresh_returns_stored_token_on_success(): void {
		$client = $this->make_client();
		$user_id = self::factory()->user->create();
		$this->seed_broker_token( $user_id );

		$this->set_http_response( 200, $this->sign_broker_response( [
			'access_token' => 'fresh-at',
			'expires_at'   => time() + 3600,
			'token_type'   => 'Bearer',
		] ) );

		$token = $client->refresh( $user_id );
		$this->assertSame( 'fresh-at', $token->accessToken() );
		$this->assertSame( 'sh-abc', $token->brokerSessionHandle() );
		$this->assertSame( 'sub-001', $token->googleSub() );
	}

	/**
	 * @group import
	 */
	public function test_refresh_throws_on_410_handle_unknown(): void {
		$client = $this->make_client();
		$user_id = self::factory()->user->create();
		$this->seed_broker_token( $user_id );

		$this->set_http_response( 410, $this->sign_broker_response( [ 'error' => 'handle_unknown' ] ) );

		try {
			$client->refresh( $user_id );
			$this->fail( 'Expected ReauthorizationRequiredException was not thrown.' );
		} catch ( \Pressbooks\Modules\Import\GoogleDocs\ReauthorizationRequiredException $e ) {
			// Expected — broker says session is gone.
		}

		// The 410 path must delete local state so retries don't loop on a dead session.
		$storage = new \Pressbooks\Modules\Import\GoogleDocs\Storage\BrokerBackedStorage(
			new \Pressbooks\Modules\Import\GoogleDocs\Storage\SodiumCipher(),
			self::$encryptionKey
		);
		$this->assertNull( $storage->load( $user_id ), 'Local token must be deleted after broker reports 410.' );
	}

	/**
	 * @group import
	 */
	public function test_refresh_throws_on_unsigned_response(): void {
		$client = $this->make_client();
		$user_id = self::factory()->user->create();
		$this->seed_broker_token( $user_id );

		$this->set_http_response( 200, json_encode( [ 'access_token' => 'forged' ] ) );

		$this->expectException( \RuntimeException::class );
		$client->refresh( $user_id );
	}
}
