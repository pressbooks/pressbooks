<?php
/*
Plugin Name: PressBooks
Plugin URI: http://www.pressbooks.com
Description: Simple Book Production
Version: 2.5
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
		ini_set( 'session.use_only_cookies', true );
		session_start();
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
// Minimum requirements
// -------------------------------------------------------------------------------------------------------------------

$pb_minimum_php = '5.4.0';
function _pb_minimum_php() {
	global $pb_minimum_php;
	echo '<div id="message" class="error fade"><p>';
	printf( __( 'PressBooks will not work with your version of PHP. PressBooks requires PHP version %s or greater. Please upgrade PHP if you would like to use PressBooks.', 'pressbooks' ), $pb_minimum_php );
	echo '</p></div>';
}
if ( ! version_compare( PHP_VERSION, $pb_minimum_php, '>=' ) ) {
	add_action( 'admin_notices', '_pb_minimum_php' );
	return;
}

$pb_minimum_wp = '4.2.1';
if ( ! is_multisite() || ! version_compare( get_bloginfo( 'version' ), $pb_minimum_wp, '>=' ) ) {

	add_action( 'admin_notices', function () use ( $pb_minimum_wp ) {
		echo '<div id="message" class="error fade"><p>';
		printf( __( 'PressBooks will not work with your version of WordPress. PressBooks requires a dedicated install of WordPress Multi-Site, version %s or greater. Please upgrade WordPress if you would like to use PressBooks.', 'pressbooks' ), $pb_minimum_wp );
		echo '</p></div>';
	} );

	return;
}

// -------------------------------------------------------------------------------------------------------------------
// Setup some defaults
// -------------------------------------------------------------------------------------------------------------------

if ( ! defined( 'PB_PLUGIN_DIR' ) )
	define ( 'PB_PLUGIN_DIR', __DIR__ . '/' ); // Must have trailing slash!

if ( ! defined( 'PB_PLUGIN_URL' ) )
	define ( 'PB_PLUGIN_URL', plugin_dir_url( __FILE__ ) ); // Must have trailing slash!

// -------------------------------------------------------------------------------------------------------------------
// Class autoloader
// -------------------------------------------------------------------------------------------------------------------

function _pressbooks_autoload( $class_name ) {

	$parts = explode( '\\', strtolower( $class_name ) );

	if ( strpos( @$parts[0], 'pressbooks' ) !== 0 ) {
		// Ignore classes not in our namespace
		return;
	}

	$look_for_class = array();

	if ( count( $parts ) > 1 && 'pressbooks' == @$parts[0] ) {
		// Namespaced, Ie. PressBooks\Export\Prince\Pdf()
		array_shift( $parts );
		$class_file = 'class-pb-' . str_replace( '_', '-', array_pop( $parts ) ) . '.php';
		$sub_path = count( $parts ) ? implode( '/', $parts ) . '/' : '';

		$look_for_class[] = PB_PLUGIN_DIR . $sub_path . $class_file;
		$look_for_class[] = PB_PLUGIN_DIR . "includes/modules/" . $sub_path . $class_file;

	} else {
		// Classic, Ie. PressBooks_Export()
		$class_file = 'class-' . str_replace( '_', '-', str_replace( 'pressbooks', 'pb', end( $parts ) ) ) . '.php';
	}

	$look_for_class[] = PB_PLUGIN_DIR . "admin/$class_file";
	array_unshift( $look_for_class, PB_PLUGIN_DIR . "includes/$class_file" ); // Most probable first

	foreach ( $look_for_class as $file ) {
		if ( is_file( $file ) ) {
			require_once( $file );
			if ( class_exists( $class_name ) ) {
				break;
			}
		}
	}
}

spl_autoload_register( '_pressbooks_autoload' );

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
