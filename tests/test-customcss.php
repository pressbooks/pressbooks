<?php

use Pressbooks\CustomCss;

class CustomCssTest extends \WP_UnitTestCase {

	use utilsTrait;

	/**
	 * @var \Pressbooks\CustomCss()
	 */
	protected $cc;

	public function test_getCustomCssFolder() {
		$path = CustomCss::getCustomCssFolder();
		$this->assertStringEndsWith( '/custom-css/', $path );

	}

	public function test_isCustomCss() {
		$this->assertTrue( is_bool( CustomCss::isCustomCss() ) );
	}

	public function test_isRomanized() {
		$this->assertTrue( is_bool( CustomCss::isRomanized() ) );
	}
	
	public function test_getBaseTheme() {

		$input = file_get_contents( WP_CONTENT_DIR . '/themes/pressbooks-book/style.css' );
		$output = CustomCss::getCustomCssFolder() . sanitize_file_name( 'web.css' );

		file_put_contents( $output, $input );

		$web = CustomCss::getBaseTheme( 'web' );

		$this->assertTrue( 'pressbooks-book' == $web );

		$prince = CustomCss::getBaseTheme( 'prince' );

		$this->assertFalse( 'pressbooks-book' == $prince );
	}

}
