<?php

class Modules_ImportGoogleDocsStorageTest extends \WP_UnitTestCase {

	/**
	 * @group import
	 */
	public function test_token_mode_enum_values(): void {
		$this->assertSame( 'direct', \Pressbooks\Modules\Import\GoogleDocs\Storage\TokenMode::Direct->value );
		$this->assertSame( 'broker', \Pressbooks\Modules\Import\GoogleDocs\Storage\TokenMode::Broker->value );
	}

	/**
	 * @group import
	 */
	public function test_stored_token_direct_mode_accessors(): void {
		$payload = [
			'access_token'  => 'at-123',
			'refresh_token' => 'rt-456',
			'expires_at'    => time() + 3600,
			'created'       => time(),
			'scope'         => 'documents.readonly drive.readonly',
			'token_type'    => 'Bearer',
		];
		$token = new \Pressbooks\Modules\Import\GoogleDocs\Storage\StoredToken(
			$payload,
			\Pressbooks\Modules\Import\GoogleDocs\Storage\TokenMode::Direct
		);

		$this->assertSame( 'at-123', $token->accessToken() );
		$this->assertSame( 'rt-456', $token->refreshToken() );
		$this->assertNull( $token->brokerSessionHandle() );
		$this->assertNull( $token->googleSub() );
		$this->assertSame( $payload['expires_at'], $token->expiresAt() );
		$this->assertFalse( $token->isExpired() );
		$this->assertTrue( $token->isExpired( 3700 ) ); // skew pushes expiry into past
	}

	/**
	 * @group import
	 */
	public function test_stored_token_broker_mode_accessors(): void {
		$payload = [
			'session_handle' => 'sh-abc',
			'access_token'   => 'at-789',
			'expires_at'     => time() + 3600,
			'google_sub'     => 'sub-001',
		];
		$token = new \Pressbooks\Modules\Import\GoogleDocs\Storage\StoredToken(
			$payload,
			\Pressbooks\Modules\Import\GoogleDocs\Storage\TokenMode::Broker
		);

		$this->assertSame( 'at-789', $token->accessToken() );
		$this->assertNull( $token->refreshToken() );
		$this->assertSame( 'sh-abc', $token->brokerSessionHandle() );
		$this->assertSame( 'sub-001', $token->googleSub() );
	}

	/**
	 * @group import
	 */
	public function test_stored_token_is_immutable(): void {
		$token = new \Pressbooks\Modules\Import\GoogleDocs\Storage\StoredToken(
			[ 'access_token' => 'x', 'expires_at' => time() + 100 ],
			\Pressbooks\Modules\Import\GoogleDocs\Storage\TokenMode::Direct
		);
		$this->expectException( \Error::class );
		$token->payload = [ 'access_token' => 'mutated' ]; // @phpstan-ignore-line
	}

	/**
	 * @group import
	 */
	public function test_sodium_cipher_round_trip(): void {
		$cipher = new \Pressbooks\Modules\Import\GoogleDocs\Storage\SodiumCipher();
		$key = sodium_bin2base64( random_bytes( SODIUM_CRYPTO_SECRETBOX_KEYBYTES ), SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING );
		$plaintext = '{"access_token":"at-123","refresh_token":"rt-456"}';

		$blob = $cipher->encrypt( $plaintext, $key );
		$decrypted = $cipher->decrypt( $blob, $key );

		$this->assertSame( $plaintext, $decrypted );
		$this->assertNotEquals( $plaintext, $blob, 'Ciphertext must not equal plaintext' );
		$this->assertStringNotContainsString( 'at-123', $blob, 'Ciphertext must not contain plaintext substrings' );
	}

	/**
	 * @group import
	 */
	public function test_sodium_cipher_blob_format(): void {
		$cipher = new \Pressbooks\Modules\Import\GoogleDocs\Storage\SodiumCipher();
		$key = sodium_bin2base64( random_bytes( SODIUM_CRYPTO_SECRETBOX_KEYBYTES ), SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING );

		$blob = $cipher->encrypt( 'hello', $key );

		// Format is base64url(nonce || ciphertext+tag). Length must equal nonce + MAC + plaintext length.
		$raw = sodium_base642bin( $blob, SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING );
		$this->assertSame(
			SODIUM_CRYPTO_SECRETBOX_NONCEBYTES + SODIUM_CRYPTO_SECRETBOX_MACBYTES + strlen( 'hello' ),
			strlen( $raw )
		);
	}

	/**
	 * @group import
	 */
	public function test_sodium_cipher_rejects_tampered_blob(): void {
		$cipher = new \Pressbooks\Modules\Import\GoogleDocs\Storage\SodiumCipher();
		$key = sodium_bin2base64( random_bytes( SODIUM_CRYPTO_SECRETBOX_KEYBYTES ), SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING );
		$blob = $cipher->encrypt( 'hello', $key );

		$raw = sodium_base642bin( $blob, SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING );
		$raw[30] = $raw[30] === 'a' ? 'b' : 'a'; // flip one byte in the ciphertext region
		$tampered = sodium_bin2base64( $raw, SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING );

		$this->expectException( \RuntimeException::class );
		$cipher->decrypt( $tampered, $key );
	}

	/**
	 * @group import
	 */
	public function test_sodium_cipher_algorithm_name(): void {
		$cipher = new \Pressbooks\Modules\Import\GoogleDocs\Storage\SodiumCipher();
		$this->assertSame( 'crypto_secretbox', $cipher->algorithm() );
	}
}
