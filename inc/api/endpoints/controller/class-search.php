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

		register_rest_route( $this->namespace, '/' . $this->rest_base, [
			[
				'methods' => \WP_REST_Server::READABLE,
				'callback' => [ $this, 'get_items' ],
				'permission_callback' => [ $this, 'get_items_permissions_check' ],
				'args' => $this->get_collection_params(),
			],
			'schema' => [ $this, 'get_public_item_schema' ],
		] );
	}

	/**
	 * @return array
	 */
	public function get_collection_params() {

		$params = parent::get_collection_params();
		unset( $params['page'] );
		unset( $params['search'] );

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

		$response = rest_ensure_response( $this->searchBooks( $request ) );
		$this->addNextSearchLinks( $request, $response );

		return $response;
	}


}
