<?php

use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use Pressbooks\Api\Endpoints\Controller\Posts;
use Pressbooks\Container;

use Pressbooks\Health\Checks\CacheCheck;
use Pressbooks\Health\Checks\DatabaseCheck;
use Pressbooks\Health\Checks\FilesystemCheck;
use function \Pressbooks\Metadata\book_information_to_schema;

class HealthCheckTest extends \WP_UnitTestCase {
	use ArraySubsetAsserts;
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
		$data = $response->get_data();

		$this->assertEquals( 200, $response->status );

		$this->assertArraySubset( [
			'cache' => [],
			'database' => [
				'status' => 'Connected',
				'has_issue' => false,
			],
			'filesystem' => [
				'status' => 'Accessible',
				'writable' => true,
				'readable' => true,
				'has_issue' => false,
			]
		], $data );
	}

	/**
	 * @group health-check
	 */
	public function test_checks_database_connection(): void {
		$this->assertEquals([
			'status' => 'Connected',
			'has_issue' => false,
		], (new DatabaseCheck)->run());
	}

	/**
	 * @group health-check
	 */
	public function test_checks_cache_connection(): void {
		$this->assertEquals([
			'status' => 'Unknown',
			'has_issue' => false,
		], (new CacheCheck)->run());
	}

	/**
	 * @group health-check
	 */
	public function test_checks_filesystem(): void {
		$this->assertArraySubset([
			'status' => 'Accessible',
			'writable' => true,
			'readable' => true,
			'has_issue' => false,
		], (new FilesystemCheck)->run());
	}
}
