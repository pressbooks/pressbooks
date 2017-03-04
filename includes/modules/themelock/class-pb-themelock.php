<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */
namespace Pressbooks\Modules\ThemeLock;


class ThemeLock {

	static function lockTheme() {
		if ( check_ajax_referer( 'pb-lock-theme' ) ) {
			// TODO
		}
	}

	static function unlockTheme() {
		if ( check_ajax_referer( 'pb-unlock-theme' ) ) {
			// TODO
		}
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

		return trailingslashit( $lock_dir );
	}

	/**
	 * Check for a lockfile.
	 *
	 * @return bool
	 */
	static function isLocked() {
		if ( realpath( ThemeLock::pathToLockDir() . 'lock.json' ) ) {
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
		$json = file_get_contents( ThemeLock::pathToLockDir() . 'lock.json' );
		$output = json_decode( $json, true );
		return $output;
	}

	/**
	 * Display the theme lock page.
	 */
	static function display() {
		require( PB_PLUGIN_DIR . 'templates/admin/themelock.php' );
	}
}
