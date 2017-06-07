<?php

namespace Pressbooks\Api\Endpoints\Controller;

class Books extends \WP_REST_Controller {

	/**
	 * @var int
	 */
	const LIMIT = 10;

	/**
	 * @var Toc
	 */
	private $toc;

	/**
	 * @var array
	 */
	private $linkCollector = [];

	/**
	 * Table of contents
	 */
	public function __construct() {
		$this->namespace = 'pressbooks/v2';
		$this->rest_base = 'books';
		$this->toc = new Toc();
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

	public function get_item_schema() {

		$toc = $this->toc->get_item_schema();

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
					// TODO
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
	 * @return \WP_REST_Response
	 */
	public function get_items( $request ) {

		// Register missing Toc routes
		$this->toc->register_routes();

		$request_internal = new \WP_REST_Request( 'GET', '/pressbooks/v2/toc' );

		$results = [];
		foreach ( $this->getBlogIds( $request ) as $blog_id ) {
			switch_to_blog( $blog_id );

			$response_toc = rest_do_request( $request_internal );
			$results[] = [
				'id' => $blog_id,
				'link' => get_blogaddress_by_id( $blog_id ),
				'meta' => [], // TODO
				'toc' => $response_toc->get_data(),
			];
			$this->linkCollector['books'][] = [ 'href' => get_rest_url( $blog_id ) ];

			restore_current_blog();
		}

		$response = rest_ensure_response( [ 'books' => $results ] );
		$response->add_links( $this->linkCollector );

		return $response;
	}

	/**
	 * @param \WP_REST_Request
	 *
	 * @return array blog ids
	 */
	private function getBlogIds( $request ) {

		global $wpdb;

		$limit = ! empty( $request['per_page'] ) ? $request['per_page'] : self::LIMIT;
		$offset = ! empty( $request['page'] ) ? ( $request['page'] - 1 ) * $limit : 0;

		$blogs = $wpdb->get_col(
			$wpdb->prepare( "SELECT blog_id FROM {$wpdb->blogs} WHERE public = 1 AND archived = 0 AND spam = 0 AND deleted = 0 AND blog_id != %d LIMIT %d, %d ", get_network()->site_id, $offset, $limit )
		);

		return $blogs;
	}

}
