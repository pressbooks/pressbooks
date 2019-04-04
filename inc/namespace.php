<?php
/**
 * $_SESSION functions.
 *
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks;

/**
 * @return bool
 */
function use_non_blocking_session() {
	if ( wp_doing_ajax() ) {
		return true;
	}
	if ( is_admin() === false && in_array( $GLOBALS['pagenow'], [ 'wp-login.php', 'wp-register.php', 'wp-signup.php' ], true ) === false ) {
		return true;
	}
	return false;
}

/**
 * Session Start
 */
function session_start() {
	if ( ! session_id() ) {
		if ( ! headers_sent() ) {
			ini_set( 'session.use_only_cookies', true );
			ini_set( 'session.cookie_domain', COOKIE_DOMAIN );

			$options = [];
			if ( use_non_blocking_session() ) {
				// PHP Sessions are allowed but they are "READ ONLY" for ajax and webbook.
				// It reads the session data and immediately releases the lock so other scripts won't block on it.
				$options['read_and_close'] = true;
			}

			/**
			 * Adjust session configuration as needed.
			 *
			 * @since 5.5.0
			 *
			 * @param array $options
			 */
			$override_options = apply_filters( 'pb_session_configuration', $options );
			if ( is_array( $override_options ) ) {
				$options = $override_options;
			}
			$session_ok = @\session_start( $options ); // @codingStandardsIgnoreLine
			if ( ! $session_ok ) {
				session_regenerate_id( true );
				\session_start( $options );
			}
		} else {
			error_log( 'There was a problem with \Pressbooks\session_start(), headers already sent!' ); // @codingStandardsIgnoreLine
		}
	}
}

/**
 * Session Destroy
 */
function session_kill() {
	$_SESSION = [];
	@session_destroy(); // @codingStandardsIgnoreLine
}


/**
 * @return array
 */
function get_all_notices() {
	return get_all( 'pb_notices' );
}

/**
 * @param $msg
 */
function add_notice( $msg ) {
	add( $msg, 'pb_notices' );
}

/**
 * Delete all notices
 */
function flush_all_notices() {
	flush_all( 'pb_notices' );
}

/**
 * @return array
 */
function get_all_errors() {
	return get_all( 'pb_errors' );
}

/**
 * @param $msg
 */
function add_error( $msg ) {
	add( $msg, 'pb_errors' );
}

/**
 * Delete all errors
 */
function flush_all_errors() {
	flush_all( 'pb_errors' );
}

/**
 * @param string $key
 *
 * @return array
 */
function get_all( $key ) {
	$messages = [];
	if ( ! empty( $_SESSION[ $key ] ) ) {
		// Array-ify the error(s).
		if ( ! is_array( $_SESSION[ $key ] ) ) {
			$_SESSION[ $key ] = [ $_SESSION[ $key ] ];
		}
		$messages = array_merge( $messages, $_SESSION[ $key ] );
	}
	$transient = get_site_transient( $key . get_current_user_id() );
	if ( ! empty( $transient ) ) {
		if ( ! is_array( $transient ) ) {
			$transient = [ $transient ];
		}
		$messages = array_merge( $messages, $transient );
	}
	return $messages;
}

/**
 * @param string $msg
 * @param string $key
 */
function add( $msg, $key ) {
	$use_non_blocking_session = use_non_blocking_session();
	$current_user_id = get_current_user_id();
	if ( $use_non_blocking_session ) {
		$messages = get_site_transient( "{$key}{$current_user_id}" );
	} else {
		$messages = $_SESSION[ $key ] ?? [];
	}
	if ( empty( $messages ) ) {
		$messages = [];
	}
	if ( ! is_array( $messages ) ) {
		$messages = [ $messages ];
	}
	$messages[] = $msg;
	if ( $use_non_blocking_session ) {
		set_site_transient( "{$key}{$current_user_id}", $messages, 15 * MINUTE_IN_SECONDS );
	} else {
		$_SESSION[ $key ] = $messages;
	}
}

/**
 * @param string $key
 */
function flush_all( $key ) {
	unset( $_SESSION[ $key ] );
	delete_site_transient( $key . get_current_user_id() );
}
