<?php

require_once( PB_PLUGIN_DIR . 'inc/admin/covergenerator/namespace.php' );

class Admin_CoverGeneratorTest extends \WP_UnitTestCase {

	use utilsTrait;

	public function set_up() {
		parent::set_up();
		$this->_setPdfOptionsForTesting();
	}

	/**
	 * @group covergenerator
	 */
	function test_display_generator() {
		ob_start();
		\Pressbooks\Admin\Covergenerator\display_generator();
		$buffer = ob_get_clean();
		$this->assertStringContainsString( '<h1>Cover Generator</h1>', $buffer );
	}

	/**
	 * @group covergenerator
	 */
	function test_generator_css_js() {
		global $wp_scripts, $wp_styles;
		$_REQUEST['page'] = 'pressbooks_cg';
		$hooks_suffix = get_plugin_page_hookname( 'pressbooks_cg', 'pb_export' );
		\Pressbooks\Admin\Covergenerator\generator_css_js( $hooks_suffix );
		$this->assertContains( 'cg/js', $wp_scripts->queue );
		$this->assertContains( 'cg/css', $wp_styles->queue );
	}

	/**
	 * @group covergenerator
	 */
	function test_cg_options_init() {
		global $wp_settings_sections;
		\Pressbooks\Admin\Covergenerator\cg_options_init();
		$this->assertArrayHasKey( 'pressbooks_cg', $wp_settings_sections );
	}

	/**
	 * @group covergenerator
	 */
	function test_pressbooks_cg_text_callback() {
		ob_start();
		\Pressbooks\Admin\Covergenerator\pressbooks_cg_text_callback();
		$buffer = ob_get_clean();
		$this->assertStringContainsString( 'The text below is pulled from', $buffer );
	}

	/**
	 * @group covergenerator
	 */
	function test_pressbooks_cg_title_callback() {
		ob_start();
		\Pressbooks\Admin\Covergenerator\pressbooks_cg_title_callback( null );
		$buffer = ob_get_clean();
		$this->assertStringContainsString( '<textarea id="pb_title"', $buffer );
	}

	/**
	 * @group covergenerator
	 */
	function test_pressbooks_cg_title_spine_callback() {
		ob_start();
		\Pressbooks\Admin\Covergenerator\pressbooks_cg_title_spine_callback( null );
		$buffer = ob_get_clean();
		$this->assertStringContainsString( '<input id="pb_title_spine"', $buffer );
	}

	/**
	 * @group covergenerator
	 */
	function test_pressbooks_cg_subtitle_callback() {
		ob_start();
		\Pressbooks\Admin\Covergenerator\pressbooks_cg_subtitle_callback( null );
		$buffer = ob_get_clean();
		$this->assertStringContainsString( '<textarea id="pb_subtitle"', $buffer );
	}

	/**
	 * @group covergenerator
	 */
	function test_pressbooks_cg_author_callback() {
		ob_start();
		\Pressbooks\Admin\Covergenerator\pressbooks_cg_author_callback( null );
		$buffer = ob_get_clean();
		$this->assertStringContainsString( '<textarea id="pb_author"', $buffer );
	}

	/**
	 * @group covergenerator
	 */
	function test_pressbooks_cg_author_spine_callback() {
		ob_start();
		\Pressbooks\Admin\Covergenerator\pressbooks_cg_author_spine_callback( null );
		$buffer = ob_get_clean();
		$this->assertStringContainsString( '<input id="pb_author_spine"', $buffer );
	}

	/**
	 * @group covergenerator
	 */
	function test_pressbooks_cg_about_callback() {
		ob_start();
		\Pressbooks\Admin\Covergenerator\pressbooks_cg_about_callback( null );
		$buffer = ob_get_clean();
		$this->assertStringContainsString( '<div id="wp-pb_about_unlimited-wrap"', $buffer );
	}

	/**
	 * @group covergenerator
	 */
	function test_pressbooks_cg_isbn_callback() {
		ob_start();
		\Pressbooks\Admin\Covergenerator\pressbooks_cg_isbn_callback( [ 'Description ' ] );
		$buffer = ob_get_clean();
		$this->assertStringContainsString( '<input id="pb_print_isbn"', $buffer );
	}

