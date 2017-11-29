<?php

use Pressbooks\Theme\Lock;

class ThemeLockTest extends \WP_UnitTestCase {

	public function test_getLockDir() {

		$result = Lock::getLockDir();

		$this->assertEquals( true, substr( $result, -strlen( '/wp-content/uploads/lock' ) ) == '/wp-content/uploads/lock' );
	}

	public function test_getLockDirURI() {
		$result = Lock::getLockDirURI();

		$this->assertEquals( true, substr( $result, -strlen( '/wp-content/uploads/lock' ) ) == '/wp-content/uploads/lock' );
	}

	public function test_toggleThemeLock() {
		$time = time() - 10;
		$theme = wp_get_theme();
		$result = Lock::toggleThemeLock( [], [ 'theme_lock' => 1 ], 'pressbooks_export_options' );

		$this->assertArrayHasKey( 'stylesheet', $result );
		$this->assertArrayHasKey( 'name', $result );
		$this->assertArrayHasKey( 'version', $result );
		$this->assertArrayHasKey( 'timestamp', $result );
		$this->assertEquals( $result['stylesheet'], get_stylesheet() );
		$this->assertEquals( $result['name'], $theme->get( 'Name' ) );
		$this->assertEquals( $result['version'], $theme->get( 'Version' ) );
		$this->assertGreaterThanOrEqual( $time, $result['timestamp'] );

		$theme = wp_get_theme();
		$result = Lock::toggleThemeLock( [ 'theme_lock' => 1 ], [], 'pressbooks_export_options' );

		$this->assertEquals( $theme, $result );
	}

	public function test_lockTheme() {
		$time = time() - 10;
		$theme = wp_get_theme();

		$result = Lock::lockTheme();

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
		array_map( 'unlink', glob( Lock::getLockDir() . '/*' ) );

		$return = Lock::copyAssets();

		// Styles are included
		$base = file_get_contents( get_stylesheet_directory() . '/style.css' );
		$lock = file_get_contents( Lock::getLockDir() . '/style.css' );
		$this->assertEquals( true, $return );
		$this->assertEquals( $base, $lock );

		// PHP Files are excluded
		$this->assertTrue( file_exists( get_stylesheet_directory() . '/index.php' ) );
		$this->assertFalse( file_exists( Lock::getLockDir() . '/index.php' ) );
	}

	public function test_generateLock() {
		$time = time();

		$theme = wp_get_theme();

		$result = Lock::generateLock( $time );

		$this->assertEquals( true, file_exists( Lock::getLockDir() . '/lock.json' ) );
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
		$dir = Lock::getLockDir();
		Lock::unlockTheme();

		$this->assertEquals( false, is_dir( $dir ) );
	}

	public function test_isLocked() {
		update_option( 'pressbooks_export_options', [ 'theme_lock' => 1 ] );
		Lock::generateLock( time() );

		$value = Lock::isLocked();

		$this->assertEquals( true, $value );

		update_option( 'pressbooks_export_options', [] );
		Lock::unlockTheme();

		$value = Lock::isLocked();

		$this->assertEquals( false, $value );
	}

	public function test_getLockData() {
		$time = time() - 10;

		$theme = wp_get_theme();
		add_theme_support( 'zig-zag-zog' );

		Lock::generateLock( $time );
		$result = Lock::getLockData();

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
}
