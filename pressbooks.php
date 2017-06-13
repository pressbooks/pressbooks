<?php
/*
Plugin Name: Pressbooks
Plugin URI: https://pressbooks.com
Description: Simple Book Production
Version: 3.9.10
Author: Book Oven Inc.
Author URI: https://pressbooks.com
Text Domain: pressbooks
License: GPLv2
GitHub Plugin URI: https://github.com/pressbooks/pressbooks
Release Asset: true
Network: True
*/

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

// -------------------------------------------------------------------------------------------------------------------
// Turn on $_SESSION
// -------------------------------------------------------------------------------------------------------------------

function _pb_session_start() {
	if ( ! session_id() ) {
		if ( ! headers_sent() ) {
			ini_set( 'session.use_only_cookies', true );
			apply_filters( 'pressbooks_session_configuration', false );
			session_start();
		} else {
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

if ( ! defined( 'PB_PLUGIN_VERSION' ) ) {
	define( 'PB_PLUGIN_VERSION', '3.9.10' );
}

if ( ! defined( 'PB_PLUGIN_DIR' ) ) {
	define( 'PB_PLUGIN_DIR', ( is_link( WP_PLUGIN_DIR . '/pressbooks' ) ? trailingslashit( WP_PLUGIN_DIR . '/pressbooks' ) : trailingslashit( __DIR__ ) ) ); // Must have trailing slash!
}

if ( ! defined( 'PB_PLUGIN_URL' ) ) {
	define( 'PB_PLUGIN_URL', trailingslashit( plugins_url( 'pressbooks' ) ) ); // Must have trailing slash!
}

if ( ! defined( 'WP_DEFAULT_THEME' ) ) {
	if ( defined( 'PB_BOOK_THEME' ) ) {
		define( 'WP_DEFAULT_THEME', PB_BOOK_THEME );
	} else {
		define( 'WP_DEFAULT_THEME', 'pressbooks-book' );
	}
}

if ( ! defined( 'PB_ROOT_THEME' ) ) {
	define( 'PB_ROOT_THEME', 'pressbooks-publisher' );
}

// -------------------------------------------------------------------------------------------------------------------
// Class autoloader
// -------------------------------------------------------------------------------------------------------------------

function _pressbooks_autoload( $class_name ) {

	$prefix = 'Pressbooks\\';
	$len = strlen( $prefix );
	if ( strncasecmp( $prefix, $class_name, $len ) !== 0 ) {
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
// Composer autoloader (if needed)
// -------------------------------------------------------------------------------------------------------------------
if ( file_exists( $composer = PB_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
	require_once( $composer );
} else {
	if ( ! class_exists( '\Pimple\Container' ) ) {
		die( sprintf( __( 'Pressbooks dependencies are missing. Please make sure that your project&rsquo;s <a href="%1$s">Composer autoload file</a> is being required, or use the <a href="%2$s">latest release</a> instead.', 'pressbooks' ), 'https://getcomposer.org/doc/01-basic-usage.md#autoloading', 'https://github.com/pressbooks/pressbooks/releases/latest/' ) );
	}
}

// -------------------------------------------------------------------------------------------------------------------
// Check minimum requirements
// -------------------------------------------------------------------------------------------------------------------
if ( ! function_exists( 'pb_meets_minimum_requirements' ) && ! @include_once( PB_PLUGIN_DIR . 'compatibility.php' ) ) {
	add_action( 'admin_notices', function () {
		echo '<div id="message" class="error fade"><p>' . __( 'Cannot find Pressbooks install.', 'pressbooks' ) . '</p></div>';
	} );
	return;
} elseif ( ! pb_meets_minimum_requirements() ) {
	return;
}

// -------------------------------------------------------------------------------------------------------------------
// Configure root site
// -------------------------------------------------------------------------------------------------------------------

register_activation_hook( __FILE__, function () {
	$activate = new \Pressbooks\Activation();
	$activate->registerActivationHook();
} );

// -------------------------------------------------------------------------------------------------------------------
// Initialize
// -------------------------------------------------------------------------------------------------------------------

$GLOBALS['pressbooks'] = new \Pressbooks\Pressbooks();

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

/* The distinction between "the internet" & "books" will disappear in 5 years. Start adjusting now. */
