<?php

namespace Pressbooks\Api\Endpoints\Controller;

use Pressbooks\Book;

class Theme extends \WP_REST_Controller {

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
		$this->rest_base = 'theme';
	}

	/**
	 * Register API route
	 *
	 * @return void
	 */
	public function register_routes() : void {
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
			'title' => 'Theme',
			'type' => 'object',
			'properties' => [
				'name' => [
					'type' => 'string',
					'description' => __( 'The theme\'s name' ),
					'context' => [ 'view' ],
					'readonly' => true,
				],
				'version' => [
					'type' => 'string',
					'description' => __( 'The theme\'s version' ),
					'context' => [ 'view' ],
					'readonly' => true,
				],
				'stylesheet' => [
					'type' => 'string',
					'description' => __( 'The theme\'s stylesheet' ),
					'context' => [ 'view' ],
					'readonly' => true,
				],
				'options' => [
					'type' => 'array',
					'description' => __( 'The theme options for the book' ),
					'items' => [
						'@type' => [
							'type' => 'string',
							'enum' => [
								'Option',
							],
							'description' => __( 'The theme option for the book' ),
							'context' => [ 'view' ],
							'readonly' => true,
						],
						'global' => [
							'type' => 'object',
							'description' => __( 'The global theme options of the book' ),
							'context' => [ 'view' ],
							'readonly' => true,
						],
						'web' => [
							'type' => 'object',
							'description' => __( 'The theme options for web version of the book' ),
							'context' => [ 'view' ],
							'readonly' => true,
						],
						'pdf' => [
							'type' => 'object',
							'description' => __( 'The theme options for pdf version of the book' ),
							'context' => [ 'view' ],
							'readonly' => true,
						],
						'ebook' => [
							'type' => 'object',
							'description' => __( 'The theme options for ebook version of the book' ),
							'context' => [ 'view' ],
							'readonly' => true,
						],
					],
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
		$theme = wp_get_theme();
		$response = rest_ensure_response( [
			'name' => $theme->get( 'Name' ),
			'version' => $theme->get( 'Version' ),
			'stylesheet' => $theme->get_stylesheet(),
			'options' => Book::getThemeOptions(),
		] );
		$this->linkCollector['self'] = [
			'href' => rest_url( sprintf( '%s/%s', $this->namespace, $this->rest_base ) ),
		];
		$response->add_links( $this->linkCollector );
		return $response;
	}
}
