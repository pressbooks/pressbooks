<?php
/**
 * Theme handling.
 *
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Theme;

use Pressbooks\Container;
use Pressbooks\CustomCss;

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
		wp_die(
			sprintf(
				__( 'Your theme, %1$s, is not installed. Please visit %2$s for installation instructions.', 'pressbooks' ),
				$theme->get_stylesheet(),
				sprintf(
					'<a href="%1$s">%2$s</a>',
					'https://github.com/pressbooks/' . $theme->get_stylesheet(),
					'GitHub'
				)
			)
		);
	}

	$theme = wp_get_theme( 'pressbooks-book' );
	if ( ! $theme->exists() ) {
		wp_die(
			sprintf(
				__( 'The Pressbooks Book theme is not installed, but Pressbooks needs it in order to function properly. Please visit %s for installation instructions.', 'pressbooks' ),
				sprintf(
					'<a href="%1$s">%2$s</a>',
					'https://github.com/pressbooks/pressbooks-book',
					'GitHub'
				)
			)
		);
	}

	set_transient( 'pb_has_required_themes', 1 );
}

/**
 * Check if custom-css is old; prompt to upgrade
 *
 * @since 4.3
 */
function check_upgraded_customcss() {
	if ( get_transient( 'pb_has_upgraded_custom_css' ) !== false ) {
		return;
	}

	foreach ( [ 'pressbooks-custom-css', 'pressbooks-customcss' ] as $name ) {
		$theme = wp_get_theme( $name );
		if ( $theme->exists() && ! version_compare( $theme->get( 'Version' ), '1.0.0', '>=' ) ) {
			wp_die(
				sprintf(
					__( 'The Pressbooks Custom CSS theme must be upgraded. Please visit %s for installation instructions.', 'pressbooks' ),
					sprintf(
						'<a href="%1$s">%2$s</a>',
						'https://github.com/pressbooks/pressbooks-custom-css',
						'GitHub'
					)
				)
			);
		}
	}

	set_transient( 'pb_has_upgraded_custom_css', 1 );
}

/**
 * Update theme slugs from Pressbooks < 4.0.
 *
 * @since 4.0
 */
function migrate_book_themes() {
	$pressbooks_theme_migration = (int) get_option( 'pressbooks_theme_migration', 0 );

	// Upgrade from old slugs (themes included as files inside the pressbooks plugin) to new slugs (separate github repos)
	if ( ! $pressbooks_theme_migration ) {
		$comparisons = [
			'austen' => 'pressbooks-austenclassic',
			'clarke' => 'pressbooks-clarke',
			'donham' => 'pressbooks-donham',
			'fitzgerald' => 'pressbooks-fitzgerald',
		];

		$theme = wp_get_theme()->get_stylesheet();

		if ( isset( $comparisons[ $theme ] ) ) {
			switch_theme( $comparisons[ $theme ] );

			$lock = Lock::init();
			if ( $lock->isLocked() ) {
				$data = $lock->getLockData();
				$data['stylesheet'] = $comparisons[ $theme ];
				$json = wp_json_encode( $data );
				$lockfile = $lock->getLockDir( false ) . '/lock.json';
				\Pressbooks\Utility\put_contents( $lockfile, $json );
			}
		}

		$pressbooks_theme_migration = 1;
		update_option( 'pressbooks_theme_migration', $pressbooks_theme_migration );
	}

	// Upgrade to McLuhan, fallback to Luther
	if ( $pressbooks_theme_migration === 1 ) {
		$theme = wp_get_theme()->get_stylesheet();
		if ( $theme === 'pressbooks-book' ) {
			if ( wp_get_theme( 'pressbooks-luther' )->exists() ) {
				switch_theme( 'pressbooks-luther' );
				$lock = Lock::init();
				if ( $lock->isLocked() ) {
					$data = $lock->getLockData();
					$data['stylesheet'] = 'pressbooks-luther';
					$json = wp_json_encode( $data );
					$lockfile = $lock->getLockDir() . '/lock.json';
					\Pressbooks\Utility\put_contents( $lockfile, $json );
				}
			} else {
				add_action(
					'admin_notices', function () {
						/* translators: 1: URL to Luther theme */
						echo '<div id="message" class="error fade"><p>' . sprintf(
							__( 'Luther has been replaced with McLuhan as Pressbooks’ default book theme. To continue using Luther for your book, please ensure that the standalone <a href="%1$s">Luther theme</a> is installed and network activated.', 'pressbooks' ),
							'https://github.com/pressbooks/pressbooks-luther/'
						) . '</p></div>';
					}
				);
			}
		}

		$pressbooks_theme_migration = 2;
		update_option( 'pressbooks_theme_migration', $pressbooks_theme_migration );
	}

	// Fix badly compiled *DEPRECATED* Custom CSS theme
	if ( $pressbooks_theme_migration === 2 ) {
		if ( CustomCss::isCustomCss() ) {
			Container::get( 'Styles' )->updateWebBookStyleSheet();
		}
		$pressbooks_theme_migration = 3;
		update_option( 'pressbooks_theme_migration', $pressbooks_theme_migration );
	}

	// Transition from Dillard + Dillard Plain 1.x to Dillard 2.0
	if ( $pressbooks_theme_migration === 3 ) {
		$theme = wp_get_theme()->get_stylesheet();
		if ( $theme === 'pressbooks-dillard' ) {
			// Enable title decoration for Dillard 2.0
			$options = get_option( 'pressbooks_theme_options_global' );
			$options['enable_title_decoration'] = 1;
			update_option( 'pressbooks_theme_options_global', $options );
		}

		if ( $theme === 'pressbooks-dillardplain' ) {
			// Switch theme to Dillard 2.0 with title decoration disabled
			switch_theme( 'pressbooks-dillard' );
			$lock = Lock::init();
			if ( $lock->isLocked() ) {
				$data = $lock->getLockData();
				$data['stylesheet'] = 'pressbooks-dillard';
				$json = wp_json_encode( $data );
				$lockfile = $lock->getLockDir() . '/lock.json';
				\Pressbooks\Utility\put_contents( $lockfile, $json );
			}
		}

		$pressbooks_theme_migration = 4;
		update_option( 'pressbooks_theme_migration', $pressbooks_theme_migration );
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
