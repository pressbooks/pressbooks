<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */

namespace Pressbooks\Theme;

class Lock {

	/**
	 * Get path to the theme lock directory.
	 *
	 * @return string
	 */
	static function getLockDir() {

		$wp_upload_dir = wp_upload_dir();
		$lock_dir = $wp_upload_dir['basedir'] . '/lock';

		if ( ! file_exists( $lock_dir ) ) {
			wp_mkdir_p( $lock_dir );
		}

		return $lock_dir;
	}

	/**
	 * @return string
	 */
	static function getLockDirURI() {
		$wp_upload_dir = wp_upload_dir();
		$lock_dir_uri = $wp_upload_dir['baseurl'] . '/lock';
		$lock_dir_uri = \Pressbooks\Sanitize\maybe_https( $lock_dir_uri );
		return $lock_dir_uri;
	}


	/**
	 * @param array $old_value
	 * @param array $value
	 * @param $option
	 *
	 * @return mixed
	 */
	static function toggleThemeLock( $old_value, $value, $option ) {
		if ( isset( $value['theme_lock'] ) && 1 === absint( $value['theme_lock'] ) ) {
			return Lock::lockTheme();
		} elseif ( 1 === absint( $old_value['theme_lock'] ) && empty( $value['theme_lock'] ) ) {
			return Lock::unlockTheme();
		}
		return false;
	}

	/**
	 * Lock the current theme by copying assets to the lock directory and generating a timestamped lockfile.
	 *
	 * @return mixed
	 */
	static function lockTheme() {
		if ( true === Lock::copyAssets() ) {
			$time = time();
			$data = Lock::generateLock( $time );
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

	/**
	 * @return bool
	 */
	static function copyAssets() {

		$source_dir = realpath( get_stylesheet_directory() );
		$dest_dir = Lock::getLockDir();

		// Start by copying all the assets we can find (for old theme compatibility)

		$ok = \Pressbooks\Utility\rcopy(
			$source_dir,
			$dest_dir,
			[ '*.php', '*.git', 'node_modules/', '.github/', '.tx/' ], // Excludes
			[ '*.css', '*.scss', '*.png', '*.jpg', '*.gif', '*.svg', '*.js' ] // Includes
		);
		if ( ! $ok ) {
			return false;
		}

		// We should only copy:

		$keepers = [
			"{$source_dir}/assets/fonts/" => "{$dest_dir}/assets/fonts/",
			"{$source_dir}/assets/images/" => "{$dest_dir}/assets/images/",
			"{$source_dir}/assets/scripts/" => "{$dest_dir}/assets/scripts/",
			"{$source_dir}/assets/styles/" => "{$dest_dir}/assets/styles/",
			"{$source_dir}/node_modules/buckram/" => "{$dest_dir}/node_modules/buckram/",
		];
		foreach ( $keepers as $source => $dest ) {
			if ( file_exists( $source ) ) {
				wp_mkdir_p( $dest );
				$ok = \Pressbooks\Utility\rcopy(
					$source,
					$dest,
					[ '*.php', '.git' ] // Excludes
				);
				if ( ! $ok ) {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * @param $time
	 *
	 * @return array
	 */
	static function generateLock( $time ) {
		global $_wp_theme_features;
		$theme = wp_get_theme();
		$theme_features = is_array( $_wp_theme_features ) ? array_keys( $_wp_theme_features ) : [];
		$data = [
			'stylesheet' => get_stylesheet(),
			'name' => $theme->get( 'Name' ),
			'version' => $theme->get( 'Version' ),
			'timestamp' => $time,
			'features' => $theme_features,
		];
		$json = json_encode( $data );
		$lockfile = Lock::getLockDir() . '/lock.json';
		file_put_contents( $lockfile, $json );
		return $data;
	}

	/**
	 * @return \WP_Theme
	 */
	static function unlockTheme() {
		$dir = Lock::getLockDir();
		@rmrdir( $dir ); // @codingStandardsIgnoreLine
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
		if ( realpath( Lock::getLockDir() . '/lock.json' ) && isset( $options['theme_lock'] ) && 1 === absint( $options['theme_lock'] ) ) {
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
		$json = file_get_contents( Lock::getLockDir() . '/lock.json' );
		$output = json_decode( $json, true );
		return $output;
	}

	/**
	 * Restrict access to Themes and Theme Options.
	 */
	static function restrictThemeManagement() {
		$locked = \Pressbooks\Theme\Lock::isLocked();
		if ( $locked ) {
			$data = \Pressbooks\Theme\Lock::getLockData();

			// Redirect and notify users of theme lock status.

			$check_against_url = parse_url( ( is_ssl() ? 'http://' : 'https://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], PHP_URL_PATH );
			$redirect_url = get_site_url( get_current_blog_id(), '/wp-admin/' );

			// ---------------------------------------------------------------------------------------------------------------
			// Don't let user go to theme (options) page, under any circumstance

			$restricted = [
				'themes',
			];

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
