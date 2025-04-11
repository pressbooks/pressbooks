<?php

namespace Pressbooks\Log;

use Aws\CloudWatchLogs\CloudWatchLogsClient;
use Aws\Credentials\CredentialProvider;
use Aws\Exception\UnresolvedApiException;
use function Pressbooks\Utility\debug_error_log;
use Maxbanton\Cwh\Handler\CloudWatch;
use Monolog\Formatter\JsonFormatter;
use Monolog\Logger;

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

	/**
	 * @var integer
	 */
	private $retention_days;

	/**
	 * @var string
	 */
	private $group;

	/**
	 * @var string
	 */
	private $stream;

	/**
	 * @var string
	 */
	private $channel;

	const AWS_CONFIG_FILENAME = 'does_not_exist.ini';

	public function __construct( int $retention_days, string $group, string $stream, string $channel ) {
		$this->retention_days = $retention_days;
		$this->group = $group;
		$this->stream = $stream;
		$this->channel = $channel;
	}

	private function create() {
		if ( self::areEnvironmentVariablesPresent() && is_null( $this->client ) ) {
			$this->region = env( 'AWS_S3_REGION' );
			$this->version = env( 'AWS_S3_VERSION' );
			$this->access_key_id = env( 'AWS_ACCESS_KEY_ID' );
			$this->secret_key = env( 'AWS_SECRET_ACCESS_KEY' );
			if ( is_null( $this->client ) ) {
				try {
					$this->client = new CloudWatchLogsClient(
						[
							'region' => $this->region,
							'version' => $this->version,
							'credentials' => CredentialProvider::env(),
						]
					);
					$this->handler = new CloudWatch(
						$this->client,
						$this->group,
						$this->stream,
						$this->retention_days,
						10000,
						[], // TODO: Implement tags
					);
					$this->handler->setFormatter( new JsonFormatter() );
					$this->logger = new Logger( $this->channel );
					$this->logger->pushHandler( $this->handler );
				} catch ( UnresolvedApiException $e ) {
					debug_error_log( 'Error initializing Cloudwatch Storage Provider: ' . $e->getMessage() );
					return false;
				}
			}
			return true;
		} else {
			debug_error_log( 'Error initializing CloudWatch Storage Provider: Some environment variables are not present.' );
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
		}
		return false;
	}

	public function store( array $data, string $file_header = null ) {
		if ( $this->create() ) {
			$data = $this->getDataFormat( $data );
			try {
				$this->logger->debug( 'SAML Log', $data );
				return true;
			} catch ( \Exception $e ) {
				debug_error_log( 'Error saving logs in CloudWatch: ' . $e->getMessage() );
			}
		}
		return false;
	}

	public function getDataFormat( $data ) {
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
