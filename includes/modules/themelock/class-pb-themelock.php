<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */
namespace Pressbooks\Modules\ThemeLock;

use Pressbooks\Container;

class ThemeLock {

	static function lockTheme() {
		if ( check_ajax_referer( 'pb-lock-theme' ) ) {
			ThemeLock::copyAssets();
			ThemeLock::generateLock( time() );
		}
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
	 * Display the theme lock page.
	 */
	static function display() {
		require( PB_PLUGIN_DIR . 'templates/admin/themelock.php' );
	}
}
