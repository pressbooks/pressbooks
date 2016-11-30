<?php

class L10nTest extends \WP_UnitTestCase {

	/**
	 * @covers \Pressbooks\L10n\get_locale
	 */
	public function test_get_locale() {

		$locale = \Pressbooks\L10n\get_locale();

		$this->assertTrue( is_string( $locale ) );
	}


	/**
	 * @covers \Pressbooks\L10n\load_plugin_textdomain
	 */
	public function test_load_plugin_textdomain() {

		\Pressbooks\L10n\load_plugin_textdomain();

		$this->assertTrue( true );
	}


	/**
	 * @covers \Pressbooks\L10n\include_core_overrides
	 */
	public function test_include_core_overrides() {

		$overrides = \Pressbooks\L10n\include_core_overrides();

		$this->assertTrue( is_array( $overrides ) );
		$this->assertArrayHasKey( 'My Sites', $overrides );
	}


	/**
	 * @covers \Pressbooks\L10n\override_core_strings
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
	 * @covers \Pressbooks\L10n\set_locale
	 */
	public function test_set_locate() {

		$this->assertTrue(
			is_string( \Pressbooks\L10n\set_locale( 'en_US' ) )
		);
	}


	/**
	 * @covers \Pressbooks\L10n\set_root_locale
	 */
	public function test_set_root_locate() {

		$this->assertTrue(
			is_string( \Pressbooks\L10n\set_root_locale( 'en_US' ) )
		);
	}


	/**
	 * @covers \Pressbooks\L10n\supported_languages
	 */
	public function test_supported_languages() {

		$supported_languages = \Pressbooks\L10n\supported_languages();
		$this->assertTrue( is_array( $supported_languages ) );
	}


	/**
	 * @covers \Pressbooks\L10n\wplang_codes
	 */
	public function test_wplang_codes() {

		$wplang_codes = \Pressbooks\L10n\wplang_codes();
		$this->assertTrue( is_array( $wplang_codes ) );
	}

	/**
	 * @covers \Pressbooks\L10n\romanize
	 */
	public function test_romanize() {

		$this->assertEquals( \Pressbooks\L10n\romanize( 1 ), 'I' );
		$this->assertEquals( \Pressbooks\L10n\romanize( 2 ), 'II' );
		$this->assertEquals( \Pressbooks\L10n\romanize( 3 ), 'III' );
		$this->assertEquals( \Pressbooks\L10n\romanize( 4 ), 'IV' );
		$this->assertEquals( \Pressbooks\L10n\romanize( 1975 ), 'MCMLXXV' );
	}


	/**
	 * @covers \Pressbooks\L10n\use_book_locale
	 */
	public function test_use_book_locale() {

		$this->assertFalse( \Pressbooks\L10n\use_book_locale() );

		$timestamp = time();
		$md5 = md5( $timestamp );
		$_SERVER['REQUEST_URI'] = home_url() . "/format/xhtml?timestamp={$timestamp}&hashkey={$md5}";

		$this->assertTrue( \Pressbooks\L10n\use_book_locale() );

	}

}
