<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks;

use function \Pressbooks\Utility\debug_error_log;
use PressbooksMix\Assets;

class Sentry {

	const DEFAULT_ENVIRNOMENT = 'staging';

	const WP_SCRIPT_NAME = 'script-sentry';

	/**
	 * @var Sentry
	 */
	protected static $instance = null;

	/**
	 * Sentry DSN
	 *
	 * @var string
	 */
	protected static $dsn;

	/**
	 * @since 5.5.3
	 *
	 * @return Sentry
	 */
	static public function init() {

		if ( self::areEnvironmentAvailable() ) {
			self::$dsn = env( 'SENTRY_DSN' );
		}

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			if (
				! is_null( env( 'SENTRY_INITIALIZE_PHP' ) ) &&
				intval( env( 'SENTRY_INITIALIZE_PHP' ) ) === 1
			) {
				self::$instance->phpObserver();
			}
			if (
				! is_null( env( 'SENTRY_INITIALIZE_JAVASCRIPT' ) ) &&
				intval( env( 'SENTRY_INITIALIZE_JAVASCRIPT' ) ) === 1
			) {
				self::$instance->javascriptObserver();
			}
		}

		return self::$instance;

	}

	/**
	 * Check if required environment variables are available to initialize Sentry's SDKs
	 *
	 * @return bool
	 */
	static private function areEnvironmentAvailable() {
		return ! is_null( env( 'WP_ENV' ) ) && ! is_null( env( 'SENTRY_DSN' ) );
	}


	/**
	 * Initialize Sentry for PHP using Sentry's PHP SDK
	 *
	 * @return bool
	 */
	public function phpObserver() {
		try {
			\Sentry\init(
				[
					'dsn' => self::$dsn,
					'environment' => env( 'WP_ENV' ) ?: self::DEFAULT_ENVIRNOMENT,
				]
			);
			return true;
		} catch ( \Exception $exception ) {
			debug_error_log( 'Error initializing Sentry for PHP: ' . $exception->getMessage() );
		}
		return false;
	}

	/**
	 * Get HTML JS tag inclusion for Sentry's JavaScript SDK
	 *
	 * @return string
	 */
	public function javascriptObserver() {
		try {
			$assets = new Assets( 'pressbooks', 'plugin' );
			$src = $assets->getPath( 'scripts/sentry.js' );
			wp_enqueue_script( self::WP_SCRIPT_NAME, $src );
			$script_params = [
				'dsn' => self::$dsn,
				'environment' => env( 'WP_ENV' ) ?: self::DEFAULT_ENVIRNOMENT,
			];
			wp_localize_script( self::WP_SCRIPT_NAME, 'SentryParams', $script_params );
			return true;
		} catch ( \Exception $exception ) {
			debug_error_log( 'Error initializing Sentry for PHP: ' . $exception->getMessage() );
		}
		return false;

	}
}
