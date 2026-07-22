<?php

use Pressbooks\Tracking\BookDownload;
use Pressbooks\Tracking\Tracking;

class Track_BookDownloadTest extends \WP_UnitTestCase {
	use utilsTrait;

	protected $table;

	/**
	 * @var array Preserved $_SERVER for restoration in tear_down.
	 */
	protected $originalServer;

	/**
	 * Browser-likewise default request environment so the existing assertions
	 * keep working once BookDownload::store() filters bots/non-GET requests.
	 */
	private $browserUa = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36';

	public function set_up() {
		parent::set_up();

		// Reset the BookDownload singleton so init() re-runs per test.
		$reflection = new ReflectionClass( BookDownload::class );
		$instance = $reflection->getProperty( 'instance' );
		$instance->setAccessible( true );
		$instance->setValue( null, null );

		remove_all_actions( 'store_download_data' );

		// Default to a real browser GET request so the existing assertions
		// keep working once the filter is in place.
		$this->originalServer = $_SERVER;
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$_SERVER['HTTP_USER_AGENT'] = $this->browserUa;

		$this->_book();

		global $wpdb;
		$this->table = $wpdb->base_prefix . 'pressbooks_tracking';
		$wpdb->query( "DELETE FROM {$this->table}" );
	}

	public function tear_down() {
		$_SERVER = $this->originalServer;
		parent::tear_down();
	}

	/**
	 * Triggers the tracking code path by firing the store_download_data action.
	 *
	 * @return void
	 */
	private function fireDownloadAction( $value = 'pdf' ) {
		BookDownload::init();
		do_action( 'store_download_data', $value );
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

		$this->fireDownloadAction( 'epub' );

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

		$message = '';
		try {
			\Pressbooks\Redirect\do_open();
		} catch ( \WPDieException $e ) {
			$message = $e->getMessage();
		}

		$this->assertSame( 'Error: Unknown export format.', $message );
	}

	/**
	 * @group book_download
	 */
	public function test_download_book_call() {
		global $wpdb;

		BookDownload::init();

		$_GET['type'] = 'pdf';
		$GLOBALS['wp_query']->query_vars = array_merge( $GLOBALS['wp_query']->query_vars, [ 'open' => 'download' ] );

		$filepath = \Pressbooks\Modules\Export\Export::getExportFolder() . 'test-1623077888.pdf';
		copy( __DIR__ . '/data/test.pdf', $filepath );

		\Pressbooks\Redirect\do_open( static function ( $param ) {} );

		$record = $wpdb->get_row( "SELECT * FROM $this->table" );

		$this->assertSame( 'pdf', $record->track_value );
		$this->assertSame( 'book_download', $record->track_type );
	}

	/**
	 * @group book_download
	 */
	public function test_inserted_row_has_only_original_columns() {
		global $wpdb;

		$this->fireDownloadAction( 'pdf' );

		$columns = $wpdb->get_col( "SHOW COLUMNS FROM {$this->table}" );
		$expected = [ 'id', 'blog_id', 'track_type', 'track_value', 'logged_in', 'created_at' ];

		$this->assertSame( $expected, $columns, 'pressbooks_tracking schema must be unchanged — no UA/IP/referrer stored.' );
	}

	// -----------------------------------------------------------------------------------------------------------------
	// Filters
	// -----------------------------------------------------------------------------------------------------------------

	/**
	 * @group book_download
	 */
	public function test_filter_skips_non_get_requests() {
		global $wpdb;

		foreach ( [ 'HEAD', 'POST' ] as $method ) {
			$_SERVER['REQUEST_METHOD'] = $method;
			$this->fireDownloadAction( 'pdf' );
		}

		$this->assertSame( '0', $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table}" ) );
	}

	/**
	 * @group book_download
	 */
	public function test_filter_skips_empty_user_agent() {
		global $wpdb;

		unset( $_SERVER['HTTP_USER_AGENT'] );

		$this->fireDownloadAction( 'pdf' );

		$this->assertSame( '0', $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table}" ) );
	}

	/**
	 * @group book_download
	 * @dataProvider botUserAgents
	 */
	public function test_filter_skips_known_bots( $ua ) {
		global $wpdb;

		$_SERVER['HTTP_USER_AGENT'] = $ua;

		$this->fireDownloadAction( 'pdf' );

		$this->assertSame( '0', $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table}" ), "Bot UA not filtered: {$ua}" );
	}

	public function botUserAgents() {
		// Crawler-Detect catches named crawlers AND generic HTTP libraries
		// (python-requests, curl, Wget) — the latter are missed by
		// matomo/device-detector, which is why we use Crawler-Detect here.
		return [
			'Googlebot' => [ 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)' ],
			'Bingbot' => [ 'Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)' ],
			'Bytespider' => [ 'Mozilla/5.0 (compatible; Bytespider; spider-feedback@bytedance.com)' ],
			'python-requests' => [ 'python-requests/2.31.0' ],
			'curl' => [ 'curl/8.4.0' ],
			'Wget' => [ 'Wget/1.21' ],
		];
	}

	/**
	 * @group book_download
	 * @dataProvider humanUserAgents
	 */
	public function test_filter_counts_real_browsers( $ua ) {
		global $wpdb;

		$_SERVER['HTTP_USER_AGENT'] = $ua;

		$this->fireDownloadAction( 'pdf' );

		$this->assertSame( '1', $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table}" ), "Human UA filtered out: {$ua}" );
	}

	public function humanUserAgents() {
		return [
			'Chrome desktop' => [ 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36' ],
			'Firefox desktop' => [ 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:127.0) Gecko/20100101 Firefox/127.0' ],
			'Safari desktop' => [ 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Safari/605.1.15' ],
			'Safari mobile' => [ 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1' ],
		];
	}
}
