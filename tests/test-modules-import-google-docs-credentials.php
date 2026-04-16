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
	 * @group import
	 */
	public function test_get_user_token_returns_null_when_not_set(): void {
		$store = new \Pressbooks\Modules\Import\GoogleDocs\CredentialsStore();
		$this->assertNull( $store->getUserToken( 1 ) );
	}

	/**
	 * @group import
	 */
	public function test_save_and_get_user_token(): void {
		$store = new \Pressbooks\Modules\Import\GoogleDocs\CredentialsStore();
		$token = [
			'access_token'  => 'at-123',
			'refresh_token' => 'rt-456',
			'expires_at'    => time() + 3600,
			'scopes'        => 'documents.readonly drive.readonly',
			'connected_at'  => time(),
		];
		$user_id = self::factory()->user->create();
		$store->saveUserToken( $user_id, $token );
		$saved = $store->getUserToken( $user_id );
		$this->assertSame( 'at-123', $saved['access_token'] );
		$this->assertSame( 'rt-456', $saved['refresh_token'] );
	}

	/**
	 * @group import
	 */
	public function test_delete_user_token(): void {
		$store = new \Pressbooks\Modules\Import\GoogleDocs\CredentialsStore();
		$user_id = self::factory()->user->create();
		$store->saveUserToken( $user_id, [ 'access_token' => 'x' ] );
		$store->deleteUserToken( $user_id );
		$this->assertNull( $store->getUserToken( $user_id ) );
	}

	/**
	 * @group import
	 */
	public function test_user_is_connected(): void {
		$store = new \Pressbooks\Modules\Import\GoogleDocs\CredentialsStore();
		$user_id = self::factory()->user->create();
		$this->assertFalse( $store->isUserConnected( $user_id ) );
		$store->saveUserToken( $user_id, [ 'access_token' => 'x', 'refresh_token' => 'y' ] );
		$this->assertTrue( $store->isUserConnected( $user_id ) );
	}
}
