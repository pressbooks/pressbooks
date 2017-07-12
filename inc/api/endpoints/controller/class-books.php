<?php

namespace Pressbooks\Api\Endpoints\Controller;

use function Pressbooks\Api\get_custom_post_types;

class Books extends \WP_REST_Controller {

	/**
	 * Maximum number of books per page
	 *
	 * @var int
	 */
	protected $limit;

	/**
	 * Maximum amount of time for search, in seconds
	 */
	protected $maxSearchTime;

	/**
	 * @var int
	 */
	protected $totalBooks = 0;

	/**
	 * @var int
	 */
	protected $lastKnownBookId = 0;

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

		$this->limit = apply_filters( 'pb_api_books_limit', 10 );
		$this->maxSearchTime = apply_filters( 'pb_api_books_max_search_time', 15 );

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
				'metadata' => [
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
		$params['per_page']['maximum'] = $this->limit;
		$params['per_page']['default'] = $this->limit;

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
			$response = rest_ensure_response( $this->searchBooks( $request ) );
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

		$result = $this->renderBook( $request['id'] );
		$response = rest_ensure_response( $result );
		$response->add_links( $this->linkCollector );

		return $response;
	}

	/**
	 * @param int $last_known_book_id
	 */
	public function setLastKnownBookId( $last_known_book_id ) {
		$this->lastKnownBookId = $last_known_book_id;
	}

	/**
	 * @return int
	 */
	public function getLastKnownBookId() {
		return $this->lastKnownBookId;
	}

	/**
	 * Define route dependencies.
	 * Books content is built by querying a book, but those API routes may not exist at the root level.
	 */
	protected function registerRouteDependencies() {
		$this->toc->register_routes();
		$this->metadata->register_routes();
	}

	// -------------------------------------------------------------------------------------------------------------------
	// List Books
	// -------------------------------------------------------------------------------------------------------------------

