<?php

use Pressbooks\Tracking\BookDownload;

class Track_BookDownloadTest extends \WP_UnitTestCase {
	use utilsTrait;

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
		$type = $download->getType();

		$this->_book();

		do_action( 'store_download_data', 'epub' );

		$record = $wpdb->get_row( "SELECT `track_value` FROM $table WHERE `track_type` = '$type'" );

        $this->assertSame( 'epub', $record->track_value );
    }
}
