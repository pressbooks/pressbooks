<?php

namespace Pressbooks\Api\Endpoints\Controller;

class Books extends \WP_REST_Controller {

	/**
	 * Table of contents
	 */
	public function __construct() {
		$this->namespace = 'pressbooks/v2';
		$this->rest_base = 'books';
	}

	/**
	 *  Registers routes for TOC
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

}
