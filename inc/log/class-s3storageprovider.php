<?php

namespace Pressbooks\Log;

use Aws\Credentials\CredentialProvider;
use Aws\Exception\UnresolvedApiException;
use Aws\S3\S3Client as S3Client;
use function Pressbooks\Utility\debug_error_log;

class S3StorageProvider implements StorageProvider {

	/**
	 * @var string
	 */
	private $bucket_name;

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
	 * @var S3Client
	 */
	private $client;

	/**
	 * @var string
	 */
	private $file_path;

	/**
	 * @var string
	 */
	private $filename;

	/**
	 * @var string
	 */
	private $aws_folder;

	const AWS_CONFIG_FILENAME = 'does_not_exist.ini';

	public function __construct( string $aws_folder, string $filename ) {
		$this->aws_folder = $aws_folder;
		$this->filename = $filename;
	}

	private function create() {
		if (
			self::areEnvironmentVariablesPresent() &&
			is_null( $this->client ) &&
			! is_null( $this->filename ) &&
			! is_null( $this->aws_folder )
		) {
			$this->region = env( 'AWS_S3_REGION' );
			$this->version = env( 'AWS_S3_VERSION' );
			$this->bucket_name = env( 'AWS_S3_OIDC_BUCKET' );
			$this->access_key_id = env( 'AWS_ACCESS_KEY_ID' );
			$this->secret_key = env( 'AWS_SECRET_ACCESS_KEY' );
			$environment = env( 'WP_ENV' ) ? env( 'WP_ENV' ) : 'production';
			$scheme = is_ssl() ? 'https' : 'http';
			$this->file_path = is_null( $this->file_path ) ? 's3://' . $this->bucket_name . '/' . $this->aws_folder .
				'/' . $environment . '/' . wp_hash( network_home_url( '', $scheme ) ) . '/' . current_time( 'Y-m' ) .
				$this->filename : $this->file_path;
			if ( is_null( $this->client ) ) {
				try {
					$this->client = new S3Client(
						[
							'region' => $this->region,
							'version' => $this->version,
							'credentials' => CredentialProvider::env(),
						]
					);
				} catch ( UnresolvedApiException $e ) {
					debug_error_log( 'Error creating S3 client: ' . $e->getMessage() );
					return false;
				}
			}
			return true;
		} else {
			return false;
		}
	}

	public function setFilePath( string $file_path ) {
		$this->file_path = $file_path;
	}

	/**
	 * NOTE: We will use the same environment variables for OIDC plugin temporary.
	 *
	 * @return bool
	 */
	public static function areEnvironmentVariablesPresent() {
		if (
			! is_null( env( 'LOG_LOGIN_ATTEMPTS' ) ) &&
			! is_null( env( 'AWS_S3_OIDC_BUCKET' ) ) &&
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
			try {
				$this->client->registerStreamWrapper();
				$stream = fopen( $this->file_path, 'a' );
				$data = $this->getDataFormat( $data );
				if ( ! is_null( $file_header ) && ! file_exists( $this->file_path ) ) {
					$data = $file_header . $data;
				}
				fwrite( $stream, $data );
				fclose( $stream );
				return true;
			} catch ( AwsException $e ) {
				debug_error_log( 'Error saving logs in S3: ' . $e->getMessage() );
			}
		}
		return false;
	}

	public function getDataFormat( array $data ) {
		$data_csv_format = '';
		$current_date = current_time( 'mysql' );
		foreach ( $data as $key => $value ) {
			$data_csv_format .= $current_date . ',' . $key . ',"' . print_r( $value, true ) . '"' . "\n"; // @codingStandardsIgnoreLine
		}
		return $data_csv_format;
	}

	public function setClient( $client ) {
		$this->client = $client;
	}
}
