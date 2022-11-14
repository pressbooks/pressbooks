<?php

use Pressbooks\Health\Checks\CacheCheck;
use Pressbooks\Health\Checks\DatabaseCheck;
use Pressbooks\Health\Checks\FilesystemCheck;

class HealthCheckTest extends \WP_UnitTestCase {
	use utilsTrait;

	/**
	 * @group health-check
	 */
	public function test_health_check_endpoint_exists(): void {
		$server = $this->_setupRootApi();

		$request = new WP_REST_Request( 'OPTIONS', '/pressbooks/v2/health-check' );

		$data = $server->dispatch( $request )->get_data();

		$this->assertEquals( 'pressbooks/v2', $data['namespace'] );
	}

	/**
	 * @group health-check
	 */
	public function test_health_check_endpoint_success_response(): void {
		$server = $this->_setupRootApi();

		$request = new WP_REST_Request( 'GET', '/pressbooks/v2/health-check' );

		$response = $server->dispatch( $request );

//		/** @var \Illuminate\Support\Collection $data */
		$data = $response->get_data();

		$this->assertEquals( 200, $response->status );

		$this->assertEquals( [
			'cache' => [
				'status' => 'Ok',
				'message' => '',
			],
			'object-cache-pro' => [
				'status' => 'Ok',
				'message' => 'Object Cache Pro plugin is not installed.',
			],
			'database' => [
				'status' => 'Ok',
				'message' => '',
			],
			'filesystem' => [
				'status' => 'Ok',
				'message' => '',
			]
		], $data->toArray() );
	}

	/**
	 * @group health-check
	 */
	public function test_checks_database_connection(): void {
		$result = (new DatabaseCheck)->run();

		$this->assertEquals([
			'status' => 'Ok',
			'message' => '',
		], $result->toArray());
	}

	/**
	 * @group health-check
	 */
	public function test_checks_cache_connection(): void {
		$result = (new CacheCheck)->run();

		$this->assertEquals( [
			'status' => 'Ok',
			'message' => '',
		], $result->toArray() );
	}

	/**
	 * @group health-check
	 */
	public function test_checks_filesystem(): void {
		$result = (new FilesystemCheck)->run();

		$this->assertEquals([
			'status' => 'Ok',
			'message' => '',
		], $result->toArray());
	}
}
