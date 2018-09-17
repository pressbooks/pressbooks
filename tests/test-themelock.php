<?php

use Pressbooks\Theme\Lock;

class ThemeLockTest extends \WP_UnitTestCase {

	use utilsTrait;

	/**
	 * @var Lock
	 */
	protected $lock;
	
	public function setUp() {
		$this->lock = new Lock();
	}

	public function test_init() {
		$instance = Lock::init();
		$this->assertTrue( $instance instanceof \Pressbooks\Theme\Lock );
	}

//	public function test_hooks() { // TODO
//	}

	public function test_getLockDir() {

		$result = $this->lock->getLockDir();

		$this->assertEquals( true, substr( $result, -strlen( '/wp-content/uploads/pressbooks/lock' ) ) == '/wp-content/uploads/pressbooks/lock' );
	}

	public function test_getLockDirURI() {
		$result = $this->lock->getLockDirURI();

		$this->assertEquals( true, substr( $result, -strlen( '/wp-content/uploads/pressbooks/lock' ) ) == '/wp-content/uploads/pressbooks/lock' );
	}

	public function test_toggleThemeLock() {
		$time = time() - 10;
		$theme = wp_get_theme();
		$result = $this->lock->toggleThemeLock( [], [ 'theme_lock' => 1 ], 'pressbooks_export_options' );

		$this->assertArrayHasKey( 'stylesheet', $result );
		$this->assertArrayHasKey( 'name', $result );
		$this->assertArrayHasKey( 'version', $result );
		$this->assertArrayHasKey( 'timestamp', $result );
		$this->assertEquals( $result['stylesheet'], get_stylesheet() );
		$this->assertEquals( $result['name'], $theme->get( 'Name' ) );
		$this->assertEquals( $result['version'], $theme->get( 'Version' ) );
		$this->assertGreaterThanOrEqual( $time, $result['timestamp'] );

		$theme = wp_get_theme();
		$result = $this->lock->toggleThemeLock( [ 'theme_lock' => 1 ], [], 'pressbooks_export_options' );

		$this->assertEquals( $theme, $result );
	}

	public function test_lockTheme() {
		$time = time() - 10;
		$theme = wp_get_theme();

		$result = $this->lock->lockTheme();

		$this->assertArrayHasKey( 'stylesheet', $result );
		$this->assertArrayHasKey( 'name', $result );
		$this->assertArrayHasKey( 'version', $result );
		$this->assertArrayHasKey( 'timestamp', $result );
		$this->assertEquals( $result['stylesheet'], get_stylesheet() );
		$this->assertEquals( $result['name'], $theme->get( 'Name' ) );
		$this->assertEquals( $result['version'], $theme->get( 'Version' ) );
		$this->assertGreaterThanOrEqual( $time, $result['timestamp'] );
	}

	public function test_copyAssets() {
		// Delete all files in the lock directory before testing
		array_map( 'unlink', array_filter( glob( $this->lock->getLockDir() . '/*' ), 'is_file' ) );

		$return = $this->lock->copyAssets();

		// Styles are included
		$base = file_get_contents( get_stylesheet_directory() . '/style.css' );
		$lock = file_get_contents( $this->lock->getLockDir() . '/style.css' );
		$this->assertEquals( true, $return );
		$this->assertEquals( $base, $lock );

		// PHP Files are excluded
		$this->assertTrue( file_exists( get_stylesheet_directory() . '/index.php' ) );
		$this->assertFalse( file_exists( $this->lock->getLockDir() . '/index.php' ) );
	}

	public function test_generateLock() {
		$time = time();

		$theme = wp_get_theme();

		$result = $this->lock->generateLock( $time );

		$this->assertEquals( true, file_exists( $this->lock->getLockDir() . '/lock.json' ) );
		$this->assertArrayHasKey( 'stylesheet', $result );
		$this->assertArrayHasKey( 'name', $result );
		$this->assertArrayHasKey( 'version', $result );
		$this->assertArrayHasKey( 'timestamp', $result );
		$this->assertEquals( $result['stylesheet'], get_stylesheet() );
		$this->assertEquals( $result['name'], $theme->get( 'Name' ) );
		$this->assertEquals( $result['version'], $theme->get( 'Version' ) );
		$this->assertEquals( $result['timestamp'], $time );
	}

	public function test_unlockTheme() {
		$dir = $this->lock->getLockDir();
		$this->lock->unlockTheme();

		$this->assertEquals( false, is_dir( $dir ) );
	}

	public function test_isLocked() {
		update_option( 'pressbooks_export_options', [ 'theme_lock' => 1 ] );
		$this->lock->generateLock( time() );

		$value = $this->lock->isLocked();

		$this->assertEquals( true, $value );

		update_option( 'pressbooks_export_options', [] );
		$this->lock->unlockTheme();

		$value = $this->lock->isLocked();

		$this->assertEquals( false, $value );
	}

	public function test_getLockData() {
		$time = time() - 10;

		$theme = wp_get_theme();
		add_theme_support( 'zig-zag-zog' );

		$this->lock->generateLock( $time );
		$result = $this->lock->getLockData();

		$this->assertArrayHasKey( 'stylesheet', $result );
		$this->assertArrayHasKey( 'name', $result );
		$this->assertArrayHasKey( 'version', $result );
		$this->assertArrayHasKey( 'timestamp', $result );
		$this->assertArrayHasKey( 'features', $result );
		$this->assertTrue( is_array( $result['features'] ) );
		$this->assertEquals( $result['stylesheet'], get_stylesheet() );
		$this->assertEquals( $result['name'], $theme->get( 'Name' ) );
		$this->assertEquals( $result['version'], $theme->get( 'Version' ) );
		$this->assertEquals( $result['timestamp'], $time );
		$this->assertContains( 'zig-zag-zog', $result['features'] );
	}

	public function test_globalComponentsPath() {
		// Delete all files in the lock directory before testing
		array_map( 'unlink', glob( $this->lock->getLockDir() . '/*' ) );

		$result = $this->lock->globalComponentsPath( '/hello-world' );
		$this->assertEquals( '/hello-world', $result );

		$this->lock->copyAssets();
		$result = $this->lock->globalComponentsPath( '/hello-world' );
		$this->assertContains( 'lock/global-components', $result );
	}
}
