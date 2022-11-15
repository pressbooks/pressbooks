<?php

use Illuminate\Support\Str;
use Pressbooks\Health\Checks\CacheCheck;
use Pressbooks\Health\Checks\DatabaseCheck;
use Pressbooks\Health\Checks\FilesystemCheck;

class HealthCheckTest extends \WP_UnitTestCase {
	use utilsTrait;

	/**
	 * @test
	 * @group health-check
	 */
	public function health_check_endpoint_exists(): void {
		$server = $this->_setupRootApi();

		$request = new WP_REST_Request( 'OPTIONS', '/pressbooks/v2/health-check' );

		$data = $server->dispatch( $request )->get_data();

		$this->assertEquals( 'pressbooks/v2', $data['namespace'] );
	}

	/**
	 * @test
	 * @group health-check
	 */
	public function health_check_endpoint_forbidden_response(): void {
		$server = $this->_setupRootApi();

		$request = new WP_REST_Request( 'GET', '/pressbooks/v2/health-check' );

		$response = $server->dispatch( $request );

		$this->assertEquals( 401, $response->status );

		$request->set_query_params( [
			'_token' => 'not-a-valid-token',
		] );

		$response = $server->dispatch( $request );

		$this->assertEquals( 401, $response->status );
	}

	/**
	 * @test
	 * @group health-check
	 */
	public function health_check_endpoint_success_response(): void {
		$server = $this->_setupRootApi();

		$request = new WP_REST_Request( 'GET', '/pressbooks/v2/health-check' );

		$request->set_query_params( [
			'_token' => env( 'PB_HEALTH_CHECK_TOKEN' ),
		] );

		$response = $server->dispatch( $request );

		/** @var \Illuminate\Support\Collection $data */
		$data = $response->get_data();

		$this->assertEquals( 200, $response->status );

		$this->assertEquals( [
			'cache' => [
				'status' => 'Ok',
				'message' => '',
			],
			'object-cache-pro' => [
				'status' => 'Ok',
				'message' => 'Object Cache Pro plugin is either inactive or not installed.',
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
	 * @test
	 * @group health-check
	 */
	public function it_checks_database_connection(): void {
		$result = ( new DatabaseCheck )->run();

		$this->assertEquals( [
			'status' => 'Ok',
			'message' => '',
		], $result->toArray() );
	}

	/**
	 * @test
	 * @group health-check
	 */
	public function database_connection_check_fails(): void {
		$check = $this->getMockBuilder( DatabaseCheck::class )
			->onlyMethods( [ 'checkConnection' ] )
			->getMock();

		$check->method( 'checkConnection' )->willReturn( false );

		$result = $check->run();

		$this->assertEquals( [
			'status' => 'Failed',
			'message' => 'Could not connect to the database.',
		], $result->toArray() );
	}

	/**
	 * @test
	 * @group health-check
	 */
	public function it_checks_cache_connection(): void {
		$result = ( new CacheCheck )->run();

		$this->assertEquals( [
			'status' => 'Ok',
			'message' => '',
		], $result->toArray() );
	}

	/**
	 * @test
	 * @group health-check
	 */
	public function cache_connection_check_fails(): void {
		$check = $this->getMockBuilder( CacheCheck::class )
			->onlyMethods( [ 'canWriteValuesToCache' ] )
			->getMock();

		$check->method( 'canWriteValuesToCache' )->willReturn( false );

		$result = $check->run();

		$this->assertEquals( [
			'status' => 'Failed',
			'message' => 'Could not set or retrieve an application cache value.',
		], $result->toArray() );
	}

	/**
	 * @test
	 * @group health-check
	 */
	public function it_checks_filesystem(): void {
		$result = ( new FilesystemCheck )->run();

		$this->assertEquals( [
			'status' => 'Ok',
			'message' => '',
		], $result->toArray() );
	}

	/**
	 * @test
	 * @group health-check
	 */
	public function filesystem_check_fails(): void {
		$checkConnection = $this->getMockBuilder( FilesystemCheck::class )
			->onlyMethods( [ 'canConnectToFilesystem' ] )
			->getMock();

		$checkConnection->method( 'canConnectToFilesystem' )->willReturn( false );

		$connectionResult = $checkConnection->run();

		$this->assertEquals( [
			'status' => 'Failed',
			'message' => 'Failed to obtain filesystem write access.',
		], $connectionResult->toArray() );

		$checkWritable = $this->getMockBuilder( FilesystemCheck::class )
			->onlyMethods( [ 'canWriteToFilesystem' ] )
			->getMock();

		$checkWritable->method( 'canWriteToFilesystem' )->willReturn( false );

		$writableResult = $checkWritable->run();

		$this->assertEquals( [
			'status' => 'Failed',
			'message' => 'The filesystem is not writable.',
		], $writableResult->toArray() );

		$checkReadable = $this->getMockBuilder( FilesystemCheck::class )
			->onlyMethods( [ 'canReadFromFilesystem' ] )
			->getMock();

		$checkReadable->method( 'canReadFromFilesystem' )->willReturn( false );

		$readableResult = $checkReadable->run();

		$this->assertEquals( [
			'status' => 'Failed',
			'message' => 'The filesystem is not readable.',
		], $readableResult->toArray() );

		$checkDisk = $this->getMockBuilder( FilesystemCheck::class )
			->onlyMethods( [ 'getDiskUsagePercentage' ] )
			->getMock();

		$checkDisk->method( 'getDiskUsagePercentage' )->willReturn( 91 );

		$diskResult = $checkDisk->run();

		$this->assertEquals( [
			'status' => 'Failed',
			'message' => 'The disk is almost full (91% used).',
		], $diskResult->toArray() );
	}
}
