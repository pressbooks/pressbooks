<?php

use Pressbooks\Container;

class StylesTest extends \WP_UnitTestCase {

	use utilsTrait;

	/**
	 * @var \Pressbooks\Styles
	 * @group styles
	 */
	protected $cs;

	/**
	 * @group styles
	 */
	public function set_up() {
		parent::set_up();
		$this->cs = Container::get( 'Styles' );
	}

	/**
	 * @group styles
	 */
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

	/**
	 * @group styles
	 */
	public function test_get_all_custom_posts() {
		$this->cs->registerPosts();
		$this->cs->initPosts();
		$all_styles_posts = \Pressbooks\Styles::getAllPostContent();
		$this->assertNotEmpty( $all_styles_posts );
		$this->assertArrayHasKey( 'epub', $all_styles_posts );
		$this->assertArrayHasKey( 'web', $all_styles_posts );
		$this->assertArrayHasKey( 'prince', $all_styles_posts );
	}

	/**
	 * @group styles
	 */
	public function test_get_all_custom_style_saved() {
		$this->cs->registerPosts();
		$this->cs->initPosts();
		$custom_styles = ".custom-class { margin: auto; }";
		foreach ( [ 'web', 'epub', 'prince' ] as $slug ) {
			$post = $this->cs->getPost( $slug );
			$post_params = [
				'ID' => $post->ID,
				'post_content' => $custom_styles,
			];
			wp_update_post( $post_params, true );
			$all_styles_posts = \Pressbooks\Styles::getAllPostContent();
			$this->assertArrayHasKey( $slug, $all_styles_posts );
			$this->assertStringContainsString( $custom_styles, $all_styles_posts[ $slug ] );
		}

	}

	/**
	 * @group styles
	 */
	public function test_basepath() {
		$v1 = wp_get_theme( 'pressbooks-book' );
		$this->assertNotEmpty( $this->cs->getDir( $v1, true ) );
		$this->assertNotEmpty( $this->cs->getDir( $v1, false ) );

		$this->assertNotEmpty( $this->cs->getDir( null, true ) );
		$this->assertNotEmpty( $this->cs->getDir( null, false ) );
	}

	/**
	 * @group styles
	 */
	public function test_pathToScss() {
		// V1
		$v1 = wp_get_theme( 'pressbooks-luther' );
		$this->assertStringContainsString( 'style.scss', $this->cs->getPathToWebScss( $v1 ) );
		$this->assertStringContainsString( '/export/', $this->cs->getPathToEpubScss( $v1 ) );
		$this->assertStringContainsString( '/export/', $this->cs->getPathToPrinceScss( $v1 ) );
		// V2
		$v2 = wp_get_theme( 'pressbooks-book' );
		$this->assertStringContainsString( '/assets/styles/', $this->cs->getPathToWebScss( $v2 ) );
		$this->assertStringContainsString( '/assets/styles/', $this->cs->getPathToEpubScss( $v2 ) );
		$this->assertStringContainsString( '/assets/styles/', $this->cs->getPathToPrinceScss( $v2 ) );
	}

	/**
	 * @group styles
	 */
	public function test_isCurrentThemeCompatible() {
		// V1
		$v1 = wp_get_theme( 'pressbooks-luther' );
		$this->assertTrue( $this->cs->isCurrentThemeCompatible( 1, $v1 ) );
		$this->assertFalse( $this->cs->isCurrentThemeCompatible( 2, $v1 ) );
		$this->assertFalse( $this->cs->isCurrentThemeCompatible( 999, $v1 ) );
		// V2
		$v2 = wp_get_theme( 'pressbooks-book' );
		$this->assertFalse( $this->cs->isCurrentThemeCompatible( 1, $v2 ) );
		$this->assertTrue( $this->cs->isCurrentThemeCompatible( 2, $v2 ) );
		$this->assertFalse( $this->cs->isCurrentThemeCompatible( 999, $v2 ) );
	}

	/**
	 * @group styles
	 */
	public function test_getBuckramVersion() {
		$this->assertGreaterThanOrEqual( 0, version_compare( $this->cs->getBuckramVersion(), '0.2.0' ) );
	}

	/**
	 * @group styles
	 */
	public function test_hasBuckram() {
		$this->_book( 'pressbooks-luther' );
		$this->assertFalse( $this->cs->hasBuckram() );
		$this->_book( 'pressbooks-book' );
		$this->assertTrue( $this->cs->hasBuckram() );
		$this->assertTrue( $this->cs->hasBuckram( '0.2.0' ) );
		$this->assertFalse( $this->cs->hasBuckram( 42 ) );
	}

