<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */
namespace Pressbooks\Modules\ThemeLock;

use Pressbooks\Container;

class ThemeLock {

	/**
	 * Lock the current theme by copying assets to the lock directory and generating a timestamped lockfile.
	 *
	 * @return int
	 */
	static function lockTheme() {
		if ( check_ajax_referer( 'pb-lock-theme' ) ) {
			ThemeLock::copyAssets();
			$time = time();
			ThemeLock::generateLock( $time );
			return $time;
		}
		return false;
	}

	static function unlockTheme() {
		if ( check_ajax_referer( 'pb-unlock-theme' ) ) {
			if ( ! WP_Filesystem() ) {
				exit;
			}

			global $wp_filesystem;

			$wp_filesystem->delete( ThemeLock::pathToLockDir() . '/lock.json' );

			if ( $wp_filesystem->is_dir( ThemeLock::pathToLockDir() . '/assets' ) ) {
				\Pressbooks\Utility\delete_directory( ThemeLock::pathToLockDir() . '/assets' );
			} elseif ( $wp_filesystem->is_dir( ThemeLock::pathToLockDir() . '/export' ) ) {
				\Pressbooks\Utility\delete_directory( ThemeLock::pathToLockDir() . '/export' );
				$wp_filesystem->delete( ThemeLock::pathToLockDir() . '/style.scss' );
			}
		}
		return false;
	}

	static function copyAssets() {
		if ( ! WP_Filesystem() ) {
			exit;
		}

		global $wp_filesystem;

		if ( Container::get( 'Sass' )->isCurrentThemeCompatible( 1 ) ) {
			$target = ThemeLock::pathToLockDir() . '/export';
			$wp_filesystem->mkdir( $target );
			copy_dir( realpath( get_stylesheet_directory() . '/export' ), $target );
		} elseif ( Container::get( 'Sass' )->isCurrentThemeCompatible( 2 ) ) {
			$target = ThemeLock::pathToLockDir() . '/assets';
			$wp_filesystem->mkdir( $target );
			copy_dir( realpath( get_stylesheet_directory() . '/assets' ), $target );
			$wp_filesystem->copy( realpath( get_stylesheet_directory() . '/style.scss' ), ThemeLock::pathToLockDir() . '/style.scss' );
		}
	}

	static function generateLock( $time ) {
		$theme = wp_get_theme();
		$data = array(
			'stylesheet' => get_stylesheet(),
			'name' => $theme->get( 'Name' ),
			'version' => $theme->get( 'Version' ),
			'timestamp' => $time,
		);
		$json = json_encode( $data );
		$lockfile = ThemeLock::pathToLockDir() . '/lock.json';
		file_put_contents( $lockfile, $json );
	}

	/**
	 * Get path to the theme lock directory.
	 *
	 * @return string
	 */
	static function pathToLockDir() {

		$wp_upload_dir = wp_upload_dir();
		$lock_dir = $wp_upload_dir['basedir'] . '/lock';

		if ( ! file_exists( $lock_dir ) ) {
			mkdir( $lock_dir, 0775, true );
		}

		return $lock_dir;
	}

	/**
	 * Check for a lockfile.
	 *
	 * @return bool
	 */
	static function isLocked() {
		if ( realpath( ThemeLock::pathToLockDir() . '/lock.json' ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Load data from the lockfile.
	 *
	 * @return array
	 */
	static function getLockData() {
		$json = file_get_contents( ThemeLock::pathToLockDir() . '/lock.json' );
		$output = json_decode( $json, true );
		return $output;
	}

	/**
	 * Restrict access to Themes and Theme Options.
	 */
	static function restrictThemeManagement() {
		$locked = \Pressbooks\Modules\ThemeLock\ThemeLock::isLocked();
		if ( $locked ) {
			$data = \Pressbooks\Modules\ThemeLock\ThemeLock::getLockData();
		}
		if ( $locked ) {
			// Notify users of theme lock status.
			global $pagenow;
			if ( 'themes.php' == $pagenow && 'pressbooks_theme_lock' !== @$_GET['page'] ) {
				$_SESSION['pb_errors'][] = sprintf(
					__( 'Your book&rsquo;s theme, %1$s, was locked in its current state on %2$s at %3$s. To select a new theme or change your theme options, please %4$s.', 'pressbooks' ),
					$data['name'],
					strftime( '%x', $data['timestamp'] ),
					strftime( '%X', $data['timestamp'] ),
					sprintf(
						'<a href="%s">%s</a>',
						admin_url( 'themes.php?page=pressbooks_theme_lock' ),
						'unlock your theme'
					)
				);
			}

			// Hide theme management elements.
			add_action( 'admin_head-themes.php', function() {
				echo '<style>.theme-browser, .theme-count, .wp-filter-search { display: none; }</style>';
			} );

			// Disable theme options.
			add_action( 'pb_before_themeoptions_settings_fields', function() {
				echo '<script type="text/javascript">jQuery(document).ready(function() { jQuery("form :input").attr("disabled","disabled"); });</script>';
			} );
		}
	}

	/**
	 * Display the theme lock page.
	 */
	static function display() {
		require( PB_PLUGIN_DIR . 'templates/admin/themelock.php' );
	}
}
