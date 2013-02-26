<?php
/*
Plugin Name: PressBooks
Plugin URI: http://www.pressbooks.com
Description: Simple Book Production
Version: 2.0
Author: BookOven Inc.
Author URI: http://www.pressbooks.com
Text Domain: pressbooks
License: GPLv2
*/

if ( ! defined( 'ABSPATH' ) || ! is_multisite() )
	return;

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

	$look_for_class = array();

	$parts = explode( '\\', strtolower( $class_name ) );

	if ( ! preg_match( '/^pressbooks/', @$parts[0] ) ) {
		// Ignore classes not in our namespace
		return;
	}

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

require_once ( PB_PLUGIN_DIR . 'hooks.php' );

if ( is_admin() ) {
	require_once ( PB_PLUGIN_DIR . 'hooks-admin.php' );
}

// --------------------------------------------------------------------------------------------------------------------
// Shortcuts to help template designers who don't use real namespaces...
// --------------------------------------------------------------------------------------------------------------------

require_once ( PB_PLUGIN_DIR . 'functions.php' );


/* The distinction between "the internet" & "books" will disappear in 5 years. Start adjusting now. */
