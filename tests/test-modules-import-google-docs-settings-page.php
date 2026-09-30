<?php

use Pressbooks\Modules\Import\GoogleDocs\CredentialsStore;
use Pressbooks\Modules\Import\GoogleDocs\OAuthClient;
use Pressbooks\Modules\Import\GoogleDocs\SettingsPage;
use Pressbooks\Modules\Import\GoogleDocs\Storage\TokenStorage;

class Modules_ImportGoogleDocsSettingsPageTest extends \WP_UnitTestCase {

	private int $super_admin_id;
	private SettingsPage $settings;
	private CredentialsStore $creds_store;
	private OAuthClient $mock_oauth;
	private TokenStorage $mock_storage;

	public function setUp(): void {
		parent::setUp();

		$this->super_admin_id = $this->factory->user->create();
		grant_super_admin( $this->super_admin_id );
		wp_set_current_user( $this->super_admin_id );

		$this->creds_store  = $this->createMock( CredentialsStore::class );
		$this->mock_oauth   = $this->createMock( OAuthClient::class );
		$this->mock_storage = $this->createMock( TokenStorage::class );
		$this->mock_storage->method( 'isAvailable' )->willReturn( true );

		$this->settings = new SettingsPage( $this->creds_store, $this->mock_oauth, $this->mock_storage );
	}

	public function tearDown(): void {
		$_GET     = [];
		$_POST    = [];
		$_REQUEST = [];
		parent::tearDown();
	}

	private function catchRedirect( callable $fn ): string {
		$captured = '';
		$filter   = static function ( string $location ) use ( &$captured ): string {
			$captured = $location;
			// Use \Error (not \Exception) so production catch(\Exception) blocks cannot intercept it.
			throw new \Error( 'redirect:' . $location );
		};
		add_filter( 'wp_redirect', $filter );
		try {
			$fn();
		} catch ( \Error $e ) {
			// expected — execution stopped before exit
		} finally {
			remove_filter( 'wp_redirect', $filter );
		}
		return $captured;
	}

	/**
	 * @group import
	 */
	public function test_handle_callback_redirects_with_denied_when_google_error_and_valid_state(): void {
		$state      = 'test_state_abc';
		$return_url = 'https://example.com/import';
		set_site_transient( 'pb_gdocs_state_' . $state, $return_url, 600 );

		$_GET['error'] = 'access_denied';
		$_GET['state'] = $state;

		$location = $this->catchRedirect( function () {
			$this->settings->handleOAuthCallback();
		} );

		$this->assertStringContainsString( 'pb_gdocs=denied', $location );
		$this->assertStringContainsString( $return_url, $location );
		$this->assertFalse( (bool) get_site_transient( 'pb_gdocs_state_' . $state ) );
	}

	/**
	 * @group import
	 */
	public function test_handle_callback_redirects_to_admin_when_error_and_no_state(): void {
		$_GET['error'] = 'access_denied';
		// No state transient set — falls back to admin import page

		$location = $this->catchRedirect( function () {
			$this->settings->handleOAuthCallback();
		} );

		$this->assertStringContainsString( 'pb_gdocs=denied', $location );
		$this->assertStringContainsString( 'pb_import', $location );
	}

	/**
	 * @group import
	 */
	public function test_handle_callback_processes_broker_token_and_redirects_connected(): void {
		$state      = 'broker_state_xyz';
		$return_url = 'https://example.com/return';
		set_site_transient( 'pb_gdocs_state_' . $state, $return_url, 600 );

		$_GET['token'] = 'jwt.token.here';
		$_GET['state'] = $state;

		$this->mock_oauth
			->expects( $this->once() )
			->method( 'handleCallback' )
			->with( 'jwt.token.here', $state, $this->super_admin_id )
			->willReturn( $return_url );

		$location = $this->catchRedirect( function () {
			$this->settings->handleOAuthCallback();
		} );

		$this->assertStringContainsString( 'pb_gdocs=connected', $location );
		$this->assertStringContainsString( $return_url, $location );
	}

	/**
	 * @group import
	 */
	public function test_handle_callback_processes_code_and_redirects_connected(): void {
		$state      = 'code_state_xyz';
		$return_url = 'https://example.com/return';
		set_site_transient( 'pb_gdocs_state_' . $state, $return_url, 600 );

		$_GET['code']  = 'auth_code_123';
		$_GET['state'] = $state;

		$this->mock_oauth
			->expects( $this->once() )
			->method( 'handleCallback' )
			->with( 'auth_code_123', $state, $this->super_admin_id )
			->willReturn( $return_url );

		$location = $this->catchRedirect( function () {
			$this->settings->handleOAuthCallback();
		} );

		$this->assertStringContainsString( 'pb_gdocs=connected', $location );
		$this->assertStringContainsString( $return_url, $location );
	}

	/**
	 * @group import
	 */
	public function test_render_page_saves_credentials_on_valid_post(): void {
		$this->mock_oauth->method( 'isBrokerMode' )->willReturn( false );
		$this->mock_oauth->method( 'getRedirectUri' )->willReturn( 'https://example.com/callback' );
		$this->creds_store->method( 'getClientCredentials' )->willReturn( [
			'client_id'     => '',
			'client_secret' => '',
		] );
		$this->creds_store
			->expects( $this->once() )
			->method( 'saveClientCredentials' )
			->with( 'my-client-id', 'my-secret' );

		$_POST['client_id']     = 'my-client-id';
		$_POST['client_secret'] = 'my-secret';
		$_POST['_wpnonce']      = wp_create_nonce( 'pb_save_google_docs_settings' );
		$_REQUEST               = $_POST;

		ob_start();
		$this->settings->renderPage();
		ob_end_clean();
	}
}
