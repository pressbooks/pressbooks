<?php

use Aws\CloudWatchLogs\CloudWatchLogsClient;
use Aws\S3\S3Client as S3Client;
use Maxbanton\Cwh\Handler\CloudWatch;
use Monolog\Logger;
use Pressbooks\Log;

class LogTest extends \WP_UnitTestCase {

	/**
	 * @var Log\S3StorageProvider
	 */
	private $s3_provider_mock;

	/**
	 * @var Log\CloudWatchProvider
	 */
	private $cloudwatch_provider_mock;

	/**
	 * @var Log\Log
	 */
	private $log;

	const TEST_FILE_PATH = 'tests/data/log.csv';

	/**
	 * Test setup
	 */
	public function set_up() {
		$this->setEnvironmentVariables();
	}

	private function setEnvironmentVariables() {
		putenv( 'LOG_LOGIN_ATTEMPTS=1' );
		putenv( 'AWS_S3_OIDC_BUCKET=fakeBucket' );
		putenv( 'AWS_SECRET_ACCESS_KEY=fakeAccessKey' );
		putenv( 'AWS_ACCESS_KEY_ID=fakeKeyId' );
		putenv( 'AWS_S3_VERSION=fake' );
		putenv( 'AWS_S3_REGION=fakeRegion' );
	}

	private function setS3ClientMock() {
		$s3_client_mock = $this
			->getMockBuilder( S3Client::class )
			->disableOriginalConstructor()
			->setMethods([
				'registerStreamWrapper',
			])
			->getMock();
		$this->s3_provider_mock = new Log\S3StorageProvider( 'tests/data', 'log.csv' );
		$this->s3_provider_mock->setClient( $s3_client_mock );
		$this->s3_provider_mock->setFilePath( self::TEST_FILE_PATH );
		$this->log = new Log\Log( $this->s3_provider_mock );
	}

	private function setLoggerMock() {
		$logger_mock = $this
			->getMockBuilder( Logger::class )
			->disableOriginalConstructor()
			->setMethods([
				'debug',
				'pushHandler',
			])
			->getMock();
		$logger_mock->expects( $this->any() )
			->method( 'debug' )
			->will( $this->onConsecutiveCalls( true ) );
		$logger_mock->expects( $this->any() )
			->method( 'pushHandler' )
			->will( $this->onConsecutiveCalls( true ) );
		$cloudwatch_logs_mock = $this
			->getMockBuilder( CloudWatchLogsClient::class )
			->disableOriginalConstructor()
			->getMock();
		$handler = $this
			->getMockBuilder( CloudWatch::class )
			->disableOriginalConstructor()
			->setMethods([
				'setFormatter',
			])
			->getMock();
		$handler->expects( $this->any() )
			->method( 'setFormatter' )
			->will( $this->onConsecutiveCalls( true ) );
		$this->cloudwatch_provider_mock = new Log\CloudWatchProvider( 90, 'pressbooks-logs', 'pressbooks-plugin', 'saml-logs' );
		$this->cloudwatch_provider_mock->setLogger( $logger_mock );
		$this->cloudwatch_provider_mock->setHandler( $handler );
		$this->cloudwatch_provider_mock->setClient( $cloudwatch_logs_mock );
		$this->log = new Log\Log( $this->cloudwatch_provider_mock );
	}

	/**
	 * Use Reflexion for private method
	 *
	 * @param $object
	 * @param string $method
	 * @param array $parameters
	 * @return mixed
	 * @throws ReflectionException
	 */
	private function callMethodForReflection($object, string $method , array $parameters = []) {
		try {
			$className = get_class( $object );
			$reflection = new \ReflectionClass( $className );
		} catch ( \ReflectionException $e ) {
			throw new \Exception( $e->getMessage() );
		}

		$method = $reflection->getMethod( $method );
		$method->setAccessible( true );

		return $method->invokeArgs( $object, $parameters );
	}

	/**
	 * @group log
	 */
	public function test_s3_store() {
		$this->setS3ClientMock();
		$this->log->addRowToData( 'Test key 1', ['Test value'] );
		$this->log->addRowToData( 'Test key 2', [
			'Test a' => 'Test b',
			'Test c' => 'Test d',
		] );
		$this->assertTrue( $this->log->store() );
		$file_content = str_getcsv( file_get_contents( self::TEST_FILE_PATH ) );
		unlink( self::TEST_FILE_PATH );
		$this->assertEquals( 'Test key 1', $file_content[1] );
		$this->assertContains( 'Test value', $file_content[2] );
		$this->assertEquals( 'Test key 2', $file_content[3] );
		$this->assertContains( 'Test b', $file_content[4] );
		$this->assertContains( 'Test d', $file_content[4] );
		$this->assertContains( '[Test a] =>', $file_content[4] );
		$this->assertContains( '[Test c] =>', $file_content[4] );
	}

	/**
	 * @group log
	 */
	public function test_s3_invalid_store_action_because_fake_env_variables() {
		$s3_provider = new Log\S3StorageProvider( 'tests/data', 'log.csv' );
		$this->log = new Log\Log( $s3_provider );
		$this->log->addRowToData( 'Test key 1', ['Test value'] );
		$this->log->addRowToData( 'Test key 2', [
			'Test a' => 'Test b',
			'Test c' => 'Test d',
		] );
		$this->assertFalse( $this->log->store() );
	}

	/**
	 * @group log
	 */
	public function test_s3_create_action() {
		$s3_provider = new Log\S3StorageProvider( 'tests/data', 'log.csv' );
		$this->assertFalse( $this->callMethodForReflection( $s3_provider, 'create' ) );
	}

	/**
	 * @group log
	 */
	public function test_cloudwatch_store() {
		$this->setLoggerMock();
		$this->log->addRowToData( 'Test key 1', ['Test value'] );
		$this->log->addRowToData( 'Test key 2', [
			'Test a' => 'Test b',
			'Test c' => 'Test d',
		] );
		$this->assertTrue( $this->log->store() );
	}

	/**
	 * @group log
	 */
	public function test_cloudwatch_invalid_store_action_because_fake_env_variables() {
		$cloudwatch_provider = new Log\CloudWatchProvider( 90, 'pressbooks-logs', 'pressbooks-plugin', 'saml-logs' );
		$this->log = new Log\Log( $cloudwatch_provider );
		$this->log->addRowToData( 'Test key 1', ['Test value'] );
		$this->log->addRowToData( 'Test key 2', [
			'Test a' => 'Test b',
			'Test c' => 'Test d',
		] );
		$this->assertFalse( $this->log->store() );
	}
}