	/**
	 * @group styles
	 */
	public function test_applyOverrides() {
		// V1
		$this->_book( 'pressbooks-luther' );
		$result = $this->cs->applyOverrides( '// SCSS.', '// Override.' );
		$this->assertTrue( str_starts_with($result, '// SCSS.') );
		$result = $this->cs->applyOverrides( '// SCSS.', [ '// Override 1.', '// Override 2.' ] );
		$this->assertTrue( str_starts_with($result, '// SCSS.') );
		$this->assertStringContainsString( '// Override 2.', $result );
		// V2
		switch_theme( 'pressbooks-book' );
		$result = $this->cs->applyOverrides( '// SCSS.', '// Override.' );
		$this->assertTrue( str_starts_with($result, '// Override.') );
		$result = $this->cs->applyOverrides( '// SCSS.', [ '// Override 1.', '// Override 2.' ] );
		$this->assertTrue( str_starts_with($result, '// Override 1.') );
		$this->assertStringContainsString( '// SCSS.', $result );
	}

	/**
	 * @group styles
	 */
	public function test_customize() {
		// V1
		$this->_book( 'pressbooks-luther' );
		$this->assertStringContainsString( 'font-size:', $this->cs->customizeWeb() );
		$this->assertStringContainsString( 'font-size:', $this->cs->customizeEpub() );
		$this->assertStringContainsString( 'font-size:', $this->cs->customizePrince() );
		// V2
		switch_theme( 'pressbooks-book' );
		$this->assertStringContainsString( 'font-size:', $this->cs->customizeWeb() );
		$this->assertStringContainsString( 'font-size:', $this->cs->customizeEpub() );
		$this->assertStringContainsString( 'font-size:', $this->cs->customizePrince() );
	}

	/**
	 * @group styles
	 */
	public function test_updateWebBookStyleSheet() {

		$this->_book( 'pressbooks-clarke' ); // Pick a theme with some built-in $supported_languages

		$this->cs->updateWebBookStyleSheet();

		$file = $this->cs->getSass()->pathToUserGeneratedCss() . '/style.css';

		$this->assertFileExists( $file );
		$this->assertNotEmpty( file_get_contents( $file ) );
	}

	/**
	 * @group styles
	 */
	public function test_maybeUpdateStyleSheets() {

		$this->_book( 'pressbooks-book' );

		update_option( 'pressbooks_theme_version', 2.0 );
		$result = $this->cs->maybeUpdateStylesheets();
		$this->assertTrue( $result );

		update_option( 'pressbooks_buckram_version', 1.0 );
		$result = $this->cs->maybeUpdateStylesheets();
		$this->assertTrue( $result );

		$result = $this->cs->maybeUpdateStylesheets();
		$this->assertEquals( $result, false );
	}

	public function test_isShapeShifterCompatible() {
		$this->assertIsBool($this->cs->isShapeShifterCompatible());

		add_filter( 'pb_is_shape_shifter_compatible', '__return_true' );
		$this->assertTrue( $this->cs->isShapeShifterCompatible() );
		remove_filter( 'pb_is_shape_shifter_compatible', '__return_true' );


		add_filter( 'pb_is_shape_shifter_compatible', '__return_false' );
		$this->assertFalse( $this->cs->isShapeShifterCompatible() );
		remove_filter( 'pb_is_shape_shifter_compatible', '__return_false' );
	}

	public function test_getShapeShifterFonts() {
		$theme_default = __( 'Theme default', 'pressbooks' );
		$serif_key = __( 'Serif', 'pressbooks' );
		$sans_serif_key = __( 'Sans serif', 'pressbooks' );

		$fonts = $this->cs->getShapeShifterFonts();
		$this->assertTrue( $fonts[''] === $theme_default );
		$this->assertTrue( is_array( $fonts[ $serif_key ] ) );
		$this->assertTrue( is_array( $fonts[ $sans_serif_key ] ) );
	}

	public function test_isShaperShifterFontSerif() {
		$this->assertTrue( $this->cs->isShaperShifterFontSerif( 'Crimson Text' ) );
		$this->assertTrue( $this->cs->isShaperShifterFontSerif( 'crimson text' ) );
		$this->assertFalse( $this->cs->isShaperShifterFontSerif( 'Libre Franklin' ) );
	}

	/**
	 * @group styles
	 */
	public function test_editor() {
		$this->_book();

		$dropdown = $this->cs->renderDropdownForSlugs( 'web' );
		$this->assertStringContainsString( '</select>', $dropdown );
		$revisions = $this->cs->renderRevisionsTable( 'web', $this->cs->getPost( 'web' )->ID );
		$this->assertStringContainsString( '</table>', $revisions );

		ob_start();
		$this->cs->editor();
		$output = ob_get_clean();
		$this->assertStringContainsString( '<h1>Custom Styles</h1>', $output );
	}
}