	/**
	 * @group covergenerator
	 */
	function test_pressbooks_cg_sku_callback() {
		ob_start();
		\Pressbooks\Admin\Covergenerator\pressbooks_cg_sku_callback( [ 'Description ' ] );
		$buffer = ob_get_clean();
		$this->assertStringContainsString( '<input id="pb_print_sku"', $buffer );
	}

	/**
	 * @group covergenerator
	 */
	function test_pressbooks_cg_design_callback() {
		ob_start();
		\Pressbooks\Admin\Covergenerator\pressbooks_cg_design_callback();
		$buffer = ob_get_clean();
		$this->assertStringContainsString( 'You can upload a background image here', $buffer );
	}

	/**
	 * @group covergenerator
	 */
	function test_pressbooks_cg_front_background_image_callback() {
		ob_start();
		\Pressbooks\Admin\Covergenerator\pressbooks_cg_front_background_image_callback( null );
		$buffer = ob_get_clean();
		$this->assertStringContainsString( '<input id="front_background_image"', $buffer );
	}

	/**
	 * @group covergenerator
	 */
	function test_pressbooks_cg_text_transform_callback() {
		ob_start();
		\Pressbooks\Admin\Covergenerator\pressbooks_cg_text_transform_callback( [] );
		$buffer = ob_get_clean();
		$this->assertStringContainsString( '<select name=\'pressbooks_cg_options[text_transform]', $buffer );
	}

	/**
	 * @group covergenerator
	 */
	function test_pressbooks_cg_spine_size_callback() {
		ob_start();
		\Pressbooks\Admin\Covergenerator\pressbooks_cg_spine_size_callback();
		$buffer = ob_get_clean();
		$this->assertStringContainsString( 'We can calculate the spine size based on CreateSpace and Ingram specifications', $buffer );
	}

	/**
	 * @group covergenerator
	 */
	function test_pressbooks_cg_pdf_pagecount_callback() {
		ob_start();
		\Pressbooks\Admin\Covergenerator\pressbooks_cg_pdf_pagecount_callback( null );
		$buffer = ob_get_clean();
		$this->assertStringContainsString( '<input id="pdf_pagecount"', $buffer );
	}

	/**
	 * @group covergenerator
	 */
	function test_pressbooks_cg_ppi_callback() {
		ob_start();
		\Pressbooks\Admin\Covergenerator\pressbooks_cg_ppi_callback( [] );
		$buffer = ob_get_clean();
		$this->assertStringContainsString( '<select name=\'pressbooks_cg_options[ppi]', $buffer );
	}

	/**
	 * @group covergenerator
	 */
	function test_pressbooks_cg_custom_ppi_callback() {
		ob_start();
		\Pressbooks\Admin\Covergenerator\pressbooks_cg_custom_ppi_callback( null );
		$buffer = ob_get_clean();
		$this->assertStringContainsString( '<input id="custom_ppi"', $buffer );
	}

	/**
	 * @group covergenerator
	 */
	function test_pressbooks_cg_colors_callback() {
		ob_start();
		\Pressbooks\Admin\Covergenerator\pressbooks_cg_colors_callback();
		$buffer = ob_get_clean();
		$this->assertStringContainsString( 'Choose text color and background colors below', $buffer );
	}

	/**
	 * @group covergenerator
	 */
	function test_pressbooks_cg_color_callback() {
		ob_start();
		\Pressbooks\Admin\Covergenerator\pressbooks_cg_color_callback( [ 'id' ] );
		$buffer = ob_get_clean();
		$this->assertStringContainsString( '<input class="colorpicker"', $buffer );
	}

	/**
	 * @group covergenerator
	 */
	function test_pressbooks_cg_options_sanitize() {
		$input = \Pressbooks\Admin\Covergenerator\pressbooks_cg_options_sanitize( [] );
		$this->assertArrayHasKey( 'pb_title', $input );
	}
}
