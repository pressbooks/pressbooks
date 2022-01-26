<?php

use Pressbooks\Tracking\BookDownload;

class Track_BookDownloadTest extends \WP_UnitTestCase {
	use utilsTrait;

	protected $table;

	public function set_up() {
		parent::set_up();

		global $wpdb;

		$reflection = new ReflectionClass(BookDownload::class);
		$instance = $reflection->getProperty('instance');
		$instance->setAccessible(true);
		$instance->setValue(null);
		$instance->setAccessible(false);

		$this->_book();

		$this->table = $wpdb->base_prefix . 'pressbooks_tracking';
	}

	/**
	 * @group book_download
	 */
	public function test_init() {
		global $wpdb, $wp_filter;

		$bookDownload = BookDownload::init();

		$this->assertInstanceOf( BookDownload::class, $bookDownload ); // sanity check

		$this->assertNotEmpty( $wp_filter['store_download_data'] );
		$this->assertSame( $wpdb->get_var( "SHOW TABLES LIKE '$this->table'" ), $this->table );
	}

	/**
	 * @group book_download
	 */
	public function test_store_download_action() {
		global $wpdb;

		BookDownload::init();

		do_action( 'store_download_data', 'epub' );

		$record = $wpdb->get_row( "SELECT * FROM $this->table" );

		$this->assertSame( 'epub', $record->track_value );
		$this->assertSame( 'book_download', $record->track_type );
	}

	/**
	 * @group book_download
	 */
	public function test_download_book_call_exception() {
		$_GET['type'] = 'epub';
		$GLOBALS['wp_query']->query_vars = array_merge( $GLOBALS['wp_query']->query_vars, [ 'open' => 'download' ] );

		try{
			\Pressbooks\Redirect\do_open();
		} catch (\WPDieException $e) {
			$message = $e->getMessage();
		}

		$this->assertSame( 'Error: Unknown export format.', $message );
	}

	/**
	 * @group book_download
	 */
	public function test_download_book_call() {
		global $wpdb;

		$_GET['type'] = 'pdf';
		$GLOBALS['wp_query']->query_vars = array_merge( $GLOBALS['wp_query']->query_vars, [ 'open' => 'download' ] );

		$filepath = \Pressbooks\Modules\Export\Export::getExportFolder() . 'test-1623077888.pdf';
		copy( __DIR__ . '/data/test.pdf', $filepath );

		\Pressbooks\Redirect\do_open( static function( $param ) {} );

		$record = $wpdb->get_row( "SELECT * FROM $this->table" );

		$this->assertSame( 'pdf', $record->track_value );
		$this->assertSame( 'book_download', $record->track_type );
	}
}
