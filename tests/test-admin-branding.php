<?php

class Admin_BrandingTest extends \WP_UnitTestCase {

	public function test_custom_color_scheme() {

		$this->expectOutputRegex( '/<link rel="stylesheet" type="text\/css" href="\S*" media="screen" \/>/' );
		\Pressbooks\Admin\Branding\custom_color_scheme();
	}

	public function test_custom_login_logo() {

		$this->expectOutputRegex( '/<\/style>/' );
		\Pressbooks\Admin\Branding\custom_login_logo();
	}

	public function test_login_url() {

		$this->assertRegExp( '#^https?://#i', \Pressbooks\Admin\Branding\login_url() );
	}

	public function test_login_title() {

		$title = \Pressbooks\Admin\Branding\login_title();

		$this->assertInternalType( 'string', $title );
		$this->assertNotEmpty( $title );
	}

}
