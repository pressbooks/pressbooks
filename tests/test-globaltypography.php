<?php

use \Pressbooks\GlobalTypography;
use \Pressbooks\Container;

class GlobalTypographyTest extends \WP_UnitTestCase {

	use utilsTrait;

	/**
	 * @var \Pressbooks\GlobalTypography()
	 * @group typography
	 */
	protected $gt;

	/**
	 * @group typography
	 */
	public function setUp() {
		parent::setUp();
		$this->gt = new GlobalTypography( Container::get( 'Sass' ) );
	}

	/**
	 * @group typography
	 */
	public function test_getSupportedLanguages() {

		$result = $this->gt->getSupportedLanguages();

		$this->assertTrue( is_array( $result ) );

		// Test that we have (at the very least) Greek an Hebrew
		$this->assertArrayHasKey( 'grc', $result );
		$this->assertArrayHasKey( 'he', $result );
	}

	/**
	 * @group typography
	 */
	public function test_getRequiredLanguages() {

		$result = $this->gt->_getRequiredLanguages();

		$this->assertTrue( is_array( $result ) );
	}

	/**
	 * @group typography
	 */
	public function test_getThemeFontStacks() {

		$this->_book( 'pressbooks-clarke' ); // Pick a theme with some built-in $supported_languages

		$this->gt->updateGlobalTypographyMixin();
		$this->assertNotEmpty( $this->gt->getThemeFontStacks( 'epub' ) );

		$this->assertEmpty( $this->gt->getThemeFontStacks( 'garbage' ) );
	}

	/**
	 * @group typography
	 */
	public function test_getThemeSupportedLanguages() {

		$this->_book();

		add_theme_support( 'pressbooks_global_typography', 'grc', 'he' );

		$supported_languages = $this->gt->getThemeSupportedLanguages();

		$this->assertContains( 'grc', $supported_languages );
	}

	/**
	 * @group typography
	 */
	public function test_fontPacks() {
		$fontpacks = $this->gt->fontPacks();
		foreach ( $fontpacks as $val ) {
			$baseurl = $val['baseurl'];
			foreach ( $val['files'] as $font => $font_url ) {
				$status = '404 Not Found';
				$url = $baseurl . $font_url;
				$headers = wp_get_http_headers( $url );
				if ( $headers && isset( $headers['status'] ) ) {
					$status = $headers['status'];
				} else {
					$this->assertTrue( false, "Cannot download: {$url}" );
				}
				$this->assertNotContains( $status, '404', "404 Not Found: {$url}" );
			}
		}
	}

	/**
	 * @group typography
	 */
	public function test_getFonts() {
		@unlink( WP_CONTENT_DIR . '/uploads/assets/fonts/NotoSansCJKkr-Regular.otf' );
		@unlink( WP_CONTENT_DIR . '/uploads/assets/fonts/NotoSansCJKkr-Bold.otf' );
		$result = $this->gt->getFonts( [ 'ko' ] );
		$this->assertTrue( $result );
		$this->assertFileExists( WP_CONTENT_DIR . '/uploads/assets/fonts/NotoSansCJKkr-Regular.otf' );
		$this->assertFileExists( WP_CONTENT_DIR . '/uploads/assets/fonts/NotoSansCJKkr-Bold.otf' );

		@unlink( WP_CONTENT_DIR . '/uploads/assets/fonts/NotoSansBengali-Regular.ttf' );
		@unlink( WP_CONTENT_DIR . '/uploads/assets/fonts/NotoSansBengali-Bold.ttf' );
		@unlink( WP_CONTENT_DIR . '/uploads/assets/fonts/NotoSerifBengali-Regular.ttf' );
		@unlink( WP_CONTENT_DIR . '/uploads/assets/fonts/NotoSerifBengali-Bold.ttf' );
		$result = $this->gt->getFonts( [ 'bn' ] );
		$this->assertTrue( $result );
		$this->assertFileExists( WP_CONTENT_DIR . '/uploads/assets/fonts/NotoSansBengali-Regular.ttf' );
		$this->assertFileExists( WP_CONTENT_DIR . '/uploads/assets/fonts/NotoSansBengali-Bold.ttf' );
		$this->assertFileExists( WP_CONTENT_DIR . '/uploads/assets/fonts/NotoSerifBengali-Regular.ttf' );
		$this->assertFileExists( WP_CONTENT_DIR . '/uploads/assets/fonts/NotoSerifBengali-Bold.ttf' );
	}
}
