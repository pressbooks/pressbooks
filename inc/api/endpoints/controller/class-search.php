<?php

namespace Pressbooks\Api\Endpoints\Controller;

class Search extends Books {

	/**
	 * Search
	 */
	public function __construct() {
		parent::__construct();
		$this->rest_base = 'books/search';
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
	}

	/**
	 * @return array
	 */
	public function get_collection_params() {

		$params = parent::get_collection_params();

		unset( $params['page'] );
		unset( $params['search'] );

		$params['*'] = [
			'description' => __( 'Any meta key, comma separated values, limit of 5.', 'pressbooks' ),
			'type' => 'string',
		];

		return $params;
	}


	/**
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_REST_Response
	 */
	public function get_items( $request ) {

		// Register missing routes
		$this->registerRouteDependencies();

		// Override search request
		$search = $request->get_query_params();
		unset( $search['per_page'], $search['next'] );
		$request['search'] = ! empty( $search ) ? $search : [
			null => null,
		]; // Set some weird value that means abort

		$response = rest_ensure_response( $this->searchBooks( $request ) );
		unset( $request['search'] );

		$this->addNextSearchLinks( $request, $response );

		return $response;
	}

	/**
	 * Overridable find method for how to search a book
	 *
	 * @param mixed $search
	 *
	 * @return bool
	 */
	public function find( $search ) {

		if ( ! is_array( $search ) ) {
			wp_die( 'LogicException: $search should be a [meta_key => val] array' );
		}

		if ( $search === [
			null => null,
		] ) {
			return false; // Abort search
		}

		if ( $this->paramSearchMeta( $search ) !== false ) {
			return true;
		}

		return false;
	}

	/**
	 * Parameter based search
	 *
	 * @param array $search
	 *
	 * @return bool
	 */
	protected function paramSearchMeta( array $search ) {

		// Book Metadata
		$request_metadata = new \WP_REST_Request( 'GET', '/pressbooks/v2/metadata' );
		$response_metadata = rest_do_request( $request_metadata );
		$book_metadata = $response_metadata->get_data();
		if ( $this->keyValueSearchInMeta( $search, $book_metadata ) ) {
			return true;
		}

		// Chapter Metadata
		$request_metadata = new \WP_REST_Request( 'GET', '/pressbooks/v2/toc' );
		$response_metadata = rest_do_request( $request_metadata );
		$toc = $response_metadata->get_data();
		foreach ( $toc['front-matter'] as $fm ) {
			if ( $this->keyValueSearchInMeta( $search, $fm['metadata'] ) ) {
				return true;
			}
		}
		foreach ( $toc['parts'] as $p ) {
			foreach ( $p['chapters'] as $ch ) {
				if ( $this->keyValueSearchInMeta( $search, $ch['metadata'] ) ) {
					return true;
				}
			}
		}
		foreach ( $toc['back-matter'] as $bm ) {
			if ( $this->keyValueSearchInMeta( $search, $bm['metadata'] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * subjects=biology,technology&keywords=education
	 * returns all books in a collection with either subject 'biology' OR 'technology' AND the keyword 'education',
	 * (where 'subjects' and 'keywords' are keys in $metadata )
	 *
	 * @param array $search
	 * @param array $metadata
	 *
	 * @return bool
	 */
	protected function keyValueSearchInMeta( array $search, array $metadata ) {

		$found = [];
		foreach ( $search as $search_key => $needle ) {
			$found[ $search_key ] = false;
			if ( isset( $metadata[ $search_key ] ) ) {
				$haystack = is_array( $metadata[ $search_key ] ) ? $metadata[ $search_key ] : (array) $metadata[ $search_key ];
				foreach ( $haystack as $hay ) {
					if ( false !== strpos( $needle, ',' ) ) { // look for more than one search word
						$needles = array_slice( explode( ',', $needle ), 0, 5 ); // prevent excessive requests
					} else {
						$needles = (array) $needle;
					}
					foreach ( $needles as $pin ) {
						if ( stripos( $hay, trim( $pin ) ) !== false ) {
							$found[ $search_key ] = true;
							break 2;
						}
					}
				}
			}
		}

		foreach ( $found as $key => $val ) {
			if ( $val === false ) {
				return false;
			}
		}

		return true;
	}

}
