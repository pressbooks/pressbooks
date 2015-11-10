<?php

class Admin_BrandingTest extends \WP_UnitTestCase {


	/**
	 * @covers \PressBooks\Admin\Branding\custom_login_logo
	 */
	public function test_custom_login_logo() {

		$this->expectOutputRegex( '/<\/style>/' );
		\PressBooks\Admin\Branding\custom_login_logo();
	}


	/**
	 * @covers \PressBooks\Admin\Branding\login_url
	 */
	public function test_login_url() {

		$this->assertRegExp( '#^https?://#i', \PressBooks\Admin\Branding\login_url() );
	}


	/**
	 * @covers \PressBooks\Admin\Branding\login_title
	 */
	public function test_login_title() {

		$title = \PressBooks\Admin\Branding\login_title();

		$this->assertInternalType( 'string', $title );
		$this->assertNotEmpty( $title );
	}

}