	/**
	 * Switches to a book, renders it for use in JSON response if found
	 *
	 * @param int $id
	 * @param mixed $search (optional)
	 *
	 * @return array
	 */
	protected function renderBook( $id, $search = null ) {

		switch_to_blog( $id );

		// Search
		if ( ! empty( $search ) ) {
			if ( ! $this->find( $search ) ) {
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
			'metadata' => $this->prepare_response_for_collection( $response_metadata ),
			'toc' => $this->prepare_response_for_collection( $response_toc ),
		];

		$this->linkCollector['api'][] = [ 'href' => get_rest_url( $id ) ];

		restore_current_blog();

		$this->linkCollector['metadata'][] = [ 'href' => $item['metadata']['_links']['self'][0]['href'] ];
		unset( $item['metadata']['_links'] );

		$this->linkCollector['toc'][] = [ 'href' => $item['toc']['_links']['self'][0]['href'] ];
		foreach ( $item['toc']['_links']['metadata'] as $v ) {
			$this->linkCollector['metadata'][] = [ 'href' => $v['href'] ];
		}
		unset( $item['toc']['_links'] );

		$this->linkCollector['self'][] = [ 'href' => rest_url( sprintf( '%s/%s/%d', $this->namespace, $this->rest_base, $id ) ) ];

		return $item;
	}

	/**
	 * @param \WP_REST_Request
	 *
	 * @return array
	 */
	protected function listBooks( $request ) {
		$results = [];
		$book_ids = $this->listBookIds( $request );
		foreach ( $book_ids as $id ) {
			$response = rest_ensure_response( $this->renderBook( $id ) );
			$response->add_links( $this->linkCollector );
			$results[] = $this->prepare_response_for_collection( $response );
			$this->linkCollector = []; // re-initialize
			$this->lastKnownBookId = $id;
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

		$limit = ! empty( $request['per_page'] ) ? $request['per_page'] : $this->limit;
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
	 * Overridable find method for how to search a book
	 *
	 * @param mixed $search
	 *
	 * @return bool
	 */
	public function find( $search ) {

		if ( ! is_array( $search ) ) {
			$search = (array) $search;
		}

		foreach ( $search as $val ) {
			if ( $this->fulltextSearchInPost( $val ) !== false ) {
				return true;
			}
			if ( $this->fulltextSearchInMeta( $val ) !== false ) {
				return true;
			}
		}

		return false;
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
	 * Fulltext search entire book
	 *
	 * @param string $search
	 *
	 * @return bool
	 */
	protected function fulltextSearchInPost( $search ) {

		$s = $this->searchArgs( $search );
		$q = new \WP_Query( $s );

		// @codingStandardsIgnoreStart
		//
		// SELECT wp_2_posts.ID FROM wp_2_posts
		// WHERE 1=1 AND (((wp_2_posts.post_title LIKE '%Foo%') OR (wp_2_posts.post_excerpt LIKE '%Foo%') OR (wp_2_posts.post_content LIKE '%Foo%')))
		// AND (wp_2_posts.post_password = '')  AND wp_2_posts.post_type IN ('front-matter', 'back-matter', 'part', 'chapter') AND (wp_2_posts.post_status = 'publish')
		// ORDER BY wp_2_posts.post_title LIKE '%Vikings%' DESC, wp_2_posts.post_date DESC LIMIT 0, 10
		//
		// @codingStandardsIgnoreEnd

		if ( $q->post_count > 0 ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Fulltext search all `pb_` prefixed meta keys in metadata post
	 *
	 * @param string $search
	 *
	 * @return bool
	 */
	protected function fulltextSearchInMeta( $search ) {

		$meta = new \Pressbooks\Metadata();
		$data = $meta->getMetaPostMetadata();

		foreach ( $data as $key => $haystack ) {
			// Skip anything not prefixed with pb_
			if ( ! preg_match( '/^pb_/', $key ) ) {
				continue;
			}
			if ( is_array( $haystack ) ) {
				$haystack = implode( ' ', $haystack );
			}
			if ( stripos( $haystack, $search ) !== false ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Fulltext search
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return array
	 */
	protected function searchBooks( $request ) {

		if ( ! empty( $request['next'] ) ) {
			$this->lastKnownBookId = $request['next'];
		}

		$start_time = time();
		$searched_books = 0;
		$found_books = 0;
		$results = [];
		while ( $found_books < $request['per_page']  ) {
			$book_ids = $this->searchBookIds( $request );
			foreach ( $book_ids as $id ) {
				$node = $this->renderBook( $id, $request['search'] );
				if ( ! empty( $node ) ) {
					$response = rest_ensure_response( $node );
					$response->add_links( $this->linkCollector );
					$results[] = $this->prepare_response_for_collection( $response );
					$this->linkCollector = []; // re-initialize
					++$found_books;
				}
				++$searched_books;
				$this->lastKnownBookId = $id;
				if ( time() - $start_time > $this->maxSearchTime ) {
					break 2;
				}
			}
			if ( $searched_books >= $this->totalBooks ) {
				break;
			}
		}

		if ( ! $searched_books ) {
			$this->lastKnownBookId = 0;
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

		$limit = ! empty( $request['per_page'] ) ? $request['per_page'] : $this->limit;

		$blogs = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT SQL_CALC_FOUND_ROWS blog_id FROM {$wpdb->blogs} 
				 WHERE public = 1 AND archived = 0 AND spam = 0 AND deleted = 0 AND blog_id != %d AND blog_id > %d 
				 ORDER BY blog_id LIMIT  %d ", get_network()->site_id, $this->lastKnownBookId, $limit
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

		if ( $this->lastKnownBookId ) {
			unset( $request['page'] );
			$request_params = $request->get_query_params();
			$base = add_query_arg( $request_params, rest_url( sprintf( '%s/%s', $this->namespace, $this->rest_base ) ) );
			$next_link = add_query_arg( 'next', $this->lastKnownBookId, $base );
			$response->link_header( 'next', $next_link );
		}
	}

}
