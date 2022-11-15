<?php

namespace Pressbooks\Api\Endpoints\Controller;

use Pressbooks\Health\Check;
use Pressbooks\Health\Checks\CacheCheck;
use Pressbooks\Health\Checks\DatabaseCheck;
use Pressbooks\Health\Checks\FilesystemCheck;
use Pressbooks\Health\Checks\ObjectCacheProCheck;
use Pressbooks\Health\Result;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

class HealthCheck extends WP_REST_Controller {
	public function __construct() {
		$this->namespace = 'pressbooks/v2';
		$this->rest_base = 'health-check';
	}

	public function register_routes(): void {
		register_rest_route($this->namespace, $this->rest_base, [
			'methods' => WP_REST_Server::READABLE,
			'callback' => [ $this, 'healthCheck' ],
			'permission_callback' => [ $this, 'authorize' ],
			'args' => [
				'_token' => [
					'description' => 'A token stored in your .env file as PB_HEALTH_CHECK_TOKEN and used to authorize the request.',
					'type' => 'string',
					'required' => true,
				],
			],
		]);
	}

	public function healthCheck(): WP_REST_Response {
		// TODO: allow users to customise the list of checks
		$checks = [
			new CacheCheck,
			new ObjectCacheProCheck,
			new DatabaseCheck,
			new FilesystemCheck,
		];

		$results = collect( $checks )
			->flatMap(fn( Check $check ) => [
				$check->getName() => $check->run(),
			]);

		$status = $results
			->filter( fn( Result $result ) => ! $result->status )
			->isEmpty() ? 200 : 500;

		return rest_ensure_response(
			new WP_REST_Response( $results, $status )
		);
	}

	public function authorize( WP_REST_Request $request ): bool {
		$value = $request->get_param( '_token' );
		$expected = env( 'PB_HEALTH_CHECK_TOKEN' );

		if ( $value !== $expected ) {
			return false;
		}

		return true;
	}
}
