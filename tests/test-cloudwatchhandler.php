<?php

use Aws\CloudWatchLogs\CloudWatchLogsClient;
use Aws\CloudWatchLogs\Exception\CloudWatchLogsException;
use Aws\CommandInterface;
use Monolog\Level;
use Monolog\LogRecord;
use Pressbooks\Log\CloudWatchHandler;

class CloudWatchHandlerTest extends \WP_UnitTestCase {

	private function createMockClient(): CloudWatchLogsClient {
		return $this->getMockBuilder( CloudWatchLogsClient::class )
			->disableOriginalConstructor()
			->addMethods( [ 'createLogGroup', 'putRetentionPolicy', 'createLogStream', 'putLogEvents' ] )
			->getMock();
	}

	private function createMockCommand(): CommandInterface {
		return $this->createMock( CommandInterface::class );
	}

	private function createResourceAlreadyExistsException(): CloudWatchLogsException {
		return new CloudWatchLogsException(
			'Resource already exists',
			$this->createMockCommand(),
			[ 'code' => 'ResourceAlreadyExistsException' ]
		);
	}

	private function createLogRecord( string $message = 'test message', ?DateTimeImmutable $datetime = null ): LogRecord {
		return new LogRecord(
			datetime: $datetime ?? new DateTimeImmutable(),
			channel: 'test',
			level: Level::Debug,
			message: $message,
		);
	}

	/**
	 * @test
	 * @group log
	 */
	public function it_can_be_instantiated(): void {
		$client = $this->createMockClient();

		$handler = new CloudWatchHandler(
			$client,
			'test-group',
			'test-stream',
			90,
			10000,
			Level::Debug,
			true
		);

		$this->assertInstanceOf( CloudWatchHandler::class, $handler );
	}

	/**
	 * @test
	 * @group log
	 */
	public function it_buffers_records_until_flushed(): void {
		$client = $this->createMockClient();

		// putLogEvents should only be called once on close, not per handle call
		$client->expects( $this->once() )->method( 'putLogEvents' )
			->with( $this->callback( function ( $args ) {
				return count( $args['logEvents'] ) === 3;
			} ) );

		$handler = new CloudWatchHandler( $client, 'test-group', 'test-stream' );
		$handler->handle( $this->createLogRecord( 'message 1' ) );
		$handler->handle( $this->createLogRecord( 'message 2' ) );
		$handler->handle( $this->createLogRecord( 'message 3' ) );
		$handler->close();
	}

	/**
	 * @test
	 * @group log
	 */
	public function it_flushes_buffer_on_close(): void {
		$client = $this->createMockClient();

		$client->expects( $this->once() )->method( 'createLogGroup' )
			->with( [ 'logGroupName' => 'test-group' ] );

		$client->expects( $this->once() )->method( 'putRetentionPolicy' )
			->with( [
				'logGroupName' => 'test-group',
				'retentionInDays' => 90,
			] );

		$client->expects( $this->once() )->method( 'createLogStream' )
			->with( [
				'logGroupName' => 'test-group',
				'logStreamName' => 'test-stream',
			] );

		$client->expects( $this->once() )->method( 'putLogEvents' )
			->with( $this->callback( function ( $args ) {
				return $args['logGroupName'] === 'test-group'
					&& $args['logStreamName'] === 'test-stream'
					&& count( $args['logEvents'] ) === 1;
			} ) );

		$handler = new CloudWatchHandler( $client, 'test-group', 'test-stream', 90 );
		$handler->handle( $this->createLogRecord() );
		$handler->close();
	}

	/**
	 * @test
	 * @group log
	 */
	public function it_does_not_flush_when_buffer_is_empty(): void {
		$client = $this->createMockClient();
		$client->expects( $this->never() )->method( 'putLogEvents' );
		$client->expects( $this->never() )->method( 'createLogGroup' );

		$handler = new CloudWatchHandler( $client, 'test-group', 'test-stream' );
		$handler->close();
	}

	/**
	 * @test
	 * @group log
	 */
	public function it_flushes_when_batch_size_is_reached(): void {
		$client = $this->createMockClient();

		$client->expects( $this->once() )->method( 'createLogGroup' );
		$client->expects( $this->once() )->method( 'createLogStream' );
		$client->expects( $this->once() )->method( 'putLogEvents' )
			->with( $this->callback( function ( $args ) {
				return count( $args['logEvents'] ) === 3;
			} ) );

		$handler = new CloudWatchHandler( $client, 'test-group', 'test-stream', 90, 3 );

		$handler->handle( $this->createLogRecord( 'message 1' ) );
		$handler->handle( $this->createLogRecord( 'message 2' ) );
		$handler->handle( $this->createLogRecord( 'message 3' ) );
	}

