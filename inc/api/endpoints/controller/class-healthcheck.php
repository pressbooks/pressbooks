<?php

namespace Pressbooks\Api\Endpoints\Controller;

use Pressbooks\Health\Check;
use Pressbooks\Health\checks\CacheCheck;
use Pressbooks\Health\checks\DatabaseCheck;
use Pressbooks\Health\checks\FilesystemCheck;
use WP_REST_Controller;
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
			'permission_callback' => '__return_true',
		]);
	}

	public function healthCheck(): WP_REST_Response {
		$checks = [
			new CacheCheck,
			new DatabaseCheck,
			new FilesystemCheck,
		];

		$results = collect( $checks )->flatMap(fn( Check $check ) => [
			$check->getName() => $check->run(),
		]);

		$status = $results->filter(function( array $result ) {
			return $result['has_issue'] ?? false;
		})->isEmpty() ? 200 : 500;

		return rest_ensure_response(
			new WP_REST_Response( $results, $status )
		);
	}
}
