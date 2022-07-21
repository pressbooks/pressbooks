<?php

class L10nTest extends \WP_UnitTestCase {


	/**
	 * @group localization
	 */
	public function test_get_locale() {
		apply_filters( 'locale', fn($locale) => 'en_US' );
		$locale = \Pressbooks\L10n\get_locale();
		$this->assertEquals( 'en_US', $locale );

		$user_id = $this->factory()->user->create( [ 'role' => 'contributor', 'locale' => 'fr_FR' ] );
		wp_set_current_user( $user_id );
		$locale = \Pressbooks\L10n\get_locale();
		$this->assertEquals( 'fr_FR', $locale );

		wp_update_user( ['ID' => $user_id, 'locale' => ''] );
		$locale = \Pressbooks\L10n\get_locale();
		$this->assertEquals( 'en_US', $locale );
	}

	/**
	 * @group localization
	 */
	public function test_load_plugin_textdomain() {

		\Pressbooks\L10n\load_plugin_textdomain();
		$this->assertTrue( true ); // Did not crash
	}

	/**
	 * @group localization
	 */
	public function test_include_core_overrides() {

		$overrides = \Pressbooks\L10n\include_core_overrides();

		$this->assertTrue( is_array( $overrides ) );
		$this->assertArrayHasKey( 'My Sites', $overrides );
	}

	/**
	 * @group localization
	 */
	public function test_override_core_strings() {

		$text = 'My Sites';
		$domain = 'default';
		$translations = get_translations_for_domain( $domain )->translate( $text );

		$translated = \Pressbooks\L10n\override_core_strings( $translations, $text, $domain );

		$this->assertNotEmpty( $translated );
		$this->assertNotEquals( $text, $translated ); // 'My Sites' should be 'My Books', 'Mes Livres', ...
	}

	/**
	 * @group localization
	 */
	public function test_set_locate() {

		$this->assertTrue(
			is_string( \Pressbooks\L10n\set_locale( 'en_US' ) )
		);
	}

	/**
	 * @group localization
	 */
	public function test_set_root_locate() {

		$this->assertTrue(
			is_string( \Pressbooks\L10n\set_root_locale( 'en_US' ) )
		);
	}

	/**
	 * @group localization
	 */
	public function test_supported_languages() {

		$supported_languages = \Pressbooks\L10n\supported_languages();
		$this->assertTrue( is_array( $supported_languages ) );
	}

	/**
	 * @group localization
	 */
	public function test_wplang_codes() {

		$wplang_codes = \Pressbooks\L10n\wplang_codes();
		$this->assertTrue( is_array( $wplang_codes ) );
	}

	/**
	 * @group localization
	 */
	public function test_romanize() {

		$this->assertEquals( \Pressbooks\L10n\romanize( 1 ), 'I' );
		$this->assertEquals( \Pressbooks\L10n\romanize( 2 ), 'II' );
		$this->assertEquals( \Pressbooks\L10n\romanize( 3 ), 'III' );
		$this->assertEquals( \Pressbooks\L10n\romanize( 4 ), 'IV' );
		$this->assertEquals( \Pressbooks\L10n\romanize( 1975 ), 'MCMLXXV' );
	}

	/**
	 * @group localization
	 */
	public function test_install_book_locale() {

		// Test for incorrect meta_key
		$output = \Pressbooks\L10n\install_book_locale( 1, 1, 'pb_authors', 'Some Guy' );
		$this->assertEquals( false, $output );

		// Test that we don't download anything for english
		$output = \Pressbooks\L10n\install_book_locale( 1, 1, 'pb_language', 'en-us' );
		$this->assertEquals( false, $output );

		// Test that we can download chinese
		$output = \Pressbooks\L10n\install_book_locale( 1, 1, 'pb_language', 'zh-cn' );
		$this->assertEquals( 'zh_CN', $output );
	}


//	public function test_update_user_locale() { // TODO
//	}

	public function test_get_book_language() {
		$lang = \Pressbooks\L10n\get_book_language();
		$this->assertNotEmpty( $lang );
		$this->assertTrue( is_string( $lang ) );
	}

}
