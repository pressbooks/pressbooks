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

		return update_site_option( self::NETWORK_OPTION_KEY, [
			'client_id'                => $client_id,
			'encrypted_client_secret'  => $this->cipher->encrypt( $client_secret, $this->key ),
		] );
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
