<?php

use Pressbooks\Container;

class StylesTest extends \WP_UnitTestCase {

	use utilsTrait;

	/**
	 * @var \Pressbooks\Styles
	 */
	protected $cs;

	/**
	 *
	 */
	public function setUp() {
		parent::setUp();
		$this->cs = Container::get( 'Styles' );
	}


	public function test_basepath() {
		$v1 = wp_get_theme( 'pressbooks-book' );
		$this->assertNotEmpty( $this->cs->basepath( $v1, true ) );
		$this->assertNotEmpty( $this->cs->basepath( $v1, false ) );
	}

	public function test_pathToScss() {
		// V1
		$v1 = wp_get_theme( 'pressbooks-book' );
		$this->assertContains( 'style.scss', $this->cs->pathToWebScss( $v1 ) );
		$this->assertContains( '/export/', $this->cs->pathToEpubScss( $v1 ) );
		$this->assertContains( '/export/', $this->cs->pathToPrinceScss( $v1 ) );
		// V2
		$v2 = wp_get_theme( 'pressbooks-clarke' );
		$this->assertContains( '/assets/styles/', $this->cs->pathToWebScss( $v2 ) );
		$this->assertContains( '/assets/styles/', $this->cs->pathToEpubScss( $v2 ) );
		$this->assertContains( '/assets/styles/', $this->cs->pathToPrinceScss( $v2 ) );
	}

	public function test_isCurrentThemeCompatible() {
		// V1
		$v1 = wp_get_theme( 'pressbooks-book' );
		$this->assertTrue( $this->cs->isCurrentThemeCompatible( 1, $v1 ) );
		$this->assertFalse( $this->cs->isCurrentThemeCompatible( 2, $v1 ) );
		$this->assertFalse( $this->cs->isCurrentThemeCompatible( 999, $v1 ) );
		// V2
		$v2 = wp_get_theme( 'pressbooks-clarke' );
		$this->assertFalse( $this->cs->isCurrentThemeCompatible( 1, $v2 ) );
		$this->assertTrue( $this->cs->isCurrentThemeCompatible( 2, $v2 ) );
		$this->assertFalse( $this->cs->isCurrentThemeCompatible( 999, $v2 ) );
	}

	public function test_applyOverrides() {
		// V1
		$this->_book();
		$result = $this->cs->applyOverrides( '// SCSS.', '// Override.' );
		$this->assertTrue( strpos( $result, '// SCSS.' ) === 0 );
		$result = $this->cs->applyOverrides( '// SCSS.', [ '// Override 1.', '// Override 2.' ] );
		$this->assertTrue( strpos( $result, '// SCSS.' ) === 0 );
		$this->assertContains( '// Override 2.', $result );
		// V2
		switch_theme( 'pressbooks-clarke' );
		$result = $this->cs->applyOverrides( '// SCSS.', '// Override.' );
		$this->assertTrue( strpos( $result, '// Override.' ) === 0 );
		$result = $this->cs->applyOverrides( '// SCSS.', [ '// Override 1.', '// Override 2.' ] );
		$this->assertTrue( strpos( $result, '// Override 1.' ) === 0 );
		$this->assertContains( '// SCSS.', $result );
	}

	public function test_updateWebBookStyleSheet() {

		$this->_book( 'pressbooks-clarke' ); // Pick a theme with some built-in $supported_languages

		$this->cs->updateWebBookStyleSheet();

		$file = $this->cs->getSass()->pathToUserGeneratedCss() . '/style.css';

		$this->assertFileExists( $file );
		$this->assertNotEmpty( file_get_contents( $file ) );
	}

	public function test_maybeUpdateWebBookStyleSheet() {

		$this->_book( 'pressbooks-book' );
		$theme = wp_get_theme();
		$version = $theme->get( 'Version' );
		update_option( 'pressbooks_theme_version', floatval( $version ) - 0.1 );

		$result = $this->cs->maybeUpdateWebBookStylesheet();
		$this->assertTrue( $result );

		$result = $this->cs->maybeUpdateWebBookStylesheet();
		$this->assertEquals( $result, false );
	}

}



