<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Modules\Import\GoogleDocs\Storage;

use Pressbooks\Modules\Import\GoogleDocs\EncryptionKeyMissingException;

class DirectEncryptedStorage implements TokenStorage {

	const META_KEY = 'pressbooks_google_docs_token';

	private Cipher $cipher;

	private string $key;

	public function __construct( Cipher $cipher, string $key ) {
		$this->cipher = $cipher;
		$this->key = $key;
	}

	public function isAvailable(): bool {
		return $this->key !== '';
	}

	public function load( int $user_id ): ?StoredToken {
		$blob = get_user_meta( $user_id, self::META_KEY, true );
		if ( ! is_string( $blob ) || $blob === '' ) {
			return null;
		}

		try {
			$json = $this->cipher->decrypt( $blob, $this->key );
		} catch ( \Throwable $e ) {
			return null;
		}

		$payload = json_decode( $json, true );
		if ( ! is_array( $payload ) ) {
			return null;
		}

		return new StoredToken( $payload, TokenMode::Direct );
	}

	public function save( int $user_id, StoredToken $token ): bool {
		if ( ! $this->isAvailable() ) {
			throw new EncryptionKeyMissingException();
		}

		$json = json_encode( $token->payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		$blob = $this->cipher->encrypt( $json, $this->key );

		return (bool) update_user_meta( $user_id, self::META_KEY, $blob );
	}

	public function delete( int $user_id ): bool {
		return delete_user_meta( $user_id, self::META_KEY );
	}
}
