<?php
/*
Plugin Name: Pressbooks
Plugin URI: https://pressbooks.org
GitHub Plugin URI: pressbooks/pressbooks
Release Asset: true
Description: Simple Book Production
Version: 5.14.6
Author: Pressbooks (Book Oven Inc.)
Author URI: https://pressbooks.org
Text Domain: pressbooks
License: GPL v3 or later
Network: True
*/

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

// -------------------------------------------------------------------------------------------------------------------
// Setup some defaults
// -------------------------------------------------------------------------------------------------------------------

// Note to developers: `PB_PLUGIN_VERSION` is set later in `pb_meets_minimum_requirements()`

if ( ! defined( 'PB_PLUGIN_DIR' ) ) {
	define( 'PB_PLUGIN_DIR', ( is_link( WP_PLUGIN_DIR . '/pressbooks' ) ? trailingslashit( WP_PLUGIN_DIR . '/pressbooks' ) : trailingslashit( __DIR__ ) ) ); // Must have trailing slash!
}

if ( ! defined( 'PB_PLUGIN_URL' ) ) {
	define( 'PB_PLUGIN_URL', trailingslashit( plugins_url( 'pressbooks' ) ) ); // Must have trailing slash!
}

function _pb_default_book_theme() {
	if ( ! defined( 'WP_DEFAULT_THEME' ) ) {
		if ( defined( 'PB_BOOK_THEME' ) ) {
			define( 'WP_DEFAULT_THEME', PB_BOOK_THEME );
		} else {
			define( 'WP_DEFAULT_THEME', get_site_option( 'pressbooks_default_book_theme', 'pressbooks-book' ) );
		}
	}
}
add_action( 'setup_theme', '_pb_default_book_theme', 1 );

/**
 * Set locale to UTF8 so escapeshellcmd() doesn't strip valid characters
 *
 * @since 4.3.5
 * @see https://bugs.php.net/bug.php?id=54391
 *
 * @param string $pb_lc_ctype
 * @return string
 */
$pb_lc_ctype = apply_filters( 'pb_lc_ctype', 'en_US.UTF-8' );
setlocale( LC_CTYPE, 'UTF8', $pb_lc_ctype );
putenv( "LC_CTYPE={$pb_lc_ctype}" );

// -------------------------------------------------------------------------------------------------------------------
// Turn on $_SESSION
// -------------------------------------------------------------------------------------------------------------------

require_once( PB_PLUGIN_DIR . 'inc/namespace.php' );
add_action( 'plugins_loaded', '\Pressbooks\session_start', 1 );
add_action( 'wp_logout', '\Pressbooks\session_kill' );
add_action( 'wp_login', '\Pressbooks\session_kill' );

// -------------------------------------------------------------------------------------------------------------------
// Composer autoloader (if needed)
// -------------------------------------------------------------------------------------------------------------------

$composer = PB_PLUGIN_DIR . 'vendor/autoload.php';
if ( file_exists( $composer ) ) {
	require_once( $composer );
} else {
	if ( ! class_exists( '\Illuminate\Container\Container' ) ) {
		/* translators: 1: URL to Composer documentation, 2: URL to Pressbooks latest releases */
		die( sprintf( __( 'Pressbooks dependencies are missing. Please make sure that your project&rsquo;s <a href="%1$s">Composer autoload file</a> is being required, or use the <a href="%2$s">latest release</a> instead.', 'pressbooks' ), 'https://getcomposer.org/doc/01-basic-usage.md#autoloading', 'https://github.com/pressbooks/pressbooks/releases/latest/' ) );
	}
}

// -------------------------------------------------------------------------------------------------------------------
// Check minimum requirements
// -------------------------------------------------------------------------------------------------------------------

if ( ! function_exists( 'pb_meets_minimum_requirements' ) && ! @include_once( PB_PLUGIN_DIR . 'compatibility.php' ) ) { // @codingStandardsIgnoreLine
	return add_action(
		'admin_notices', function () {
			echo '<div id="message" role="alert" class="error fade"><p>' . __( 'Cannot find Pressbooks install.', 'pressbooks' ) . '</p></div>';
		}
	);
} elseif ( ! pb_meets_minimum_requirements() ) {
	return;
}

// -------------------------------------------------------------------------------------------------------------------
// Class autoloader
// -------------------------------------------------------------------------------------------------------------------

pb_init_autoloader();

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
// Functions
// --------------------------------------------------------------------------------------------------------------------

require( PB_PLUGIN_DIR . 'functions.php' );

/* The distinction between "the internet" & "books" will disappear in 5 years. Start adjusting now. */
