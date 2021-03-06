<?php

namespace Pressbooks\Log;

use function Pressbooks\Utility\debug_error_log;
use Aws\Credentials\CredentialProvider;
use Aws\CloudWatchLogs\CloudWatchLogsClient;
use Maxbanton\Cwh\Handler\CloudWatch;
use Monolog\Logger;
use Monolog\Formatter\JsonFormatter;

class CloudWatchProvider implements StorageProvider {

	/**
	 * @var string
	 */
	private $secret_key;

	/**
	 * @var string
	 */
	private $access_key_id;

	/**
	 * @var string
	 */
	private $version;

	/**
	 * @var string
	 */
	private $region;

	/**
	 * @var CloudWatchLogsClient
	 */
	private $client;

	/**
	 * @var CloudWatch
	 */
	private $handler;

	/**
	 * @var Logger
	 */
	private $logger;

	const RETENTION_DAYS = 90;

	const GROUP = 'pressbooks-logs';

	const STREAM = 'pressbooks-plugin';

	const AWS_CONFIG_FILENAME = 'does_not_exist.ini';

	const CHANNEL = 'saml-logs';

	private function create() {
		if ( self::areEnvironmentVariablesPresent() && is_null( $this->client ) ) {
			$this->region = env( 'AWS_S3_REGION' );
			$this->version = env( 'AWS_S3_VERSION' );
			$this->access_key_id = env( 'AWS_ACCESS_KEY_ID' );
			$this->secret_key = env( 'AWS_SECRET_ACCESS_KEY' );
			$environment = env( 'WP_ENV' ) ? env( 'WP_ENV' ) : 'production';
			$scheme = is_ssl() ? 'https' : 'http';
			if ( is_null( $this->client ) ) {
				$this->client = new CloudWatchLogsClient( [
					'region' => $this->region,
					'version' => $this->version,
					'credentials' => CredentialProvider::env(),
				] );
				$this->handler = new CloudWatch(
					$this->client,
					self::GROUP,
					self::STREAM,
					self::RETENTION_DAYS,
					10000,
					[], // TODO: Implement tags
				);
				$this->handler->setFormatter( new JsonFormatter() );
				$this->logger = new Logger( self::CHANNEL );
				$this->logger->pushHandler( $this->handler );
			}
			return true;
		} else {
			debug_error_log( 'Error initializing S3 Storage Provider: Some environment variables are not present.' );
		}
		return ! is_null( $this->client ) && ! is_null( $this->handler );
	}

	/**
	 * NOTE: We will use the same environment variables for OIDC plugin temporary.
	 *
	 * @return bool
	 */
	public static function areEnvironmentVariablesPresent() {
		if (
			! is_null( env( 'LOG_LOGIN_ATTEMPTS' ) ) &&
			! is_null( env( 'AWS_SECRET_ACCESS_KEY' ) ) &&
			! is_null( env( 'AWS_ACCESS_KEY_ID' ) ) &&
			! is_null( env( 'AWS_S3_VERSION' ) ) &&
			! is_null( env( 'AWS_S3_REGION' ) )
		) {
			if ( is_null( env( 'AWS_CONFIG_FILE' ) ) ) {
				// this is an env variable needed for AWS SDK
				putenv( 'AWS_CONFIG_FILE=' . __DIR__ . '/' . self::AWS_CONFIG_FILENAME );
			}
			return true;
		} else {
			debug_error_log( 'Error initializing S3 Storage Provider: Some environment variables are not present.' );
		}
		return false;
	}

	public function setFilePath( string $file_path ) {
		$this->file_path = $file_path;
	}

	public function store( array $data, string $file_header = null ) {
		if ( $this->create() ) {
			try {
				$data = $this->getDataFormat( $data );
				$this->logger->debug( 'SAML Log', $data );
				return true;
			} catch ( AwsException $e ) {
				debug_error_log( 'Error saving logs in CloudWatch: ' . $e->getMessage() );
			}
		}
		return false;
	}

	public function getDataFormat($data ) {
		$scheme = is_ssl() ? 'https' : 'http';
		$data['Environment'] = env( 'WP_ENV' ) ? env( 'WP_ENV' ) : 'production';
		$data['Network'] = [
			'Name' => get_site_option( 'site_name' ),
			'URL' => network_home_url( '', $scheme ),
		];
		return $data;
	}

	public function setLogger( Logger $logger ) {
		$this->logger = $logger;
	}

	public function setClient( $client ) {
		$this->client = $client;
	}

	public function setHandler( CloudWatch $handler ) {
		$this->handler = $handler;
	}
}
