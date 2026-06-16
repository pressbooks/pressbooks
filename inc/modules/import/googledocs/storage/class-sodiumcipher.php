<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Modules\Import\GoogleDocs\Storage;

class SodiumCipher implements Cipher {

	public function encrypt( string $plaintext, string $key ): string {
		$key_bytes = sodium_base642bin( $key, SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING );
		if ( strlen( $key_bytes ) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES ) {
			throw new \RuntimeException( 'Encryption key must be ' . SODIUM_CRYPTO_SECRETBOX_KEYBYTES . ' bytes (base64url-encoded).' );
		}

		$nonce = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
		$ciphertext = sodium_crypto_secretbox( $plaintext, $nonce, $key_bytes );

		$blob = $nonce . $ciphertext;
		sodium_memzero( $key_bytes );

		return sodium_bin2base64( $blob, SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING );
	}

	public function decrypt( string $blob, string $key ): string {
		$key_bytes = sodium_base642bin( $key, SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING );
		if ( strlen( $key_bytes ) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES ) {
			throw new \RuntimeException( 'Encryption key must be ' . SODIUM_CRYPTO_SECRETBOX_KEYBYTES . ' bytes (base64url-encoded).' );
		}

		$raw = sodium_base642bin( $blob, SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING );
		if ( strlen( $raw ) < SODIUM_CRYPTO_SECRETBOX_NONCEBYTES + SODIUM_CRYPTO_SECRETBOX_MACBYTES ) {
			throw new \RuntimeException( 'Ciphertext too short.' );
		}

		$nonce = substr( $raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
		$ciphertext = substr( $raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );

		$plaintext = sodium_crypto_secretbox_open( $ciphertext, $nonce, $key_bytes );
		sodium_memzero( $key_bytes );

		if ( $plaintext === false ) {
			throw new \RuntimeException( 'Decryption failed: ciphertext integrity check failed or key mismatch.' );
		}

		return $plaintext;
	}

	public function algorithm(): string {
		return 'crypto_secretbox';
	}
}
