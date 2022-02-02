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
	public function set_up() {
		parent::set_up();
		$this->privacy = new \Pressbooks\Privacy();
	}

	/**
	 * @group privacy
	 */
	public static function set_up_before_class()
	{
		parent::set_up_before_class();
		$blog_ids = get_sites( [ 'site__not_in' => 1 ] );

		foreach ( $blog_ids as $blog ) {
			wp_delete_site($blog->blog_id);
		}
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
		// Doing it right
		global $current_screen;
		$current_screen = WP_Screen::get( 'front-matter' ); // is_admin
		global $wp_current_filter;
		$wp_current_filter = [ 'admin_init' ]; // doing_action
		$this->privacy->addPrivacyPolicyContent();
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
		$this->_book();

		ob_start();
		book_directory_excluded_callback( [] );
		$buffer = ob_get_clean();
		$html_group = '<input type="radio" id="include-in-directory" name="pb_book_directory_excluded" value="0" checked="checked" /><label for="include-in-directory"> Yes. I want this book to be listed in the Pressbooks directory.</label><br /><input type="radio" id="exclude-from-directory" name="pb_book_directory_excluded" value="1" /><label for="exclude-from-directory"> No. Exclude this book from the Pressbooks directory.</label>';
		$this->assertEquals( $buffer, $html_group );

		$this->assertEquals( get_option( 'pb_book_directory_excluded' ), 0 );

	}

	/**
	 * @group privacy
	 */
	public function test_getPublicBooks_zero_to_one_non_catalog_books() {

		// assume the first blog is the main wp site and not a book
		$this->assertIsArray( SharingAndPrivacyOptions::getPublicBooks() );
		$this->assertCount( 0, SharingAndPrivacyOptions::getPublicBooks() );
		$this->assertCount( 0, SharingAndPrivacyOptions::getPublicBooks( false ) );
		$this->assertCount( 0, SharingAndPrivacyOptions::getPublicBooks( true ) );

		$this->_book();
		$this->assertCount( 1, SharingAndPrivacyOptions::getPublicBooks() );
		$this->assertCount( 1, SharingAndPrivacyOptions::getPublicBooks( false ) );
		$this->assertCount( 1, SharingAndPrivacyOptions::getPublicBooks( true ) );

		update_site_meta( get_current_blog_id(), \Pressbooks\DataCollector\Book::IN_CATALOG, 1 );
		$this->assertCount( 1, SharingAndPrivacyOptions::getPublicBooks() );
		$this->assertCount( 1, SharingAndPrivacyOptions::getPublicBooks( false ) );
		$this->assertCount( 0, SharingAndPrivacyOptions::getPublicBooks( true ) );

		update_site_meta( get_current_blog_id(), \Pressbooks\DataCollector\Book::IN_CATALOG, 0 );
		$this->assertCount( 1, SharingAndPrivacyOptions::getPublicBooks() );
		$this->assertCount( 1, SharingAndPrivacyOptions::getPublicBooks(false) );
		$this->assertCount( 1, SharingAndPrivacyOptions::getPublicBooks( true ) );

		$this->_book();
		update_site_meta( get_current_blog_id(), \Pressbooks\DataCollector\Book::PUBLIC, 0 );
		update_site_meta( get_current_blog_id(), \Pressbooks\DataCollector\Book::IN_CATALOG, 1 );
		$this->assertCount( 1, SharingAndPrivacyOptions::getPublicBooks() );
		$this->assertCount( 1, SharingAndPrivacyOptions::getPublicBooks( false ) );
		$this->assertCount( 1, SharingAndPrivacyOptions::getPublicBooks( true ) );

		update_site_meta( get_current_blog_id(), \Pressbooks\DataCollector\Book::PUBLIC, 1 );
		update_site_meta( get_current_blog_id(), \Pressbooks\DataCollector\Book::IN_CATALOG, 0 );
		$this->assertCount( 2, SharingAndPrivacyOptions::getPublicBooks() );
		$this->assertCount( 2, SharingAndPrivacyOptions::getPublicBooks( false ) );
		$this->assertCount( 2, SharingAndPrivacyOptions::getPublicBooks( true ) );

		update_site_meta( get_current_blog_id(), \Pressbooks\DataCollector\Book::IN_CATALOG, 1 );
		$this->assertCount( 2, SharingAndPrivacyOptions::getPublicBooks() );
		$this->assertCount( 2, SharingAndPrivacyOptions::getPublicBooks( false ) );
		$this->assertCount( 1, SharingAndPrivacyOptions::getPublicBooks( true ) );

	}

	/**
	 * @group privacy
	 */
	public function test_excludeNonCatalogBooksFromDirectoryAction() {

		$books = $this->factory()->blog->create_many( 2 );

		$this->assertEquals(
			SharingAndPrivacyOptions::excludeNonCatalogBooksFromDirectoryAction( $books ),
			[
				'directory_delete_responses' => [ false ],
				'blogs_not_updated' => [],
			]
		);

		$this->assertEquals(
			SharingAndPrivacyOptions::excludeNonCatalogBooksFromDirectoryAction( $books, true ),
			[
				'directory_delete_responses' => [],
				'blogs_not_updated' => [],
			]
		);

		$books = $this->factory()->blog->create_many( 52 );

		$this->assertEquals(
			SharingAndPrivacyOptions::excludeNonCatalogBooksFromDirectoryAction( $books ),
			[
				'directory_delete_responses' => [ false, false ],
				'blogs_not_updated' => [],
			]
		);

		$this->assertEquals(
			SharingAndPrivacyOptions::excludeNonCatalogBooksFromDirectoryAction( $books, true ),
			[
				'directory_delete_responses' => [],
				'blogs_not_updated' => [],
			]
		);

		$books[] = 9876;

		$this->assertEquals(
			SharingAndPrivacyOptions::excludeNonCatalogBooksFromDirectoryAction( $books ),
			[
				'directory_delete_responses' => [ false, false ],
				'blogs_not_updated' => [ 9876 ],
			]
		);

		$this->assertEquals(
			SharingAndPrivacyOptions::excludeNonCatalogBooksFromDirectoryAction( $books, true),
			[
				'directory_delete_responses' => [],
				'blogs_not_updated' => [ 9876 ],
			]
		);
	}

}
