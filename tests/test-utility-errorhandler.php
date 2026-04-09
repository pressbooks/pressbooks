<?php

use Pressbooks\Utility\ErrorHandler;

/**
 * @group utility
 */
class ErrorHandlerTest extends \WP_UnitTestCase {

	protected $added_filters = [];

	public function tear_down() {
		// Clean up all filters we added
		foreach ( $this->added_filters as $filter_name ) {
			remove_all_filters( $filter_name );
		}
		$this->added_filters = [];
		parent::tear_down();
	}

	protected function add_test_filter( $filter_name, $callback ) {
		add_filter( $filter_name, $callback );
		$this->added_filters[] = $filter_name;
	}

	/**
	 * @group utility
	 */
	public function test_init_returns_singleton() {
		$instance1 = ErrorHandler::init();
		$instance2 = ErrorHandler::init();

		$this->assertSame( $instance1, $instance2 );
		$this->assertInstanceOf( ErrorHandler::class, $instance1 );
	}

	/**
	 * @group utility
	 */
	public function test_silence_deprecation_notices_returns_false_for_non_deprecated_errors() {
		$handler = new ErrorHandler();

		$result = $handler->silenceDeprecationNotices( E_WARNING, '/some/file.php' );
		$this->assertFalse( $result );

		$result = $handler->silenceDeprecationNotices( E_NOTICE, '/some/file.php' );
		$this->assertFalse( $result );

		$result = $handler->silenceDeprecationNotices( E_ERROR, '/some/file.php' );
		$this->assertFalse( $result );
	}

	/**
	 * @group utility
	 */
	public function test_silence_deprecation_notices_returns_false_for_deprecated_when_no_paths_configured() {
		$handler = new ErrorHandler();

		$result = $handler->silenceDeprecationNotices( E_DEPRECATED, '/any/file.php' );
		$this->assertFalse( $result );
	}

	/**
	 * @group utility
	 */
	public function test_silence_deprecation_notices_returns_true_when_path_matches() {
		// Temporarily override env to ensure test runs
		putenv( 'WP_ENV=testing' );
		putenv( 'DISPLAY_PHP_DEPRECATIONS=false' );

		$this->add_test_filter( 'pressbooks_deprecation_paths_to_silence', function( $paths ) {
			$paths[] = '/plugins/pressbooks/inc/deprecated/';
			return $paths;
		} );

		$handler = new ErrorHandler();

		$result = $handler->silenceDeprecationNotices(
			E_DEPRECATED,
			'/var/www/html/wp-content/plugins/pressbooks/inc/deprecated/old-code.php'
		);
		$this->assertTrue( $result );

		// Restore env
		putenv( 'WP_ENV' );
		putenv( 'DISPLAY_PHP_DEPRECATIONS' );
	}

	/**
	 * @group utility
	 */
	public function test_silence_deprecation_notices_returns_false_when_path_does_not_match() {
		// Temporarily override env to ensure test runs
		putenv( 'WP_ENV=testing' );
		putenv( 'DISPLAY_PHP_DEPRECATIONS=false' );

		$this->add_test_filter( 'pressbooks_deprecation_paths_to_silence', function( $paths ) {
			$paths[] = '/plugins/pressbooks/inc/deprecated/';
			return $paths;
		} );

		$handler = new ErrorHandler();

		$result = $handler->silenceDeprecationNotices(
			E_DEPRECATED,
			'/var/www/html/wp-content/plugins/other-plugin/some-file.php'
		);
		$this->assertFalse( $result );

		// Restore env
		putenv( 'WP_ENV' );
		putenv( 'DISPLAY_PHP_DEPRECATIONS' );
	}

	/**
	 * @group utility
	 */
	public function test_silence_deprecation_notices_filter_receives_empty_array() {
		$received_paths = null;

		$this->add_test_filter( 'pressbooks_deprecation_paths_to_silence', function( $paths ) use ( &$received_paths ) {
			$received_paths = $paths;
			return $paths;
		} );

		$handler = new ErrorHandler();
		$handler->silenceDeprecationNotices( E_DEPRECATED, '/file.php' );

		$this->assertIsArray( $received_paths );
		$this->assertEmpty( $received_paths );
	}

	/**
	 * @group utility
	 */
	public function test_silence_deprecation_notices_handles_multiple_paths() {
		putenv( 'WP_ENV=testing' );
		putenv( 'DISPLAY_PHP_DEPRECATIONS=false' );

		$this->add_test_filter( 'pressbooks_deprecation_paths_to_silence', function( $paths ) {
			$paths[] = '/path/one/';
			$paths[] = '/path/two/';
			return $paths;
		} );

		$handler = new ErrorHandler();

		// Should match first path
		$result = $handler->silenceDeprecationNotices( E_DEPRECATED, '/var/www/path/one/file.php' );
		$this->assertTrue( $result );

		// Should match second path
		$result = $handler->silenceDeprecationNotices( E_DEPRECATED, '/var/www/path/two/file.php' );
		$this->assertTrue( $result );

		// Should not match either path
		$result = $handler->silenceDeprecationNotices( E_DEPRECATED, '/var/www/path/three/file.php' );
		$this->assertFalse( $result );

		// Restore env
		putenv( 'WP_ENV' );
		putenv( 'DISPLAY_PHP_DEPRECATIONS' );
	}
}
