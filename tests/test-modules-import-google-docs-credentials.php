<?php

class Modules_ImportGoogleDocsCredentialsTest extends \WP_UnitTestCase {

	private string $encryption_key;

	public function set_up(): void {
		parent::set_up();
		if ( ! defined( 'PRESSBOOKS_GOOGLE_DOCS_ENCRYPTION_KEY' ) ) {
			$key = sodium_bin2base64( random_bytes( SODIUM_CRYPTO_SECRETBOX_KEYBYTES ), SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING );
			define( 'PRESSBOOKS_GOOGLE_DOCS_ENCRYPTION_KEY', $key );
		}
		$this->encryption_key = PRESSBOOKS_GOOGLE_DOCS_ENCRYPTION_KEY;
	}

	public function tear_down(): void {
		delete_site_option( 'pressbooks_google_docs_oauth' );
		parent::tear_down();
	}

	/**
	 * @group import
	 */
	public function test_get_client_credentials_returns_empty_when_not_set(): void {
		$store = \Pressbooks\Modules\Import\GoogleDocs\CredentialsStore::fromEnvironment();
		$creds = $store->getClientCredentials();
		$this->assertSame( '', $creds['client_id'] );
		$this->assertSame( '', $creds['client_secret'] );
	}

	/**
	 * @group import
	 */
	public function test_save_and_get_client_credentials(): void {
		$store = \Pressbooks\Modules\Import\GoogleDocs\CredentialsStore::fromEnvironment();
		$store->saveClientCredentials( 'test-client-id', 'test-client-secret' );
		$creds = $store->getClientCredentials();
		$this->assertSame( 'test-client-id', $creds['client_id'] );
		$this->assertSame( 'test-client-secret', $creds['client_secret'] );
	}

	/**
	 * @group import
	 */
	public function test_save_encrypts_client_secret_at_rest(): void {
		$store = \Pressbooks\Modules\Import\GoogleDocs\CredentialsStore::fromEnvironment();
		$store->saveClientCredentials( 'test-client-id', 'test-client-secret' );

		$raw = get_site_option( 'pressbooks_google_docs_oauth', [] );
		$this->assertArrayHasKey( 'encrypted_client_secret', $raw );
		$this->assertArrayNotHasKey( 'client_secret', $raw );
		$this->assertStringNotContainsString( 'test-client-secret', $raw['encrypted_client_secret'] );
	}

	/**
	 * @group import
	 */
	public function test_read_falls_back_to_plaintext_legacy_secret(): void {
		update_site_option( 'pressbooks_google_docs_oauth', [
			'client_id'     => 'legacy-id',
			'client_secret' => 'legacy-plaintext-secret',
		] );

		$store = \Pressbooks\Modules\Import\GoogleDocs\CredentialsStore::fromEnvironment();
		$creds = $store->getClientCredentials();
		$this->assertSame( 'legacy-id', $creds['client_id'] );
		$this->assertSame( 'legacy-plaintext-secret', $creds['client_secret'] );
	}

	/**
	 * @group import
	 */
	public function test_is_configured_returns_false_when_empty(): void {
		$store = \Pressbooks\Modules\Import\GoogleDocs\CredentialsStore::fromEnvironment();
		$this->assertFalse( $store->isConfigured() );
	}

	/**
	 * @group import
	 */
	public function test_is_configured_returns_true_when_set(): void {
		$store = \Pressbooks\Modules\Import\GoogleDocs\CredentialsStore::fromEnvironment();
		$store->saveClientCredentials( 'id', 'secret' );
		$this->assertTrue( $store->isConfigured() );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 * @group import
	 */
	public function test_is_configured_returns_true_in_broker_mode(): void {
		if ( ! defined( 'PRESSBOOKS_AUTH_BROKER_URL' ) ) {
			define( 'PRESSBOOKS_AUTH_BROKER_URL', 'https://broker.example.test' );
		}
		$store = \Pressbooks\Modules\Import\GoogleDocs\CredentialsStore::fromEnvironment();
		$this->assertTrue( $store->isConfigured() );
	}

	/**
	 * @group import
	 */
	public function test_save_throws_when_encryption_key_missing(): void {
		$cipher = new \Pressbooks\Modules\Import\GoogleDocs\Storage\SodiumCipher();
		$store = new \Pressbooks\Modules\Import\GoogleDocs\CredentialsStore( $cipher, '' );

		$this->expectException( \Pressbooks\Modules\Import\GoogleDocs\EncryptionKeyMissingException::class );
		$store->saveClientCredentials( 'id', 'secret' );
	}
}
