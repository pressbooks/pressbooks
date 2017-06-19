<?php
/**
 * Theme utilities.
 *
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */

namespace Pressbooks\Theme;

/**
 *
 */
function check_required_themes() {
	$migrated_book_themes = [
		'pressbooks-austenclassic',
		'pressbooks-book',
		'pressbooks-clarke',
		'pressbooks-donham',
		'pressbooks-fitzgerald',
		'pressbooks-customcss',
	];

	$theme = wp_get_theme();
	if ( ! $theme->exists() && in_array( $theme->get_stylesheet(), $migrated_book_themes, true ) ) {
		wp_die( sprintf(
			__( 'Your theme, %1$s, is not installed. Please visit %2$s for installation instructions.', 'pressbooks' ),
			$theme->get_stylesheet(),
			sprintf(
				'<a href="%1$s">%2$s</a>',
				'https://github.com/pressbooks/' . $theme->get_stylesheet(),
				'GitHub'
			)
		) );
	}

	$theme = wp_get_theme( 'pressbooks-publisher' );
	if ( ! $theme->exists() ) {
		wp_die( sprintf(
			__( 'The Pressbooks Publisher theme is not installed, but Pressbooks needs it in order to function properly. Please visit %s for installation instructions.', 'pressbooks' ),
			sprintf(
				'<a href="%1$s">%2$s</a>',
				'https://github.com/pressbooks/pressbooks-book',
				'GitHub'
			)
		) );
	}

	$theme = wp_get_theme( 'pressbooks-book' );
	if ( ! $theme->exists() ) {
		wp_die( sprintf(
			__( 'The Pressbooks Book theme is not installed, but Pressbooks needs it in order to function properly. Please visit %s for installation instructions.', 'pressbooks' ),
			sprintf(
				'<a href="%1$s">%2$s</a>',
				'https://github.com/pressbooks/pressbooks-publisher',
				'GitHub'
			)
		) );
	}
}

/**
 * Update theme slugs from Pressbooks < 4.0.
 */
function migrate_book_themes() {
	if ( get_option( 'pressbooks_theme_migration' ) === false ) {
		$comparisons = [
			'austen' => 'pressbooks-austenclassic',
			'clarke' => 'pressbooks-clarke',
			'donham' => 'pressbooks-donham',
			'fitzgerald' => 'pressbooks-fitzgerald',
		];

		$theme = wp_get_theme()->get_stylesheet();

		if ( isset( $comparisons[ $theme ] ) ) {
			switch_theme( $comparisons[ $theme ] );
		}
		update_option( 'pressbooks_theme_migration', 1 );
	}
}
