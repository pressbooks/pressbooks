<?php

use function Pressbooks\Admin\Branding\admin_title;
use function Pressbooks\Admin\Branding\custom_color_scheme;
use function Pressbooks\Admin\Branding\custom_login_logo;
use function Pressbooks\Admin\Branding\favicon;
use function Pressbooks\Admin\Branding\get_customizer_colors;
use function Pressbooks\Admin\Branding\login_title;
use function Pressbooks\Admin\Branding\login_url;

class Admin_BrandingTest extends \WP_UnitTestCase {
	/**
	 * @group branding
	 */
	public function test_custom_color_scheme() {
		update_option( 'pb_network_color_primary', '#663399' );
		$this->expectOutputRegex( '/<style type="text\/css">/' );
		custom_color_scheme();
	}

	/**
	 * @group branding
	 */
	public function test_custom_login_logo() {
		$this->expectOutputRegex( '/<\/style>/' );
		custom_login_logo();
	}

	/**
	 * @group branding
	 */
	public function test_login_url() {
		$this->assertMatchesRegularExpression( '#^https?://#i', login_url() );
	}

	/**
	 * @group branding
	 */
	public function test_login_title() {
		$title = login_title();

		$this->assertIsString( $title );
		$this->assertNotEmpty( $title );
	}

	/**
	 * @group branding
	 */
	function test_admin_title() {
		$result = admin_title( 'Hello WordPress!' );
		$this->assertEquals( $result, 'Hello Pressbooks!' );

		$result = admin_title( 'Hello World!' );
		$this->assertEquals( $result, 'Hello World!' );
	}

	/**
	 * @group branding
	 */
	function test_get_customizer_colors() {
		update_option( 'pb_network_color_primary', '#663399' );
		$result = get_customizer_colors();
		$this->assertEquals( $result, '<style>:root{--header-links:#663399;--primary:#663399;}</style>' );
	}

	/**
	 * @group branding
	 */
	public function test_favicon() {
		ob_start();
		favicon();
		$buffer = ob_get_clean();
		$this->assertStringContainsString( '<link rel="shortcut icon"', $buffer );
	}
}
