<?php

class ThemeLockTest extends \WP_UnitTestCase {

	public function test_getLockDir() {

		$result = \Pressbooks\Theme\Lock::getLockDir();

		$this->assertEquals( true, substr( $result, -strlen( '/wp-content/uploads/lock' ) ) == '/wp-content/uploads/lock' );
	}

	public function test_getLockDirURI() {
		$result = \Pressbooks\Theme\Lock::getLockDirURI();

		$this->assertEquals( true, substr( $result, -strlen( '/wp-content/uploads/lock' ) ) == '/wp-content/uploads/lock' );
	}

	public function test_toggleThemeLock() {
		$time = time();
		sleep( 10 );
		$theme = wp_get_theme();
		$result = \Pressbooks\Theme\Lock::toggleThemeLock( [], [ 'theme_lock' => 1 ], 'pressbooks_export_options' );

		$this->assertArrayHasKey( 'stylesheet', $result );
		$this->assertArrayHasKey( 'name', $result );
		$this->assertArrayHasKey( 'version', $result );
		$this->assertArrayHasKey( 'timestamp', $result );
		$this->assertEquals( $result['stylesheet'], get_stylesheet() );
		$this->assertEquals( $result['name'], $theme->get( 'Name' ) );
		$this->assertEquals( $result['version'], $theme->get( 'Version' ) );
		$this->assertGreaterThanOrEqual( $time, $result['timestamp'] );

		$theme = wp_get_theme();
		$result = \Pressbooks\Theme\Lock::toggleThemeLock( [ 'theme_lock' => 1 ], [], 'pressbooks_export_options' );

		$this->assertEquals( $theme, $result );
	}

	public function test_lockTheme() {
		$time = time();
		sleep( 10 );
		$theme = wp_get_theme();

		$result = \Pressbooks\Theme\Lock::lockTheme();

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
		$return = \Pressbooks\Theme\Lock::copyAssets();

		$base = file_get_contents( get_stylesheet_directory() . '/style.css' );
		$lock = file_get_contents( \Pressbooks\Theme\Lock::getLockDir() . '/style.css' );

		$this->assertEquals( true, $return );
		$this->assertEquals( $base, $lock );
	}

	public function test_generateLock() {
		$time = time();

		$theme = wp_get_theme();

		$result = \Pressbooks\Theme\Lock::generateLock( $time );

		$this->assertEquals( true, file_exists( \Pressbooks\Theme\Lock::getLockDir() . '/lock.json' ) );
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
		$dir = \Pressbooks\Theme\Lock::getLockDir();
		\Pressbooks\Theme\Lock::unlockTheme();

		$this->assertEquals( false, is_dir( $dir ) );
	}

	public function test_isLocked() {
		update_option( 'pressbooks_export_options', [ 'theme_lock' => 1 ] );
		\Pressbooks\Theme\Lock::generateLock( time() );

		$value = \Pressbooks\Theme\Lock::isLocked();

		$this->assertEquals( true, $value );

		update_option( 'pressbooks_export_options', [] );
		\Pressbooks\Theme\Lock::unlockTheme();

		$value = \Pressbooks\Theme\Lock::isLocked();

		$this->assertEquals( false, $value );
	}

	public function test_getLockData() {
		$time = time();
		sleep( 10 );

		$theme = wp_get_theme();

		\Pressbooks\Theme\Lock::generateLock( $time );

		$result = \Pressbooks\Theme\Lock::getLockData();

		$this->assertArrayHasKey( 'stylesheet', $result );
		$this->assertArrayHasKey( 'name', $result );
		$this->assertArrayHasKey( 'version', $result );
		$this->assertArrayHasKey( 'timestamp', $result );
		$this->assertEquals( $result['stylesheet'], get_stylesheet() );
		$this->assertEquals( $result['name'], $theme->get( 'Name' ) );
		$this->assertEquals( $result['version'], $theme->get( 'Version' ) );
		$this->assertEquals( $result['timestamp'], $time );
	}
}
