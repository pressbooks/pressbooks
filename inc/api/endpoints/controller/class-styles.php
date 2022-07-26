<?php

namespace Pressbooks\Api\Endpoints\Controller;

class Styles extends \WP_REST_Controller {

	/**
	 * @var string
	 */
	protected $namespace;

	/**
	 * @var
	 */
	protected $rest_base;

	/**
	 * Metadata
	 */
	public function __construct() {
		$this->namespace = 'pressbooks/v2';
		$this->rest_base = 'styles';
	}

	/**
	 * Register API route
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			$this->namespace, '/' . $this->rest_base, [
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
				'schema' => [ $this, 'get_public_item_schema' ],
			]
		);
	}

	/**
	 * Get schema for items
	 *
	 * @return array
	 */
	public function get_item_schema() : array {
		return $this->add_additional_fields_schema( [
			'title' => 'styles',
			'type' => 'object',
			'properties' => [
				'web' => [
					'type' => 'string',
					'description' => __( 'The styles for the web format of the book' ),
					'context' => [ 'view' ],
					'readonly' => true,
				],
				'epub' => [
					'type' => 'string',
					'description' => __( 'The styles for the epub format of the book' ),
					'context' => [ 'view' ],
					'readonly' => true,
				],
				'prince' => [
					'type' => 'string',
					'description' => __( 'The styles for the prince format of the book' ),
					'context' => [ 'view' ],
					'readonly' => true,
				],
			],
		] );
	}

	/**
	 * @param  \WP_REST_Request $request Full details about the request.
	 *
	 * @return bool True if the request has read access
	 */
	public function get_item_permissions_check( $request ) : bool {
		if ( has_filter( 'pb_set_api_items_permission' ) && apply_filters( 'pb_set_api_items_permission', $this->rest_base ) ) {
			return true;
		}
		return current_user_can( 'edit_posts' ) || get_option( 'blog_public' );
	}

	/**
	 * @param \WP_REST_Request $request Full data about the request.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_item( $request ) : \WP_REST_Response {
		$response = rest_ensure_response( \Pressbooks\Styles::getAllPostContent() );
		$this->linkCollector['self'] = [
			'href' => rest_url( sprintf( '%s/%s', $this->namespace, $this->rest_base ) ),
		];
		$response->add_links( $this->linkCollector );
		return $response;
	}
}
