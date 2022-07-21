<?php

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = '/tmp/wordpress-tests-lib';
}

require_once $_tests_dir . '/includes/functions.php';

if ( ! defined( 'DOCRAPTOR_API_KEY' ) ) {
	// YOUR_API_KEY_HERE is a valid test key
	// @see: https://docraptor.com/documentation
	define( 'DOCRAPTOR_API_KEY', 'YOUR_API_KEY_HERE' );
}
if ( ! defined( 'PB_MATHJAX_URL' ) ) {
	define( 'PB_MATHJAX_URL', 'http://localhost:3000' );
}

function _manually_load_plugin() {
	require_once( __DIR__ . '/../pressbooks.php' );
}

tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

require $_tests_dir . '/includes/bootstrap.php';
require 'utils-trait.php';

if ( ! defined( 'NONCE_KEY' ) ) {
	define( 'NONCE_KEY', '40~wF,SH)lm,Zr+^[b?_M8Z.g4gk%^gnqr+ZtnT,p6_K5.NuuN 0g@Y|T9+yBI|{' );
}

// Setup: Both sites and user accounts can be registered
update_site_option( 'registration', 'all' );
