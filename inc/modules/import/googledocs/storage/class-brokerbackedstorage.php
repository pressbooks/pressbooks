<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Modules\Import\GoogleDocs\Storage;

use Pressbooks\Modules\Import\GoogleDocs\EncryptionKeyMissingException;

final class BrokerBackedStorage implements TokenStorage {

	const META_KEY = 'pressbooks_google_docs_broker_token';

	private const ALLOWED_FIELDS = [ 'session_handle', 'access_token', 'expires_at', 'google_sub' ];

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

		return new StoredToken( $payload, TokenMode::Broker );
	}

	public function save( int $user_id, StoredToken $token ): bool {
		if ( ! $this->isAvailable() ) {
			throw new EncryptionKeyMissingException();
		}

		$filtered = array_intersect_key( $token->payload, array_flip( self::ALLOWED_FIELDS ) );

		$json = json_encode( $filtered, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		$blob = $this->cipher->encrypt( $json, $this->key );

		return (bool) update_user_meta( $user_id, self::META_KEY, $blob );
	}

	public function delete( int $user_id ): bool {
		return delete_user_meta( $user_id, self::META_KEY );
	}
}
