<?php

class ThemeLockTest extends \WP_UnitTestCase {

	/**
	 * @covers \Pressbooks\ThemeLock::unlockTheme
	 */
	public function test_unlockTheme() {
		$dir = \Pressbooks\ThemeLock::getLockDir();
		\Pressbooks\ThemeLock::unlockTheme();

		$this->assertEquals( false, is_dir( $dir ) );
	}

	/**
	 * @covers \Pressbooks\ThemeLock::copyAssets
	 */
	public function test_copyAssets() {
		$return = \Pressbooks\ThemeLock::copyAssets();

		$base = file_get_contents( get_stylesheet_directory() . '/style.css' );
		$lock = file_get_contents( \Pressbooks\ThemeLock::getLockDir() . '/style.css' );

		$this->assertEquals( true, $return );
		$this->assertEquals( $base, $lock );
	}

	/**
	 * @covers \Pressbooks\ThemeLock::generateLock
	 */
	public function test_generateLock() {
		$theme = wp_get_theme();
		$time = time();

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

	/**
	 * @covers \Pressbooks\ThemeLock::getLockDir
	 */
	public function test_getLockDir() {

		$result = \Pressbooks\ThemeLock::getLockDir();

		$this->assertEquals( true, substr( $result, -strlen( '/wp-content/uploads/lock' ) ) == '/wp-content/uploads/lock' );
	}

	/**
	 * @covers \Pressbooks\ThemeLock::getLockDirURI
	 */
	public function test_getLockDirURI() {

		$result = \Pressbooks\ThemeLock::getLockDirURI();

		$this->assertEquals( true, substr( $result, -strlen( '/wp-content/uploads/lock' ) ) == '/wp-content/uploads/lock' );
	}

}
