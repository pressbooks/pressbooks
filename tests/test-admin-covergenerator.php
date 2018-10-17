<?php

require_once( PB_PLUGIN_DIR . 'inc/admin/covergenerator/namespace.php' );

class Admin_CoverGeneratorTest extends \WP_UnitTestCase {

	use utilsTrait;

	function test_display_generator() {
		ob_start();
		\Pressbooks\Admin\Covergenerator\display_generator();
		$buffer = ob_get_clean();
		$this->assertContains( '<h1>Cover Generator</h1>', $buffer );
	}

	function test_generator_css_js() {
		global $wp_scripts, $wp_styles;
		$_REQUEST['page'] = 'pressbooks_cg';
		\Pressbooks\Admin\Covergenerator\generator_css_js();
		$this->assertContains( 'cg/js', $wp_scripts->queue );
		$this->assertContains( 'cg/css', $wp_styles->queue );
	}

	function test_cg_options_init() {
		global $wp_settings_sections;
		\Pressbooks\Admin\Covergenerator\cg_options_init();
		$this->assertArrayHasKey( 'pressbooks_cg', $wp_settings_sections );
	}

	function test_pressbooks_cg_text_callback() {
		ob_start();
		\Pressbooks\Admin\Covergenerator\pressbooks_cg_text_callback();
		$buffer = ob_get_clean();
		$this->assertContains( 'The text below is pulled from', $buffer );
	}

	function test_pressbooks_cg_title_callback() {
		ob_start();
		\Pressbooks\Admin\Covergenerator\pressbooks_cg_title_callback( null );
		$buffer = ob_get_clean();
		$this->assertContains( '<textarea id="pb_title"', $buffer );
	}

	function test_pressbooks_cg_title_spine_callback() {
		ob_start();
		\Pressbooks\Admin\Covergenerator\pressbooks_cg_title_spine_callback( null );
		$buffer = ob_get_clean();
		$this->assertContains( '<input id="pb_title_spine"', $buffer );
	}

	function test_pressbooks_cg_subtitle_callback() {
		ob_start();
		\Pressbooks\Admin\Covergenerator\pressbooks_cg_subtitle_callback( null );
		$buffer = ob_get_clean();
		$this->assertContains( '<textarea id="pb_subtitle"', $buffer );
	}

	function test_pressbooks_cg_author_callback() {
		ob_start();
		\Pressbooks\Admin\Covergenerator\pressbooks_cg_author_callback( null );
		$buffer = ob_get_clean();
		$this->assertContains( '<textarea id="pb_author"', $buffer );
	}

	function test_pressbooks_cg_author_spine_callback() {
		ob_start();
		\Pressbooks\Admin\Covergenerator\pressbooks_cg_author_spine_callback( null );
		$buffer = ob_get_clean();
		$this->assertContains( '<input id="pb_author_spine"', $buffer );
	}

	function test_pressbooks_cg_about_callback() {
		ob_start();
		\Pressbooks\Admin\Covergenerator\pressbooks_cg_about_callback( null );
		$buffer = ob_get_clean();
		$this->assertContains( '<div id="wp-pb_about_unlimited-wrap"', $buffer );
	}

	function test_pressbooks_cg_isbn_callback() {
		ob_start();
		\Pressbooks\Admin\Covergenerator\pressbooks_cg_isbn_callback( [ 'Description ' ] );
		$buffer = ob_get_clean();
		$this->assertContains( '<input id="pb_print_isbn"', $buffer );
	}

	function test_pressbooks_cg_sku_callback() {
		ob_start();
		\Pressbooks\Admin\Covergenerator\pressbooks_cg_sku_callback( [ 'Description ' ] );
		$buffer = ob_get_clean();
		$this->assertContains( '<input id="pb_print_sku"', $buffer );
	}

	function test_pressbooks_cg_design_callback() {
		ob_start();
		\Pressbooks\Admin\Covergenerator\pressbooks_cg_design_callback();
		$buffer = ob_get_clean();
		$this->assertContains( 'You can upload a background image here', $buffer );
	}

	function test_pressbooks_cg_front_background_image_callback() {
		ob_start();
		\Pressbooks\Admin\Covergenerator\pressbooks_cg_front_background_image_callback( null );
		$buffer = ob_get_clean();
		$this->assertContains( '<input id="front_background_image"', $buffer );
	}

	function test_pressbooks_cg_text_transform_callback() {
		ob_start();
		\Pressbooks\Admin\Covergenerator\pressbooks_cg_text_transform_callback( [] );
		$buffer = ob_get_clean();
		$this->assertContains( '<select name=\'pressbooks_cg_options[text_transform]', $buffer );
	}

	function test_pressbooks_cg_spine_size_callback() {
		ob_start();
		\Pressbooks\Admin\Covergenerator\pressbooks_cg_spine_size_callback();
		$buffer = ob_get_clean();
		$this->assertContains( 'We can calculate the spine size based on CreateSpace and Ingram specifications', $buffer );
	}

	function test_pressbooks_cg_pdf_pagecount_callback() {
		ob_start();
		\Pressbooks\Admin\Covergenerator\pressbooks_cg_pdf_pagecount_callback( null );
		$buffer = ob_get_clean();
		$this->assertContains( '<input id="pdf_pagecount"', $buffer );
	}

	function test_pressbooks_cg_ppi_callback() {
		ob_start();
		\Pressbooks\Admin\Covergenerator\pressbooks_cg_ppi_callback( [] );
		$buffer = ob_get_clean();
		$this->assertContains( '<select name=\'pressbooks_cg_options[ppi]', $buffer );
	}

	function test_pressbooks_cg_custom_ppi_callback() {
		ob_start();
		\Pressbooks\Admin\Covergenerator\pressbooks_cg_custom_ppi_callback( null );
		$buffer = ob_get_clean();
		$this->assertContains( '<input id="custom_ppi"', $buffer );
	}

	function test_pressbooks_cg_colors_callback() {
		ob_start();
		\Pressbooks\Admin\Covergenerator\pressbooks_cg_colors_callback();
		$buffer = ob_get_clean();
		$this->assertContains( 'Choose text color and background colors below', $buffer );
	}

	function test_pressbooks_cg_color_callback() {
		ob_start();
		\Pressbooks\Admin\Covergenerator\pressbooks_cg_color_callback( [ 'id' ] );
		$buffer = ob_get_clean();
		$this->assertContains( '<input class="colorpicker"', $buffer );
	}

	function test_pressbooks_cg_options_sanitize() {
		$input = \Pressbooks\Admin\Covergenerator\pressbooks_cg_options_sanitize( [] );
		$this->assertArrayHasKey( 'pb_title', $input );
	}
}
