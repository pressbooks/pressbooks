<?php

class ThemeOptionsTest extends \WP_UnitTestCase {

    use utilsTrait;

	/**
	 * @var \Pressbooks\Modules\ThemeOptions\Admin
	 */
	protected $themeOptions;

	public function setUp() {
		parent::setUp();
		$this->themeOptions = new \Pressbooks\Modules\ThemeOptions\Admin();
	}

	public function test_getTabs() {
		$val = $this->themeOptions->getTabs();
		$this->assertTrue( is_array( $val ) );
		$this->assertArrayHasKey( 'web', $val );
	}

	public function test_loadTabs() {
		global $wp_registered_settings;
		$this->_book(); // We need Book Info now :(

		$this->themeOptions->loadTabs();
		$this->assertArrayHasKey( 'pressbooks_theme_options_ebook', $wp_registered_settings );
	}

	public function test_setPermissions() {
		$val = $this->themeOptions->setPermissions( '' );
		$this->assertEquals( 'edit_others_posts', $val );
	}

	public function test_render() {
		ob_start();
		$this->themeOptions->render();
		$output = ob_get_clean();
		$this->assertContains( 'PDF Options</a>', $output );
	}

	public function test_afterSwitchTheme() {
		$option = 'pressbooks_theme_options_pdf';
		$val = get_option( $option, 'notset' );
		$this->assertEquals( 'notset', $val );

		update_option( $option, [ 'pdf_body_font_size' => 9999 ] );
		$this->themeOptions->afterSwitchTheme();

		$val = get_option( $option, 0 );
		$this->assertTrue( is_array( $val ) );
		$this->assertArrayHasKey( 'pdf_body_font_size', $val );
		$this->assertNotEquals( 9999, $val['pdf_body_font_size'] );
	}
}
