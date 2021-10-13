<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Theme;

use Pressbooks\Container;

class Lock {

	/**
	 * @var Lock
	 */
	private static $instance = null;

	/**
	 * @return Lock
	 */
	static public function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			self::hooks( self::$instance );
		}
		return self::$instance;
	}

	/**
	 * @param Lock $obj
	 */
	static public function hooks( Lock $obj ) {
		if ( \Pressbooks\Book::isBook() && $obj->isLocked() ) {
			add_filter( 'pb_stylesheet_directory', [ $obj, 'getLockDir' ] );
			add_filter( 'pb_stylesheet_directory_uri', [ $obj, 'getLockDirURI' ] );
			add_filter( 'pb_global_components_path', [ $obj, 'globalComponentsPath' ] );
		}
		if ( is_admin() ) {
			add_action( 'admin_init', [ $obj, 'restrictThemeManagement' ] );
			add_action( 'update_option_pressbooks_export_options', [ $obj, 'toggleThemeLock' ], 10, 3 );
		}
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
	}

	/**
	 * Get path to the theme lock directory.
	 *
	 * @param bool $mkdir
	 *
	 * @return string
	 */
	public function getLockDir( $mkdir = true ) {
		return \Pressbooks\Utility\get_generated_content_path( '/lock', $mkdir );
	}

	/**
	 * @return string
	 */
	public function getLockDirURI() {
		return \Pressbooks\Utility\get_generated_content_url( '/lock' );
	}

	/**
	 * @param array $old_value
	 * @param array $value
	 * @param $option
	 *
	 * @return mixed
	 */
	public function toggleThemeLock( $old_value, $value, $option ) {
		if ( isset( $value['theme_lock'] ) && 1 === absint( $value['theme_lock'] ) ) {
			return $this->lockTheme();
		} elseif ( 1 === absint( $old_value['theme_lock'] ) && empty( $value['theme_lock'] ) ) {
			return $this->unlockTheme();
		}
		return false;
	}

	/**
	 * Lock the current theme by copying assets to the lock directory and generating a timestamped lockfile.
	 *
	 * @return mixed
	 */
	public function lockTheme() {
		if ( true === $this->copyAssets() ) {
			$time = time();
			$data = $this->generateLock( $time );
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
	public function copyAssets() {

		$source_dir = realpath( get_stylesheet_directory() );
		$dest_dir = $this->getLockDir();

		// Start by copying all the assets we can find (old theme compatibility, screenshots, etc)

		$ok = \Pressbooks\Utility\rcopy(
			$source_dir,
			$dest_dir,
			[ '*.php', '.git/', 'node_modules/', '.github/', '.tx/' ], // Excludes
			[ '*.css', '*.scss', '*.png', '*.jpg', '*.gif', '*.svg', '*.js' ] // Includes
		);
		if ( ! $ok ) {
			return false;
		}

		// Next, do keepers

		$keepers = [
			"{$source_dir}/assets/images/" => "{$dest_dir}/assets/images/",
			"{$source_dir}/assets/scripts/" => "{$dest_dir}/assets/scripts/",
			"{$source_dir}/assets/styles/" => "{$dest_dir}/assets/styles/",
			"{$source_dir}/assets/fonts/" => "{$dest_dir}/assets/fonts/",
		];
		foreach ( $keepers as $source => $dest ) {
			if ( file_exists( $source ) ) {
				wp_mkdir_p( $dest );
				$ok = \Pressbooks\Utility\rcopy(
					$source,
					$dest,
					[ '*.php', '.git/' ] // Excludes
				);
				if ( ! $ok ) {
					return false;
				}
			}
		}

		// Lock the globals in a consistent place
		// We know some files may be duplicated, we do this on purpose to future-proof (and because we're lazy)

		$path_to_globals = Container::get( 'Sass' )->pathToGlobals();
		$ok = \Pressbooks\Utility\rcopy(
			$path_to_globals,
			"{$dest_dir}/global-components/",
			[ '*.php', '.git/' ] // Excludes
		);
		if ( ! $ok ) {
			return false;
		}

		return true;
	}

	/**
	 * @param $time
	 *
	 * @return array
	 */
	public function generateLock( $time ) {
		$theme = wp_get_theme();

		global $_wp_theme_features;
		$theme_features = is_array( $_wp_theme_features ) ? array_keys( $_wp_theme_features ) : [];

		$data = [
			'stylesheet' => get_stylesheet(),
			'name' => $theme->get( 'Name' ),
			'version' => $theme->get( 'Version' ),
			'timestamp' => $time,
			'features' => $theme_features,
		];
		$json = wp_json_encode( $data );
		$lockfile = $this->getLockDir() . '/lock.json';
		\Pressbooks\Utility\put_contents( $lockfile, $json );
		return $data;
	}

	/**
	 * @return \WP_Theme
	 */
	public function unlockTheme() {
		$dir = $this->getLockDir( false );
		@\Pressbooks\Utility\rmrdir( $dir ); // @codingStandardsIgnoreLine
		$_SESSION['pb_notices'][] = sprintf( '<strong>%s</strong>', __( 'Your book&rsquo;s theme has been unlocked.', 'pressbooks' ) );

		return wp_get_theme();
	}

	/**
	 * Check for a lockfile.
	 *
	 * @return bool
	 */
	public function isLocked() {
		$options = get_option( 'pressbooks_export_options' );
		if ( realpath( $this->getLockDir( false ) . '/lock.json' ) && isset( $options['theme_lock'] ) && 1 === absint( $options['theme_lock'] ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Load data from the lockfile.
	 *
	 * @return array
	 */
	public function getLockData() {
		$json = \Pressbooks\Utility\get_contents( $this->getLockDir( false ) . '/lock.json' );
		$output = json_decode( $json, true );
		return $output;
	}

	/**
	 * @param string $path
	 *
	 * @return string
	 */
	public function globalComponentsPath( $path ) {
		$dir = $this->getLockDir( false ) . '/global-components/';
		if ( file_exists( $dir ) ) {
			return $dir;
		} else {
			return $path;
		}
	}

	/**
	 * Restrict access to Themes and Theme Options.
	 */
	public function restrictThemeManagement() {
		$locked = $this->isLocked();
		if ( $locked ) {
			$data = $this->getLockData();

			// Redirect and notify users of theme lock status.

			$check_against_url = wp_parse_url( ( is_ssl() ? 'http://' : 'https://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], PHP_URL_PATH );
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
