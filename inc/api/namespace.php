<?php

namespace Pressbooks\Api;

use Pressbooks\Admin\Network\SharingAndPrivacyOptions;

/**
 * @return array
 */
function get_custom_post_types() {
	return [ 'front-matter', 'back-matter', 'part', 'chapter' ];
}

/**
 * @param \WP_REST_Response $response
 *
 * @return \WP_REST_Response
 */
function add_help_link( $response ) {
	$response->add_link( 'help', 'http://pressbooks.org/' );
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

	// Register TOC
	( new Endpoints\Controller\Toc() )->register_routes();

	// Register Book Metadata
	( new Endpoints\Controller\Metadata() )->register_routes();

	// Register Section Metadata
	( new Endpoints\Controller\SectionMetadata( 'front-matter' ) )->register_routes();
	( new Endpoints\Controller\SectionMetadata( 'back-matter' ) )->register_routes();
	( new Endpoints\Controller\SectionMetadata( 'chapter' ) )->register_routes();

	foreach ( get_custom_post_types() as $post_type ) {
		// Override Revisions routes for our custom post types
		if ( post_type_supports( $post_type, 'revisions' ) ) {
			( new Endpoints\Controller\Revisions( $post_type ) )->register_routes();
		}
	}

	foreach ( get_taxonomies() as $taxonomy ) {
		// Override Revisions routes for our custom post types
		if ( in_array( $taxonomy, [ 'front-matter-type', 'chapter-type', 'back-matter-type' ], true ) ) {
			( new Endpoints\Controller\Terms( $taxonomy ) )->register_routes();
		}
	}

	// Add Part ID to chapters
	// We disable hierarchical mode but still want to use `post_parent`
	register_rest_field( 'chapter', 'part', [
		'get_callback' => function ( $post_arr ) {
			return (int) get_post( $post_arr['id'] )->post_parent;
		},
		'update_callback' => __NAMESPACE__ . '\update_part_id',
		'schema' => [
			'description' => __( 'Part ID.', 'pressbooks' ),
			'type' => 'integer',
			'context' => [ 'view', 'edit', 'embed' ],
		],
	] );
}

/**
 * Initialize REST API init for root site
 */
function init_root() {

	// Register Books
	( new Endpoints\Controller\Books() )->register_routes();

	// Register Search
	( new Endpoints\Controller\Search() )->register_routes();
}

/**
 * Hide endpoints that don't work well with a book api
 *
 * @param array $endpoints
 *
 * @return array
 */
function hide_endpoints_from_book( $endpoints ) {

	foreach ( $endpoints as $key => $val ) {
		if (
			( strpos( $key, '/wp/v2/posts' ) === 0 ) ||
			( strpos( $key, '/wp/v2/pages' ) === 0 ) ||
			( strpos( $key, '/wp/v2/tags' ) === 0 ) ||
			( strpos( $key, '/wp/v2/categories' ) === 0 ) ||
			( strpos( $key, '/wp/v2/front-matter-type' ) === 0 ) ||
			( strpos( $key, '/wp/v2/chapter-type' ) === 0 ) ||
			( strpos( $key, '/wp/v2/back-matter-type' ) === 0 ) ||
			( strpos( $key, '/wp/v2' ) === 0 && strpos( $key, '/revisions' ) !== false )
		) {
			unset( $endpoints[ $key ] );
		}
	}

	ksort( $endpoints );
	return $endpoints;
}

/**
 * Hide endpoints from root api
 *
 * @param array $endpoints
 *
 * @return array
 */
function hide_endpoints_from_root( $endpoints ) {

	return $endpoints; // Nothing to hide
}

/**
 * @param string $url
 * @param string $path
 *
 * @return string
 */
function fix_book_urls( $url, $path ) {

	$wpns = 'wp/v2/';
	$pbns = 'pressbooks/v2/';

	if ( strpos( $path, $wpns ) !== false ) {
		foreach ( get_custom_post_types() as $post_type ) {
			if ( strpos( $path, "{$wpns}{$post_type}" ) !== false ) {
				$url = str_replace( $wpns, $pbns, $url );
				break;
			}
		}
	}

	return $url;
}

/**
 * Update part ID callback function
 *
 * @param int $part_id
 * @param \WP_Post $post_obj
 *
 * @return bool|\WP_Error
 */
function update_part_id( $part_id, $post_obj ) {

	$part = get_post( $part_id );
	if ( $part === null ) {
		return new \WP_Error( 'rest_chapter_part_failed', __( 'Part does not exist', 'pressbooks' ), [ 'status' => 500 ] );
	}
	if ( $part->post_type !== 'part' ) {
		return new \WP_Error( 'rest_chapter_part_failed', __( 'ID is not a part', 'pressbooks' ), [ 'status' => 500 ] );
	}

	$ret = wp_update_post( [ 'ID' => $post_obj->ID, 'post_parent' => $part_id ] );
	if ( false === $ret ) {
		return new \WP_Error( 'rest_chapter_part_failed', __( 'Failed to update chapter part', 'pressbooks' ), [ 'status' => 500 ] );
	}

	return true;
}

/**
 * @return bool
 */
function is_enabled() {
	$enable_network_api = get_site_option( 'pressbooks_sharingandprivacy_options', [] );
	$enable_network_api = isset( $enable_network_api['enable_network_api'] ) ? $enable_network_api['enable_network_api'] : SharingAndPrivacyOptions::getDefaults()['enable_network_api'];
	return (bool) $enable_network_api;
}
