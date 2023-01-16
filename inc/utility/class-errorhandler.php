<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Utility;

class ErrorHandler {

	/**
	 * @var ErrorHandler
	 */
	protected static $instance = null;

	static public function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			set_error_handler( [ self::$instance, 'silenceDeprecationsNotices' ] );
		}

		return self::$instance;
	}

	/**
	 * Silence PHP 8.1 deprecation notices for some WP Core, WP plugins and libraries until they get fixed.
	 * For WP_ENV = development, deprecations can be displayed if DISPLAY_PHP8_1_DEPRECATIONS is defined and is true.
	 * See https://github.com/pressbooks/private/issues/1070
	 *
	 * @param int $errorno
	 * @param string $errstr
	 * @param string $errfile
	 * @param int $errline
	 * @return bool
	 */
	public function silenceDeprecationsNotices( int $errorno, string $errstr, string $errfile, int $errline ): bool {
		if ( env( 'WP_ENV' ) === 'development' && env( 'DISPLAY_PHP8_1_DEPRECATIONS' ) === true ) {
			return false;
		}

		$paths_to_silence = [
			ABSPATH, // WordPress core
			WP_PLUGIN_DIR . '/h5p/', // H5P Plugin: https://github.com/h5p/h5p-wordpress-plugin/issues/152
			WP_PLUGIN_DIR . '/pressbooks/vendor/rmccue/requests/', // https://github.com/wp-cli/wp-cli/issues/5623
		];

		$is_errfile_in_silenced_path = array_filter( $paths_to_silence, function ( $path ) use ( $errfile ) {
			return str_contains($errfile, $path);
		});

		return E_DEPRECATED === $errorno && count( $is_errfile_in_silenced_path ) > 0;
	}

}
