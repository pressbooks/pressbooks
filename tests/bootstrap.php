<?php

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = '/tmp/wordpress-tests-lib';
}

if ( ! function_exists( '\HM\Autoloader\register_class_path' ) ) {
	require_once( __DIR__ . '/../hm-autoloader.php' );
}

require_once $_tests_dir . '/includes/functions.php';

function _manually_load_plugin() {
	require_once( __DIR__ . '/../hm-autoloader.php' );
	require_once( __DIR__ . '/../pressbooks.php' );
}

tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

require $_tests_dir . '/includes/bootstrap.php';
require 'utils-trait.php';
