<?php

use \Pressbooks\GlobalTypography;
use \Pressbooks\Container;

class GlobalTypographyTest extends \WP_UnitTestCase {

	use utilsTrait;

	/**
	 * @var \Pressbooks\GlobalTypography()
	 */
	protected $gt;


	/**
	 *
	 */
	public function setUp() {
		parent::setUp();
		$this->gt = new GlobalTypography( Container::get( 'Sass' ) );
	}

	public function test_getSupportedLanguages() {

		$result = $this->gt->getSupportedLanguages();

		$this->assertTrue( is_array( $result ) );

		// Test that we have (at the very least) Greek an Hebrew
		$this->assertArrayHasKey( 'grc', $result );
		$this->assertArrayHasKey( 'he', $result );
	}

	public function test_getRequiredLanguages() {

		$result = $this->gt->_getRequiredLanguages();

		$this->assertTrue( is_array( $result ) );
	}

	public function test_getThemeFontStacks() {

		$this->_book( 'pressbooks-clarke' ); // Pick a theme with some built-in $supported_languages

		$this->gt->updateGlobalTypographyMixin();
		$this->assertNotEmpty( $this->gt->getThemeFontStacks( 'epub' ) );

		$this->assertEmpty( $this->gt->getThemeFontStacks( 'garbage' ) );
	}


	public function test_getThemeSupportedLanguages() {

		$this->_book();

		add_theme_support( 'pressbooks_global_typography', 'grc', 'he' );

		$supported_languages = $this->gt->getThemeSupportedLanguages();

		$this->assertContains( 'grc', $supported_languages );
	}


	public function test_getFonts() {
		$result = $this->gt->getFonts( [ 'ko' ] );
		if ( $result === false && ! empty( $_SESSION['pb_errors'] ) ) {
			$this->markTestIncomplete( print_r( $_SESSION['pb_errors'], true ) );
			return;
		}
		$this->assertTrue( $result );
		$this->assertFileExists( WP_CONTENT_DIR . '/uploads/assets/fonts/NotoSansCJKkr-Regular.otf' );
		$this->assertFileExists( WP_CONTENT_DIR . '/uploads/assets/fonts/NotoSansCJKkr-Bold.otf' );
		$result = $this->gt->getFonts( [ 'bn' ] );
		$this->assertTrue( $result );
		$this->assertFileExists( WP_CONTENT_DIR . '/uploads/assets/fonts/NotoSansBengali-Regular.ttf' );
		$this->assertFileExists( WP_CONTENT_DIR . '/uploads/assets/fonts/NotoSansBengali-Bold.ttf' );
		$this->assertFileExists( WP_CONTENT_DIR . '/uploads/assets/fonts/NotoSerifBengali-Regular.ttf' );
		$this->assertFileExists( WP_CONTENT_DIR . '/uploads/assets/fonts/NotoSerifBengali-Bold.ttf' );
	}
}