	/**
	 * @test
	 * @group log
	 */
	public function it_sorts_events_by_timestamp_before_flushing(): void {
		$client = $this->createMockClient();

		$client->expects( $this->once() )->method( 'putLogEvents' )
			->with( $this->callback( function ( $args ) {
				$events = $args['logEvents'];

				for ( $i = 1; $i < count( $events ); $i++ ) {
					if ( $events[ $i ]['timestamp'] < $events[ $i - 1 ]['timestamp'] ) {
						return false;
					}
				}

				return true;
			} ) );

		$handler = new CloudWatchHandler( $client, 'test-group', 'test-stream' );

		$handler->handle( $this->createLogRecord( 'later', new DateTimeImmutable( '2026-01-02 12:00:00' ) ) );
		$handler->handle( $this->createLogRecord( 'earlier', new DateTimeImmutable( '2026-01-01 12:00:00' ) ) );

		$handler->close();
	}

	/**
	 * @test
	 * @group log
	 */
	public function it_handles_existing_log_group_and_stream(): void {
		$client = $this->createMockClient();
		$exception = $this->createResourceAlreadyExistsException();

		$client->expects( $this->once() )->method( 'createLogGroup' )
			->willThrowException( $exception );

		$client->expects( $this->once() )->method( 'createLogStream' )
			->willThrowException( $exception );

		$client->expects( $this->once() )->method( 'putLogEvents' );

		$handler = new CloudWatchHandler( $client, 'test-group', 'test-stream' );
		$handler->handle( $this->createLogRecord() );
		$handler->close();
	}

	/**
	 * @test
	 * @group log
	 */
	public function it_rethrows_non_resource_exists_exceptions_from_log_group(): void {
		$client = $this->createMockClient();

		$exception = new CloudWatchLogsException(
			'Access denied',
			$this->createMockCommand(),
			[ 'code' => 'AccessDeniedException' ]
		);

		$client->expects( $this->atLeastOnce() )->method( 'createLogGroup' )
			->willThrowException( $exception );

		$this->expectException( CloudWatchLogsException::class );

		$handler = new CloudWatchHandler( $client, 'test-group', 'test-stream' );
		$handler->handle( $this->createLogRecord() );
		$handler->close();
	}

	/**
	 * @test
	 * @group log
	 */
	public function it_rethrows_non_resource_exists_exceptions_from_log_stream(): void {
		$client = $this->createMockClient();

		$exception = new CloudWatchLogsException(
			'Access denied',
			$this->createMockCommand(),
			[ 'code' => 'AccessDeniedException' ]
		);

		$client->expects( $this->atLeastOnce() )->method( 'createLogGroup' );
		$client->expects( $this->atLeastOnce() )->method( 'createLogStream' )
			->willThrowException( $exception );

		$this->expectException( CloudWatchLogsException::class );

		$handler = new CloudWatchHandler( $client, 'test-group', 'test-stream' );
		$handler->handle( $this->createLogRecord() );
		$handler->close();
	}

	/**
	 * @test
	 * @group log
	 */
	public function it_skips_retention_policy_when_retention_is_zero(): void {
		$client = $this->createMockClient();

		$client->expects( $this->once() )->method( 'createLogGroup' );
		$client->expects( $this->never() )->method( 'putRetentionPolicy' );
		$client->expects( $this->once() )->method( 'createLogStream' );
		$client->expects( $this->once() )->method( 'putLogEvents' );

		$handler = new CloudWatchHandler( $client, 'test-group', 'test-stream', 0 );
		$handler->handle( $this->createLogRecord() );
		$handler->close();
	}

	/**
	 * @test
	 * @group log
	 */
	public function it_only_initializes_once(): void {
		$client = $this->createMockClient();

		// These should only be called once even though we flush twice
		$client->expects( $this->once() )->method( 'createLogGroup' );
		$client->expects( $this->once() )->method( 'createLogStream' );
		$client->expects( $this->exactly( 2 ) )->method( 'putLogEvents' );

		$handler = new CloudWatchHandler( $client, 'test-group', 'test-stream', 90, 1 );

		// Each handle triggers a flush since batch_size = 1
		$handler->handle( $this->createLogRecord( 'first' ) );
		$handler->handle( $this->createLogRecord( 'second' ) );
	}

	/**
	 * @test
	 * @group log
	 */
	public function it_formats_log_record_timestamp_in_milliseconds(): void {
		$client = $this->createMockClient();
		$datetime = new DateTimeImmutable( '2026-01-15 10:30:00' );
		$expected_timestamp = $datetime->getTimestamp() * 1000;

		$client->expects( $this->once() )->method( 'putLogEvents' )
			->with( $this->callback( function ( $args ) use ( $expected_timestamp ) {
				return $args['logEvents'][0]['timestamp'] === $expected_timestamp;
			} ) );

		$handler = new CloudWatchHandler( $client, 'test-group', 'test-stream' );
		$handler->handle( $this->createLogRecord( 'test', $datetime ) );
		$handler->close();
	}
}
