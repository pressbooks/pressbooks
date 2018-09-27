<?php

namespace Pressbooks\Api;

use function Pressbooks\Utility\str_starts_with;
use Pressbooks\Admin\Network\SharingAndPrivacyOptions;

/**
 * @return array
 */
function get_custom_post_types() {
	return [ 'front-matter', 'back-matter', 'part', 'chapter', 'glossary' ];
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
	( new Endpoints\Controller\SectionMetadata( 'glossary' ) )->register_routes();

	foreach ( get_custom_post_types() as $post_type ) {
		// Override Revisions routes for our custom post types
		if ( post_type_supports( $post_type, 'revisions' ) ) {
			( new Endpoints\Controller\Revisions( $post_type ) )->register_routes();
		}
	}

	foreach ( get_taxonomies() as $taxonomy ) {
		// Override custom taxonomy routes
		if ( in_array( $taxonomy, [ 'front-matter-type', 'chapter-type', 'back-matter-type', 'glossary-type', 'license', 'contributor' ], true ) ) {
			( new Endpoints\Controller\Terms( $taxonomy ) )->register_routes();
		}
	}

	// Add Part ID to chapters
	// We disable hierarchical mode but still want to use `post_parent`
	register_rest_field(
		'chapter', 'part', [
			'get_callback' => function ( $post_arr ) {
				return (int) get_post( $post_arr['id'] )->post_parent;
			},
			'update_callback' => __NAMESPACE__ . '\update_part_id',
			'schema' => [
				'description' => __( 'Part ID.', 'pressbooks' ),
				'type' => 'integer',
				'context' => [ 'view', 'edit', 'embed' ],
			],
		]
	);

	// Batch endpoint
	init_batch();

	// Gutenberg hack
	gutenberg_hack();
}

/**
 * Gutenberg hack
 *
 * Expose post types and taxonomies in REST /wp/v2/ namespace. Hacky way of getting our post types working with WordPress
 * without having to keep nagging them to fix their project for broader use cases.
 *
 * @see https://github.com/WordPress/gutenberg/issues/8683
 */
function gutenberg_hack() {
	if ( current_user_can( 'edit_posts' ) === false ) {
		// If the user cannot edit posts then don't turn on this hack.
		// Our Public/Private mechanisms are ignored by WP_REST_Controller(s) and we don't want these exposed to the world.
		return;
	}

	// TODO: Remove this once https://core.trac.wordpress.org/ticket/44864 is fixed.
	if ( is_multisite() ) {
		require_once( ABSPATH . 'wp-admin/includes/ms-admin-filters.php' );
		require_once( ABSPATH . 'wp-admin/includes/ms.php' );
		require_once( ABSPATH . 'wp-admin/includes/ms-deprecated.php' );
	}

	foreach ( get_post_types( [ 'show_in_rest' => true ], 'objects' ) as $post_type ) {
		if ( $post_type->rest_controller_class === '\Pressbooks\Api\Endpoints\Controller\Posts' ) {
			$controller = new \WP_REST_Posts_Controller( $post_type->name );
			$controller->register_routes();
			if ( post_type_supports( $post_type->name, 'revisions' ) ) {
				$revisions_controller = new \WP_REST_Revisions_Controller( $post_type->name );
				$revisions_controller->register_routes();
			}
		}
	}
	foreach ( get_taxonomies( [ 'show_in_rest' => true ], 'object' ) as $taxonomy ) {
		if ( $taxonomy->rest_controller_class === '\Pressbooks\Api\Endpoints\Controller\Terms' ) {
			$controller = new \WP_REST_Terms_Controller( $taxonomy->name );
			$controller->register_routes();
		}
	}
}

/**
 * Initialize REST API init for root site
 */
function init_root() {

	// Disabled up to here because we don't want them in the root site Admin UI...
	\Pressbooks\Metadata\init_book_data_models();

	// Register Books
	( new Endpoints\Controller\Books() )->register_routes();

	// Register Search
	( new Endpoints\Controller\Search() )->register_routes();
}

