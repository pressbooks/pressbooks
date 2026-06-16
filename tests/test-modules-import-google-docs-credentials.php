<?php

class Modules_ImportGoogleDocsCredentialsTest extends \WP_UnitTestCase {

	public function tear_down(): void {
		delete_site_option( 'pressbooks_google_docs_oauth' );
		parent::tear_down();
	}

	/**
	 * @group import
	 */
	public function test_get_client_credentials_returns_empty_when_not_set(): void {
		$store = new \Pressbooks\Modules\Import\GoogleDocs\CredentialsStore();
		$creds = $store->getClientCredentials();
		$this->assertSame( '', $creds['client_id'] );
		$this->assertSame( '', $creds['client_secret'] );
	}

	/**
	 * @group import
	 */
	public function test_save_and_get_client_credentials(): void {
		$store = new \Pressbooks\Modules\Import\GoogleDocs\CredentialsStore();
		$store->saveClientCredentials( 'test-client-id', 'test-client-secret' );
		$creds = $store->getClientCredentials();
		$this->assertSame( 'test-client-id', $creds['client_id'] );
		$this->assertSame( 'test-client-secret', $creds['client_secret'] );
	}

	/**
	 * @group import
	 */
	public function test_is_configured_returns_false_when_empty(): void {
		$store = new \Pressbooks\Modules\Import\GoogleDocs\CredentialsStore();
		$this->assertFalse( $store->isConfigured() );
	}

	/**
	 * @group import
	 */
	public function test_is_configured_returns_true_when_set(): void {
		$store = new \Pressbooks\Modules\Import\GoogleDocs\CredentialsStore();
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
		$store = new \Pressbooks\Modules\Import\GoogleDocs\CredentialsStore();
		$this->assertTrue( $store->isConfigured() );
	}
}
