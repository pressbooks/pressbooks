<?php

namespace Pressbooks\Api\Endpoints\Controller;

use function Pressbooks\Api\get_custom_post_types;

class Books extends \WP_REST_Controller {

	/**
	 * Maximum number of books per page
	 *
	 * @var int
	 */
	const LIMIT = 10;

	/**
	 * Maximum amount of time for search, in seconds
	 */
	const MAX_SEARCH_TIME = 15;

	/**
	 * @var int
	 */
	protected $totalBooks = 0;

	/**
	 * @var int
	 */
	protected $lastFoundBookId = 0;

	/**
	 * @var Toc
	 */
	protected $toc;

	/**
	 * @var Metadata
	 */
	protected $metadata;

	/**
	 * @var array
	 */
	protected $linkCollector = [];

	/**
	 * Books
	 */
	public function __construct() {
		$this->namespace = 'pressbooks/v2';
		$this->rest_base = 'books';
		$this->toc = new Toc();
		$this->metadata = new Metadata();
	}

	/**
	 *  Registers routes for Books
	 */
	public function register_routes() {

		register_rest_route( $this->namespace, '/' . $this->rest_base, [
			[
				'methods' => \WP_REST_Server::READABLE,
				'callback' => [ $this, 'get_items' ],
				'permission_callback' => [ $this, 'get_items_permissions_check' ],
				'args' => $this->get_collection_params(),
			],
			'schema' => [ $this, 'get_public_item_schema' ],
		] );

		register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)', [
			'args' => [
				'id' => [
					'description' => __( 'Unique identifier for the object.' ),
					'type' => 'integer',
				],
			],
			[
				'methods' => \WP_REST_Server::READABLE,
				'callback' => [ $this, 'get_item' ],
				'permission_callback' => [ $this, 'get_item_permissions_check' ],
				'args' => [
					'context' => $this->get_context_param( [ 'default' => 'view' ] ),
				],
			],
		] );
	}

	public function get_item_schema() {

		$toc = $this->toc->get_item_schema();
		$metadata = $this->metadata->get_item_schema();

		$schema = [
			'$schema' => 'http://json-schema.org/schema#',
			'title' => 'book',
			'type' => 'object',
			'properties' => [
				'id' => [
					'description' => __( 'Unique identifier for the object.' ),
					'type' => 'integer',
					'context' => [ 'view' ],
					'readonly' => true,
				],
				'link' => [
					'description' => __( 'URL to the object.' ),
					'type' => 'string',
					'format' => 'uri',
					'context' => [ 'view' ],
					'readonly' => true,
				],
				'meta' => [
					'description' => __( 'Metadata', 'pressbooks' ),
					'type' => 'object',
					'properties' => $metadata['properties'],
					'context' => [ 'view' ],
					'readonly' => true,
				],
				'toc' => [
					'description' => __( 'Table of Contents', 'pressbooks' ),
					'type' => 'object',
					'properties' => $toc['properties'],
					'context' => [ 'view' ],
					'readonly' => true,
				],
			],
		];

		return $this->add_additional_fields_schema( $schema );
	}

	/**
	 * @return array
	 */
	public function get_collection_params() {

		$params = parent::get_collection_params();

		$params['context']['default'] = 'view';
		$params['per_page']['maximum'] = self::LIMIT;

		$params['next'] = [
			'description' => __( 'ID offset, overrides page.', 'pressbooks' ),
			'type' => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		];

		return $params;
	}

	/**
	 * @param \WP_REST_Request $request
	 *
	 * @return bool
	 */
	public function get_items_permissions_check( $request ) {

		return true;
	}

	/**
	 * @param \WP_REST_Request $request
	 *
	 * @return bool
	 */
	public function get_item_permissions_check( $request ) {

		if ( $request['id'] === get_network()->site_id ) {
			return false;
		}

		$allowed = false;

		switch_to_blog( $request['id'] );

		if ( 1 === absint( get_option( 'blog_public' ) ) ) {
			$allowed = true;
		}

		restore_current_blog();

		return $allowed;
	}

	/**
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_REST_Response
	 */
	public function get_items( $request ) {

		// Register missing routes
		$this->registerRouteDependencies();

		if ( ! empty( $request['search'] ) ) {
			$s = $this->searchArgs( $request['search'] );
			$m = []; // TODO: https://codex.wordpress.org/Class_Reference/WP_Meta_Query
			$response = rest_ensure_response( $this->searchBooks( $request, $s, $m, (int) $request['per_page'] ) );
			$this->addNextSearchLinks( $request, $response );
		} else {
			$response = rest_ensure_response( $this->listBooks( $request ) );
			$this->addPreviousNextLinks( $request, $response );
		}

		return $response;
	}

	/**
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_REST_Response
	 */
	public function get_item( $request ) {

		// Register missing routes
		$this->registerRouteDependencies();

		$result = $this->renderNode( $request['id'] );
		$response = rest_ensure_response( $result );
		$response->add_links( $this->linkCollector );

		return $response;
	}

	/**
	 * Define route dependencies.
	 * Books content is built by querying a book, but those API routes may not exist at the root level.
	 */
	protected function registerRouteDependencies() {
		$this->toc->register_routes();
		$this->metadata->register_routes();
	}

	/**
	 * Args for \WP_Query
	 *
	 * @see https://codex.wordpress.org/Class_Reference/WP_Query
	 *
	 * @param string $s
	 *
	 * @return array
	 */
	protected function searchArgs( $s ) {
		$s = [
			's' => $s,
			'post_type' => get_custom_post_types(),
			'fields' => 'ids', // Optimize: skip the unwanted returned array of WP_Post properties
			'no_found_rows' => true, // Optimize: only interested in post count
		];

		return $s;
	}

	/**
	 * Render an item node for use in JSON response
	 *
	 * @param int $id
	 * @param array $s (optional) args for \WP_Query
	 * @param array $m (optional) args for \WP_Meta_Query
	 *
	 * @return array
	 */
	protected function renderNode( $id, $s = [], $m = [] ) {

		switch_to_blog( $id );

		// Search
		if ( ! empty( $s ) ) {
			$q = new \WP_Query( $s );
			if ( $q->post_count < 1 ) {
				restore_current_blog();
				return [];
			}
		}

		$request_metadata = new \WP_REST_Request( 'GET', '/pressbooks/v2/metadata' );
		$response_metadata = rest_do_request( $request_metadata );

		$request_toc = new \WP_REST_Request( 'GET', '/pressbooks/v2/toc' );
		$response_toc = rest_do_request( $request_toc );

		$item = [
			'id' => $id,
			'link' => get_blogaddress_by_id( $id ),
			'meta' => $this->prepare_response_for_collection( $response_metadata ),
			'toc' => $this->prepare_response_for_collection( $response_toc ),
		];

		$this->linkCollector['api'][] = [ 'href' => get_rest_url( $id ) ];

		restore_current_blog();

		$this->linkCollector['self'][] = [ 'href' => rest_url( sprintf( '%s/%s/%d', $this->namespace, $this->rest_base, $id ) ) ];

		return $item;
	}

	// -------------------------------------------------------------------------------------------------------------------
	// List Books
	// -------------------------------------------------------------------------------------------------------------------

	/**
	 * @param \WP_REST_Request
	 *
	 * @return array
	 */
	protected function listBooks( $request ) {
		$results = [];
		$book_ids = $this->listBookIds( $request );
		foreach ( $book_ids as $id ) {
			$response = rest_ensure_response( $this->renderNode( $id ) );
			$response->add_links( $this->linkCollector );
			$results[] = $this->prepare_response_for_collection( $response );
			$this->linkCollector = []; // re-initialize
			$this->lastFoundBookId = $id;
		}
		return $results;
	}

	/**
	 * Count all books, update $this->>totalBooks, return a paginated subset of book ids
	 *
	 * @param \WP_REST_Request
	 *
	 * @return array blog ids
	 */
	protected function listBookIds( $request ) {

		global $wpdb;

		$limit = ! empty( $request['per_page'] ) ? $request['per_page'] : self::LIMIT;
		$offset = ! empty( $request['page'] ) ? ( $request['page'] - 1 ) * $limit : 0;

		$blogs = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT SQL_CALC_FOUND_ROWS blog_id FROM {$wpdb->blogs} 
				WHERE public = 1 AND archived = 0 AND spam = 0 AND deleted = 0 AND blog_id != %d 
				ORDER BY blog_id LIMIT %d, %d ", get_network()->site_id, $offset, $limit
			)
		);

		$this->totalBooks = $wpdb->get_var( 'SELECT FOUND_ROWS()' );

		return $blogs;
	}

	/**
	 * Add previous/next links like it's done in WP-API
	 *
	 * @param \WP_REST_Request $request
	 * @param \WP_REST_Response $response
	 */
	protected function addPreviousNextLinks( $request, $response ) {

		$page = (int) $request['page'];
		$max_pages = (int) ceil( $this->totalBooks / (int) $request['per_page'] );

		$response->header( 'X-WP-Total', (int) $this->totalBooks );
		$response->header( 'X-WP-TotalPages', $max_pages );

		$request_params = $request->get_query_params();
		$base = add_query_arg( $request_params, rest_url( sprintf( '%s/%s', $this->namespace, $this->rest_base ) ) );

		if ( $page > 1 ) {
			$prev_page = $page - 1;
			if ( $prev_page > $max_pages ) {
				$prev_page = $max_pages;
			}
			$prev_link = add_query_arg( 'page', $prev_page, $base );
			$response->link_header( 'prev', $prev_link );
		}
		if ( $max_pages > $page ) {
			$next_page = $page + 1;
			$next_link = add_query_arg( 'page', $next_page, $base );
			$response->link_header( 'next', $next_link );
		}
	}

	// -------------------------------------------------------------------------------------------------------------------
	// Search Books
	// -------------------------------------------------------------------------------------------------------------------

	/**
	 * @param \WP_REST_Request
	 * @param array $s args for \WP_Query
	 * @param array $m args for \WP_Meta_Query
	 * @param int $per_page
	 *
	 * @return array
	 */
	protected function searchBooks( $request, $s, $m, $per_page ) {

		if ( ! empty( $request['next'] ) ) {
			$this->lastFoundBookId = $request['next'];
		}

		$start_time = time();
		$searched_books = 0;
		$found_books = 0;
		$results = [];
		while ( $found_books < $per_page ) {
			$book_ids = $this->searchBookIds( $request );
			foreach ( $book_ids as $id ) {
				$node = $this->renderNode( $id, $s, $m );
				if ( ! empty( $node ) ) {
					$response = rest_ensure_response( $node );
					$response->add_links( $this->linkCollector );
					$results[] = $this->prepare_response_for_collection( $response );
					$this->linkCollector = []; // re-initialize
					++$found_books;
				}
				++$searched_books;
				$this->lastFoundBookId = $id;
				if ( time() - $start_time > self::MAX_SEARCH_TIME ) {
					break 2;
				}
			}
			if ( $searched_books >= $this->totalBooks ) {
				break;
			}
		}

		if ( ! $searched_books ) {
			$this->lastFoundBookId = 0;
		}

		return $results;
	}

	/**
	 * Count all books, update $this->>totalBooks, return a paginated subset of book ids
	 *
	 * @param \WP_REST_Request
	 *
	 * @return array blog ids
	 */
	protected function searchBookIds( $request ) {

		global $wpdb;

		$limit = ! empty( $request['per_page'] ) ? $request['per_page'] : self::LIMIT;

		$blogs = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT SQL_CALC_FOUND_ROWS blog_id FROM {$wpdb->blogs} 
				 WHERE public = 1 AND archived = 0 AND spam = 0 AND deleted = 0 AND blog_id != %d AND blog_id > %d 
				 ORDER BY blog_id LIMIT  %d ", get_network()->site_id, $this->lastFoundBookId, $limit
			)
		);

		$this->totalBooks = $wpdb->get_var( 'SELECT FOUND_ROWS()' );

		return $blogs;
	}

	/**
	 * Add a next link for search results
	 *
	 * @param \WP_REST_Request $request
	 * @param \WP_REST_Response $response
	 */
	protected function addNextSearchLinks( $request, $response ) {

		$max_pages = (int) ceil( $this->totalBooks / (int) $request['per_page'] );

		$response->header( 'X-WP-Total', (int) $this->totalBooks );
		$response->header( 'X-WP-TotalPages', $max_pages );

		if ( $this->lastFoundBookId ) {
			unset( $request['page'] );
			$request_params = $request->get_query_params();
			$base = add_query_arg( $request_params, rest_url( sprintf( '%s/%s', $this->namespace, $this->rest_base ) ) );
			$next_link = add_query_arg( 'next', $this->lastFoundBookId, $base );
			$response->link_header( 'next', $next_link );
		}
	}

}
