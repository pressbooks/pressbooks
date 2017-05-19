<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */
namespace Pressbooks;

use Pressbooks\Container;

class ThemeLock {

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
		$lock_dir_uri = $wp_upload_dir['baseurl'] . '/lock';
		$lock_dir_uri = \Pressbooks\Sanitize\maybe_https( $lock_dir_uri );
		return $lock_dir_uri;
	}

	/**
	 *
	 */
	static function toggleThemeLock( $old_value, $value, $option ) {
		if ( isset( $value['theme_lock'] ) && 1 == $value['theme_lock'] ) {
			return ThemeLock::lockTheme();
		} elseif ( 1 == $old_value['theme_lock'] && ! isset( $value['theme_lock'] ) ) {
			return ThemeLock::unlockTheme();
		}
	}

	/**
	 * Lock the current theme by copying assets to the lock directory and generating a timestamped lockfile.
	 *
	 * @return int
	 */
	static function lockTheme() {
		if ( true == ThemeLock::copyAssets() ) {
			$time = time();
			$data = ThemeLock::generateLock( $time );
			$_SESSION['pb_notices'][] = sprintf(
				'<strong>%s</strong>',
				sprintf(
					__( 'Your book&rsquo;s theme, %1$s, has been locked in its current state as of %2$s at %3$s.', 'pressbooks' ),
					$data['name'],
					strftime( '%x', $data['timestamp'] ),
					strftime( '%X', $data['timestamp'] )
				)
			);
			return $data;
		} else {
			$option = get_option( 'pressbooks_export_options' );
			unset( $option['theme_lock'] );
			update_option( 'pressbooks_export_options', $option );
			$_SESSION['pb_errors'][] = sprintf(
				'<strong>%s</strong>',
				__( 'Your book&rsquo;s theme could not be locked. Please ensure that you have write access to the uploads directory.', 'pressbooks' )
			);
		}
		return false;
	}

	static function copyAssets() {
		return \Pressbooks\Utility\rcopy( realpath( get_stylesheet_directory() ), ThemeLock::getLockDir() );
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
		return $data;
	}

	static function unlockTheme() {
		rmrdir( ThemeLock::getLockDir() );
		$_SESSION['pb_notices'][] = sprintf( '<strong>%s</strong>', __( 'Your book&rsquo;s theme has been unlocked.', 'pressbooks' ) );

		return wp_get_theme();
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
		$locked = \Pressbooks\ThemeLock::isLocked();
		if ( $locked ) {
			$data = \Pressbooks\ThemeLock::getLockData();
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
