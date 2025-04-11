<?php

use Pressbooks\CustomCss;

class CustomCssTest extends \WP_UnitTestCase {
	/**
	 * @group customcss
	 */
	public function test_getCustomCssFolder() {
		$this->assertStringEndsWith( '/custom-css/', CustomCss::getCustomCssFolder() );
	}

	/**
	 * @group customcss
	 */
	public function test_isCustomCss() {
		$this->assertTrue( is_bool( CustomCss::isCustomCss() ) );
	}

	/**
	 * @group customcss
	 */
	public function test_isRomanized() {
		$this->assertTrue( is_bool( CustomCss::isRomanized() ) );
	}

	/**
	 * @group customcss
	 */
	public function test_getBaseTheme() {
		$input = file_get_contents( WP_CONTENT_DIR . '/themes/pressbooks-book/style.css' );
		$output = CustomCss::getCustomCssFolder() . sanitize_file_name( 'web.css' );

		file_put_contents( $output, $input );

		$web = CustomCss::getBaseTheme( 'web' );

		$this->assertEquals( 'pressbooks-book', $web );

		$prince = CustomCss::getBaseTheme( 'prince' );

		$this->assertNotEquals( 'pressbooks-book', $prince );
	}
}
