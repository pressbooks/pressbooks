<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks;

use function \Pressbooks\Utility\debug_error_log;
use PressbooksMix\Assets;

class PressbooksSentry {

	const DEFAULT_ENVIRNOMENT = 'staging';

	const DEFAULT_TRACE_SAMPLE_RATE = 0.5;

	const WP_SCRIPT_NAME = 'script-sentry';

	const ENABLED_VALUE = 1;

	const ENVIRONMENT_VARIABLES_V1 = 1;

	const ENVIRONMENT_VARIABLES_V2 = 2;

	/**
	 * @var PressbooksSentry
	 */
	protected static $instance = null;

	/**
	 * Sentry DSN
	 *
	 * @var string\bool
	 */
	private $dsn = false;

	/**
	 * @var \WP_User
	 */
	private $user;

	/**
	 * @since 5.5.3
	 *
	 * @return PressbooksSentry
	 */
	static public function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			self::$instance->setSentryDSNFromEnvironmentVariables();
			self::$instance->getCurrentUserForTracking();
			if (
				self::environmentVariablesType() === self::ENVIRONMENT_VARIABLES_V1 ||
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
	 * Get current user for tracking (if available)
	 *
	 * @return false|\WP_User
	 */
	public function getCurrentUserForTracking() {
		$user_id = get_current_user_id();
		$this->user = $user_id > 0 ? get_userdata( $user_id ) : false;
		return $this->user;
	}

	/**
	 * Set user for tracking
	 *
	 * @return false|\WP_User
	 */
	public function setUserForTracking( \WP_User $user ) {
		$this->user = $user;
		return $this->user;
	}

	/**
	 * Get type of environment variables defined.
	 * V1 = SENTRY_KEY, SENTRY_ORGANIZATION and SENTRY_PROJECT.
	 * V2 = ENABLE_SENTRY and SENTRY_DSN.
	 *
	 * @return false|int
	 */
	static public function environmentVariablesType() {
		if ( self::areEnvironmentVariablesPresent() ) {
			return (
				! is_null( env( 'SENTRY_KEY' ) ) &&
				! is_null( env( 'SENTRY_ORGANIZATION' ) ) &&
				! is_null( env( 'SENTRY_PROJECT' ) )
			) ? self::ENVIRONMENT_VARIABLES_V1 : self::ENVIRONMENT_VARIABLES_V2;
		}
		return false;
	}


	/**
	 * Are necessary environment variables present for Sentry integration?
	 *
	 * @return bool
	 */
	static public function areEnvironmentVariablesPresent() {
		return ! is_null( env( 'WP_ENV' ) ) &&
			(
				(
					! is_null( env( 'ENABLE_SENTRY' ) ) &&
					intval( env( 'ENABLE_SENTRY' ) ) === self::ENABLED_VALUE &&
					! is_null( env( 'SENTRY_DSN' ) )
				) ||
				(
					! is_null( env( 'SENTRY_KEY' ) ) &&
					! is_null( env( 'SENTRY_ORGANIZATION' ) ) &&
					! is_null( env( 'SENTRY_PROJECT' ) )
				)
			);
	}


	/**
	 * Set DSN Sentry string if available. If environment variables are not present, it returns false.
	 *
	 * @return bool|string
	 */
	public function setSentryDSNFromEnvironmentVariables() {
		if ( self::areEnvironmentVariablesPresent() ) {
			if (
				! is_null( env( 'ENABLE_SENTRY' ) ) &&
				intval( env( 'ENABLE_SENTRY' ) ) === self::ENABLED_VALUE &&
				! is_null( env( 'SENTRY_DSN' ) )
			) {
				$this->dsn = env( 'SENTRY_DSN' );
			} else {
				$this->dsn = 'https://' . env( 'SENTRY_KEY' ) . '@' . env( 'SENTRY_ORGANIZATION' ) .
					'.ingest.sentry.io/' . env( 'SENTRY_PROJECT' );
			}
			return $this->dsn;
		}
		return false;
	}


	/**
	 * Initialize Sentry for PHP using Sentry's PHP SDK
	 *
	 * @return bool
	 */
	public function phpObserver() {
		if ( self::areEnvironmentVariablesPresent() && $this->dsn ) {
			try {
				\Sentry\init(
					[
						'dsn' => $this->dsn,
						'environment' => env( 'WP_ENV' ) ?: self::DEFAULT_ENVIRNOMENT,
					]
				);
				if ( $this->user ) {
					$user = $this->user;
					\Sentry\configureScope(
						function ( \Sentry\State\Scope $scope ) use ( $user ) {
							$scope->setUser(
								[
									'username' => $user->user_login,
									'email' => $user->user_email,
								]
							);
						}
					);
				}
				return true;
			} catch ( \Exception $exception ) {
				debug_error_log( 'Error initializing Sentry for PHP: ' . $exception->getMessage() );
			}
		}
		return false;
	}

	/**
	 * Get HTML JS tag inclusion for Sentry's JavaScript SDK
	 *
	 * @return string
	 */
	public function javascriptObserver() {
		if ( self::areEnvironmentVariablesPresent() && $this->dsn ) {
			try {
				$assets = new Assets( 'pressbooks', 'plugin' );
				$src = $assets->getPath( 'scripts/sentry.js' );
				wp_enqueue_script( self::WP_SCRIPT_NAME, $src );
				$sample_rate = floatval( env( 'SENTRY_TRACE_SAMPLE_RATE' ) );
				$script_params = [
					'dsn' => $this->dsn,
					'environment' => env( 'WP_ENV' ) ?: self::DEFAULT_ENVIRNOMENT,
					'user' => false,
					'sample' => ( $sample_rate > 0 && $sample_rate <= 1 ) ? $sample_rate : self::DEFAULT_TRACE_SAMPLE_RATE,
				];
				if ( $this->user ) {
					$script_params['user'] = [
						'username' => $this->user->user_login,
						'email' => $this->user->user_email,
					];
				}
				wp_localize_script( self::WP_SCRIPT_NAME, 'SentryParams', $script_params );
				return true;
			} catch ( \Exception $exception ) {
				debug_error_log( 'Error initializing Sentry for JavaScript: ' . $exception->getMessage() );
			}
		}
		return false;

	}
}
