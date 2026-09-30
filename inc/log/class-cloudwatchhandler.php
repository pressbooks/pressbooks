<?php

namespace Pressbooks\Log;

use Aws\CloudWatchLogs\CloudWatchLogsClient;
use Aws\CloudWatchLogs\Exception\CloudWatchLogsException;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;

/**
 * Monolog handler for AWS CloudWatch Logs.
 *
 * Replaces maxbanton/cwh with a minimal implementation using the AWS SDK directly.
 * AWS deprecated sequence tokens for putLogEvents in 2023, simplifying this handler.
 */
class CloudWatchHandler extends AbstractProcessingHandler {

	/**
	 * @var CloudWatchLogsClient
	 */
	private CloudWatchLogsClient $client;

	/**
	 * @var string
	 */
	private string $group;

	/**
	 * @var string
	 */
	private string $stream;

	/**
	 * @var int
	 */
	private int $retention_days;

	/**
	 * @var bool
	 */
	private bool $initialized = false;

	/**
	 * @var array<array{timestamp: int, message: string}>
	 */
	private array $buffer = [];

	/**
	 * @var int
	 */
	private int $batch_size;

	/**
	 * @param CloudWatchLogsClient $client       AWS CloudWatch Logs client
	 * @param string               $group        Log group name
	 * @param string               $stream       Log stream name
	 * @param int                  $retention    Retention in days (0 = never expire)
	 * @param int                  $batch_size   Max events per putLogEvents call
	 * @param int|string|Level     $level        Minimum log level
	 * @param bool                 $bubble       Whether messages bubble up the stack
	 */
	public function __construct(
		CloudWatchLogsClient $client,
		string $group,
		string $stream,
		int $retention = 90,
		int $batch_size = 10000,
		int|string|Level $level = Level::Debug,
		bool $bubble = true
	) {
		parent::__construct( $level, $bubble );

		$this->client = $client;
		$this->group = $group;
		$this->stream = $stream;
		$this->retention_days = $retention;
		$this->batch_size = $batch_size;
	}

	/**
	 * Ensure log group and stream exist.
	 */
	private function initialize(): void {
		if ( $this->initialized ) {
			return;
		}

		try {
			$this->client->createLogGroup( [ 'logGroupName' => $this->group ] );

			if ( $this->retention_days > 0 ) {
				$this->client->putRetentionPolicy( [
					'logGroupName' => $this->group,
					'retentionInDays' => $this->retention_days,
				] );
			}
		} catch ( CloudWatchLogsException $e ) {
			if ( $e->getAwsErrorCode() !== 'ResourceAlreadyExistsException' ) {
				throw $e;
			}
		}

		try {
			$this->client->createLogStream( [
				'logGroupName' => $this->group,
				'logStreamName' => $this->stream,
			] );
		} catch ( CloudWatchLogsException $e ) {
			if ( $e->getAwsErrorCode() !== 'ResourceAlreadyExistsException' ) {
				throw $e;
			}
		}

		$this->initialized = true;
	}

	/**
	 * {@inheritdoc}
	 */
	protected function write( LogRecord $record ): void {
		$this->buffer[] = [
			'timestamp' => $record->datetime->getTimestamp() * 1000,
			'message' => $record->formatted ?? (string) $record->message,
		];

		if ( count( $this->buffer ) >= $this->batch_size ) {
			$this->flush();
		}
	}

	/**
	 * Flush buffered events to CloudWatch.
	 */
	private function flush(): void {
		if ( empty( $this->buffer ) ) {
			return;
		}

		$this->initialize();

		// Sort by timestamp (CloudWatch requirement)
		usort( $this->buffer, fn( $a, $b ) => $a['timestamp'] <=> $b['timestamp'] );

		$this->client->putLogEvents( [
			'logGroupName' => $this->group,
			'logStreamName' => $this->stream,
			'logEvents' => $this->buffer,
		] );

		$this->buffer = [];
	}

	/**
	 * Flush on close / destruct.
	 */
	public function close(): void {
		$this->flush();
		parent::close();
	}
}
