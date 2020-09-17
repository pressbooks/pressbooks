<?php

use Pressbooks\DataCollector\Book as DataCollector;
use function Pressbooks\Admin\Laf\book_directory_excluded_callback;
use Pressbooks\Admin\Network\SharingAndPrivacyOptions;

class GdprTest extends \WP_UnitTestCase {

	use utilsTrait;

	/**
	 * @var \Pressbooks\Privacy
	 * @group privacy
	 */
	protected $privacy;

	/**
	 * @group privacy
	 */
	public function setUp() {
		parent::setUp();
		$this->privacy = new \Pressbooks\Privacy();
	}
	/**
	 * @group privacy
	 */
	public function test_reschedulePrivacyDeleteOldExportFiles() {
		$nochange = (object) [ 'hook' => 'cant_touch_this', 'interval' => 3600 ];
		$event = $this->privacy->reschedulePrivacyCron( $nochange );
		$this->assertEquals( $nochange, $event );

		$fixme = (object) [ 'hook' => 'wp_privacy_delete_old_export_files', 'interval' => 3600 ];
		$event = $this->privacy->reschedulePrivacyCron( $fixme );
		$this->assertEquals( $event->schedule, 'twicedaily' );
		$this->assertEquals( $event->interval, 43200 );
	}
	/**
	 * @group privacy
	 */
	public function test_addPrivacyPolicyContent() {
		// Doing it right
		global $current_screen;
		$current_screen = WP_Screen::get( 'front-matter' ); // is_admin
		global $wp_current_filter;
		$wp_current_filter = [ 'admin_init' ]; // doing_action

		$this->privacy->addPrivacyPolicyContent();
		$policies = WP_Privacy_Policy_Content::get_suggested_policy_text();
		$result = false;
		foreach ( $policies as $policy ) {
			if ( $policy['plugin_name'] === 'Pressbooks' ) {
				$result = true;
				break;
			}
		}
		$this->assertTrue( $result );
	}

	/**
	 * @group privacy
	 */
	public function test_namespace() {
		$last_updated_before = get_blog_details()->last_updated;
		update_site_option( 'pressbooks_sharingandprivacy_options', [ 'network_directory_excluded' => 0 ] );
		add_action( 'admin_init', '\Pressbooks\Admin\Laf\privacy_settings_init' );
		@do_action( 'admin_init' );
		do_action( 'update_option_pb_book_directory_excluded', '0', '1' );
		$last_updated_after = get_blog_details()->last_updated;
		$this->assertEquals( get_site_meta( get_current_blog_id(), DataCollector::BOOK_DIRECTORY_EXCLUDED, true ), '1' );
		$this->assertNotEquals( $last_updated_before, $last_updated_after );
	}

	/**
	 * @group privacy
	 */
	public function test_bookDirectoryExcludedCallback() {
		ob_start();
		book_directory_excluded_callback( [] );
		$buffer = ob_get_clean();
		$html_group = '<input type="radio" id="include-in-directory" name="pb_book_directory_excluded" value="0" checked="checked" /><label for="include-in-directory"> Yes. I want this book to be listed in the Pressbooks directory.</label><br /><input type="radio" id="exclude-from-directory" name="pb_book_directory_excluded" value="1" /><label for="exclude-from-directory"> No. Exclude this book from the Pressbooks directory.</label>';

		$this->assertEquals( $buffer, $html_group );
	}

	/**
	 * @group privacy
	 */
	public function test_networkExcludeOption() {

		$optionBefore =  [
			'allow_redistribution'           => 0,
			'enable_network_api'             => 1,
			'enable_cloning'                 => 1,
			'enable_thincc_weblinks'         => 1,
			'iframe_whitelist'               => '',
			SharingAndPrivacyOptions::NETWORK_DIRECTORY_EXCLUDED => 1,
		];

		$optionAfter =  [
			'allow_redistribution'           => 0,
			'enable_network_api'             => 1,
			'enable_cloning'                 => 1,
			'enable_thincc_weblinks'         => 1,
			'iframe_whitelist'               => '',
			SharingAndPrivacyOptions::NETWORK_DIRECTORY_EXCLUDED => 0,
		];

		$this->assertEquals( SharingAndPrivacyOptions::networkExcludeOption( SharingAndPrivacyOptions::getSlug() ), true);
		$this->assertEquals( SharingAndPrivacyOptions::networkExcludeOption( 'some_option_name' ), false);

		update_site_option( SharingAndPrivacyOptions::getSlug(), $optionBefore );
		update_blog_details( 1,
			[ 'last_updated' => current_time( 'mysql', true ) ]
		);
//		add_action( 'update_site_option', [ '\Pressbooks\Admin\Network\SharingAndPrivacyOptions', 'networkExcludeOption' ] );
//		do_action( 'update_site_option' );
//		do_action( SharingAndPrivacyOptions::getSlug(), $optionBefore, $optionAfter);

//		update_site_option( SharingAndPrivacyOptions::getSlug(), $optionBefore );
//		do_action( 'update_site_option' );

//		update_site_option( 'pressbooks_sharingandprivacy_options', [ 'network_directory_excluded' => 0 ] );
//		add_action( 'admin_init', '\Pressbooks\Admin\Laf\privacy_settings_init' );
//		@do_action( 'admin_init' );
//		do_action( 'update_option_pb_book_directory_excluded', '0', '1' );
//
		$last_updated_before = get_blog_details()->last_updated;
		sleep(2);
		$x = update_site_option( SharingAndPrivacyOptions::getSlug(), $optionAfter );
//		do_action( 'update_site_option' );

		update_blog_details( 1,
			[ 'last_updated' => current_time( 'mysql', true ) ]
		);
		$last_updated_after = get_blog_details()->last_updated;
		$this->assertNotEquals( $last_updated_before, $last_updated_after );

	}

	/**
	 * @group privacy
	 */
	public function test_getNonCatalogBooks_zero_to_one_non_catalog_books() {

		update_site_meta( get_current_blog_id(), 'pb_in_catalog', true );

		$this->assertIsArray( SharingAndPrivacyOptions::getNonCatalogBooks() );
		$this->assertCount( 0, SharingAndPrivacyOptions::getNonCatalogBooks() );

		$this->_book();

		$this->assertCount( 1, SharingAndPrivacyOptions::getNonCatalogBooks() );

		update_site_meta( get_current_blog_id(), 'pb_in_catalog', true );

		$this->assertCount( 0, SharingAndPrivacyOptions::getNonCatalogBooks() );

	}

	/**
	 * @group privacy
	 */
	public function test_excludeNonCatalogBooksFromDirectoryAction() {

		$books = $this->factory()->blog->create_many(2);

		$response = [
			'directory_delete_response' => false,
			'update_blogs_details_response' => [ true, true ],
		];

		$this->assertEquals(
			SharingAndPrivacyOptions::excludeNonCatalogBooksFromDirectoryAction( $books ),
			$response
		);

		$this->assertEquals(
			SharingAndPrivacyOptions::excludeNonCatalogBooksFromDirectoryAction( $books, false),
			$response
		);
	}

}
