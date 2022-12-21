<?php

namespace Pressbooks\Api\Endpoints\Controller;

use Pressbooks\CloneTokens;

class CloneComplete extends \WP_REST_Controller {

	/**
	 * @var string
	 */
	protected $namespace = 'pressbooks/v2/clone';

	/**
	 * @var string
	 */
	protected $rest_base = 'complete';

	/**
	 * @param CloneTokens $clone_tokens
	 */
	public function __construct( protected CloneTokens $clone_tokens ) {
	}

	/**
	 * Register the routes for the objects of the controller.
	 */
	public function register_routes(): void {
		register_rest_route(
			$this->namespace, '/' . $this->rest_base, [
				[
					'methods' => \WP_REST_Server::CREATABLE,
					'callback' => [ $this, 'cloneComplete' ],
					'permission_callback' => '__return_true',
					'args' => [
						'token' => [
							'required' => true,
							'validate_callback' => [ $this, 'validateToken' ],
						],
						'url' => [
							'required' => true,
							'validate_callback' => [ $this, 'validateUrl' ],
						],
						'name' => [
							'required' => true,
						],
					],
				],
			]
		);
	}

	/**
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_REST_Response
	 */
	public function cloneComplete( \WP_REST_Request $request ): \WP_REST_Response {
		global $blog_id;
		( new \Pressbooks\CloneComplete() )->store(
			$blog_id,
			$request->get_param( 'url' ),
			$request->get_param( 'name' )
		);
		return new \WP_REST_Response( [ 'success' => true ], 200 );
	}

	/**
	 * @param string $token
	 *
	 * @return bool
	 */
	public function validateToken( string $token ): bool {
		return $this->clone_tokens->isTokenValid( $token );
	}

	/**
	 * @param string $url
	 *
	 * @return bool
	 */
	public function validateUrl( string $url ): bool {
		return filter_var( $url, FILTER_VALIDATE_URL );
	}

}
