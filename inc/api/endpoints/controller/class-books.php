<?php

namespace Pressbooks\Api\Endpoints\Controller;

use function \Pressbooks\Metadata\book_information_to_schema;
use Pressbooks\DataCollector\Book as BookDataCollector;

class Books extends \WP_REST_Controller {

	/**
	 * Maximum number of books per page
	 *
	 * @var int
	 */
	protected $limit;

	/**
	 * @var int
	 */
	protected $totalBooks = 0;

	/**
	 * @var int
	 */
	protected $lastKnownBookId = 0;

	/**
	 * @var Metadata
	 */
	protected $metadata;

	/**
	 * @var array
	 */
	protected $linkCollector = [];

	/**
	 * @var BookDataCollector
	 */
	protected $bookDataCollector;

	/**
	 * Books
	 */
	public function __construct() {
		$this->namespace = 'pressbooks/v2';
		$this->rest_base = 'books';

		$this->limit = apply_filters( 'pb_api_books_limit', 10 );

		$this->metadata = new Metadata();
		$this->bookDataCollector = BookDataCollector::init();
	}

	/**
	 *  Registers routes for Books
	 */
	public function register_routes() {

		register_rest_route(
			$this->namespace, '/' . $this->rest_base, [
				[
					'methods' => \WP_REST_Server::READABLE,
					'callback' => [ $this, 'get_items' ],
					'permission_callback' => [ $this, 'get_items_permissions_check' ],
					'args' => $this->get_collection_params(),
				],
				'schema' => [ $this, 'get_public_item_schema' ],
			]
		);

		register_rest_route(
			$this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)', [
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
						'context' => $this->get_context_param(
							[
								'default' => 'view',
							]
						),
					],
				],
			]
		);
	}

	public function get_item_schema() {

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
			],
		];

		return $this->add_additional_fields_schema( $schema );
	}

	/**
	 * @return array
	 */
	public function get_collection_params() {

		$params = parent::get_collection_params();

		unset( $params['search'] ); // Fulltext search not supported

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
		if ( $this->bookDataCollector->get( $request['id'], BookDataCollector::PUBLIC ) ) {
			$allowed = true;
		}

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

		$response = rest_ensure_response( $this->listBooks( $request ) );
		$this->addPreviousNextLinks( $request, $response );

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
	 * Define route dependencies.
	 * Books content is built by querying a book, but those API routes may not exist at the root level.
	 */
	protected function registerRouteDependencies() {
		$this->metadata->register_routes();
	}

	// -------------------------------------------------------------------------------------------------------------------
	// List Books
	// -------------------------------------------------------------------------------------------------------------------

	/**
	 * Switches to a book, renders it for use in JSON response if found
	 *
	 * @param int $id
	 *
	 * @return array
	 */
	protected function renderBook( $id ) {

		$metadata_info_array = $this->bookDataCollector->get( $id, BookDataCollector::BOOK_INFORMATION_ARRAY );

		// https://github.com/pressbooks/pressbooks/issues/1797
		$keys = ['pb_word_count', 'pb_storage_size', 'pb_h5p_activities', 'pb_in_catalog'];
		$metadata_blog_meta = $this->bookDataCollector->getMultipleMeta($keys, $id);

		$metadata = array_merge( $metadata_info_array, $metadata_blog_meta );
		if ( is_array( $metadata ) && ! empty( $metadata ) ) {
			$metadata = book_information_to_schema( $metadata );
		} else {
			$metadata = [];
		}

		$item = [
			'id' => $id,
			'link' => get_blogaddress_by_id( $id ),
			'metadata' => $metadata,
		];

		$this->linkCollector['api'][] = [
			'href' => get_rest_url( $id ),
		];

		$this->linkCollector['metadata'][] = [
			'href' => get_rest_url( $id, '/pressbooks/v2/metadata' ),
		];

		$this->linkCollector['self'][] = [
			'href' => rest_url( sprintf( '%s/%s/%d', $this->namespace, $this->rest_base, $id ) ),
		];

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
		$conditions = 'public = 1 AND archived = 0 AND spam = 0 AND deleted = 0 AND blog_id != %d';

		if ( ! empty( $request['modified_since'] ) && is_numeric( $request['modified_since'] ) ) {
			$conditions .= sprintf( ' AND last_updated > \'%s\'', $request['modified_since'] );
		}

		// phpcs:disable WordPress.WP.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
		$blogs = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT SQL_CALC_FOUND_ROWS blog_id FROM {$wpdb->blogs}
				WHERE {$conditions}
				ORDER BY blog_id LIMIT %d, %d ", get_network()->site_id, $offset, $limit
			)
		);
		// phpcs:enable

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

}
