<?php

require_once( PB_PLUGIN_DIR . 'inc/admin/fonts/namespace.php' );

use Pressbooks\Container;
use Pressbooks\ServiceProvider;

class Admin_FontsTest extends \WP_UnitTestCase {
	use utilsTrait;

	/**
	 * Override
	 * @group typography
	 */
	public function set_up() {
		parent::set_up();

		// Replace Sass service with mock
		Container::set( 'Sass', function () {
			$stub = $this
				->getMockBuilder( '\Pressbooks\Sass' )
				->getMock();

			$stub
				->method( 'pathToUserGeneratedCss' )
				->willReturn( $this->_createTmpDir() );

			$stub
				->method( 'pathToPartials' )
				->willReturn( WP_CONTENT_DIR . '/themes/pressbooks-book/assets/legacy/styles' );

			return $stub;
		}, null, true );

		// Replace GlobalTypography service with mock
		Container::set( 'GlobalTypography', function () {
			return $this
				->getMockBuilder( '\Pressbooks\GlobalTypography' )
				->setConstructorArgs( [ Container::get( 'Sass' ) ] )
				->getMock();
		}, null, true );
	}


	/**
	 * Override
	 * @group typography
	 */
	public function tear_down() {
		ServiceProvider::init();

		parent::tear_down();
	}

	/**
	 * @group typography
	 */
	public function test_update_font_stacks() {
		\Pressbooks\Admin\Fonts\update_font_stacks();
		$this->assertTrue( true ); // Did not crash
		$this->assertFalse( get_transient( 'pressbooks_updating_font_stacks' ) );
	}

	/**
	 * @group typography
	 */
	public function test_fix_missing_font_stacks() {
		$this->_book( 'pressbooks-luther' );
		\Pressbooks\Admin\Fonts\maybe_update_font_stacks();
		$this->assertTrue( true ); // Did not crash
		$this->assertFalse( get_transient( 'pressbooks_updating_font_stacks' ) );
	}
}
