<?php

use Pressbooks\Modules\Import\GoogleDocs\Bootstrap;
use Pressbooks\Modules\Import\GoogleDocs\CredentialsStore;
use Pressbooks\Modules\Import\GoogleDocs\GoogleDocs;

class Modules_ImportGoogleDocsBootstrapTest extends \WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();
		if ( ! defined( 'PRESSBOOKS_GOOGLE_DOCS_ENCRYPTION_KEY' ) ) {
			$key = sodium_bin2base64( random_bytes( SODIUM_CRYPTO_SECRETBOX_KEYBYTES ), SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING );
			define( 'PRESSBOOKS_GOOGLE_DOCS_ENCRYPTION_KEY', $key );
		}
	}

	public function tear_down(): void {
		delete_site_option( CredentialsStore::NETWORK_OPTION_KEY );
		parent::tear_down();
	}

	/**
	 * @group import
	 */
	public function test_init_registers_import_type_filter(): void {
		Bootstrap::init();
		$this->assertNotFalse( has_filter( 'pb_select_import_type' ) );
	}

	/**
	 * @group import
	 */
	public function test_init_registers_oauth_admin_post_actions(): void {
		Bootstrap::init();
		$this->assertNotFalse( has_action( 'admin_post_pb_gdocs_authorize' ) );
		$this->assertNotFalse( has_action( 'admin_post_pb_gdocs_disconnect' ) );
	}

	/**
	 * Proves SettingsPage::hooks() was invoked from within Bootstrap::init().
	 *
	 * @group import
	 */
	public function test_init_registers_settings_page_hooks(): void {
		Bootstrap::init();
		$this->assertNotFalse( has_action( 'admin_post_pb_gdocs_callback' ) );
	}

	/**
	 * @group import
	 */
	public function test_import_type_absent_when_not_configured(): void {
		Bootstrap::init();
		$types = apply_filters( 'pb_select_import_type', [] );
		$this->assertArrayNotHasKey( GoogleDocs::TYPE_OF, $types );
	}

	/**
	 * @group import
	 */
	public function test_import_type_present_when_configured_and_available(): void {
		CredentialsStore::fromEnvironment()->saveClientCredentials( 'client-id', 'client-secret' );

		Bootstrap::init();
		$types = apply_filters( 'pb_select_import_type', [] );

		$this->assertArrayHasKey( GoogleDocs::TYPE_OF, $types );
		$this->assertSame( 'Google Docs', $types[ GoogleDocs::TYPE_OF ] );
	}

	/**
	 * Exercises the broker-mode branch of buildTokenStorage()/buildBrokerRefresh().
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 * @group import
	 */
	public function test_init_runs_in_broker_mode(): void {
		if ( ! defined( 'PRESSBOOKS_AUTH_BROKER_URL' ) ) {
			define( 'PRESSBOOKS_AUTH_BROKER_URL', 'https://broker.example.test' );
		}

		Bootstrap::init();

		$this->assertNotFalse( has_filter( 'pb_select_import_type' ) );
		$this->assertNotFalse( has_action( 'admin_post_pb_gdocs_authorize' ) );
	}
}
