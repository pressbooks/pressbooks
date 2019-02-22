<?php

use function Pressbooks\Theme\update_lock_file;

class ThemeTest extends \WP_UnitTestCase {
	use utilsTrait;

	/**
	 * @var \Pressbooks\Theme\Lock
	 * @group themes
	 */
	protected $lock;

	/**
	 * @group themes
	 */
	public function setUp() {
		$this->lock = new \Pressbooks\Theme\Lock();
	}

	/**
	 * @group themes
	 */
	public function test_migrate_book_themes() {
		$this->_book();
		delete_option( 'pressbooks_theme_migration' );
		\Pressbooks\Theme\migrate_book_themes();
		$this->assertEquals( 5, get_option( 'pressbooks_theme_migration' ) );
	}

	/**
	 * @group themes
	 */
	public function test_update_template_root() {
		$old = get_option( 'template_root' );

		update_option( 'template_root', '/plugins/pressbooks/themes-book' );
		\Pressbooks\Theme\update_template_root();
		$this->assertEquals( '/themes', get_option( 'template_root' ) );

		update_option( 'template_root', $old ); // Put back to normal
	}

	/**
	 * @group themes
	 */
	public function test_update_lock_file() {
		$this->_book();
		update_option( 'pressbooks_export_options', [ 'theme_lock' => 1 ] );
		$this->lock->generateLock( time() );
		$old_lock = $this->lock->getLockData();
		$this->assertArrayHasKey( 'stylesheet', $old_lock );
		$this->assertEquals( $old_lock['stylesheet'], get_stylesheet() );
		$new_data = [ 'stylesheet' => '' ];
		\Pressbooks\Theme\update_lock_file( $new_data );
		$new_lock = $this->lock->getLockData();
		$this->assertEquals( $old_lock, $new_lock );
		$new_data = [ 'stylesheet' => 'pressbooks-newtheme' ];
		$result = \Pressbooks\Theme\update_lock_file( $new_data );
		$this->assertTrue( $result );
		$new_lock = $this->lock->getLockData();
		$this->assertEquals( 'pressbooks-newtheme', $new_lock['stylesheet'] );
	}
}
