<?php

class Admin_BrandingTest extends \WP_UnitTestCase {
	/**
	 * @group branding
	 */
	public function test_custom_color_scheme() {
		update_option( 'pb_network_color_primary', '#663399' );
		$this->expectOutputRegex( '/<style type="text\/css">/' );
		\Pressbooks\Admin\Branding\custom_color_scheme();
	}

	/**
	 * @group branding
	 */
	public function test_custom_login_logo() {

		$this->expectOutputRegex( '/<\/style>/' );
		\Pressbooks\Admin\Branding\custom_login_logo();
	}

	/**
	 * @group branding
	 */
	public function test_login_url() {

		$this->assertMatchesRegularExpression( '#^https?://#i', \Pressbooks\Admin\Branding\login_url() );
	}

	/**
	 * @group branding
	 */
	public function test_login_title() {

		$title = \Pressbooks\Admin\Branding\login_title();

		$this->assertIsString( $title );
		$this->assertNotEmpty( $title );
	}

	/**
	 * @group branding
	 */
	function test_admin_title() {

		$result = \Pressbooks\Admin\Branding\admin_title( 'Hello WordPress!' );
		$this->assertEquals( $result, 'Hello Pressbooks!' );

		$result = \Pressbooks\Admin\Branding\admin_title( 'Hello World!' );
		$this->assertEquals( $result, 'Hello World!' );
	}

	/**
	 * @group branding
	 */
	function test_get_customizer_colors() {
		update_option( 'pb_network_color_primary', '#663399' );
		$result = \Pressbooks\Admin\Branding\get_customizer_colors();
		$this->assertEquals( $result, '<style type="text/css">:root{--primary:#663399;}</style>' );
	}

	/**
	 * @group branding
	 */
	public function test_favicon() {
		ob_start();
		\Pressbooks\Admin\Branding\favicon();
		$buffer = ob_get_clean();
		$this->assertStringContainsString( '<link rel="shortcut icon"', $buffer );
	}
}
