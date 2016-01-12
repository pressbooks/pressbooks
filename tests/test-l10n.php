<?php

class L10nTest extends \WP_UnitTestCase {

	/**
	 * @covers \PressBooks\L10n\get_locale
	 */
	public function test_get_locale() {

		$locale = \PressBooks\L10n\get_locale();

		$this->assertTrue( is_string( $locale ) );
	}


	/**
	 * @covers \PressBooks\L10n\load_plugin_textdomain
	 */
	public function test_load_plugin_textdomain() {

		\PressBooks\L10n\load_plugin_textdomain();

		$this->assertTrue( true );
	}


	/**
	 * @covers \PressBooks\L10n\include_core_overrides
	 */
	public function test_include_core_overrides() {

		$overrides = \PressBooks\L10n\include_core_overrides();

		$this->assertTrue( is_array( $overrides ) );
		$this->assertArrayHasKey( 'My Sites', $overrides );
	}


	/**
	 * @covers \PressBooks\L10n\override_core_strings
	 */
	public function test_override_core_strings() {

		$text = 'My Sites';
		$domain = 'default';
		$translations = get_translations_for_domain( $domain )->translate( $text );

		$translated = \PressBooks\L10n\override_core_strings( $translations, $text, $domain );

		$this->assertNotEmpty( $translated );
		$this->assertNotEquals( $text, $translated ); // 'My Sites' should be 'My Books', 'Mes Livres', ...
	}


	/**
	 * @covers \PressBooks\L10n\set_locale
	 */
	public function test_set_locate() {

		$this->assertTrue(
			is_string( \PressBooks\L10n\set_locale( 'en_US' ) )
		);
	}


	/**
	 * @covers \PressBooks\L10n\set_root_locale
	 */
	public function test_set_root_locate() {

		$this->assertTrue(
			is_string( \PressBooks\L10n\set_root_locale( 'en_US' ) )
		);
	}


	/**
	 * @covers \PressBooks\L10n\supported_languages
	 */
	public function test_supported_languages() {

		$supported_languages = \PressBooks\L10n\supported_languages();
		$this->assertTrue( is_array( $supported_languages ) );
	}


	/**
	 * @covers \PressBooks\L10n\wplang_codes
	 */
	public function test_wplang_codes() {

		$wplang_codes = \PressBooks\L10n\wplang_codes();
		$this->assertTrue( is_array( $wplang_codes ) );
	}


	/**
	 * @covers \PressBooks\L10n\get_dashboard_languages
	 */
	public function test_get_dashboard_languages() {

		$get_dashboard_languages = \PressBooks\L10n\get_dashboard_languages();
		$this->assertTrue( is_array( $get_dashboard_languages ) );
	}


	/**
	 * @covers \PressBooks\L10n\set_user_interface_lang
	 */
	function test_set_user_interface_lang() {

		$user_id = $this->factory->user->create();

		\PressBooks\L10n\set_user_interface_lang( $user_id );

		$this->assertTrue( true );
	}


	/**
	 * @covers \PressBooks\L10n\romanize
	 */
	public function test_romanize() {

		$this->assertEquals( \PressBooks\L10n\romanize( 1 ), 'I' );
		$this->assertEquals( \PressBooks\L10n\romanize( 2 ), 'II' );
		$this->assertEquals( \PressBooks\L10n\romanize( 3 ), 'III' );
		$this->assertEquals( \PressBooks\L10n\romanize( 4 ), 'IV' );
		$this->assertEquals( \PressBooks\L10n\romanize( 1975 ), 'MCMLXXV' );
	}


	/**
	 * @covers \PressBooks\L10n\use_book_locale
	 */
	public function test_use_book_locale() {

		$this->assertTrue(
			is_bool( \PressBooks\L10n\use_book_locale() )
		);
	}

}
