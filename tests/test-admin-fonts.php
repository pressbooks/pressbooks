<?php

require_once( PB_PLUGIN_DIR . 'includes/admin/pb-fonts.php' );

use PressBooks\Container;

class Admin_FontsTest extends \WP_UnitTestCase {


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
				->willReturn( untrailingslashit( sys_get_temp_dir() ) );

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

		\PressBooks\Admin\Fonts\fix_missing_font_stacks();
		$this->assertTrue( true );
	}


}
