<?php

class ThemeLockTest extends \WP_UnitTestCase {

	public function test_getLockDir() {

		$result = \Pressbooks\ThemeLock::getLockDir();

		$this->assertEquals( true, substr( $result, -strlen( '/wp-content/uploads/lock' ) ) == '/wp-content/uploads/lock' );
	}

	public function test_getLockDirURI() {
		$result = \Pressbooks\ThemeLock::getLockDirURI();

		$this->assertEquals( true, substr( $result, -strlen( '/wp-content/uploads/lock' ) ) == '/wp-content/uploads/lock' );
	}

	public function test_toggleThemeLock() {
		$time = time();
		sleep( 10 );
		$theme = wp_get_theme();
		$result = \Pressbooks\ThemeLock::toggleThemeLock( [], [ 'theme_lock' => 1 ], 'pressbooks_export_options' );

		$this->assertArrayHasKey( 'stylesheet', $result );
		$this->assertArrayHasKey( 'name', $result );
		$this->assertArrayHasKey( 'version', $result );
		$this->assertArrayHasKey( 'timestamp', $result );
		$this->assertEquals( $result['stylesheet'], get_stylesheet() );
		$this->assertEquals( $result['name'], $theme->get( 'Name' ) );
		$this->assertEquals( $result['version'], $theme->get( 'Version' ) );
		$this->assertGreaterThanOrEqual( $time, $result['timestamp'] );

		$theme = wp_get_theme();
		$result = \Pressbooks\ThemeLock::toggleThemeLock( [ 'theme_lock' => 1 ], [], 'pressbooks_export_options' );

		$this->assertEquals( $theme, $result );
	}

	public function test_lockTheme() {
		$time = time();
		sleep( 10 );
		$theme = wp_get_theme();

		$result = \Pressbooks\ThemeLock::lockTheme();

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
		$return = \Pressbooks\ThemeLock::copyAssets();

		$base = file_get_contents( get_stylesheet_directory() . '/style.css' );
		$lock = file_get_contents( \Pressbooks\ThemeLock::getLockDir() . '/style.css' );

		$this->assertEquals( true, $return );
		$this->assertEquals( $base, $lock );
	}

	public function test_generateLock() {
		$time = time();

		$theme = wp_get_theme();

		$result = \Pressbooks\ThemeLock::generateLock( $time );

		$this->assertEquals( true, file_exists( \Pressbooks\ThemeLock::getLockDir() . '/lock.json' ) );
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
		$dir = \Pressbooks\ThemeLock::getLockDir();
		\Pressbooks\ThemeLock::unlockTheme();

		$this->assertEquals( false, is_dir( $dir ) );
	}

	public function test_isLocked() {
		update_option( 'pressbooks_export_options', [ 'theme_lock' => 1 ] );
		\Pressbooks\ThemeLock::generateLock( time() );

		$value = \Pressbooks\ThemeLock::isLocked();

		$this->assertEquals( true, $value );

		update_option( 'pressbooks_export_options', [] );
		\Pressbooks\ThemeLock::unlockTheme();

		$value = \Pressbooks\ThemeLock::isLocked();

		$this->assertEquals( false, $value );
	}

	public function test_getLockData() {
		$time = time();
		sleep( 10 );

		$theme = wp_get_theme();

		\Pressbooks\ThemeLock::generateLock( $time );

		$result = \Pressbooks\ThemeLock::getLockData();

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
