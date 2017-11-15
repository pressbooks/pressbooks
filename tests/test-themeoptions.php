<?php

class ThemeOptionsTest extends \WP_UnitTestCase {

	/**
	 * @var \Pressbooks\Modules\ThemeOptions\ThemeOptions
	 */
	protected $themeOptions;

	public function setUp() {
		parent::setUp();
		$this->themeOptions = new \Pressbooks\Modules\ThemeOptions\ThemeOptions();
	}

	public function test_getTabs() {
		$val = $this->themeOptions->getTabs();
		$this->assertTrue( is_array( $val ) );
		$this->assertArrayHasKey( 'web', $val );
	}

	public function test_loadTabs() {
		$option = 'pressbooks_theme_options_ebook_version';

		$version = get_option( $option, 'notset' );
		$this->assertEquals( 'notset', $version );

		$this->themeOptions->loadTabs();

		$version = get_option( $option, 0 );
		$this->assertTrue( is_numeric( $version ) );
		$this->assertTrue( $version > 0 );
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