<?php

class GlobaltypographyTest extends \WP_UnitTestCase {

	use utilsTrait;

	/**
	 * @var \PressBooks\GlobalTypography()
	 */
	protected $gt;


	/**
	 *
	 */
	public function setUp() {
		parent::setUp();
		$this->gt = new \PressBooks\GlobalTypography();
	}


	/**
	 * @covers \PressBooks\GlobalTypography::getSupportedLanguages
	 */
	public function test_getSupportedLanguages() {

		$result = $this->gt->getSupportedLanguages();

		$this->assertTrue( is_array( $result ) );

		// Test that we have (at the very least) Greek an Hebrew
		$this->assertArrayHasKey( 'grc', $result );
		$this->assertArrayHasKey( 'he', $result );
	}


	/**
	 * @covers \PressBooks\GlobalTypography::getThemeFontStacks
	 *
	 * @covers \PressBooks\GlobalTypography::updateGlobalTypographyMixin
	 * @covers \PressBooks\GlobalTypography::_sassify
	 * @covers \PressBooks\GlobalTypography::_getBookLanguage
	 */
	public function test_getThemeFontStacks() {

		$this->_book( 'donham' ); // Pick a theme with some built-in $supported_languages

		$this->gt->updateGlobalTypographyMixin();
		$this->assertNotEmpty( $this->gt->getThemeFontStacks( 'epub' ) );

		$this->assertEmpty( $this->gt->getThemeFontStacks( 'garbage' ) );
	}


	/**
	 * @covers \PressBooks\GlobalTypography::getThemeSupportedLanguages
	 */
	public function test_getThemeSupportedLanguages() {

		$this->_book( 'donham' ); // Pick a theme with some built-in $supported_languages

		$supported_languages = $this->gt->getThemeSupportedLanguages();
		$this->assertTrue( is_array( $supported_languages ) );
	}


	/**
	 * @covers \PressBooks\GlobalTypography::updateWebBookStyleSheet
	 */
	public function test_updateWebBookStyleSheet() {

		$this->_book( 'donham' ); // Pick a theme with some built-in $supported_languages

		$this->gt->updateWebBookStyleSheet();

		$file = \Pressbooks\Container::get( 'Sass' )->pathToUserGeneratedCss() . '/style.css';

		$this->assertFileExists( $file );
		$this->assertNotEmpty( file_get_contents( $file ) );
	}
	

	/**
	 * @covers \PressBooks\GlobalTypography::fixWebFonts
	 */
	public function test_fixWebFonts() {

		$css = '@font-face { font-family: "Bergamot Ornaments"; src: url(themes-book/pressbooks-book/fonts/Bergamot-Ornaments.ttf) format("truetype"); font-weight: normal; font-style: normal; }';
		$css = $this->gt->fixWebFonts( $css );
		$this->assertContains( 'url(' . PB_PLUGIN_URL . 'themes-book/pressbooks-book/fonts/Bergamot-Ornaments.ttf', $css );

		$css = 'url(themes-book/pressbooks-book/fonts/foo.garbage)';
		$css = $this->gt->fixWebFonts( $css );
		$this->assertNotContains( 'url(' . PB_PLUGIN_URL . 'themes-book/pressbooks-book/fonts/foo.garbage', $css );
	}

}
