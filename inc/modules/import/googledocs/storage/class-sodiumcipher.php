<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Modules\Import\GoogleDocs\Storage;

final class SodiumCipher implements Cipher {

	public function encrypt( string $plaintext, string $key ): string {
		$key_bytes = $this->decodeKey( $key );

		$nonce = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
		$ciphertext = sodium_crypto_secretbox( $plaintext, $nonce, $key_bytes );

		$blob = $nonce . $ciphertext;
		sodium_memzero( $key_bytes );

		return sodium_bin2base64( $blob, SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING );
	}

	public function decrypt( string $blob, string $key ): string {
		$key_bytes = $this->decodeKey( $key );

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

	private function decodeKey( string $key ): string {
		$variants = [
			SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING,
			SODIUM_BASE64_VARIANT_ORIGINAL,
			SODIUM_BASE64_VARIANT_URLSAFE,
		];

		foreach ( $variants as $variant ) {
			try {
				$bytes = sodium_base642bin( $key, $variant );
				if ( strlen( $bytes ) === SODIUM_CRYPTO_SECRETBOX_KEYBYTES ) {
					return $bytes;
				}
			} catch ( \SodiumException $e ) {
				continue;
			}
		}

		throw new \RuntimeException(
			'Encryption key must be a valid base64-encoded ' . SODIUM_CRYPTO_SECRETBOX_KEYBYTES . '-byte string. '
			. 'Generate one with: openssl rand -base64 32'
		);
	}
}
