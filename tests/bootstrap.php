<?php

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = '/tmp/wordpress-tests-lib';
}

require_once $_tests_dir . '/includes/functions.php';

function _manually_load_plugin() {

	$dir = dirname( dirname( __FILE__ ) );

	// When running phpunit WP_PLUGIN_DIR is set to /tmp but Pressbooks is not located there. Override:
	define( 'PB_PLUGIN_DIR', "{$dir}/" );
	include_once( "{$dir}/compatibility.php" );

	require "{$dir}/pressbooks.php";
}

tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

require $_tests_dir . '/includes/bootstrap.php';
require 'utils-trait.php';
