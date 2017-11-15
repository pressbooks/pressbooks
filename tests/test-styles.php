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

	public function test_custom_posts() {
		global $wp_post_types;
		$this->cs->registerPosts();
		$this->assertArrayHasKey( 'custom-style', $wp_post_types );

		$this->cs->initPosts();
		$this->assertNotEmpty( $this->cs->getWebPost() );
		$this->assertNotEmpty( $this->cs->getEpubPost() );
		$this->assertNotEmpty( $this->cs->getPrincePost() );
		$this->assertFalse( $this->cs->getPost( 'garbage' ) );
	}

	public function test_basepath() {
		$v1 = wp_get_theme( 'pressbooks-book' );
		$this->assertNotEmpty( $this->cs->getDir( $v1, true ) );
		$this->assertNotEmpty( $this->cs->getDir( $v1, false ) );

		$this->assertNotEmpty( $this->cs->getDir( null, true ) );
		$this->assertNotEmpty( $this->cs->getDir( null, false ) );
	}

	public function test_pathToScss() {
		// V1
		$v1 = wp_get_theme( 'pressbooks-book' );
		$this->assertContains( 'style.scss', $this->cs->getPathToWebScss( $v1 ) );
		$this->assertContains( '/export/', $this->cs->getPathToEpubScss( $v1 ) );
		$this->assertContains( '/export/', $this->cs->getPathToPrinceScss( $v1 ) );
		// V2
		$v2 = wp_get_theme( 'pressbooks-clarke' );
		$this->assertContains( '/assets/styles/', $this->cs->getPathToWebScss( $v2 ) );
		$this->assertContains( '/assets/styles/', $this->cs->getPathToEpubScss( $v2 ) );
		$this->assertContains( '/assets/styles/', $this->cs->getPathToPrinceScss( $v2 ) );
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

	public function test_customize() {
		// V1
		$this->_book();
		$this->assertContains( 'font-size:', $this->cs->customizeWeb() );
		$this->assertContains( 'font-size:', $this->cs->customizeEpub() );
		$this->assertContains( 'font-size:', $this->cs->customizePrince() );
		// V2
		switch_theme( 'pressbooks-clarke' );
		$this->assertContains( 'font-size:', $this->cs->customizeWeb() );
		$this->assertContains( 'font-size:', $this->cs->customizeEpub() );
		$this->assertContains( 'font-size:', $this->cs->customizePrince() );
	}


	public function test_updateWebBookStyleSheet() {

		$this->_book( 'pressbooks-clarke' ); // Pick a theme with some built-in $supported_languages

		$this->cs->updateWebBookStyleSheet();

		$file = $this->cs->getSass()->pathToUserGeneratedCss() . '/style.css';

		$this->assertFileExists( $file );
		$this->assertNotEmpty( file_get_contents( $file ) );
	}

	public function test_maybeUpdateStyleSheets() {

		$this->_book( 'pressbooks-book' );
		$theme = wp_get_theme();
		$version = $theme->get( 'Version' );
		update_option( 'pb_theme_version', floatval( $version ) - 0.1 );

		$result = $this->cs->maybeUpdateStylesheets();
		$this->assertTrue( $result );

		$result = $this->cs->maybeUpdateStylesheets();
		$this->assertEquals( $result, false );
	}

	public function test_editor() {
		$this->_book();

		$dropdown = $this->cs->renderDropdownForSlugs( 'web' );
		$this->assertContains( '</select>', $dropdown );
		$revisions = $this->cs->renderRevisionsTable( 'web', $this->cs->getPost( 'web' )->ID );
		$this->assertContains( '</table>', $revisions );

		ob_start();
		$this->cs->editor();
		$output = ob_get_clean();
		$this->assertContains( '<h1>Custom Styles</h1>', $output );
	}





}
