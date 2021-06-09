<?php

use Pressbooks\Tracking\BookDownload;

class Track_BookDownloadTest extends \WP_UnitTestCase {
	use utilsTrait;

	public function setUp() {
		parent::setUp();

		$reflection = new ReflectionClass(BookDownload::class);
		$instance = $reflection->getProperty('instance');
		$instance->setAccessible(true);
		$instance->setValue(null);
		$instance->setAccessible(false);
    }

	/**
	 * @group book_download
	 */
	public function test_init() {
		global $wpdb, $wp_filter;

		$bookDownload = BookDownload::init();

		$table = $bookDownload->getTable();

		$this->assertInstanceOf( BookDownload::class, $bookDownload ); // sanity check

		$this->assertNotEmpty( $wp_filter['store_download_data'] );
		$this->assertSame( $wpdb->get_var( "SHOW TABLES LIKE '$table'" ), $table );
	}

	/**
	 * @group book_download
	 */
	public function test_store_download_action() {
		global $wpdb;

		$download = BookDownload::init();

		$table = $download->getTable();

		$this->_book();

		do_action( 'store_download_data', 'epub' );

		$record = $wpdb->get_row( "SELECT * FROM $table" );

		$this->assertSame( 'epub', $record->track_value );
		$this->assertSame( 'book_download', $record->track_type );
    }
}
