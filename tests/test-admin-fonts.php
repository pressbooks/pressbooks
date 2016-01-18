<?php

require_once( PB_PLUGIN_DIR . 'includes/admin/pb-fonts.php' );

use PressBooks\Container;

class Admin_FontsTest extends \WP_UnitTestCase {

	/**
	 * Create and switch to a new test book
	 */
	private function _book() {

		$blog_id = $this->factory->blog->create();
		switch_to_blog( $blog_id );
		switch_theme( 'pressbooks-book' );
	}


	/**
	 * Create a temporary directory, no trailing slash!
	 *
	 * @return string
	 * @throws \Exception
	 */
	private function _createTmpDir() {

		$temp_file = tempnam( sys_get_temp_dir(), '' );
		if ( file_exists( $temp_file ) ) {
			unlink( $temp_file );
		}
		mkdir( $temp_file );
		if ( ! is_dir( $temp_file ) ) {
			throw new \Exception( 'Could not create temporary directory.' );

		}

		return untrailingslashit( $temp_file );
	}


	/**
	 * Override
	 */
	public function setUp() {

		parent::setUp();

		// Replace GlobalTypography service with mock
		Container::set( 'GlobalTypography', function () {

			$stub = $this->getMockBuilder( '\PressBooks\GlobalTypography' )
				->getMock();

			return $stub;
		} );


		// Replace Sass service with mock
		Container::set( 'Sass', function () {

			$stub = $this->getMockBuilder( '\PressBooks\Sass' )
				->getMock();

			$stub->method( 'pathToUserGeneratedCss' )
				->willReturn( $this->_createTmpDir() );

			$stub->method( 'pathToPartials' )
				->willReturn( PB_PLUGIN_DIR . 'assets/scss/partials' );

			return $stub;
		} );
	}


	/**
	 * Override
	 */
	public function tearDown() {

		Container::init(); // Reset
		parent::tearDown();
	}


	/**
	 * @covers \PressBooks\Admin\Fonts\update_font_stacks
	 */
	public function test_update_font_stacks() {

		\PressBooks\Admin\Fonts\update_font_stacks();
		$this->assertTrue( true );
	}


	/**
	 * @covers \PressBooks\Admin\Fonts\fix_missing_font_stacks
	 */
	public function test_fix_missing_font_stacks() {

		$this->_book();
		\PressBooks\Admin\Fonts\fix_missing_font_stacks();
		$this->assertTrue( true );
	}


}
