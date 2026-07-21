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

		// Default to a real browser GET request.
		$this->originalServer = $_SERVER;
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$_SERVER['HTTP_USER_AGENT'] = $this->browserUa;
		$_SERVER['HTTP_REFERER'] = 'http://example.org/';
		$_SERVER['REMOTE_ADDR'] = '192.0.2.10';

		// Reset tracking state so each test is isolated.
		delete_site_option( Tracking::IP_SALT_OPTION );

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

	// -----------------------------------------------------------------------------------------------------------------
	// Migration
	// -----------------------------------------------------------------------------------------------------------------

	/**
	 * @group book_download
	 */
	public function test_migration_creates_evidence_columns_on_fresh_install() {
		global $wpdb;

		// Force a clean slate.
		delete_site_option( Tracking::DB_VERSION_OPTION );
		$wpdb->query( "DROP TABLE IF EXISTS {$this->table}" );

		BookDownload::init();

		$columns = $wpdb->get_col( "SHOW COLUMNS FROM {$this->table}" );
		$this->assertContains( 'user_agent', $columns );
		$this->assertContains( 'referrer', $columns );
		$this->assertContains( 'ip_hash', $columns );
		$this->assertSame( Tracking::DB_VERSION, get_site_option( Tracking::DB_VERSION_OPTION ) );
	}

	/**
	 * Reproduces the dbDelta trap (IF NOT EXISTS + backticks would silently skip
	 * the ALTER on a pre-existing table). Drops the table, recreates it with the
	 * old six-column schema, deletes the version option, then runs setup().
	 *
	 * @group book_download
	 */
	public function test_migration_upgrades_existing_table_from_old_schema() {
		global $wpdb;

		$wpdb->query( "DROP TABLE IF EXISTS {$this->table}" );
		$wpdb->query(
			"CREATE TABLE {$this->table} (
				`id` bigint(20) NOT NULL AUTO_INCREMENT,
				`blog_id` bigint(20) NOT NULL,
				`track_type` varchar(30) NOT NULL,
				`track_value` varchar(255),
				`logged_in` boolean NOT NULL default false,
				`created_at` datetime NOT NULL,
				PRIMARY KEY  (id)
			);"
		);
		delete_site_option( Tracking::DB_VERSION_OPTION );

		// Invoke setup() directly to force the migration path.
		$reflection = new ReflectionClass( Tracking::class );
		$method = $reflection->getMethod( 'setup' );
		$method->setAccessible( true );
		$instance = BookDownload::init();
		$method->invoke( $instance );

		$columns = $wpdb->get_col( "SHOW COLUMNS FROM {$this->table}" );
		$this->assertContains( 'user_agent', $columns );
		$this->assertContains( 'referrer', $columns );
		$this->assertContains( 'ip_hash', $columns );
		$this->assertSame( Tracking::DB_VERSION, get_site_option( Tracking::DB_VERSION_OPTION ) );
	}

	/**
	 * @group book_download
	 */
	public function test_setup_is_version_gated() {
		global $wpdb;

		update_site_option( Tracking::DB_VERSION_OPTION, Tracking::DB_VERSION );
		BookDownload::init();

		$reflection = new ReflectionClass( Tracking::class );
		$method = $reflection->getMethod( 'setup' );
		$method->setAccessible( true );

		$before = $wpdb->num_queries;
		$method->invoke( BookDownload::init() );

		$this->assertSame( 0, $wpdb->num_queries - $before, 'setup() queried the DB despite the version match.' );
	}

	// -----------------------------------------------------------------------------------------------------------------
	// Capture
	// -----------------------------------------------------------------------------------------------------------------

	/**
	 * @group book_download
	 */
	public function test_store_captures_evidence_columns() {
		global $wpdb;

		$this->fireDownloadAction( 'pdf' );

		$record = $wpdb->get_row( "SELECT * FROM $this->table" );

		$this->assertSame( $this->browserUa, $record->user_agent );
		$this->assertSame( 'http://example.org/', $record->referrer );
		$this->assertSame( 64, strlen( $record->ip_hash ) );
		$this->assertMatchesRegularExpression( '/^[0-9a-f]{64}$/', $record->ip_hash );
		// Raw IP must never appear in any stored value.
		$this->assertStringNotContainsString( '192.0.2.10', implode( ' ', (array) $record ) );
	}

	/**
	 * @group book_download
	 */
	public function test_store_persists_nulls_when_evidence_headers_missing() {
		global $wpdb;

		unset( $_SERVER['HTTP_REFERER'], $_SERVER['REMOTE_ADDR'] );

		$this->fireDownloadAction( 'pdf' );

		$record = $wpdb->get_row( "SELECT * FROM $this->table" );

		$this->assertNull( $record->referrer );
		$this->assertNull( $record->ip_hash );
		// UA still set in set_up(), so the row is counted.
		$this->assertSame( $this->browserUa, $record->user_agent );
	}

	/**
	 * @group book_download
	 */
	public function test_store_truncates_long_user_agent_and_referrer() {
		global $wpdb;

		$long_ua = str_repeat( 'a', 600 );
		$long_referrer = 'http://example.org/' . str_repeat( 'b', 600 );
		$_SERVER['HTTP_USER_AGENT'] = $long_ua;
		$_SERVER['HTTP_REFERER'] = $long_referrer;

		$this->fireDownloadAction( 'pdf' );

		$record = $wpdb->get_row( "SELECT * FROM $this->table" );

		$this->assertSame( 500, strlen( $record->user_agent ) );
		// Referrer is sanitized as a URL; the scheme+host survive, then trimmed to 500 chars total.
		$this->assertLessThanOrEqual( 500, strlen( $record->referrer ) );
	}

	/**
	 * @group book_download
	 */
	public function test_ip_hash_is_stable_for_same_ip_and_salt() {
		global $wpdb;

		$this->fireDownloadAction( 'pdf' );
		$first = $wpdb->get_var( "SELECT ip_hash FROM {$this->table} ORDER BY id DESC LIMIT 1" );
		$wpdb->query( "DELETE FROM {$this->table}" );

		$this->fireDownloadAction( 'pdf' );
		$second = $wpdb->get_var( "SELECT ip_hash FROM {$this->table} ORDER BY id DESC LIMIT 1" );

		$this->assertSame( $first, $second, 'ip_hash must be deterministic for the same IP + salt.' );

		// And the salt option must have been persisted.
		$this->assertNotEmpty( get_site_option( Tracking::IP_SALT_OPTION ) );
	}

	/**
	 * @group book_download
	 */
	public function test_pb_tracking_client_ip_filter_overrides_ip_source() {
		global $wpdb;

		add_filter( 'pb_tracking_client_ip', $cb = function () {
			return '198.51.100.42';
		} );

		$this->fireDownloadAction( 'pdf' );

		remove_filter( 'pb_tracking_client_ip', $cb );

		$record = $wpdb->get_row( "SELECT * FROM $this->table" );

		$salt = get_site_option( Tracking::IP_SALT_OPTION );
		$expected = hash( 'sha256', '198.51.100.42' . $salt );

		$this->assertSame( $expected, $record->ip_hash );
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
		return [
			'Googlebot' => [ 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)' ],
			'Bingbot' => [ 'Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)' ],
			'python-requests' => [ 'python-requests/2.31.0' ],
			'curl' => [ 'curl/8.4.0' ],
			'Bytespider' => [ 'Mozilla/5.0 (compatible; Bytespider; spider-feedback@bytedance.com)' ],
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
