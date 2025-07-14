<?php

class L10nTest extends \WP_UnitTestCase {
	/**
	 * @group localization
	 */
	public function test_get_locale() {
		apply_filters( 'locale', function ( $locale ) { return 'en_US'; } );
		$locale = \Pressbooks\L10n\get_locale();
		$this->assertEquals( 'en_US', $locale );

		$user_id = $this->factory()->user->create( [ 'role' => 'contributor', 'locale' => 'fr_FR' ] );
		wp_set_current_user( $user_id );
		$this->assertEquals( 'en_US', $locale );

		global $current_screen;
		$current_screen = WP_Screen::get( 'admin' ); // is_admin
		$locale = \Pressbooks\L10n\get_locale();
		$this->assertEquals( 'fr_FR', $locale );

		wp_update_user( array( 'ID' => $user_id, 'locale' => '' ) );
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

	public function test_get_book_language() {
		$lang = \Pressbooks\L10n\get_book_language();
		$this->assertNotEmpty( $lang );
		$this->assertTrue( is_string( $lang ) );
	}

	/**
	 * @test
	 * @group localization
	 */
	public function it_gets_translated_languages(): void {
		
		$temp_file = tempnam( sys_get_temp_dir(), 'pb_lang_test_' );

		$yaml_content = <<<YAML
git:
	filters:
		- filter_type: file
		  file_format: PO
		  source_file: languages/pressbooks.pot
		  source_language: en
		  translation_files_expression: languages/pressbooks-<lang>.po
	settings:
		pr_branch_name: chore/update-translations-<br_unique_id>
		language_mapping:
			de: de_DE
			es: es_ES
			fr: fr_FR
YAML;

		file_put_contents( $temp_file, $yaml_content );

		$translated_languages = \Pressbooks\L10n\get_translated_languages( $temp_file );
		
		unlink( $temp_file );

		$this->assertIsArray( $translated_languages );
		$this->assertArrayHasKey( 'de_DE', $translated_languages );
		$this->assertArrayHasKey( 'es_ES', $translated_languages );
		$this->assertArrayHasKey( 'fr_FR', $translated_languages );

		$this->assertEquals( 'German', $translated_languages['de_DE'] );
		$this->assertEquals( 'Spanish', $translated_languages['es_ES'] );
		$this->assertEquals( 'French', $translated_languages['fr_FR'] );

		$sorted_languages = array_values( $translated_languages );
		$this->assertEquals( [ 'French', 'German', 'Spanish' ], $sorted_languages );
	}
}
