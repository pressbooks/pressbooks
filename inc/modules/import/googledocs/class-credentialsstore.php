<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Modules\Import\GoogleDocs;

use Pressbooks\Modules\Import\GoogleDocs\Storage\Cipher;
use Pressbooks\Modules\Import\GoogleDocs\Storage\SodiumCipher;

class CredentialsStore {

	const NETWORK_OPTION_KEY = 'pressbooks_google_docs_oauth';

	private Cipher $cipher;
	private string $key;

	public function __construct( Cipher $cipher, string $key ) {
		$this->cipher = $cipher;
		$this->key = $key;
	}

	public static function fromEnvironment(): self {
		$key = defined( 'PRESSBOOKS_GOOGLE_DOCS_ENCRYPTION_KEY' ) ? PRESSBOOKS_GOOGLE_DOCS_ENCRYPTION_KEY : '';
		return new self( new SodiumCipher(), $key );
	}

	public function getClientCredentials(): array {
		$option = get_site_option( self::NETWORK_OPTION_KEY, [] );

		$client_id = $option['client_id'] ?? '';
		$client_secret = '';

		if ( ! empty( $option['encrypted_client_secret'] ) && $this->key !== '' ) {
			try {
				$client_secret = $this->cipher->decrypt( $option['encrypted_client_secret'], $this->key );
			} catch ( \Throwable $e ) {
				$client_secret = '';
			}
		}

		if ( $client_secret === '' && ! empty( $option['client_secret'] ) ) {
			$client_secret = $option['client_secret'];
		}

		return [
			'client_id'     => $client_id,
			'client_secret' => $client_secret,
		];
	}

	public function saveClientCredentials( string $client_id, string $client_secret ): bool {
		if ( $this->key === '' ) {
			throw new EncryptionKeyMissingException();
		}

		$option = get_site_option( self::NETWORK_OPTION_KEY, [] );
		$option['client_id'] = $client_id;
		$option['encrypted_client_secret'] = $this->cipher->encrypt( $client_secret, $this->key );
		unset( $option['client_secret'] );

		return update_site_option( self::NETWORK_OPTION_KEY, $option );
	}

	/**
	 * Google Picker configuration. The API key (developer key) and app ID (Google
	 * Cloud project number) are required by the Picker and are not secrets: both
	 * are visible in the browser. Constants take precedence so broker-mode
	 * networks can configure the Picker in application config (wp-config.php or
	 * Bedrock config/application.php); direct mode can use the settings page
	 * fields instead. Both values must belong to the same Google Cloud project
	 * as the OAuth client or Picker grants won't apply.
	 *
	 * @return array{api_key: string, app_id: string}
	 */
	public function getPickerConfig(): array {
		$option = get_site_option( self::NETWORK_OPTION_KEY, [] );

		$api_key = defined( 'PRESSBOOKS_GOOGLE_DOCS_PICKER_API_KEY' ) && ! empty( PRESSBOOKS_GOOGLE_DOCS_PICKER_API_KEY )
			? PRESSBOOKS_GOOGLE_DOCS_PICKER_API_KEY
			: (string) ( $option['picker_api_key'] ?? '' );

		$app_id = defined( 'PRESSBOOKS_GOOGLE_DOCS_PICKER_APP_ID' ) && ! empty( PRESSBOOKS_GOOGLE_DOCS_PICKER_APP_ID )
			? PRESSBOOKS_GOOGLE_DOCS_PICKER_APP_ID
			: (string) ( $option['picker_app_id'] ?? '' );

		return [
			'api_key' => $api_key,
			'app_id'  => $app_id,
		];
	}

	public function savePickerConfig( string $api_key, string $app_id ): bool {
		$option = get_site_option( self::NETWORK_OPTION_KEY, [] );
		$option['picker_api_key'] = $api_key;
		$option['picker_app_id'] = $app_id;

		return update_site_option( self::NETWORK_OPTION_KEY, $option );
	}

	public function isPickerConfigured(): bool {
		$config = $this->getPickerConfig();
		return $config['api_key'] !== '' && $config['app_id'] !== '';
	}

	public function isConfigured(): bool {
		if ( $this->isBrokerMode() ) {
			return true;
		}
		$creds = $this->getClientCredentials();
		return ! empty( $creds['client_id'] ) && ! empty( $creds['client_secret'] );
	}

	public function isBrokerMode(): bool {
		return defined( 'PRESSBOOKS_AUTH_BROKER_URL' ) && ! empty( PRESSBOOKS_AUTH_BROKER_URL );
	}
}
