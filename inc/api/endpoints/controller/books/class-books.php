<?php

namespace Pressbooks\Api\Endpoints\Controller\Books;

use function Pressbooks\Metadata\book_information_to_schema;
use function Pressbooks\Utility\apply_https_if_available;
use Pressbooks\Admin\Network\SharingAndPrivacyOptions;
use Pressbooks\Api\Endpoints\Controller\Books\BooksQueryBuilder;
use Pressbooks\Api\Endpoints\Controller\Metadata;
use Pressbooks\DataCollector\Book as BookDataCollector;
use Pressbooks\Licensing;

class Books extends \WP_REST_Controller {

	/**
	 * Maximum number of books per page
	 */
	protected mixed $limit;

	protected int $totalBooks = 0;

	protected int $lastKnownBookId = 0;

	protected Metadata $metadata;

	protected array $linkCollector = [];

	protected ?BookDataCollector $bookDataCollector;

	protected bool $networkExcludedDirectory = false;

	/**
	 * Books
	 */
	public function __construct() {
		$this->namespace = 'pressbooks/v2';
		$this->rest_base = 'books';
		$this->limit = apply_filters( 'pb_api_books_limit', 10 );
		$network_options = get_site_option( SharingAndPrivacyOptions::getSlug() );
		$this->networkExcludedDirectory = isset( $network_options[ SharingAndPrivacyOptions::NETWORK_DIRECTORY_EXCLUDED ] )
			&& (bool) $network_options[ SharingAndPrivacyOptions::NETWORK_DIRECTORY_EXCLUDED ];
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

		$params['modified_since'] = [
			'description' => __( 'Timestamp for updated field.', 'pressbooks' ),
			'type' => 'integer',
			'sanitize_callback' => 'absint',
		];

		$params['license_code'] = [
			'description' => __( 'Array of license codes to filter books.', 'pressbooks' ),
			'type' => 'array',
			'items' => [
				'type' => 'string',
			],
			'validate_callback' => function ( $param, $request, $key ) {
				if ( ! is_array( $param ) ) {
					return false;
				}

				$licensing = new Licensing;

				$supported_types = $licensing->getSupportedTypes();
				$supported_codes = array_map( fn ( $license ) => $license['abbreviation'], $supported_types );

				$values = array_map( fn ( $value ) => str_starts_with( $value, '-' ) ? substr( $value, 1 ) : $value, $param );

				return array_intersect( $values, array_values( $supported_codes ) ) === $values;
			},
		];

		$params['title'] = [
			'description' => __( 'Array of title filters to filter books.', 'pressbooks' ),
			'type' => 'array',
			'items' => [
				'type' => 'string',
			],
			'validate_callback' => function ( $param, $request, $key ) {
				return is_array( $param );
			},
		];

		$params['in_directory'] = [
			'description' => __( 'Boolean value to filter books by directory exclusion.', 'pressbooks' ),
			'type' => 'boolean',
			'default' => null,
		];

		$params['words'] = [
			'description' => __( 'String value to filter books by word count range.', 'pressbooks' ),
			'type' => 'string',
			'pattern' => '^gte_\d+|^lte_\d+$',
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

		$keys = [
			BookDataCollector::WORD_COUNT,
			BookDataCollector::STORAGE_SIZE,
			BookDataCollector::H5P_ACTIVITIES,
			BookDataCollector::IN_CATALOG,
			BookDataCollector::BOOK_URL,
			BookDataCollector::BOOK_DIRECTORY_EXCLUDED,
		];
		$metadata_blog_meta = $this->bookDataCollector->getMultipleMeta( $id, $keys );

		if ( ! isset( $metadata_blog_meta[ BookDataCollector::BOOK_DIRECTORY_EXCLUDED ] ) ) {
			$metadata_blog_meta[ BookDataCollector::BOOK_DIRECTORY_EXCLUDED ] = get_blog_option( $id, BookDataCollector::BOOK_DIRECTORY_EXCLUDED, 0 );
		}

		$blog_info = [
			'site_name' => get_site_option( 'site_name' ),
			'last_updated' => strtotime( get_blog_details( $id )->last_updated ),
		];

		$metadata_thumb['pb_thumbnail'] = $this->bookDataCollector->getCoverThumbnail( $id, $metadata_info_array['pb_cover_image'] );

		$metadata = array_merge( $metadata_info_array, $metadata_blog_meta, $blog_info, $metadata_thumb );
		$metadata = ( is_array( $metadata ) && ! empty( $metadata ) ) ? book_information_to_schema( $metadata, $this->networkExcludedDirectory ) : [];

		$item = [
			'id' => $id,
			'link' => apply_https_if_available( get_blogaddress_by_id( $id ) ),
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
	 * @param \WP_REST_Request $request
	 *
	 * @return array blog ids
	 */
	protected function listBookIds( \WP_REST_Request $request ): array {

		$book_query_builder = new BooksQueryBuilder();
		$blogs = $book_query_builder->build( $request )->get();
		$this->totalBooks = $book_query_builder->getNumberOfRows();

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
