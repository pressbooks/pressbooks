<?php

namespace Pressbooks\Api;

/**
 * @param \WP_REST_Response $response
 *
 * @return \WP_REST_Response
 */
function add_help_link( $response ) {

	$response->add_link( 'help', 'http://pressbooks.dev/api/v1/docs' );
	return $response;
}

/**
 * Initialize REST API for book
 *
 * There are a couple ways to initialize REST endpoints in WP. One is passing `show_in_rest`, `rest_base`, and/or `rest_controller_class`
 * arguments to `register_post_type()`, another is the `rest_api_init` action. This function covers the latter.
 *
 * @see \Pressbooks\PostType\register_post_types
 * @see https://developer.wordpress.org/rest-api/extending-the-rest-api/
 */
function init_book() {
	foreach ( [ 'chapter', 'part', 'front-matter', 'back-matter' ] as $post_type ) {
		if ( post_type_supports( $post_type, 'revisions' ) ) {
			$revisions_controller = new Endpoints\Controller\Revisions( $post_type );
			$revisions_controller->register_routes();
		}
	}
}

/**
 * Initialize REST API init for root site
 */
function init_root() {
	// TODO
}

/**
 * @param array $endpoints
 *
 * @return array
 */
function hide_incompatible_endpoints( $endpoints ) {

	foreach ( $endpoints as $key => $val ) {
		if (
			( strpos( $key, '/wp/v2/posts' ) === 0 ) ||
			( strpos( $key, '/wp/v2/pages' ) === 0 ) ||
			( strpos( $key, '/wp/v2' ) === 0 && strpos( $key, '/revisions' ) !== false )
		) {
			unset( $endpoints[ $key ] );
		}
	}

	ksort( $endpoints );
	return $endpoints;
}
