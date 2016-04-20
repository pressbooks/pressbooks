<?php
/*
Plugin Name: Pressbooks
Plugin URI: http://www.pressbooks.com
Description: Simple Book Production
Version: 3.2
Author: BookOven Inc.
Author URI: http://www.pressbooks.com
Text Domain: pressbooks
License: GPLv2
*/

if ( ! defined( 'ABSPATH' ) )
	return;

// -------------------------------------------------------------------------------------------------------------------
// Turn on $_SESSION
// -------------------------------------------------------------------------------------------------------------------

function _pb_session_start() {
	if ( ! session_id() ) {
		if ( ! headers_sent() ) {
			ini_set( 'session.use_only_cookies', true );
			session_start();
		}
		else {
			error_log( 'There was a problem with _pb_session_start(), headers already sent!' );
		}
	}
}

function _pb_session_kill() {
	$_SESSION = array();
	session_destroy();
}

add_action( 'init', '_pb_session_start', 1 );
add_action( 'wp_logout', '_pb_session_kill' );
add_action( 'wp_login', '_pb_session_kill' );

// -------------------------------------------------------------------------------------------------------------------
// Setup some defaults
// -------------------------------------------------------------------------------------------------------------------

if ( ! defined( 'PB_PLUGIN_VERSION' ) )
	define ( 'PB_PLUGIN_VERSION', '3.2' );

if ( ! defined( 'PB_PLUGIN_DIR' ) )
	define ( 'PB_PLUGIN_DIR', __DIR__ . '/' ); // Must have trailing slash!

if ( ! defined( 'PB_PLUGIN_URL' ) )
	define ( 'PB_PLUGIN_URL', plugin_dir_url( __FILE__ ) ); // Must have trailing slash!

// -------------------------------------------------------------------------------------------------------------------
// Class autoloader
// -------------------------------------------------------------------------------------------------------------------

function _pressbooks_autoload( $class_name ) {

	$prefix = 'PressBooks\\';
	$len = strlen( $prefix );
	if ( strncmp( $prefix, $class_name, $len ) !== 0 ) {
		// Ignore classes not in our namespace
		return;
	}

	$parts = explode( '\\', strtolower( $class_name ) );
	array_shift( $parts );
	$class_file = 'class-pb-' . str_replace( '_', '-', array_pop( $parts ) ) . '.php';
	$path = count( $parts ) ? implode( '/', $parts ) . '/' : '';
	@include( PB_PLUGIN_DIR . 'includes/' . $path . $class_file );
}

spl_autoload_register( '_pressbooks_autoload' );

// -------------------------------------------------------------------------------------------------------------------
// Check minimum requirements
// -------------------------------------------------------------------------------------------------------------------

if ( ! @include_once( __DIR__ . '/compatibility.php' ) ) {
	add_action( 'admin_notices', function () {
		echo '<div id="message" class="error fade"><p>' . __( 'Cannot find Pressbooks install.', 'pressbooks' ) . '</p></div>';
	} );
	return;
}
elseif ( ! pb_meets_minimum_requirements() ) {
	return;
}

// -------------------------------------------------------------------------------------------------------------------
// Configure root site
// -------------------------------------------------------------------------------------------------------------------

register_activation_hook( __FILE__, function () {
	$activate = new \PressBooks\Activation();
	$activate->registerActivationHook();
} );

// -------------------------------------------------------------------------------------------------------------------
// Initialize
// -------------------------------------------------------------------------------------------------------------------

$GLOBALS['pressbooks'] = new \PressBooks\PressBooks();

// -------------------------------------------------------------------------------------------------------------------
// Hooks
// -------------------------------------------------------------------------------------------------------------------

require( PB_PLUGIN_DIR . 'hooks.php' );

if ( is_admin() ) {
	require( PB_PLUGIN_DIR . 'hooks-admin.php' );
}

// --------------------------------------------------------------------------------------------------------------------
// Shortcuts to help template designers who don't use real namespaces...
// --------------------------------------------------------------------------------------------------------------------

require( PB_PLUGIN_DIR . 'functions.php' );

// -------------------------------------------------------------------------------------------------------------------
// Override wp_mail()
// -------------------------------------------------------------------------------------------------------------------

if ( ! function_exists( 'wp_mail' ) && isset( $GLOBALS['PB_SECRET_SAUCE']['POSTMARK_API_KEY'] ) && isset( $GLOBALS['PB_SECRET_SAUCE']['POSTMARK_SENDER_ADDRESS'] ) ) {
	function wp_mail( $to, $subject, $message, $headers = '', $attachments = array() ) {
		return \PressBooks\Utility\wp_mail( $to, $subject, $message, $headers, $attachments );
	}
}

/* The distinction between "the internet" & "books" will disappear in 5 years. Start adjusting now. */