/**
 * Forked from:
 *
 * @author Joe Hoyle
 * @source https://gist.github.com/joehoyle/44a71a2e458b05c22a10
 *
 * The endpoint `/wp-json/pressbooks/v2/batch` allows a client to send multiple requests to the
 * server for processing in a single HTTP request. Each child request can have it's own
 * HTTP Method, Body, Headers, URL Params and Path.
 *
 * If the HTTP Method, Body or Headers are not supplied for each request, they will be
 * inherited from the HTTP request.
 *
 * The client must send an array of "request" objects in the `requests` param, the "request"
 * object can be a Path for shorthand (enabling the inheritance of HTTP Method, Body and Headers
 * from the HTTP request.)
 *
 * When not supplying only string Paths in the `requests` array, the "request" object must be in the
 * form of `{ path: '/some/path', headers: [], body: {}, method: 'POST' }`
 *
 * Example 1: Fetch all recent posts and all recent pages
 *
 * `curl example.com/wp-json/pressbooks/v2/batch?requests[]=/pressbooks/v2/front-matter&requests[]=/pressbooks/v2/back-matter`
 *
 * Example 2: Delete 2 posts
 *
 * `curl -X DELETE example.com/wp-json/pressbooks/v2/batch?requests[]=/pressbooks/v2/front-matter/1&requests[]=/pressbooks/v2/front-matter/2`
 *
 * Responses are in the form of a WP REST API enveloped response object. The HTTP request will return an
 * array of enveloped response objects in the order the `requests` parameter was passed.
 */
function init_batch() {
	register_rest_route(
		'pressbooks/v2', 'batch', [
			'methods' => \WP_REST_Server::ALLMETHODS,
			'args' => [
				'requests' => [],
			],
			'callback' => __NAMESPACE__ . '\batch_serve_request',
		]
	);
}

/**
 * @param \WP_REST_Request $request
 *
 * @return mixed
 */
function batch_serve_request( $request ) {
	if ( ! is_array( $request['requests'] ) ) {
		return new \WP_Error(
			'rest_invalid_param', __( 'Invalid batch parameter(s).', 'pressbooks' ), [
				'status' => 400,
			]
		);
	}
	/** @var \WP_REST_Server $wp_rest_server */
	global $wp_rest_server;
	$responses = [];
	foreach ( $request['requests'] as $single_request ) {
		if ( is_string( $single_request ) ) {
			$single_request = [
				'path' => $single_request,
			];
		}
		if ( empty( $single_request['method'] ) ) {
			$single_request['method'] = $_SERVER['REQUEST_METHOD'];
		}
		if ( empty( $single_request['body'] ) ) {
			$single_request['body'] = $_POST; // @codingStandardsIgnoreLine
		}
		if ( empty( $single_request['headers'] ) ) {
			$single_request['headers'] = $_SERVER;
		}

		$parsed_url = wp_parse_url( $single_request['path'] );
		$rest_request = new \WP_REST_Request( $single_request['method'], $parsed_url['path'] );
		$rest_request->set_headers( $wp_rest_server->get_headers( $single_request['headers'] ) );
		$rest_request->set_body_params( $single_request['body'] );
		if ( ! empty( $parsed_url['query'] ) ) {
			parse_str( $parsed_url['query'], $params );
			$rest_request->set_query_params( $params );
		}

		$result = $wp_rest_server->dispatch( $rest_request );
		$result = rest_ensure_response( $result );
		$result = apply_filters( 'rest_post_dispatch', rest_ensure_response( $result ), $wp_rest_server, $rest_request );
		$result = $wp_rest_server->envelope_response( $result, $rest_request->get_param( '_embed' ) );
		$responses[] = $result;
	}
	return $responses;
}

/**
 * Hide endpoints that don't work well with a book api
 *
 * @see https://github.com/WordPress/gutenberg/issues/8683
 *
 * @param array $endpoints
 *
 * @return array
 */
