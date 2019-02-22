<?php

use function Pressbooks\Theme\update_lock_file;

class ThemeTest extends \WP_UnitTestCase {
	use utilsTrait;

	/**
	 * @var \Pressbooks\Theme\Lock
	 */
	protected $lock;

	public function setUp() {
		$this->lock = new \Pressbooks\Theme\Lock();
	}

	public function test_migrate_book_themes() {
		$this->_book();
		delete_option( 'pressbooks_theme_migration' );
		\Pressbooks\Theme\migrate_book_themes();
		$this->assertEquals( 5, get_option( 'pressbooks_theme_migration' ) );
	}

	public function test_update_template_root() {
		$old = get_option( 'template_root' );

		update_option( 'template_root', '/plugins/pressbooks/themes-book' );
		\Pressbooks\Theme\update_template_root();
		$this->assertEquals( '/themes', get_option( 'template_root' ) );

		update_option( 'template_root', $old ); // Put back to normal
	}

	public function test_update_lock_file() {
		$this->_book();
		$lock = $this->lock::init();
		$old_lock = $lock->lockTheme();
		$this->assertArrayHasKey( 'stylesheet', $old_lock );
		$this->assertEquals( $old_lock['stylesheet'], get_stylesheet() );
		$new_data = [ 'stylesheet' => '' ];
		\Pressbooks\Theme\update_lock_file( $new_data );
		$new_lock = $lock->getLockData();
		$this->assertEquals( $old_lock, $new_lock );
		$new_data = [ 'stylesheet' => 'pressbooks-what' ];
		\Pressbooks\Theme\update_lock_file( $new_data );
		$new_lock = $lock->getLockData();
		$this->assertEquals( 'pressbooks-what', $new_lock['stylesheet'] );
	}
}
