<?php
/**
 * Plugin Name: Pressbooks REST API
 * Plugin URI: https://github.com/pressbooks/pb-api
 * Description: A JSON-based REST API for Pressbooks.
 * Version: 1.0
 * Author: Book Oven Inc.
 * Author URI: https://pressbooks.org/
 * License: GPLv2
 */

/**
 * Pressbooks\Modules\Api_v1\Api class.
 */
if ( ! class_exists( '\Pressbooks\Modules\Api_v1\Api' ) ) {
	require_once dirname( __FILE__ ) . '/includes/modules/api_v1/class-pb-api.php';
}

/**
 * Pressbooks\Modules\Api_v1\Books\BooksApi class.
 */
if ( ! class_exists( '\Pressbooks\Modules\Api_v1\Books\BooksApi' ) ) {
	require_once dirname( __FILE__ ) . '/includes/modules/api_v1/books/class-pb-booksapi.php';
}
