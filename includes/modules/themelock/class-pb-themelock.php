<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */
namespace Pressbooks\Modules\ThemeLock;

use Pressbooks\Container;

class ThemeLock {

	/**
	 *
	 */
	static function lockOrUnlockTheme( $old_value, $value, $option ) {
		if ( isset( $value['theme_lock'] ) && 1 == $value['theme_lock'] ) {
			ThemeLock::lockTheme();
		} else {
			ThemeLock::unlockTheme();
		}
	}

	/**
	 * Lock the current theme by copying assets to the lock directory and generating a timestamped lockfile.
	 *
	 * @return int
	 */
	static function lockTheme() {
		ThemeLock::copyAssets();
		$time = time();
		ThemeLock::generateLock( $time );
		return $time;
	}

	static function unlockTheme() {
		if ( ! WP_Filesystem() ) {
			exit;
		}

		global $wp_filesystem;

		\Pressbooks\Utility\delete_directory( ThemeLock::getLockDir() );
	}

	static function copyAssets() {
		if ( ! WP_Filesystem() ) {
			exit;
		}

		global $wp_filesystem;

		copy_dir( get_stylesheet_directory(), ThemeLock::getLockDir() );
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
		$lockfile = ThemeLock::getLockDir() . '/lock.json';
		file_put_contents( $lockfile, $json );
	}

	/**
	 * Get path to the theme lock directory.
	 *
	 * @return string
	 */
	static function getLockDir() {

		$wp_upload_dir = wp_upload_dir();
		$lock_dir = $wp_upload_dir['basedir'] . '/lock';

		if ( ! file_exists( $lock_dir ) ) {
			mkdir( $lock_dir, 0775, true );
		}

		return $lock_dir;
	}

	/**
	 *
	 */
	static function getLockDirURI() {
		$wp_upload_dir = wp_upload_dir();
		$lock_dir = $wp_upload_dir['baseurl'] . '/lock';
		$lock_dir = \Pressbooks\Sanitize\maybe_https( $lock_dir );
		return $lock_dir;
	}

	/**
	 * Check for a lockfile.
	 *
	 * @return bool
	 */
	static function isLocked() {
		$options = get_option( 'pressbooks_export_options' );
		if ( realpath( ThemeLock::getLockDir() . '/lock.json' ) && 1 == @$options['theme_lock'] ) {
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
		$json = file_get_contents( ThemeLock::getLockDir() . '/lock.json' );
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
			// Redirect and notify users of theme lock status.

			$check_against_url = parse_url( ( is_ssl() ? 'http://' : 'https://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], PHP_URL_PATH );
			$redirect_url = get_site_url( get_current_blog_id(), '/wp-admin/' );

			// ---------------------------------------------------------------------------------------------------------------
			// Don't let user go to theme (options) page, under any circumstance

			$restricted = array(
				'themes',
			);

			$expr = '~/wp-admin/(' . implode( '|', $restricted ) . ')\.php$~';
			if ( preg_match( $expr, $check_against_url ) ) {
				$_SESSION['pb_errors'][] = sprintf(
					__( 'Your book&rsquo;s theme, %1$s, was locked in its current state as of %2$s at %3$s. To select a new theme or change your theme options, please %4$s.', 'pressbooks' ),
					$data['name'],
					strftime( '%x', $data['timestamp'] ),
					strftime( '%X', $data['timestamp'] ),
					sprintf(
						'<a href="%s">%s</a>',
						admin_url( 'options-general.php?page=pressbooks_export_options' ),
						'unlock your theme'
					)
				);
				\Pressbooks\Redirect\location( $redirect_url );
			}
		}
	}
}
