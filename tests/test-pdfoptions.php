<?php

class PDFOptionsTest extends \WP_UnitTestCase {
	use utilsTrait;

	public function set_up() {
		parent::set_up();
		$this->_setPdfOptionsForTesting();
	}

	/**
	 * @group themeoptions
	 */
	public function test_scssOverrides() {
		$this->_book( 'pressbooks-luther' );

		update_option( 'pressbooks_theme_options_global', [
			'chapter_numbers' => 0,
		] );
		$this->_setPdfOptionsForTesting();
		$result = \Pressbooks\Modules\ThemeOptions\PDFOptions::scssOverrides( '' );
		$this->assertStringContainsString( 'div.part-title-wrap > .part-number, div.chapter-title-wrap > .chapter-number, #toc .part a::before, #toc .chapter a::before { display: none !important; }', $result );

	}

	/**
	 * @group themeoptions
	 */
	public function test_replaceRunningContentTags() {
		$result = \Pressbooks\Modules\ThemeOptions\PDFOptions::replaceRunningContentTags( '%section_title%' );
		$this->assertEquals( '"" string(section-title) ""', $result );
		$result = \Pressbooks\Modules\ThemeOptions\PDFOptions::replaceRunningContentTags( 'foo' );
		$this->assertEquals( '"foo"', $result );
	}

	/**
	 * @group themeoptions
	 */
	public function test_replaceRunningContentStrings() {
		$result = \Pressbooks\Modules\ThemeOptions\PDFOptions::replaceRunningContentStrings( 'string(section-title)' );
		$this->assertEquals( '%section_title%', $result );
		$result = \Pressbooks\Modules\ThemeOptions\PDFOptions::replaceRunningContentStrings( 'foo' );
		$this->assertEquals( 'foo', $result );
	}

	/**
	 * @group themeoptions
	 */
	public function test_princeVersionDefaults() {
		$defaults = \Pressbooks\Modules\ThemeOptions\PDFOptions::getDefaults();
		$this->assertEquals( 'prince-16', $defaults['pdf_prince_version'] );
		$this->assertEquals( 'slice', $defaults['pdf_box_decoration_break'] );
	}

	/**
	 * @group themeoptions
	 */
	public function test_scssOverridesPrince15() {
		$this->_book( 'pressbooks-luther' );

		update_option( 'pressbooks_theme_options_pdf', [
			'pdf_page_width' => 10,
			'pdf_page_height' => 10,
			'pdf_crop_marks' => 0,
			'pdf_hyphens' => 1,
			'pdf_toc' => 1,
			'pdf_prince_version' => 'prince-15',
		] );
		update_option( 'pressbooks_theme_options_global', [
			'chapter_numbers' => 1,
		] );

		$result = \Pressbooks\Modules\ThemeOptions\PDFOptions::scssOverrides( '' );
		$this->assertIsString( $result );
	}

	/**
	 * @group themeoptions
	 */
	public function test_predefinedOptionsIncludePrinceVersion() {
		$predefined = \Pressbooks\Modules\ThemeOptions\PDFOptions::getPredefinedOptions();
		$this->assertContains( 'pdf_prince_version', $predefined );
		$this->assertContains( 'pdf_box_decoration_break', $predefined );
	}

	/**
	 * @group themeoptions
	 */
	public function test_upgradePrinceVersion() {
		$this->_book( 'pressbooks-luther' );

		update_option( 'pressbooks_theme_options_pdf', [
			'pdf_page_width' => '5.5in',
			'pdf_page_height' => '8.5in',
		] );

		$options = new \Pressbooks\Modules\ThemeOptions\PDFOptions( [] );
		$options->upgrade( 2 );

		$saved = get_option( 'pressbooks_theme_options_pdf' );
		$this->assertEquals( 'prince-15', $saved['pdf_prince_version'] );
		$this->assertEquals( 'clone', $saved['pdf_box_decoration_break'] );
	}
}
