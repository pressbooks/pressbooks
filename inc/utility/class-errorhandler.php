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
			set_error_handler( [ self::$instance, 'silenceDeprecationNotices' ] );
		}

		return self::$instance;
	}

	/**
	 * Silence PHP deprecation notices for desired path until they are fixed.
	 * For WP_ENV = development, deprecations can be displayed if DISPLAY_PHP_DEPRECATIONS is defined and is true.
	 *
	 * @param int $errorno Only E_DEPRECATED errors are considered.
	 * @param string $errstr Unused.
	 * @param string $errfile Used to check source path.
	 * @param int $errline Unused.
	 * @return bool True to suppress, false to allow.
	 */
	public function silenceDeprecationNotices( int $errorno, string $_errstr, string $errfile, int $_errline ): bool {
		if ( env( 'WP_ENV' ) === 'development' && env( 'DISPLAY_PHP_DEPRECATIONS' ) === true ) {
			return false;
		}

		if ( $errorno !== E_DEPRECATED ) {
			return false;
		}

		foreach ( $this->getPathsToSilence() as $path ) {
			if ( str_contains( $errfile, $path ) ) {
				return true;
			}
		}

		return false;
	}

	protected function getPathsToSilence(): array {
		$paths = [];

		/**
		 * Filter the list of paths for which deprecation notices should be silenced.
		 *
		 * @param string[] $paths Array of file path prefixes.
		 */
		return apply_filters( 'pressbooks_deprecation_paths_to_silence', $paths );
	}

}
