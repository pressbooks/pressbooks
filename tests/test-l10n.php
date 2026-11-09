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
	public function test_override_core_strings_fr_ca() {
		$set_locale = fn() => 'fr_CA';
		add_filter( 'plugin_locale', $set_locale );

		$this->assertEquals( 'Utilisateur·rices', \Pressbooks\L10n\override_core_strings( 'Comptes', 'Users', 'default' ) );
		$this->assertEquals( 'Tous les comptes utilisateurs', \Pressbooks\L10n\override_core_strings( 'Tous les comptes', 'All Users', 'default' ) );
		$this->assertEquals( 'Ajouter', \Pressbooks\L10n\override_core_strings( 'Add New', 'Add User', 'default' ) );
		$this->assertEquals( 'Modifier le rôle&hellip;', \Pressbooks\L10n\override_core_strings( 'Changer de rôle pour&hellip;', 'Change role to&hellip;', 'default' ) );
		$this->assertEquals( '&mdash; Aucun rôle dans ce livre &mdash;', \Pressbooks\L10n\override_core_strings( '&mdash; Aucun rôle sur ce site &mdash;', '&mdash; No role for this site &mdash;', 'default' ) );
		$this->assertEquals( 'Rechercher des utilisateur·rices', \Pressbooks\L10n\override_core_strings( 'Rechercher des comptes', 'Search Users', 'default' ) );
		$this->assertEquals( 'Courriel', \Pressbooks\L10n\override_core_strings( 'E-mail', 'Email', 'default' ) );
		$this->assertEquals( 'Profil', \Pressbooks\L10n\override_core_strings( 'Profil', 'Profile', 'default' ) );

		remove_filter( 'plugin_locale', $set_locale );
	}

	/**
	 * @group localization
	 */
	public function test_override_core_strings_with_context() {
		$set_locale = fn() => 'fr_CA';
		add_filter( 'plugin_locale', $set_locale );

		$this->assertEquals( 'Abonné·e', \Pressbooks\L10n\override_core_strings_with_context( 'Abonné / abonnée', 'Subscriber', 'User role', 'default' ) );
		$this->assertEquals( 'Collaborateur·rice', \Pressbooks\L10n\override_core_strings_with_context( 'Collaborator', 'Collaborator', 'User role', 'default' ) );
		$this->assertEquals( 'Auteur·rice', \Pressbooks\L10n\override_core_strings_with_context( 'Auteur / autrice', 'Author', 'User role', 'default' ) );
		$this->assertEquals( 'Éditeur·rice', \Pressbooks\L10n\override_core_strings_with_context( 'Éditeur / éditrice', 'Editor', 'User role', 'default' ) );
		$this->assertEquals( 'Administrateur·rice', \Pressbooks\L10n\override_core_strings_with_context( 'Administrateur / administratrice', 'Administrator', 'User role', 'default' ) );

		// Same text, different context: untouched
		$this->assertEquals( 'Auteur', \Pressbooks\L10n\override_core_strings_with_context( 'Auteur', 'Author', 'post', 'default' ) );
		$this->assertEquals( 'Articles', \Pressbooks\L10n\override_core_strings_with_context( 'Articles', 'Posts', 'post type general name', 'default' ) );

		remove_filter( 'plugin_locale', $set_locale );

		// Other locales: untouched
		$this->assertEquals( 'Author', \Pressbooks\L10n\override_core_strings_with_context( 'Author', 'Author', 'User role', 'default' ) );
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
}