function hide_endpoints_from_book( $endpoints ) {
	// If the user cannot edit posts then hide certain WP endpoints.
	// Our Public/Private mechanisms are ignored by WP_REST_Controller(s) and we don't want these exposed to the world.
	if ( current_user_can( 'edit_posts' ) === false ) {
		foreach ( $endpoints as $key => $val ) {
			if ( str_starts_with( $key, '/wp/v2/media' ) ) {
				// Don't touch media
				continue;
			}
			if (
				( str_starts_with( $key, '/wp/v2/posts' ) ) ||
				( str_starts_with( $key, '/wp/v2/pages' ) ) ||
				( str_starts_with( $key, '/wp/v2/tags' ) ) ||
				( str_starts_with( $key, '/wp/v2/categories' ) ) ||
				( str_starts_with( $key, '/wp/v2/front-matter-type' ) ) ||
				( str_starts_with( $key, '/wp/v2/chapter-type' ) ) ||
				( str_starts_with( $key, '/wp/v2/back-matter-type' ) ) ||
				( str_starts_with( $key, '/wp/v2/glossary-type' ) ) ||
				( str_starts_with( $key, '/wp/v2/license' ) ) ||
				( str_starts_with( $key, '/wp/v2/contributor' ) ) ||
				( str_starts_with( $key, '/wp/v2' ) && strpos( $key, '/revisions' ) !== false ) ||
				( str_starts_with( $key, '/wp/v2' ) && strpos( $key, '/autosaves' ) !== false )
			) {
				unset( $endpoints[ $key ] );
			}
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
 * Filter to adjust the url returned by the get_rest_url() function
 *
 * @param string $url
 * @param string $path
 *
 * @return string
 */
function fix_book_urls( $url, $path ) {

	$wpns = 'wp/v2/';
	$pbns = 'pressbooks/v2/';

	if ( strpos( $path, $wpns ) !== false ) {
		$fixes = get_custom_post_types() + [ 'license', 'contributor' ];
		foreach ( $fixes as $fix ) {
			if ( strpos( $path, "{$wpns}{$fix}" ) !== false ) {
				$url = str_replace( $wpns, $pbns, $url );
				break;
			}
		}
	}

	return $url;
}

/**
 * @param \WP_REST_Response $response The response object.
 * @param \WP_Post $post The original attachment post.
 * @param \WP_REST_Request $request Request used to generate the response.
 *
 * @return \WP_REST_Response
 */
function fix_attachment( $response, $post, $request ) {
	// This filter is called twice in a single request.
	// The 1st time by `apply_filters( "rest_prepare_{$this->post_type}" ...` in parent class WP_REST_Posts_Controller::prepare_item_for_response
	// The 2nd time by `apply_filters( 'rest_prepare_attachment' ...` in child class WP_REST_Attachments_Controller::prepare_item_for_response
	if ( ! isset( $response->data['media_type'] ) ) {
		// Was called by the 1st, is not an attachment (yet), so bail
		return $response;
	}

	// Add raw data to view/embed contexts so that we can use it when cloning over REST API
	$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
	if ( $context === 'view' || $context === 'embed' ) {
		if ( isset( $response->data['title'] ) && ! isset( $response->data['title']['raw'] ) ) {
			$response->data['title']['raw'] = $post->post_title;
		}
		if ( isset( $response->data['description'] ) && ! isset( $response->data['description']['raw'] ) ) {
			$response->data['description']['raw'] = $post->post_content;
		}
		if ( isset( $response->data['caption'] ) && ! isset( $response->data['caption']['raw'] ) ) {
			$response->data['caption']['raw'] = $post->post_excerpt;
		}
	}

	return $response;
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
		return new \WP_Error(
			'rest_chapter_part_failed', __( 'Part does not exist', 'pressbooks' ), [
				'status' => 500,
			]
		);
	}
	if ( $part->post_type !== 'part' ) {
		return new \WP_Error(
			'rest_chapter_part_failed', __( 'ID is not a part', 'pressbooks' ), [
				'status' => 500,
			]
		);
	}

	$ret = wp_update_post(
		[
			'ID' => $post_obj->ID,
			'post_parent' => $part_id,
		]
	);
	if ( false === $ret ) {
		return new \WP_Error(
			'rest_chapter_part_failed', __( 'Failed to update chapter part', 'pressbooks' ), [
				'status' => 500,
			]
		);
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
