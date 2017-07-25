<?php
/**
 * Theme utilities.
 *
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */

namespace Pressbooks\Theme;

use Pressbooks\Theme\Lock;

use function Pressbooks\Admin\Fonts\update_font_stacks;

/**
 * Check for required themes; prompt to install if missing.
 *
 * @since 4.0
 */
function check_required_themes() {
	if ( get_transient( 'pb_has_required_themes' ) !== false ) {
		return;
	}

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

	if ( PB_ROOT_THEME === 'pressbooks-publisher' ) { // To bypass this check, define PB_ROOT_THEME to the name of your custom root theme in wp-config.php.
		$theme = wp_get_theme( 'pressbooks-publisher' );
		if ( ! $theme->exists() ) {
			wp_die( sprintf(
				__( 'The Pressbooks Publisher theme is not installed, but Pressbooks needs it in order to function properly. Please visit %s for installation instructions.', 'pressbooks' ),
				sprintf(
					'<a href="%1$s">%2$s</a>',
					'https://github.com/pressbooks/pressbooks-publisher',
					'GitHub'
				)
			) );
		}
	}

	$theme = wp_get_theme( 'pressbooks-book' );
	if ( ! $theme->exists() ) {
		wp_die( sprintf(
			__( 'The Pressbooks Book theme is not installed, but Pressbooks needs it in order to function properly. Please visit %s for installation instructions.', 'pressbooks' ),
			sprintf(
				'<a href="%1$s">%2$s</a>',
				'https://github.com/pressbooks/pressbooks-book',
				'GitHub'
			)
		) );
	}

	set_transient( 'pb_has_required_themes', 1 );
}

/**
 * Update theme slugs from Pressbooks < 4.0.
 *
 * @since 4.0
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

			if ( Lock::isLocked() ) {
				$data = Lock::getLockData();
				$data['stylesheet'] = $comparisons[ $theme ];
				$json = json_encode( $data );
				$lockfile = Lock::getLockDir() . '/lock.json';
				file_put_contents( $lockfile, $json );
			}
		}

		update_option( 'pressbooks_theme_migration', 1 );
	}
}

/**
 * Update template_root from Pressbooks < 4.0.
 *
 * @since 4.0.1
 */
function update_template_root() {
	$template_root = get_option( 'template_root' );
	if ( strpos( $template_root, '/plugins/pressbooks/themes-book' ) !== false ) {
		update_option( 'template_root', str_replace( '/plugins/pressbooks/themes-book', '/themes', $template_root ) );
	}
}
