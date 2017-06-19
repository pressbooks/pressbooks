<?php
/**
 * Theme utilities.
 *
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */

namespace Pressbooks\Theme;

/**
 * Update theme slugs from Pressbooks < 4.0.
 *
 * @return bool
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
	}

	update_option( 'pressbooks_theme_migration', 1 );
}
