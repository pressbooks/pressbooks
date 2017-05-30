<?php

class SassTest extends \WP_UnitTestCase {

	use utilsTrait;

  /**
	 * @var \Pressbooks\Sass()
	 */
	protected $sass;


	/**
	 *
	 */
	public function setUp() {
		parent::setUp();
		$this->sass = new \Pressbooks\Sass();
	}


  /**
   * @covers \Pressbooks\Sass::getStringsToLocalize
   */
  public function test_getStringsToLocalize() {

    $result = $this->sass->getStringsToLocalize();

    $this->assertTrue( is_array( $result ) );

    $this->assertArrayHasKey( 'chapter', $result );

    $this->assertEquals( 'chapter',  strtolower( $result['chapter'] ) );

  }

  /**
   * @covers \Pressbooks\Sass::prependLocalizedVars
   */
  public function test_prependLocalizedVars() {

    $scss = '/* Silence is golden. */';

    $result = $this->sass->prependLocalizedVars( $scss );

    $this->assertContains( $scss, $result );

    $this->assertContains( "\$chapter: 'Chapter';", $result );

  }

	/**
	 * @covers \Pressbooks\Sass::parseVariables
	 */
	public function test_parseVariables() {
		$scss = '$red: #d4002d !default;
		$font-size: 14pt;';

		$vars = $this->sass->parseVariables( $scss );

		$this->assertArrayHasKey( 'red', $vars );
		$this->assertArrayHasKey( 'font-size', $vars );
		$this->assertEquals( $vars['red'], '#d4002d' );
		$this->assertEquals( $vars['font-size'], '14pt' );
	}

	/**
	 * @covers \Pressbooks\Sass::applyOverrides
	 */
	public function test_applyOverrides() {
		$result = $this->sass->applyOverrides( '// SCSS.', '// Override.' );
		$this->assertTrue( strpos( $result, '// SCSS.' ) === 0 );
	}

	/**
	 * @covers \Pressbooks\Sass::updateWebBookStyleSheet
	 */
	public function test_updateWebBookStyleSheet() {

		$this->_book( 'donham' ); // Pick a theme with some built-in $supported_languages

		$this->sass->updateWebBookStyleSheet();

		$file = $this->sass->pathToUserGeneratedCss() . '/style.css';

		$this->assertFileExists( $file );
		$this->assertNotEmpty( file_get_contents( $file ) );
	}


	/**
	 * @covers \Pressbooks\Sass::fixWebFonts
	 */
	public function test_fixWebFonts() {

		$css = '@font-face { font-family: "Bergamot Ornaments"; src: url(themes-book/pressbooks-book/fonts/Bergamot-Ornaments.ttf) format("truetype"); font-weight: normal; font-style: normal; }';
		$css = $this->sass->fixWebFonts( $css );
		$this->assertContains( 'url(' . PB_PLUGIN_URL . 'themes-book/pressbooks-book/fonts/Bergamot-Ornaments.ttf', $css );

		$css = 'url(themes-book/pressbooks-book/fonts/foo.garbage)';
		$css = $this->sass->fixWebFonts( $css );
		$this->assertNotContains( 'url(' . PB_PLUGIN_URL . 'themes-book/pressbooks-book/fonts/foo.garbage', $css );
	}

	/**
	 * @covers \Pressbooks\Sass::maybeUpdateWebBookStyleSheet
	 */
	public function test_maybeUpdateWebBookStyleSheet() {

		$this->_book( 'pressbooks-book' );
		$theme = wp_get_theme();
		$version = $theme->get( 'Version' );
		update_option( 'pressbooks_theme_version', $version - 0.1 );

		$result = $this->sass->maybeUpdateWebBookStylesheet();
		$this->assertTrue( $result );

		$result = $this->sass->maybeUpdateWebBookStylesheet();
		$this->assertEquals( $result, false );
	}
}
