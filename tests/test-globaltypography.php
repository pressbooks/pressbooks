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
	public function set_up() {
		parent::set_up();
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

            foreach ( $val['files'] as $font_url ) {
                $url = $baseurl . $font_url;
                $headers = wp_get_http_headers( $url );

                // Handle GitHub-hosted fonts
                if ( str_contains( $baseurl, 'https://github.com/notofonts/noto-cjk/raw/main/Sans/OTF/' ) ) {
                    if ( $headers && isset( $headers['location'] ) ) {
                        $font_url = $headers['location'];
                    } else {
                        $this->assertTrue(false, "Cannot download: {$url}");
                    }
                    $this->assertStringContainsString( "/notofonts/noto-cjk/", $font_url );
                    continue;
                }

                // Handle CDN-hosted fonts
                if ( str_contains( $baseurl, 'https://cdn.jsdelivr.net/gh/notofonts/notofonts.github.io/fonts/' ) ) {
                    if ( $headers && isset( $headers['content-type'] ) ) {
                        $content_type = $headers['content-type'];
                    } else {
                        $this->assertTrue(false, "Cannot download: {$url}");
                    }
                    // jsDelivr CDN sometimes returns randomly Package size exceeded the configured limit of 50 MB with content type: text/plain
                    // @see https://github.com/jsdelivr/jsdelivr/issues/18294
                    // maybe it's a throttling issue, so we need to check for both content types
                    // if we try to download each font separately they are being fetched as expected see test_getFonts below
                    $valid_content_types = [ 'font/otf', 'text/plain; charset=utf-8' ];
                    $this->assertContains( $content_type, $valid_content_types, "Unexpected content type: {$content_type} for URL: {$url}" );
                }
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

		@unlink( WP_CONTENT_DIR . '/uploads/assets/fonts/NotoSansBengali-Regular.otf' );
		@unlink( WP_CONTENT_DIR . '/uploads/assets/fonts/NotoSansBengali-Bold.otf' );
		@unlink( WP_CONTENT_DIR . '/uploads/assets/fonts/NotoSerifBengali-Regular.otf' );
		@unlink( WP_CONTENT_DIR . '/uploads/assets/fonts/NotoSerifBengali-Bold.otf' );
		$result = $this->gt->getFonts( [ 'bn' ] );
		$this->assertTrue( $result );
		$this->assertFileExists( WP_CONTENT_DIR . '/uploads/assets/fonts/NotoSansBengali-Regular.otf' );
		$this->assertFileExists( WP_CONTENT_DIR . '/uploads/assets/fonts/NotoSansBengali-Bold.otf' );
		$this->assertFileExists( WP_CONTENT_DIR . '/uploads/assets/fonts/NotoSerifBengali-Regular.otf' );
		$this->assertFileExists( WP_CONTENT_DIR . '/uploads/assets/fonts/NotoSerifBengali-Bold.otf' );
	}

	/**
	 * @group typography
	 */
	public function test_appendCustomFonts_no_custom_fonts_option() {
		delete_site_option( 'pressbooks_custom_fonts' );

		$scss = '$color: red;';
		$result = GlobalTypography::appendCustomFonts( $scss );

		$this->assertEquals( $scss, $result );
	}

	/**
	 * @group typography
	 */
	public function test_appendCustomFonts_empty_custom_fonts_option() {
		update_site_option( 'pressbooks_custom_fonts', [] );

		$scss = '$color: red;';
		$result = GlobalTypography::appendCustomFonts( $scss );

		$this->assertEquals( $scss, $result );
	}

	/**
	 * @group typography
	 */
	public function test_appendCustomFonts_css_file_does_not_exist() {
		update_site_option( 'pressbooks_custom_fonts', [ 'MyFont' => [] ] );

		$scss = '$color: red;';
		$result = GlobalTypography::appendCustomFonts( $scss );

		$this->assertEquals( $scss, $result );
	}

	/**
	 * @group typography
	 */
	public function test_appendCustomFonts_prepends_css_content() {
		update_site_option( 'pressbooks_custom_fonts', [ 'MyFont' => [] ] );

		$dir = WP_CONTENT_DIR . '/uploads/assets/custom-fonts';
		if ( ! is_dir( $dir ) ) {
			mkdir( $dir, 0755, true );
		}

		$css_file = $dir . '/custom-fonts.css';
		$css_content = '@font-face { font-family: "MyFont"; }';
		file_put_contents( $css_file, $css_content );

		$scss = '$color: red;';
		$result = GlobalTypography::appendCustomFonts( $scss );

		$this->assertStringContainsString( $css_content, $result );
		$this->assertStringContainsString( $scss, $result );
		$this->assertStringStartsWith( $css_content, $result );

		unlink( $css_file );
	}
}
