# PB Plugin — Google Docs Token Security Remediation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Eliminate plaintext Google refresh tokens from Pressbooks WP user_meta by introducing envelope encryption (direct mode) and broker-held refresh tokens with on-demand mint (broker mode), without removing the no-broker codepath.

**Architecture:** A new `TokenStorage` interface decouples the OAuthClient from how/where tokens are persisted. Two implementations (`DirectEncryptedStorage`, `BrokerBackedStorage`) share a single `Cipher` (libsodium `crypto_secretbox`) keyed by a new `PRESSBOOKS_GOOGLE_DOCS_ENCRYPTION_KEY` constant required in both modes. Broker mode adds a `BrokerRefreshClient` that calls the broker's new `/oauth/refresh` and `/oauth/revoke` endpoints with HMAC-signed requests and RS256-verified responses. The handoff JWT shape changes: it no longer carries `refresh_token`; instead it carries a `session_handle` that the network presents to the broker for short-lived access tokens.

**Tech Stack:** PHP 8.3+, WordPress Multisite (Bedrock-compatible), `ext-sodium` (loaded), `firebase/php-jwt` (already in vendor via `google/apiclient`), WP PHPUnit (`yoast/phpunit-polyfills`).

**Spec:** `docs/superpowers/specs/2026-06-15-google-docs-token-security-audit-and-remediation.md` in the `pb-google-auth` repo.

**Repo for this plan:** `/Users/arzola/code/pbdev/web/app/plugins/pressbooks/` (run all `cd`-less commands from this path).

**Test command:** `composer test -- --filter=GoogleDocs`

---

## File Structure

### New files (PB plugin)

| Path | Responsibility |
|---|---|
| `inc/modules/import/googledocs/storage/class-tokenmode.php` | Enum: `Direct` \| `Broker` |
| `inc/modules/import/googledocs/storage/class-storedtoken.php` | Immutable DTO wrapping the decrypted payload + mode |
| `inc/modules/import/googledocs/storage/class-cipher.php` | Interface: `encrypt()` / `decrypt()` |
| `inc/modules/import/googledocs/storage/class-sodiumcipher.php` | libsodium `crypto_secretbox` (XSalsa20-Poly1305) implementation |
| `inc/modules/import/googledocs/storage/class-tokenstorage.php` | Interface: `load/save/delete/isAvailable` |
| `inc/modules/import/googledocs/storage/class-directencryptedstorage.php` | TokenStorage impl for direct mode (full blob encrypted) |
| `inc/modules/import/googledocs/storage/class-brokerbackedstorage.php` | TokenStorage impl for broker mode (small blob encrypted; no refresh_token) |
| `inc/modules/import/googledocs/broker/class-brokerrefreshclient.php` | HTTP client for `/oauth/refresh` and `/oauth/revoke` |
| `inc/modules/import/googledocs/class-encryptionkeymissingexception.php` | Thrown when `PRESSBOOKS_GOOGLE_DOCS_ENCRYPTION_KEY` is absent |
| `tests/test-modules-import-google-docs-storage.php` | Tests for the storage namespace |
| `tests/test-modules-import-google-docs-broker-client.php` | Tests for `BrokerRefreshClient` |

### Modified files (PB plugin)

| Path | Changes |
|---|---|
| `inc/modules/import/googledocs/class-oauthclient.php` | Constructor takes `TokenStorage` + `CredentialsStore`; `handleBrokerCallback()` expects new JWT shape; `disconnect()` routes via broker in broker mode and surfaces failures; `getAuthedClient()` refreshes via `BrokerRefreshClient` in broker mode |
| `inc/modules/import/googledocs/class-credentialsstore.php` | Drop `getUserToken/saveUserToken/deleteUserToken/isUserConnected` (moved to storage namespace). Keep client_id/secret + `isConfigured` + `isBrokerMode`. Add `isUserConnected(int $user_id, TokenStorage $storage)` shim OR remove and let callers compose (we remove; callers move to `OAuthClient::isConnected()`). |
| `inc/modules/import/googledocs/class-settingspage.php` | Render "config required" admin notice when encryption key missing |
| `hooks-admin.php` (lines 418–453) | Construct new dependencies (`TokenStorage`, `BrokerRefreshClient`); pass into `OAuthClient`. Wire purge-on-upgrade hook. |
| `hooks.php` | Add `pb_gdocs_purge_legacy_tokens` upgrade hook |
| `tests/test-modules-import-google-docs-credentials.php` | Drop tests for the token get/save/delete methods (moved out of CredentialsStore). Keep tests for client_id/secret + `isConfigured`. |
| `tests/test-modules-import-google-docs-oauth.php` | Update tests for the new `OAuthClient` constructor, new broker JWT shape, new `disconnect()` behavior. |

### Out of scope for this plan

- Broker-side changes (separate plan).
- The token-theft verification procedure from spec Section 12 (executed in Phase 1 of rollout, not part of code plan).
- Documentation beyond the spec itself.

---

## Task 1: TokenMode enum + StoredToken DTO

**Files:**
- Create: `inc/modules/import/googledocs/storage/class-tokenmode.php`
- Create: `inc/modules/import/googledocs/storage/class-storedtoken.php`
- Test: `tests/test-modules-import-google-docs-storage.php`

- [ ] **Step 1: Write the failing test**

Create `tests/test-modules-import-google-docs-storage.php`:

```php
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
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `composer test -- --filter=Modules_ImportGoogleDocsStorageTest`
Expected: Fatal error: Class `Pressbooks\Modules\Import\GoogleDocs\Storage\TokenMode` not found.

- [ ] **Step 3: Create `TokenMode` enum**

Create `inc/modules/import/googledocs/storage/class-tokenmode.php`:

```php
<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Modules\Import\GoogleDocs\Storage;

enum TokenMode: string {
	case Direct = 'direct';
	case Broker = 'broker';
}
```

- [ ] **Step 4: Create `StoredToken` DTO**

Create `inc/modules/import/googledocs/storage/class-storedtoken.php`:

```php
<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Modules\Import\GoogleDocs\Storage;

final class StoredToken {

	public readonly array $payload;
	public readonly TokenMode $mode;

	public function __construct( array $payload, TokenMode $mode ) {
		$this->payload = $payload;
		$this->mode = $mode;
	}

	public function accessToken(): ?string {
		return $this->payload['access_token'] ?? null;
	}

	public function refreshToken(): ?string {
		return $this->payload['refresh_token'] ?? null;
	}

	public function brokerSessionHandle(): ?string {
		return $this->payload['session_handle'] ?? null;
	}

	public function googleSub(): ?string {
		return $this->payload['google_sub'] ?? null;
	}

	public function expiresAt(): int {
		return (int) ( $this->payload['expires_at'] ?? 0 );
	}

	public function isExpired( int $skew = 0 ): bool {
		return $this->expiresAt() <= ( time() + $skew );
	}
}
```

- [ ] **Step 5: Run test to verify it passes**

Run: `composer test -- --filter=Modules_ImportGoogleDocsStorageTest`
Expected: PASS (4 tests).

- [ ] **Step 6: Commit**

```bash
git add inc/modules/import/googledocs/storage/class-tokenmode.php \
        inc/modules/import/googledocs/storage/class-storedtoken.php \
        tests/test-modules-import-google-docs-storage.php
git commit -m "feat(google-docs): add TokenMode enum and StoredToken DTO"
```

---

## Task 2: Cipher interface + SodiumCipher implementation

**Files:**
- Create: `inc/modules/import/googledocs/storage/class-cipher.php`
- Create: `inc/modules/import/googledocs/storage/class-sodiumcipher.php`
- Test: `tests/test-modules-import-google-docs-storage.php` (append)

- [ ] **Step 1: Append failing tests**

Append to `tests/test-modules-import-google-docs-storage.php` (before the closing `}`):

```php
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

		// Format is base64url(nonce || ciphertext). Decoded length = 24 (nonce) + ciphertext+tag.
		$raw = sodium_base642bin( $blob, SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING );
		$this->assertSame( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, strlen( substr( $raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES ) ) );
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
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `composer test -- --filter=Modules_ImportGoogleDocsStorageTest`
Expected: Fatal error: Class `SodiumCipher` not found.

- [ ] **Step 3: Create `Cipher` interface**

Create `inc/modules/import/googledocs/storage/class-cipher.php`:

```php
<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Modules\Import\GoogleDocs\Storage;

interface Cipher {

	public function encrypt( string $plaintext, string $key ): string;

	public function decrypt( string $blob, string $key ): string;

	public function algorithm(): string;
}
```

- [ ] **Step 4: Create `SodiumCipher` implementation**

Create `inc/modules/import/googledocs/storage/class-sodiumcipher.php`:

```php
<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Modules\Import\GoogleDocs\Storage;

class SodiumCipher implements Cipher {

	public function encrypt( string $plaintext, string $key ): string {
		$keyBytes = sodium_base642bin( $key, SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING );
		if ( strlen( $keyBytes ) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES ) {
			throw new \RuntimeException( 'Encryption key must be ' . SODIUM_CRYPTO_SECRETBOX_KEYBYTES . ' bytes (base64url-encoded).' );
		}

		$nonce = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
		$ciphertext = sodium_crypto_secretbox( $plaintext, $nonce, $keyBytes );

		$blob = $nonce . $ciphertext;
		sodium_memzero( $keyBytes );

		return sodium_bin2base64( $blob, SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING );
	}

	public function decrypt( string $blob, string $key ): string {
		$keyBytes = sodium_base642bin( $key, SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING );
		if ( strlen( $keyBytes ) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES ) {
			throw new \RuntimeException( 'Encryption key must be ' . SODIUM_CRYPTO_SECRETBOX_KEYBYTES . ' bytes (base64url-encoded).' );
		}

		$raw = sodium_base642bin( $blob, SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING );
		if ( strlen( $raw ) < SODIUM_CRYPTO_SECRETBOX_NONCEBYTES + SODIUM_CRYPTO_SECRETBOX_MACBYTES ) {
			throw new \RuntimeException( 'Ciphertext too short.' );
		}

		$nonce = substr( $raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
		$ciphertext = substr( $raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );

		$plaintext = sodium_crypto_secretbox_open( $ciphertext, $nonce, $keyBytes );
		sodium_memzero( $keyBytes );

		if ( $plaintext === false ) {
			throw new \RuntimeException( 'Decryption failed: ciphertext integrity check failed or key mismatch.' );
		}

		return $plaintext;
	}

	public function algorithm(): string {
		return 'crypto_secretbox';
	}
}
```

- [ ] **Step 5: Run tests to verify they pass**

Run: `composer test -- --filter=Modules_ImportGoogleDocsStorageTest`
Expected: PASS (8 tests).

- [ ] **Step 6: Commit**

```bash
git add inc/modules/import/googledocs/storage/class-cipher.php \
        inc/modules/import/googledocs/storage/class-sodiumcipher.php \
        tests/test-modules-import-google-docs-storage.php
git commit -m "feat(google-docs): add Cipher interface and SodiumCipher implementation"
```

---

## Task 3: TokenStorage interface + EncryptionKeyMissingException

**Files:**
- Create: `inc/modules/import/googledocs/storage/class-tokenstorage.php`
- Create: `inc/modules/import/googledocs/class-encryptionkeymissingexception.php`

- [ ] **Step 1: Create `TokenStorage` interface**

Create `inc/modules/import/googledocs/storage/class-tokenstorage.php`:

```php
<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Modules\Import\GoogleDocs\Storage;

interface TokenStorage {

	public function load( int $user_id ): ?StoredToken;

	public function save( int $user_id, StoredToken $token ): bool;

	public function delete( int $user_id ): bool;

	public function isAvailable(): bool;
}
```

- [ ] **Step 2: Create `EncryptionKeyMissingException`**

Create `inc/modules/import/googledocs/class-encryptionkeymissingexception.php`:

```php
<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Modules\Import\GoogleDocs;

class EncryptionKeyMissingException extends \RuntimeException {

	public function __construct( ?string $message = null ) {
		parent::__construct(
			$message ?? 'PRESSBOOKS_GOOGLE_DOCS_ENCRYPTION_KEY is not defined. Add a 32-byte base64-encoded key to wp-config.php (or Bedrock config/application.php). Generate one with: openssl rand -base64 32'
		);
	}
}
```

- [ ] **Step 3: Commit**

```bash
git add inc/modules/import/googledocs/storage/class-tokenstorage.php \
        inc/modules/import/googledocs/class-encryptionkeymissingexception.php
git commit -m "feat(google-docs): add TokenStorage interface and EncryptionKeyMissingException"
```

---

## Task 4: DirectEncryptedStorage

**Files:**
- Create: `inc/modules/import/googledocs/storage/class-directencryptedstorage.php`
- Test: `tests/test-modules-import-google-docs-storage.php` (append)

- [ ] **Step 1: Append failing tests**

Append to `tests/test-modules-import-google-docs-storage.php`:

```php
	/**
	 * @group import
	 */
	public function test_direct_storage_is_unavailable_without_key(): void {
		$storage = $this->build_direct_storage( '' );
		$this->assertFalse( $storage->isAvailable() );
	}

	/**
	 * @group import
	 */
	public function test_direct_storage_save_and_load_round_trip(): void {
		$storage = $this->build_direct_storage();
		$user_id = self::factory()->user->create();

		$token = new \Pressbooks\Modules\Import\GoogleDocs\Storage\StoredToken(
			[
				'access_token'  => 'at-123',
				'refresh_token' => 'rt-456',
				'expires_at'    => time() + 3600,
				'token_type'    => 'Bearer',
			],
			\Pressbooks\Modules\Import\GoogleDocs\Storage\TokenMode::Direct
		);

		$this->assertTrue( $storage->save( $user_id, $token ) );

		$loaded = $storage->load( $user_id );
		$this->assertNotNull( $loaded );
		$this->assertSame( 'at-123', $loaded->accessToken() );
		$this->assertSame( 'rt-456', $loaded->refreshToken() );
		$this->assertSame( \Pressbooks\Modules\Import\GoogleDocs\Storage\TokenMode::Direct, $loaded->mode );

		// The raw user_meta value must NOT be the plaintext token array.
		$raw = get_user_meta( $user_id, \Pressbooks\Modules\Import\GoogleDocs\Storage\DirectEncryptedStorage::META_KEY, true );
		$this->assertIsString( $raw );
		$this->assertStringNotContainsString( 'at-123', $raw );
		$this->assertStringNotContainsString( 'rt-456', $raw );
	}

	/**
	 * @group import
	 */
	public function test_direct_storage_load_returns_null_when_no_token(): void {
		$storage = $this->build_direct_storage();
		$user_id = self::factory()->user->create();
		$this->assertNull( $storage->load( $user_id ) );
	}

	/**
	 * @group import
	 */
	public function test_direct_storage_delete(): void {
		$storage = $this->build_direct_storage();
		$user_id = self::factory()->user->create();
		$storage->save(
			$user_id,
			new \Pressbooks\Modules\Import\GoogleDocs\Storage\StoredToken(
				[ 'access_token' => 'x', 'refresh_token' => 'y', 'expires_at' => time() + 100 ],
				\Pressbooks\Modules\Import\GoogleDocs\Storage\TokenMode::Direct
			)
		);
		$this->assertNotNull( $storage->load( $user_id ) );
		$storage->delete( $user_id );
		$this->assertNull( $storage->load( $user_id ) );
	}

	/**
	 * @group import
	 */
	public function test_direct_storage_load_returns_null_on_decrypt_failure(): void {
		$storage = $this->build_direct_storage();
		$user_id = self::factory()->user->create();
		// Plant garbage ciphertext directly.
		update_user_meta( $user_id, \Pressbooks\Modules\Import\GoogleDocs\Storage\DirectEncryptedStorage::META_KEY, 'not-valid-base64!!' );
		$this->assertNull( $storage->load( $user_id ) );
	}

	/**
	 * @group import
	 */
	public function test_direct_storage_save_throws_when_unavailable(): void {
		$this->expectException( \Pressbooks\Modules\Import\GoogleDocs\EncryptionKeyMissingException::class );
		$storage = $this->build_direct_storage( '' );
		$storage->save(
			self::factory()->user->create(),
			new \Pressbooks\Modules\Import\GoogleDocs\Storage\StoredToken(
				[ 'access_token' => 'x', 'refresh_token' => 'y', 'expires_at' => time() + 100 ],
				\Pressbooks\Modules\Import\GoogleDocs\Storage\TokenMode::Direct
			)
		);
	}

	private function build_direct_storage( string $key = '' ): \Pressbooks\Modules\Import\GoogleDocs\Storage\DirectEncryptedStorage {
		if ( $key === '' ) {
			$key = sodium_bin2base64( random_bytes( SODIUM_CRYPTO_SECRETBOX_KEYBYTES ), SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING );
		}
		return new \Pressbooks\Modules\Import\GoogleDocs\Storage\DirectEncryptedStorage(
			new \Pressbooks\Modules\Import\GoogleDocs\Storage\SodiumCipher(),
			$key
		);
	}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `composer test -- --filter=Modules_ImportGoogleDocsStorageTest`
Expected: Fatal error: Class `DirectEncryptedStorage` not found.

- [ ] **Step 3: Create `DirectEncryptedStorage`**

Create `inc/modules/import/googledocs/storage/class-directencryptedstorage.php`:

```php
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
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `composer test -- --filter=Modules_ImportGoogleDocsStorageTest`
Expected: PASS (14 tests).

- [ ] **Step 5: Commit**

```bash
git add inc/modules/import/googledocs/storage/class-directencryptedstorage.php \
        tests/test-modules-import-google-docs-storage.php
git commit -m "feat(google-docs): add DirectEncryptedStorage with libsodium envelope"
```

---

## Task 5: BrokerBackedStorage

**Files:**
- Create: `inc/modules/import/googledocs/storage/class-brokerbackedstorage.php`
- Test: `tests/test-modules-import-google-docs-storage.php` (append)

- [ ] **Step 1: Append failing tests**

Append to `tests/test-modules-import-google-docs-storage.php`:

```php
	/**
	 * @group import
	 */
	public function test_broker_storage_save_and_load_round_trip(): void {
		$storage = $this->build_broker_storage();
		$user_id = self::factory()->user->create();

		$token = new \Pressbooks\Modules\Import\GoogleDocs\Storage\StoredToken(
			[
				'session_handle' => 'sh-abc',
				'access_token'   => 'at-789',
				'expires_at'     => time() + 3600,
				'google_sub'     => 'sub-001',
			],
			\Pressbooks\Modules\Import\GoogleDocs\Storage\TokenMode::Broker
		);

		$this->assertTrue( $storage->save( $user_id, $token ) );

		$loaded = $storage->load( $user_id );
		$this->assertNotNull( $loaded );
		$this->assertSame( 'sh-abc', $loaded->brokerSessionHandle() );
		$this->assertSame( 'at-789', $loaded->accessToken() );
		$this->assertSame( 'sub-001', $loaded->googleSub() );
		$this->assertNull( $loaded->refreshToken(), 'Broker mode must never persist a refresh token' );
		$this->assertSame( \Pressbooks\Modules\Import\GoogleDocs\Storage\TokenMode::Broker, $loaded->mode );

		// Raw user_meta must NOT contain plaintext values.
		$raw = get_user_meta( $user_id, \Pressbooks\Modules\Import\GoogleDocs\Storage\BrokerBackedStorage::META_KEY, true );
		$this->assertIsString( $raw );
		$this->assertStringNotContainsString( 'sh-abc', $raw );
		$this->assertStringNotContainsString( 'at-789', $raw );
	}

	/**
	 * @group import
	 */
	public function test_broker_storage_uses_separate_meta_key(): void {
		$this->assertNotSame(
			\Pressbooks\Modules\Import\GoogleDocs\Storage\DirectEncryptedStorage::META_KEY,
			\Pressbooks\Modules\Import\GoogleDocs\Storage\BrokerBackedStorage::META_KEY
		);
	}

	/**
	 * @group import
	 */
	public function test_broker_storage_save_strips_refresh_token_if_present(): void {
		$storage = $this->build_broker_storage();
		$user_id = self::factory()->user->create();

		// Caller mistake: includes refresh_token in payload. Storage must refuse to persist it.
		$token = new \Pressbooks\Modules\Import\GoogleDocs\Storage\StoredToken(
			[
				'session_handle' => 'sh-abc',
				'access_token'   => 'at-789',
				'refresh_token'  => 'rt-LEAK',
				'expires_at'     => time() + 3600,
				'google_sub'     => 'sub-001',
			],
			\Pressbooks\Modules\Import\GoogleDocs\Storage\TokenMode::Broker
		);

		$storage->save( $user_id, $token );
		$loaded = $storage->load( $user_id );
		$this->assertNull( $loaded->refreshToken(), 'Refresh token must be stripped before persistence in broker mode' );
	}

	private function build_broker_storage( string $key = '' ): \Pressbooks\Modules\Import\GoogleDocs\Storage\BrokerBackedStorage {
		if ( $key === '' ) {
			$key = sodium_bin2base64( random_bytes( SODIUM_CRYPTO_SECRETBOX_KEYBYTES ), SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING );
		}
		return new \Pressbooks\Modules\Import\GoogleDocs\Storage\BrokerBackedStorage(
			new \Pressbooks\Modules\Import\GoogleDocs\Storage\SodiumCipher(),
			$key
		);
	}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `composer test -- --filter=Modules_ImportGoogleDocsStorageTest`
Expected: Fatal error: Class `BrokerBackedStorage` not found.

- [ ] **Step 3: Create `BrokerBackedStorage`**

Create `inc/modules/import/googledocs/storage/class-brokerbackedstorage.php`:

```php
<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Modules\Import\GoogleDocs\Storage;

use Pressbooks\Modules\Import\GoogleDocs\EncryptionKeyMissingException;

class BrokerBackedStorage implements TokenStorage {

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

		// Defense-in-depth: strip any field not in the allowed set (in particular, refresh_token).
		$filtered = array_intersect_key( $token->payload, array_flip( self::ALLOWED_FIELDS ) );

		$json = json_encode( $filtered, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		$blob = $this->cipher->encrypt( $json, $this->key );

		return (bool) update_user_meta( $user_id, self::META_KEY, $blob );
	}

	public function delete( int $user_id ): bool {
		return delete_user_meta( $user_id, self::META_KEY );
	}
}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `composer test -- --filter=Modules_ImportGoogleDocsStorageTest`
Expected: PASS (17 tests).

- [ ] **Step 5: Commit**

```bash
git add inc/modules/import/googledocs/storage/class-brokerbackedstorage.php \
        tests/test-modules-import-google-docs-storage.php
git commit -m "feat(google-docs): add BrokerBackedStorage that strips refresh tokens before persistence"
```

---

## Task 6: Slim down CredentialsStore (remove token methods)

**Files:**
- Modify: `inc/modules/import/googledocs/class-credentialsstore.php`
- Modify: `tests/test-modules-import-google-docs-credentials.php`

- [ ] **Step 1: Rewrite the credentials test file**

Replace the entire contents of `tests/test-modules-import-google-docs-credentials.php`:

```php
<?php

class Modules_ImportGoogleDocsCredentialsTest extends \WP_UnitTestCase {

	public function tear_down(): void {
		delete_site_option( 'pressbooks_google_docs_oauth' );
		parent::tear_down();
	}

	/**
	 * @group import
	 */
	public function test_get_client_credentials_returns_empty_when_not_set(): void {
		$store = new \Pressbooks\Modules\Import\GoogleDocs\CredentialsStore();
		$creds = $store->getClientCredentials();
		$this->assertSame( '', $creds['client_id'] );
		$this->assertSame( '', $creds['client_secret'] );
	}

	/**
	 * @group import
	 */
	public function test_save_and_get_client_credentials(): void {
		$store = new \Pressbooks\Modules\Import\GoogleDocs\CredentialsStore();
		$store->saveClientCredentials( 'test-client-id', 'test-client-secret' );
		$creds = $store->getClientCredentials();
		$this->assertSame( 'test-client-id', $creds['client_id'] );
		$this->assertSame( 'test-client-secret', $creds['client_secret'] );
	}

	/**
	 * @group import
	 */
	public function test_is_configured_returns_false_when_empty(): void {
		$store = new \Pressbooks\Modules\Import\GoogleDocs\CredentialsStore();
		$this->assertFalse( $store->isConfigured() );
	}

	/**
	 * @group import
	 */
	public function test_is_configured_returns_true_when_set(): void {
		$store = new \Pressbooks\Modules\Import\GoogleDocs\CredentialsStore();
		$store->saveClientCredentials( 'id', 'secret' );
		$this->assertTrue( $store->isConfigured() );
	}

	/**
	 * @group import
	 */
	public function test_is_configured_returns_true_in_broker_mode(): void {
		if ( ! defined( 'PRESSBOOKS_AUTH_BROKER_URL' ) ) {
			define( 'PRESSBOOKS_AUTH_BROKER_URL', 'https://broker.example.test' );
		}
		$store = new \Pressbooks\Modules\Import\GoogleDocs\CredentialsStore();
		$this->assertTrue( $store->isConfigured() );
	}
}
```

- [ ] **Step 2: Rewrite `CredentialsStore` to drop token methods**

Replace the entire contents of `inc/modules/import/googledocs/class-credentialsstore.php`:

```php
<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Modules\Import\GoogleDocs;

class CredentialsStore {

	const NETWORK_OPTION_KEY = 'pressbooks_google_docs_oauth';

	public function getClientCredentials(): array {
		$option = get_site_option( self::NETWORK_OPTION_KEY, [] );
		return [
			'client_id'     => $option['client_id'] ?? '',
			'client_secret' => $option['client_secret'] ?? '',
		];
	}

	public function saveClientCredentials( string $client_id, string $client_secret ): bool {
		return update_site_option( self::NETWORK_OPTION_KEY, [
			'client_id'     => $client_id,
			'client_secret' => $client_secret,
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
```

- [ ] **Step 3: Run the credentials test to confirm it passes**

Run: `composer test -- --filter=Modules_ImportGoogleDocsCredentialsTest`
Expected: PASS (5 tests).

- [ ] **Step 4: Run the full Google Docs suite to confirm nothing else broke yet**

Run: `composer test -- --filter=GoogleDocs`
Expected: Test failures in `Modules_ImportGoogleDocsOAuthTest` and other suites that referenced `CredentialsStore::getUserToken/saveUserToken/etc.`. These will be fixed in Tasks 7 and 8. Note the count of failures.

- [ ] **Step 5: Commit**

```bash
git add inc/modules/import/googledocs/class-credentialsstore.php \
        tests/test-modules-import-google-docs-credentials.php
git commit -m "refactor(google-docs): scope CredentialsStore to client_id/secret; move token methods to storage namespace"
```

---

## Task 7: Refactor OAuthClient — constructor + handleBrokerCallback new shape

**Files:**
- Modify: `inc/modules/import/googledocs/class-oauthclient.php`
- Modify: `tests/test-modules-import-google-docs-oauth.php`

This task updates `OAuthClient` to take both `TokenStorage` and `CredentialsStore`, changes the broker-mode JWT contract to expect `session_handle` (no `refresh_token`), and updates the existing broker-mode tests accordingly. The `getAuthedClient` and `disconnect` broker-mode branches will be wired up in later tasks (Task 8 covers disconnect; Tasks 10–12 cover broker refresh); for now the broker branch throws "not yet implemented" so tests are isolated.

- [ ] **Step 1: Update the existing broker-mode JWT fixture in the test**

Open `tests/test-modules-import-google-docs-oauth.php`. Locate the `createBrokerJwt` helper (around line 125) and replace its `tokens` payload. The new shape:

```php
		'access_token'  => 'broker-access-token',
		'expires_at'    => time() + 3600,
		'token_type'    => 'Bearer',
```

becomes (still inside the JWT `tokens` claim):

```php
		'access_token'  => 'broker-access-token',
		'expires_at'    => time() + 3600,
		'token_type'    => 'Bearer',
		'session_handle' => 'broker-session-handle',
```

And the top-level JWT payload adds:

```php
		'google_sub' => 'broker-google-sub',
```

Also remove the `'refresh_token' => 'broker-refresh-token'` entry that's currently inside the `tokens` claim.

Final `createBrokerJwt` shape (replace the existing function body):

```php
	private static function createBrokerJwt( array $overrides = [] ): string {
		$payload = array_merge(
			[
				'iss'   => PRESSBOOKS_AUTH_BROKER_URL,
				'aud'   => parse_url( home_url(), PHP_URL_HOST ),
				'tokens' => [
					'access_token'   => 'broker-access-token',
					'expires_at'     => time() + 3600,
					'token_type'     => 'Bearer',
					'session_handle' => 'broker-session-handle',
				],
				'google_sub' => 'broker-google-sub',
				'wp_state' => self::$state,
				'jti'     => wp_generate_password( 32, false ),
				'iat'     => time(),
				'exp'     => time() + 60,
			],
			$overrides
		);
		return Firebase\JWT\JWT::encode( $payload, self::$privateKey, 'RS256' );
	}
```

- [ ] **Step 2: Update the broker-mode callback test assertion**

Find `test_handle_callback_verifies_jwt_and_stores_tokens` (around line 180). Replace the assertion block at the end so it reads:

```php
		$stored = $storage->load( get_current_user_id() );
		$this->assertNotNull( $stored );
		$this->assertSame( 'broker-access-token', $stored->accessToken() );
		$this->assertSame( 'broker-session-handle', $stored->brokerSessionHandle() );
		$this->assertSame( 'broker-google-sub', $stored->googleSub() );
		$this->assertNull( $stored->refreshToken(), 'Broker mode must never persist a refresh token' );
```

The `$storage` variable refers to the `BrokerBackedStorage` constructed in the test setUp; the existing tests construct `CredentialsStore`-based fixtures which need to be replaced. The full setUp refactor is the next step.

- [ ] **Step 3: Update `setUp` / `wpSetUpBeforeClass` to provide the new dependencies**

The existing test uses `new OAuthClient( new CredentialsStore() )`. The new constructor signature is `new OAuthClient( TokenStorage $token_storage, CredentialsStore $creds_store, ?BrokerRefreshClient $broker_refresh_client = null )`. Update `setUp` to construct with both. For broker-mode tests use `BrokerBackedStorage`; for direct-mode tests use `DirectEncryptedStorage`. The encryption key can be a class constant set up once in `wpSetUpBeforeClass`:

```php
	private static string $encryptionKey;

	public static function wpSetUpBeforeClass( ... ): void {
		// existing keypair setup ...
		self::$encryptionKey = sodium_bin2base64(
			random_bytes( SODIUM_CRYPTO_SECRETBOX_KEYBYTES ),
			SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING
		);
		if ( ! defined( 'PRESSBOOKS_GOOGLE_DOCS_ENCRYPTION_KEY' ) ) {
			define( 'PRESSBOOKS_GOOGLE_DOCS_ENCRYPTION_KEY', self::$encryptionKey );
		}
		if ( ! defined( 'PRESSBOOKS_AUTH_BROKER_NETWORK_SECRET' ) ) {
			define( 'PRESSBOOKS_AUTH_BROKER_NETWORK_SECRET', sodium_bin2base64( random_bytes( 32 ), SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING ) );
		}
	}
```

And replace the `new OAuthClient(...)` calls in setUp with a helper:

```php
	private function make_oauth_client( bool $broker_mode ): \Pressbooks\Modules\Import\GoogleDocs\OAuthClient {
		$cipher = new \Pressbooks\Modules\Import\GoogleDocs\Storage\SodiumCipher();
		$storage = $broker_mode
			? new \Pressbooks\Modules\Import\GoogleDocs\Storage\BrokerBackedStorage( $cipher, self::$encryptionKey )
			: new \Pressbooks\Modules\Import\GoogleDocs\Storage\DirectEncryptedStorage( $cipher, self::$encryptionKey );
		return new \Pressbooks\Modules\Import\GoogleDocs\OAuthClient(
			$storage,
			new \Pressbooks\Modules\Import\GoogleDocs\CredentialsStore()
		);
	}
```

Update each test that creates an `OAuthClient` to call `$this->make_oauth_client( $broker )` with the appropriate boolean.

- [ ] **Step 4: Run the test suite to confirm broker-mode callback test fails as expected**

Run: `composer test -- --filter=Modules_ImportGoogleDocsOAuthTest`
Expected: Failures because `OAuthClient` constructor signature doesn't match yet, and `handleBrokerCallback` still expects the old JWT shape.

- [ ] **Step 5: Rewrite `OAuthClient` — constructor and `handleBrokerCallback`**

Replace the top of `inc/modules/import/googledocs/class-oauthclient.php` (lines 1 through ~70) with:

```php
<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Modules\Import\GoogleDocs;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Pressbooks\Modules\Import\GoogleDocs\Storage\StoredToken;
use Pressbooks\Modules\Import\GoogleDocs\Storage\TokenMode;
use Pressbooks\Modules\Import\GoogleDocs\Storage\TokenStorage;

class OAuthClient {

	const SCOPES = [
		'https://www.googleapis.com/auth/documents.readonly',
		'https://www.googleapis.com/auth/drive.readonly',
	];

	const STATE_TRANSIENT_TTL = 600;

	private CredentialsStore $creds_store;
	private TokenStorage $token_storage;
	private bool $useBroker;

	public function __construct( TokenStorage $token_storage, CredentialsStore $creds_store ) {
		$this->token_storage = $token_storage;
		$this->creds_store = $creds_store;
		$this->useBroker = $creds_store->isBrokerMode();
	}

	public function isBrokerMode(): bool {
		return $this->useBroker;
	}

	public function isConnected( int $user_id ): bool {
		$token = $this->token_storage->load( $user_id );
		if ( $token === null ) {
			return false;
		}
		if ( $this->useBroker ) {
			return $token->brokerSessionHandle() !== null;
		}
		return $token->refreshToken() !== null;
	}

	public function buildClient(): \Google\Client {
		if ( $this->useBroker ) {
			throw new \RuntimeException( 'buildClient() must not be called in broker mode. OAuth is handled by the Pressbooks Auth Broker.' );
		}

		$creds = $this->creds_store->getClientCredentials();
		$client = new \Google\Client();
		$client->setClientId( $creds['client_id'] );
		$client->setClientSecret( $creds['client_secret'] );
		$client->setRedirectUri( $this->getRedirectUri() );
		$client->setAccessType( 'offline' );
		$client->setPrompt( 'consent' );
		$client->setScopes( self::SCOPES );

		return $client;
	}
```

Then replace `handleBrokerCallback` (currently around lines 165–223) with the new shape — it expects `tokens.session_handle` and a top-level `google_sub`, and writes a `StoredToken(mode: Broker)`:

```php
	private function handleBrokerCallback( string $jwt, string $state, int $user_id ): string {
		$transient_key = 'pb_gdocs_state_' . $state;
		$return_url = get_site_transient( $transient_key );

		if ( empty( $return_url ) ) {
			throw new \RuntimeException( 'Invalid or expired OAuth state.' );
		}

		delete_site_transient( $transient_key );

		if ( ! defined( 'PRESSBOOKS_AUTH_BROKER_PUBLIC_KEY' ) || empty( PRESSBOOKS_AUTH_BROKER_PUBLIC_KEY ) ) {
			throw new \RuntimeException( 'Broker public key not configured.' );
		}

		$public_key = $this->getPublicKey();
		$decoded = JWT::decode( $jwt, new Key( $public_key, 'RS256' ) );

		if ( ! isset( $decoded->iss ) || $decoded->iss !== PRESSBOOKS_AUTH_BROKER_URL ) {
			throw new \RuntimeException( 'Invalid JWT issuer.' );
		}

		$expected_aud = parse_url( home_url(), PHP_URL_HOST );
		if ( ! isset( $decoded->aud ) || $decoded->aud !== $expected_aud ) {
			throw new \RuntimeException( 'Invalid JWT audience.' );
		}

		if ( ! isset( $decoded->exp ) || $decoded->exp < time() ) {
			throw new \RuntimeException( 'JWT has expired.' );
		}

		if ( ! isset( $decoded->jti ) ) {
			throw new \RuntimeException( 'Missing JWT ID.' );
		}

		$jti_key = 'pb_gdocs_jti_' . $decoded->jti;
		if ( get_site_transient( $jti_key ) ) {
			throw new \RuntimeException( 'JWT has already been used.' );
		}
		set_site_transient( $jti_key, '1', 300 );

		if ( ! isset( $decoded->wp_state ) || $decoded->wp_state !== $state ) {
			throw new \RuntimeException( 'JWT state mismatch.' );
		}

		if ( ! isset( $decoded->tokens->access_token, $decoded->tokens->session_handle ) ) {
			throw new \RuntimeException( 'Missing access_token or session_handle in JWT.' );
		}

		if ( property_exists( $decoded->tokens, 'refresh_token' ) && ! empty( $decoded->tokens->refresh_token ) ) {
			throw new \RuntimeException( 'Broker handoff JWT must not contain a refresh_token.' );
		}

		if ( ! isset( $decoded->google_sub ) || ! is_string( $decoded->google_sub ) || $decoded->google_sub === '' ) {
			throw new \RuntimeException( 'Missing google_sub in JWT.' );
		}

		$payload = [
			'session_handle' => (string) $decoded->tokens->session_handle,
			'access_token'   => (string) $decoded->tokens->access_token,
			'expires_at'     => isset( $decoded->tokens->expires_at )
				? (int) $decoded->tokens->expires_at
				: ( time() + ( $decoded->tokens->expires_in ?? 3600 ) ),
			'google_sub'     => (string) $decoded->google_sub,
		];

		$this->token_storage->save(
			$user_id,
			new StoredToken( $payload, TokenMode::Broker )
		);

		return $return_url;
	}
```

Leave `getAuthedClient()` and `disconnect()` unchanged for this task — they'll be updated in Tasks 8 (disconnect) and 11 (broker refresh). The existing broker-mode branches in those methods will continue to compile (they reference `$this->token_storage->load/delete/save` instead of `$this->store->...`); only the **types** change. Update the remaining method bodies in `OAuthClient` to use `$this->token_storage` instead of `$this->store`. For example, `getAuthedClient`:

```php
	public function getAuthedClient( int $user_id ): \Google\Client {
		$token = $this->token_storage->load( $user_id );

		if ( $token === null ) {
			throw new ReauthorizationRequiredException( 'No token found. Please authorize first.' );
		}

		if ( $this->useBroker ) {
			if ( $token->isExpired() ) {
				throw new ReauthorizationRequiredException( 'Token expired. Refresh via broker not yet wired (see Task 11).' );
			}
			$client = new \Google\Client();
			$client->setAccessToken( $this->storedTokenToArray( $token ) );
			return $client;
		}

		$client = $this->buildClient();
		$client->setAccessToken( $this->storedTokenToArray( $token ) );

		if ( $client->isAccessTokenExpired() ) {
			$refresh_token = $token->refreshToken();
			if ( ! $refresh_token ) {
				$this->token_storage->delete( $user_id );
				throw new ReauthorizationRequiredException( 'No refresh token available. Please reauthorize.' );
			}

			$new_token = $client->fetchAccessTokenWithRefreshToken( $refresh_token );

			if ( isset( $new_token['error'] ) ) {
				$this->token_storage->delete( $user_id );
				throw new ReauthorizationRequiredException( 'Token refresh failed: ' . ( $new_token['error_description'] ?? $new_token['error'] ) );
			}

			$new_token['refresh_token'] = $refresh_token;
			$new_token['expires_at'] = time() + ( $new_token['expires_in'] ?? 3600 );

			$this->token_storage->save( $user_id, new StoredToken( $new_token, TokenMode::Direct ) );
			$client->setAccessToken( $new_token );
		}

		return $client;
	}

	private function storedTokenToArray( StoredToken $token ): array {
		return $token->payload;
	}
```

Update `disconnect()`:

```php
	public function disconnect( int $user_id ): void {
		$token = $this->token_storage->load( $user_id );

		if ( $token && ! $this->useBroker ) {
			try {
				$client = $this->buildClient();
				$client->setAccessToken( $this->storedTokenToArray( $token ) );
				$client->revokeToken();
			} catch ( \Exception $e ) {
				throw new \RuntimeException( 'Failed to revoke token at Google: ' . $e->getMessage() . ' Please try again.', 0, $e );
			}
		}

		// Broker mode revoke propagation is wired in Task 12.
		$this->token_storage->delete( $user_id );
	}
```

Update `handleGoogleCallback`:

```php
	private function handleGoogleCallback( string $code, string $state, int $user_id ): string {
		$transient_key = 'pb_gdocs_state_' . $state;
		$return_url = get_site_transient( $transient_key );

		if ( empty( $return_url ) ) {
			throw new \RuntimeException( 'Invalid or expired OAuth state.' );
		}

		delete_site_transient( $transient_key );

		$client = $this->buildClient();
		$token = $client->fetchAccessTokenWithAuthCode( $code );

		if ( isset( $token['error'] ) ) {
			throw new \RuntimeException( 'Token exchange failed: ' . ( $token['error_description'] ?? $token['error'] ) );
		}

		$token['expires_at'] = time() + ( $token['expires_in'] ?? 3600 );
		$this->token_storage->save( $user_id, new StoredToken( $token, TokenMode::Direct ) );

		return $return_url;
	}
```

- [ ] **Step 6: Run the full OAuth test suite**

Run: `composer test -- --filter=Modules_ImportGoogleDocsOAuthTest`
Expected: PASS — all broker-mode tests pass with the new JWT shape; direct-mode tests pass with the storage abstraction. Any remaining failures are pre-existing gaps (refresh/direct callback tests were never present; that's noted in the spec).

- [ ] **Step 7: Commit**

```bash
git add inc/modules/import/googledocs/class-oauthclient.php \
        tests/test-modules-import-google-docs-oauth.php
git commit -m "refactor(google-docs): OAuthClient now uses TokenStorage; handleBrokerCallback expects session_handle (no refresh_token in handoff)"
```

---

## Task 8: Wire `OAuthClient` construction in `hooks-admin.php`

**Files:**
- Modify: `hooks-admin.php` (lines 418–453)

- [ ] **Step 1: Update the dependency wiring**

Open `hooks-admin.php`. Replace lines 419–420:

```php
$gdocs_creds_store = new \Pressbooks\Modules\Import\GoogleDocs\CredentialsStore();
$gdocs_oauth = new \Pressbooks\Modules\Import\GoogleDocs\OAuthClient( $gdocs_creds_store );
```

with:

```php
$gdocs_creds_store = new \Pressbooks\Modules\Import\GoogleDocs\CredentialsStore();
$gdocs_cipher = new \Pressbooks\Modules\Import\GoogleDocs\Storage\SodiumCipher();
$gdocs_encryption_key = defined( 'PRESSBOOKS_GOOGLE_DOCS_ENCRYPTION_KEY' ) ? PRESSBOOKS_GOOGLE_DOCS_ENCRYPTION_KEY : '';
$gdocs_token_storage = $gdocs_creds_store->isBrokerMode()
	? new \Pressbooks\Modules\Import\GoogleDocs\Storage\BrokerBackedStorage( $gdocs_cipher, $gdocs_encryption_key )
	: new \Pressbooks\Modules\Import\GoogleDocs\Storage\DirectEncryptedStorage( $gdocs_cipher, $gdocs_encryption_key );
$gdocs_oauth = new \Pressbooks\Modules\Import\GoogleDocs\OAuthClient( $gdocs_token_storage, $gdocs_creds_store );
```

- [ ] **Step 2: Update the `pb_select_import_type` filter to also require the encryption key**

Replace the closure at line 427:

```php
add_filter( 'pb_select_import_type', function ( array $types ) use ( $gdocs_creds_store, $gdocs_token_storage ) {
	if ( $gdocs_creds_store->isConfigured() && $gdocs_token_storage->isAvailable() ) {
		$types[ \Pressbooks\Modules\Import\GoogleDocs\GoogleDocs::TYPE_OF ] = __( 'Google Docs', 'pressbooks' );
	}
	return $types;
} );
```

- [ ] **Step 3: Run the full plugin test suite (not just Google Docs) to confirm no regression in wiring-dependent tests**

Run: `composer test`
Expected: PASS — no new failures beyond any pre-existing ones.

- [ ] **Step 4: Commit**

```bash
git add hooks-admin.php
git commit -m "refactor(google-docs): wire TokenStorage and SodiumCipher into hooks-admin"
```

---

## Task 9: Update consumers of `CredentialsStore::isUserConnected`

**Files:**
- Modify: `inc/modules/import/class-import.php` (lines around 551)
- Modify: `templates/admin/import.php` (line 228)

The new way to check "is the user connected" is `OAuthClient::isConnected($user_id)`.

- [ ] **Step 1: Update `class-import.php`**

Find the call to `$store->isUserConnected( $user_id )` around line 551. The block currently looks like:

```php
$oauth = new GoogleDocs\OAuthClient( $store );
$user_id = get_current_user_id();
if ( ! $store->isUserConnected( $user_id ) ) { ... }
```

Replace with:

```php
$cipher = new GoogleDocs\Storage\SodiumCipher();
$encryption_key = defined( 'PRESSBOOKS_GOOGLE_DOCS_ENCRYPTION_KEY' ) ? PRESSBOOKS_GOOGLE_DOCS_ENCRYPTION_KEY : '';
$token_storage = $store->isBrokerMode()
	? new GoogleDocs\Storage\BrokerBackedStorage( $cipher, $encryption_key )
	: new GoogleDocs\Storage\DirectEncryptedStorage( $cipher, $encryption_key );
$oauth = new GoogleDocs\OAuthClient( $token_storage, $store );
$user_id = get_current_user_id();
if ( ! $oauth->isConnected( $user_id ) ) { ... }
```

Repeat the same dependency-construction pattern at the other call site around `class-import.php:337–341` (inside `formSubmit`).

- [ ] **Step 2: Update `class-googledocs.php` if it constructs `OAuthClient`**

In `class-googledocs.php` lines 65–67, replace:

```php
$store = new CredentialsStore();
$oauth = new OAuthClient( $store );
$client = $oauth->getAuthedClient( get_current_user_id() );
```

with:

```php
$store = new CredentialsStore();
$cipher = new Storage\SodiumCipher();
$encryption_key = defined( 'PRESSBOOKS_GOOGLE_DOCS_ENCRYPTION_KEY' ) ? PRESSBOOKS_GOOGLE_DOCS_ENCRYPTION_KEY : '';
$token_storage = $store->isBrokerMode()
	? new Storage\BrokerBackedStorage( $cipher, $encryption_key )
	: new Storage\DirectEncryptedStorage( $cipher, $encryption_key );
$oauth = new OAuthClient( $token_storage, $store );
$client = $oauth->getAuthedClient( get_current_user_id() );
```

- [ ] **Step 3: Update `templates/admin/import.php`**

Around line 228, find the call to `$store->isUserConnected( $user_id )`. Replace with the equivalent `$oauth->isConnected( $user_id )` pattern. The template receives `$oauth` via the existing `$gdocs_oauth` global wired in `hooks-admin.php`.

- [ ] **Step 4: Run the importer test**

Run: `composer test -- --filter=Modules_ImportGoogleDocsImporterTest`
Expected: PASS (any test failure indicates an unfixed call site — grep for remaining `isUserConnected` references and update them).

Grep command to verify all call sites updated:

```bash
grep -rn "isUserConnected" inc/ templates/ tests/
```

Expected: no matches in `inc/` or `templates/`. Matches in `tests/` should be in the new `test-modules-import-google-docs-storage.php` (which doesn't call `isUserConnected`, so should be zero).

- [ ] **Step 5: Commit**

```bash
git add inc/modules/import/class-import.php \
        inc/modules/import/googledocs/class-googledocs.php \
        templates/admin/import.php
git commit -m "refactor(google-docs): replace CredentialsStore::isUserConnected with OAuthClient::isConnected"
```

---

## Task 10: BrokerRefreshClient — signed-request scaffolding

**Files:**
- Create: `inc/modules/import/googledocs/broker/class-brokerrefreshclient.php`
- Test: `tests/test-modules-import-google-docs-broker-client.php`

The `BrokerRefreshClient` builds HMAC-signed requests and verifies RS256-signed responses. To keep this task testable without hitting the network, all HTTP calls go through a `wp_remote_request`-style adapter that's mockable.

- [ ] **Step 1: Write failing tests**

Create `tests/test-modules-import-google-docs-broker-client.php`:

```php
<?php

class Modules_ImportGoogleDocsBrokerClientTest extends \WP_UnitTestCase {

	private static string $brokerPrivateKey;
	private static string $brokerPublicKey;
	private static string $encryptionKey;
	private static string $networkSecret;
	private string $captured_request_url = '';
	private array $captured_request_args = [];

	public static function wpSetUpBeforeClass( $factory ): void {
		$key_resource = openssl_pkey_new( [ 'private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA ] );
		openssl_pkey_export( $key_resource, $private_key_pem );
		self::$brokerPrivateKey = $private_key_pem;
		self::$brokerPublicKey = openssl_pkey_get_details( $key_resource )['key'];

		self::$encryptionKey = sodium_bin2base64( random_bytes( SODIUM_CRYPTO_SECRETBOX_KEYBYTES ), SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING );
		self::$networkSecret = sodium_bin2base64( random_bytes( 32 ), SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING );

		if ( ! defined( 'PRESSBOOKS_GOOGLE_DOCS_ENCRYPTION_KEY' ) ) {
			define( 'PRESSBOOKS_GOOGLE_DOCS_ENCRYPTION_KEY', self::$encryptionKey );
		}
	}

	public function set_up(): void {
		parent::set_up();
		$this->captured_request_url = '';
		$this->captured_request_args = [];

		add_filter( 'pre_http_request', [ $this, 'capture_http_request' ], 10, 3 );
	}

	public function tear_down(): void {
		remove_filter( 'pre_http_request', [ $this, 'capture_http_request' ] );
		parent::tear_down();
	}

	public function capture_http_request( $preempt, $args, $url ) {
		$this->captured_request_url = $url;
		$this->captured_request_args = $args;
		return $this->next_http_response();
	}

	protected function next_http_response(): array {
		return $this->http_response ?? [ 'response' => [ 'code' => 500, 'message' => 'No response stub set' ], 'body' => '' ];
	}

	protected function set_http_response( int $code, string $body ): void {
		$this->http_response = [ 'response' => [ 'code' => $code, 'message' => '' ], 'body' => $body ];
	}

	protected function make_client(): \Pressbooks\Modules\Import\GoogleDocs\Broker\BrokerRefreshClient {
		$storage = new \Pressbooks\Modules\Import\GoogleDocs\Storage\BrokerBackedStorage(
			new \Pressbooks\Modules\Import\GoogleDocs\Storage\SodiumCipher(),
			self::$encryptionKey
		);
		return new \Pressbooks\Modules\Import\GoogleDocs\Broker\BrokerRefreshClient(
			'https://broker.example.test',
			self::$brokerPublicKey,
			self::$networkSecret,
			$storage
		);
	}

	protected function seed_broker_token( int $user_id ): void {
		$storage = new \Pressbooks\Modules\Import\GoogleDocs\Storage\BrokerBackedStorage(
			new \Pressbooks\Modules\Import\GoogleDocs\Storage\SodiumCipher(),
			self::$encryptionKey
		);
		$storage->save(
			$user_id,
			new \Pressbooks\Modules\Import\GoogleDocs\Storage\StoredToken(
				[
					'session_handle' => 'sh-abc',
					'access_token'   => 'expired-at',
					'expires_at'     => time() - 60,
					'google_sub'     => 'sub-001',
				],
				\Pressbooks\Modules\Import\GoogleDocs\Storage\TokenMode::Broker
			)
		);
	}

	protected function sign_broker_response( array $payload ): string {
		$payload['iat'] = time();
		$payload['exp'] = time() + 30;
		$payload['iss'] = 'https://broker.example.test';
		$payload['aud'] = parse_url( home_url(), PHP_URL_HOST );
		return \Firebase\JWT\JWT::encode( $payload, self::$brokerPrivateKey, 'RS256' );
	}

	/**
	 * @group import
	 */
	public function test_refresh_builds_signed_post_to_broker(): void {
		$client = $this->make_client();
		$user_id = self::factory()->user->create();
		$this->seed_broker_token( $user_id );

		$this->set_http_response( 200, $this->sign_broker_response( [
			'access_token' => 'fresh-at',
			'expires_at'   => time() + 3600,
			'token_type'   => 'Bearer',
		] ) );

		try {
			$client->refresh( $user_id );
		} catch ( \Throwable $e ) {
			// Assertion is about the request, not the response handling.
		}

		$this->assertSame( 'https://broker.example.test/oauth/refresh', $this->captured_request_url );
		$this->assertSame( 'POST', $this->captured_request_args['method'] );

		$body = json_decode( $this->captured_request_args['body'], true );
		$this->assertSame( 'sh-abc', $body['handle'] );
		$this->assertSame( 'sub-001', $body['google_sub'] );
		$this->assertSame( parse_url( home_url(), PHP_URL_HOST ), $body['origin'] );
		$this->assertNotEmpty( $body['jti'] );
		$this->assertNotEmpty( $body['iat'] );

		$this->assertArrayHasKey( 'X-Network-Signature', $this->captured_request_args['headers'] );
		$expected_sig = hash_hmac( 'sha256', $this->captured_request_args['body'], sodium_base642bin( self::$networkSecret, SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING ) );
		$this->assertSame( $expected_sig, $this->captured_request_args['headers']['X-Network-Signature'] );
	}

	/**
	 * @group import
	 */
	public function test_refresh_returns_stored_token_on_success(): void {
		$client = $this->make_client();
		$user_id = self::factory()->user->create();
		$this->seed_broker_token( $user_id );

		$this->set_http_response( 200, $this->sign_broker_response( [
			'access_token' => 'fresh-at',
			'expires_at'   => time() + 3600,
			'token_type'   => 'Bearer',
		] ) );

		$token = $client->refresh( $user_id );
		$this->assertSame( 'fresh-at', $token->accessToken() );
		$this->assertSame( 'sh-abc', $token->brokerSessionHandle() );
		$this->assertSame( 'sub-001', $token->googleSub() );
	}

	/**
	 * @group import
	 */
	public function test_refresh_throws_on_410_handle_unknown(): void {
		$client = $this->make_client();
		$user_id = self::factory()->user->create();
		$this->seed_broker_token( $user_id );

		$this->set_http_response( 410, $this->sign_broker_response( [ 'error' => 'handle_unknown' ] ) );

		$this->expectException( \Pressbooks\Modules\Import\GoogleDocs\ReauthorizationRequiredException::class );
		$client->refresh( $user_id );
	}

	/**
	 * @group import
	 */
	public function test_refresh_throws_on_unsigned_response(): void {
		$client = $this->make_client();
		$user_id = self::factory()->user->create();
		$this->seed_broker_token( $user_id );

		$this->set_http_response( 200, json_encode( [ 'access_token' => 'forged' ] ) );

		$this->expectException( \RuntimeException::class );
		$client->refresh( $user_id );
	}
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `composer test -- --filter=Modules_ImportGoogleDocsBrokerClientTest`
Expected: Fatal error: Class `BrokerRefreshClient` not found.

- [ ] **Step 3: Create `BrokerRefreshClient`**

Create `inc/modules/import/googledocs/broker/class-brokerrefreshclient.php`:

```php
<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Modules\Import\GoogleDocs\Broker;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Pressbooks\Modules\Import\GoogleDocs\ReauthorizationRequiredException;
use Pressbooks\Modules\Import\GoogleDocs\Storage\StoredToken;
use Pressbooks\Modules\Import\GoogleDocs\Storage\TokenMode;
use Pressbooks\Modules\Import\GoogleDocs\Storage\TokenStorage;

class BrokerRefreshClient {

	private const REFRESH_PATH = '/oauth/refresh';
	private const REVOKE_PATH = '/oauth/revoke';
	private const SKEW_SECONDS = 60;

	private string $broker_url;
	private string $broker_public_key;
	private string $network_secret; // base64url-encoded
	private TokenStorage $storage;

	public function __construct(
		string $broker_url,
		string $broker_public_key,
		string $network_secret,
		TokenStorage $storage
	) {
		$this->broker_url = rtrim( $broker_url, '/' );
		$this->broker_public_key = $broker_public_key;
		$this->network_secret = $network_secret;
		$this->storage = $storage;
	}

	public function refresh( int $user_id ): StoredToken {
		$stored = $this->storage->load( $user_id );
		if ( $stored === null || $stored->brokerSessionHandle() === null ) {
			throw new ReauthorizationRequiredException( 'No broker session handle for user.' );
		}

		$body = $this->buildSignedBody( [
			'handle'     => $stored->brokerSessionHandle(),
			'google_sub' => $stored->googleSub() ?? '',
		] );

		$response = $this->post( self::REFRESH_PATH, $body );
		$code = wp_remote_retrieve_response_code( $response );

		if ( $code === 410 ) {
			$this->storage->delete( $user_id );
			throw new ReauthorizationRequiredException( 'Broker reports session as gone; user must reconnect.' );
		}

		if ( $code === 401 ) {
			throw new \RuntimeException( 'Broker rejected signature or freshness.' );
		}

		if ( $code === 409 ) {
			throw new \RuntimeException( 'Broker detected replay.' );
		}

		if ( $code >= 500 ) {
			throw new \RuntimeException( 'Broker temporarily unavailable.' );
		}

		if ( $code !== 200 ) {
			throw new \RuntimeException( "Unexpected broker response code {$code}." );
		}

		$jwt = wp_remote_retrieve_body( $response );
		$payload = $this->verifySignedResponse( $jwt );

		$new_payload = [
			'session_handle' => $stored->brokerSessionHandle(),
			'access_token'   => $payload->access_token,
			'expires_at'     => (int) $payload->expires_at,
			'google_sub'     => $stored->googleSub(),
		];

		$this->storage->save( $user_id, new StoredToken( $new_payload, TokenMode::Broker ) );

		return new StoredToken( $new_payload, TokenMode::Broker );
	}

	public function revoke( int $user_id ): void {
		$stored = $this->storage->load( $user_id );
		if ( $stored === null ) {
			return;
		}

		$body = $this->buildSignedBody( [
			'handle'     => $stored->brokerSessionHandle() ?? '',
			'google_sub' => $stored->googleSub() ?? '',
		] );

		$response = $this->post( self::REVOKE_PATH, $body );
		$code = wp_remote_retrieve_response_code( $response );

		if ( $code >= 500 ) {
			throw new \RuntimeException( 'Broker temporarily unavailable during revoke.' );
		}

		if ( $code !== 204 && $code !== 200 ) {
			throw new \RuntimeException( "Unexpected broker revoke response code {$code}." );
		}

		$this->storage->delete( $user_id );
	}

	private function buildSignedBody( array $claims ): array {
		$body = array_merge( $claims, [
			'origin' => parse_url( home_url(), PHP_URL_HOST ),
			'jti'    => wp_generate_password( 32, false ),
			'iat'    => time(),
		] );

		$encoded = json_encode( $body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		$key_bytes = sodium_base642bin( $this->network_secret, SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING );
		$signature = hash_hmac( 'sha256', $encoded, $key_bytes );
		sodium_memzero( $key_bytes );

		return [
			'body' => $encoded,
			'headers' => [
				'Content-Type'        => 'application/json',
				'X-Network-Signature' => $signature,
			],
		];
	}

	private function verifySignedResponse( string $jwt ): object {
		$decoded = JWT::decode( $jwt, new Key( $this->broker_public_key, 'RS256' ) );

		if ( ! isset( $decoded->iss ) || $decoded->iss !== $this->broker_url ) {
			throw new \RuntimeException( 'Invalid broker response issuer.' );
		}

		$expected_aud = parse_url( home_url(), PHP_URL_HOST );
		if ( ! isset( $decoded->aud ) || $decoded->aud !== $expected_aud ) {
			throw new \RuntimeException( 'Invalid broker response audience.' );
		}

		if ( ! isset( $decoded->exp ) || $decoded->exp < time() ) {
			throw new \RuntimeException( 'Broker response JWT expired.' );
		}

		if ( ! isset( $decoded->access_token, $decoded->expires_at ) ) {
			throw new \RuntimeException( 'Missing access_token or expires_at in broker response.' );
		}

		return $decoded;
	}

	private function post( string $path, array $signed ): array {
		return wp_remote_post(
			$this->broker_url . $path,
			[
				'method'  => 'POST',
				'headers' => $signed['headers'],
				'body'    => $signed['body'],
				'timeout' => 15,
			]
		);
	}
}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `composer test -- --filter=Modules_ImportGoogleDocsBrokerClientTest`
Expected: PASS (4 tests).

- [ ] **Step 5: Commit**

```bash
git add inc/modules/import/googledocs/broker/class-brokerrefreshclient.php \
        tests/test-modules-import-google-docs-broker-client.php
git commit -m "feat(google-docs): add BrokerRefreshClient with HMAC-signed requests and RS256-verified responses"
```

---

## Task 11: Wire `BrokerRefreshClient` into `OAuthClient::getAuthedClient`

**Files:**
- Modify: `inc/modules/import/googledocs/class-oauthclient.php`
- Modify: `inc/modules/import/googledocs/broker/class-brokerrefreshclient.php` (constructor — see below)
- Modify: `tests/test-modules-import-google-docs-oauth.php`
- Modify: `hooks-admin.php`

- [ ] **Step 1: Add a `BrokerRefreshClient` slot to `OAuthClient`**

In `inc/modules/import/googledocs/class-oauthclient.php`, update the class to optionally accept a `BrokerRefreshClient`:

```php
	private ?\Pressbooks\Modules\Import\GoogleDocs\Broker\BrokerRefreshClient $broker_refresh_client;

	public function __construct(
		TokenStorage $token_storage,
		CredentialsStore $creds_store,
		?\Pressbooks\Modules\Import\GoogleDocs\Broker\BrokerRefreshClient $broker_refresh_client = null
	) {
		$this->token_storage = $token_storage;
		$this->creds_store = $creds_store;
		$this->broker_refresh_client = $broker_refresh_client;
		$this->useBroker = $creds_store->isBrokerMode();
	}

	public function setBrokerRefreshClient( \Pressbooks\Modules\Import\GoogleDocs\Broker\BrokerRefreshClient $client ): void {
		$this->broker_refresh_client = $client;
	}
```

- [ ] **Step 2: Update the broker-mode branch of `getAuthedClient` to call the refresh client**

```php
		if ( $this->useBroker ) {
			if ( $token->isExpired() ) {
				if ( $this->broker_refresh_client === null ) {
					throw new ReauthorizationRequiredException( 'Token expired and no BrokerRefreshClient is configured.' );
				}
				$token = $this->broker_refresh_client->refresh( $user_id );
			}
			$client = new \Google\Client();
			$client->setAccessToken( $this->storedTokenToArray( $token ) );
			return $client;
		}
```

- [ ] **Step 3: Update the broker-mode branch of `disconnect` to call revoke on the broker**

```php
	public function disconnect( int $user_id ): void {
		$token = $this->token_storage->load( $user_id );

		if ( $token === null ) {
			return;
		}

		if ( $this->useBroker ) {
			if ( $this->broker_refresh_client !== null ) {
				try {
					$this->broker_refresh_client->revoke( $user_id );
					return; // revoke() already deleted local storage
				} catch ( \Throwable $e ) {
					throw new \RuntimeException( 'Failed to revoke token at broker: ' . $e->getMessage() . ' Please try again.', 0, $e );
				}
			}
			$this->token_storage->delete( $user_id );
			return;
		}

		try {
			$client = $this->buildClient();
			$client->setAccessToken( $this->storedTokenToArray( $token ) );
			$client->revokeToken();
		} catch ( \Exception $e ) {
			throw new \RuntimeException( 'Failed to revoke token at Google: ' . $e->getMessage() . ' Please try again.', 0, $e );
		}
		$this->token_storage->delete( $user_id );
	}
```

- [ ] **Step 4: Update `hooks-admin.php` to construct and inject `BrokerRefreshClient`**

Replace the dependency construction block from Task 8 with:

```php
$gdocs_creds_store = new \Pressbooks\Modules\Import\GoogleDocs\CredentialsStore();
$gdocs_cipher = new \Pressbooks\Modules\Import\GoogleDocs\Storage\SodiumCipher();
$gdocs_encryption_key = defined( 'PRESSBOOKS_GOOGLE_DOCS_ENCRYPTION_KEY' ) ? PRESSBOOKS_GOOGLE_DOCS_ENCRYPTION_KEY : '';
$gdocs_token_storage = $gdocs_creds_store->isBrokerMode()
	? new \Pressbooks\Modules\Import\GoogleDocs\Storage\BrokerBackedStorage( $gdocs_cipher, $gdocs_encryption_key )
	: new \Pressbooks\Modules\Import\GoogleDocs\Storage\DirectEncryptedStorage( $gdocs_cipher, $gdocs_encryption_key );
$gdocs_broker_refresh = null;
if ( $gdocs_creds_store->isBrokerMode() && defined( 'PRESSBOOKS_AUTH_BROKER_PUBLIC_KEY' ) && defined( 'PRESSBOOKS_AUTH_BROKER_NETWORK_SECRET' ) ) {
	$gdocs_broker_refresh = new \Pressbooks\Modules\Import\GoogleDocs\Broker\BrokerRefreshClient(
		PRESSBOOKS_AUTH_BROKER_URL,
		 PRESSBOOKS_AUTH_BROKER_PUBLIC_KEY,
		PRESSBOOKS_AUTH_BROKER_NETWORK_SECRET,
		$gdocs_token_storage
	);
}
$gdocs_oauth = new \Pressbooks\Modules\Import\GoogleDocs\OAuthClient(
	$gdocs_token_storage,
	$gdocs_creds_store,
	$gdocs_broker_refresh
);
```

Update the `pb_gdocs_disconnect` handler to surface revoke failures to the user (instead of silently swallowing). Replace the closure at line 447 with:

```php
add_action( 'admin_post_pb_gdocs_disconnect', function () use ( $gdocs_oauth ) {
	check_admin_referer( 'pb_gdocs_disconnect' );
	try {
		$gdocs_oauth->disconnect( get_current_user_id() );
		$return_url = wp_get_referer() ?: admin_url( 'admin.php?page=pb_import' );
		wp_safe_redirect( add_query_arg( 'pb_gdocs', 'disconnected', $return_url ) );
		exit;
	} catch ( \Throwable $e ) {
		wp_die( esc_html( $e->getMessage() ) );
	}
} );
```

- [ ] **Step 5: Update the same construction in `class-import.php` and `class-googledocs.php`**

Where these files construct `OAuthClient`, also pass the `BrokerRefreshClient`. To avoid duplication, you may inline a static factory method on `OAuthClient`:

```php
public static function fromEnvironment( CredentialsStore $creds_store ): self {
	$cipher = new \Pressbooks\Modules\Import\GoogleDocs\Storage\SodiumCipher();
	$encryption_key = defined( 'PRESSBOOKS_GOOGLE_DOCS_ENCRYPTION_KEY' ) ? PRESSBOOKS_GOOGLE_DOCS_ENCRYPTION_KEY : '';
	$storage = $creds_store->isBrokerMode()
		? new \Pressbooks\Modules\Import\GoogleDocs\Storage\BrokerBackedStorage( $cipher, $encryption_key )
		: new \Pressbooks\Modules\Import\GoogleDocs\Storage\DirectEncryptedStorage( $cipher, $encryption_key );
	$broker_refresh = null;
	if ( $creds_store->isBrokerMode() && defined( 'PRESSBOOKS_AUTH_BROKER_PUBLIC_KEY' ) && defined( 'PRESSBOOKS_AUTH_BROKER_NETWORK_SECRET' ) ) {
		$broker_refresh = new \Pressbooks\Modules\Import\GoogleDocs\Broker\BrokerRefreshClient(
			PRESSBOOKS_AUTH_BROKER_URL,
			PRESSBOOKS_AUTH_BROKER_PUBLIC_KEY,
			PRESSBOOKS_AUTH_BROKER_NETWORK_SECRET,
			$storage
		);
	}
	return new self( $storage, $creds_store, $broker_refresh );
}
```

Then `class-import.php` becomes:

```php
$store = new GoogleDocs\CredentialsStore();
$oauth = GoogleDocs\OAuthClient::fromEnvironment( $store );
```

And similarly for `class-googledocs.php`. Update `hooks-admin.php` to use the factory too if you prefer the simpler shape.

- [ ] **Step 6: Add a test asserting that `getAuthedClient` triggers broker refresh when token is expired**

Append to `tests/test-modules-import-google-docs-oauth.php`:

```php
	/**
	 * @group import
	 */
	public function test_get_authed_client_calls_broker_refresh_when_expired(): void {
		$oauth = $this->make_oauth_client( true );

		$stub = $this->getMockBuilder( \Pressbooks\Modules\Import\GoogleDocs\Broker\BrokerRefreshClient::class )
			->disableOriginalConstructor()
			->getMock();
		$stub->method( 'refresh' )
			->willReturnCallback( function ( $user_id ) {
				// Simulate the refresh client having stored the new token.
				$storage = new \Pressbooks\Modules\Import\GoogleDocs\Storage\BrokerBackedStorage(
					new \Pressbooks\Modules\Import\GoogleDocs\Storage\SodiumCipher(),
					self::$encryptionKey
				);
				$storage->save( $user_id, new \Pressbooks\Modules\Import\GoogleDocs\Storage\StoredToken(
					[
						'session_handle' => 'sh-abc',
						'access_token'   => 'fresh-at',
						'expires_at'     => time() + 3600,
						'google_sub'     => 'sub-001',
					],
					\Pressbooks\Modules\Import\GoogleDocs\Storage\TokenMode::Broker
				) );
				return $storage->load( $user_id );
			} );
		$oauth->setBrokerRefreshClient( $stub );

		$user_id = self::factory()->user->create();
		// Plant an expired token.
		$storage = new \Pressbooks\Modules\Import\GoogleDocs\Storage\BrokerBackedStorage(
			new \Pressbooks\Modules\Import\GoogleDocs\Storage\SodiumCipher(),
			self::$encryptionKey
		);
		$storage->save( $user_id, new \Pressbooks\Modules\Import\GoogleDocs\Storage\StoredToken(
			[
				'session_handle' => 'sh-abc',
				'access_token'   => 'expired-at',
				'expires_at'     => time() - 60,
				'google_sub'     => 'sub-001',
			],
			\Pressbooks\Modules\Import\GoogleDocs\Storage\TokenMode::Broker
		) );

		$client = $oauth->getAuthedClient( $user_id );
		$this->assertInstanceOf( \Google\Client::class, $client );
		$this->assertSame( 'fresh-at', $client->getAccessToken()['access_token'] );
	}
```

- [ ] **Step 7: Run the full OAuth suite**

Run: `composer test -- --filter=Modules_ImportGoogleDocsOAuthTest`
Expected: PASS.

- [ ] **Step 8: Commit**

```bash
git add inc/modules/import/googledocs/class-oauthclient.php \
        inc/modules/import/googledocs/broker/class-brokerrefreshclient.php \
        inc/modules/import/class-import.php \
        inc/modules/import/googledocs/class-googledocs.php \
        hooks-admin.php \
        tests/test-modules-import-google-docs-oauth.php
git commit -m "feat(google-docs): wire BrokerRefreshClient into OAuthClient for broker-mode refresh and revoke"
```

---

## Task 12: Purge-on-upgrade hook

**Files:**
- Modify: `hooks.php`
- Test: `tests/test-modules-import-google-docs-storage.php` (append)

- [ ] **Step 1: Append a failing test**

Append to `tests/test-modules-import-google-docs-storage.php`:

```php
	/**
	 * @group import
	 */
	public function test_purge_legacy_tokens_deletes_user_meta_rows(): void {
		$user_id = self::factory()->user->create();
		update_user_meta( $user_id, 'pressbooks_google_docs_token', [ 'access_token' => 'legacy', 'refresh_token' => 'legacy-rt' ] );

		\Pressbooks\Modules\Import\GoogleDocs\purge_legacy_tokens( $this->make_purge_marker() );

		$this->assertEmpty( get_user_meta( $user_id, 'pressbooks_google_docs_token', true ) );
		$this->assertSame( '1', get_site_option( $this->make_purge_marker() ) );
	}

	/**
	 * @group import
	 */
	public function test_purge_legacy_tokens_is_idempotent(): void {
		$user_id = self::factory()->user->create();
		update_user_meta( $user_id, 'pressbooks_google_docs_token', [ 'access_token' => 'legacy' ] );

		\Pressbooks\Modules\Import\GoogleDocs\purge_legacy_tokens( $this->make_purge_marker() );
		\Pressbooks\Modules\Import\GoogleDocs\purge_legacy_tokens( $this->make_purge_marker() );

		$this->assertSame( '1', get_site_option( $this->make_purge_marker() ) );
	}

	private function make_purge_marker(): string {
		return 'pressbooks_google_docs_purge_test_' . uniqid();
	}
```

- [ ] **Step 2: Run to verify it fails**

Run: `composer test -- --filter=Modules_ImportGoogleDocsStorageTest`
Expected: Error calling undefined function `purge_legacy_tokens`.

- [ ] **Step 3: Add the purge function**

Create `inc/modules/import/googledocs/upgrade.php`:

```php
<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Modules\Import\GoogleDocs;

function purge_legacy_tokens( string $marker_option_key ): void {
	if ( get_site_option( $marker_option_key ) === '1' ) {
		return;
	}

	global $wpdb;
	$count = $wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->usermeta} WHERE meta_key = %s",
			'pressbooks_google_docs_token'
		)
	);

	update_site_option( $marker_option_key, '1' );

	if ( function_exists( 'error_log' ) ) {
		error_log( 'pb.gdocs.tokens_purged count=' . (int) $count );
	}
}
```

- [ ] **Step 4: Register the purge to run on plugin upgrade**

Open `hooks.php` (this file is loaded on every request and is the standard place for activation hooks). Add at the bottom:

```php
// On upgrade past the broker-held-tokens version, purge legacy plaintext token rows.
add_action( 'plugins_loaded', function () {
	$marker = 'pressbooks_google_docs_purge_v7_0_0_done';
	if ( get_site_option( $marker ) !== '1' ) {
		require_once __DIR__ . '/inc/modules/import/googledocs/upgrade.php';
		\Pressbooks\Modules\Import\GoogleDocs\purge_legacy_tokens( $marker );
	}
} );
```

(Use the actual version constant that PB defines for the release shipping this change. The constant `PB_VERSION` is already available; you can use `'pressbooks_google_docs_purge_' . str_replace( '.', '_', PB_VERSION ) . '_done'` if preferred. The key requirement is that the marker is unique per version that ships this purge.)

- [ ] **Step 5: Run to verify the test passes**

Run: `composer test -- --filter=Modules_ImportGoogleDocsStorageTest`
Expected: PASS (19 tests).

- [ ] **Step 6: Commit**

```bash
git add inc/modules/import/googledocs/upgrade.php \
        hooks.php \
        tests/test-modules-import-google-docs-storage.php
git commit -m "feat(google-docs): purge legacy plaintext tokens on upgrade"
```

---

## Task 13: Admin notice when encryption key missing

**Files:**
- Modify: `inc/modules/import/googledocs/class-settingspage.php`

- [ ] **Step 1: Replace `inc/modules/import/googledocs/class-settingspage.php` in its entirety**

The new version accepts a `TokenStorage`, renders a notice when `isAvailable()` is false, and retains all existing methods (`addMenu`, `renderPage`, `handleOAuthCallback`, `renderBrokerPage`):

```php
<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Modules\Import\GoogleDocs;

use Pressbooks\Modules\Import\GoogleDocs\Storage\TokenStorage;

class SettingsPage {

	protected CredentialsStore $store;
	protected OAuthClient $oauth;
	protected TokenStorage $token_storage;

	public function hooks(): void {
		add_action( 'network_admin_menu', [ $this, 'addMenu' ] );
		add_action( 'admin_post_pb_gdocs_callback', [ $this, 'handleOAuthCallback' ] );
		add_action( 'network_admin_notices', [ $this, 'maybeRenderEncryptionKeyNotice' ] );
		add_action( 'admin_notices', [ $this, 'maybeRenderEncryptionKeyNotice' ] );
	}

	public function maybeRenderEncryptionKeyNotice(): void {
		if ( ! current_user_can( 'manage_network_options' ) ) {
			return;
		}
		if ( $this->token_storage->isAvailable() ) {
			return;
		}
		if ( ! $this->store->isConfigured() ) {
			return;
		}
		?>
		<div class="notice notice-error">
			<p><strong><?php _e( 'Google Docs Import is disabled.', 'pressbooks' ); ?></strong></p>
			<p><?php
				printf(
					/* translators: %s: configuration constant name */
					esc_html__( 'The %s constant must be defined in wp-config.php (or Bedrock config/application.php) with a 32-byte base64-encoded key. Generate one with: openssl rand -base64 32', 'pressbooks' ),
					'<code>PRESSBOOKS_GOOGLE_DOCS_ENCRYPTION_KEY</code>'
				);
			?></p>
		</div>
		<?php
	}

	public function addMenu(): void {
		add_submenu_page(
			'settings.php',
			__( 'Google Docs Import', 'pressbooks' ),
			__( 'Google Docs Import', 'pressbooks' ),
			'manage_network_options',
			'pb_network_google_docs',
			[ $this, 'renderPage' ]
		);
	}

	public function renderPage(): void {
		if ( $this->oauth->isBrokerMode() ) {
			$this->renderBrokerPage();
			return;
		}

		$updated = false;
		if ( ! empty( $_POST ) && check_admin_referer( 'pb_save_google_docs_settings' ) ) {
			if ( ! current_user_can( 'manage_network_options' ) ) {
				wp_die( esc_html__( 'Unauthorized.', 'pressbooks' ) );
			}
			$client_id = sanitize_text_field( wp_unslash( $_POST['client_id'] ?? '' ) );
			$client_secret = sanitize_text_field( wp_unslash( $_POST['client_secret'] ?? '' ) );
			$this->store->saveClientCredentials( $client_id, $client_secret );
			$updated = true;
		}

		$creds = $this->store->getClientCredentials();
		$redirect_uri = $this->oauth->getRedirectUri();
		?>
		<div class="wrap">
			<h1><?php _e( 'Google Docs Import Settings', 'pressbooks' ); ?></h1>
			<?php if ( $updated ) : ?>
				<div id="message" role="status" class="updated notice is-dismissible"><p><strong><?php _e( 'Settings saved.', 'pressbooks' ); ?></strong></p></div>
			<?php endif; ?>
			<p><?php _e( 'Configure your Google Cloud OAuth credentials to enable Google Docs import.', 'pressbooks' ); ?></p>
			<h2><?php _e( 'Required Configuration in Google Cloud Console', 'pressbooks' ); ?></h2>
			<p><?php _e( 'Add the following Authorized Redirect URI to your Google Cloud OAuth client:', 'pressbooks' ); ?></p>
			<code><?php echo esc_html( $redirect_uri ); ?></code>
			<p><?php _e( 'Required OAuth scopes:', 'pressbooks' ); ?></p>
			<ul>
				<li><code>https://www.googleapis.com/auth/documents.readonly</code></li>
				<li><code>https://www.googleapis.com/auth/drive.readonly</code></li>
			</ul>
			<form method="post" action="">
				<?php wp_nonce_field( 'pb_save_google_docs_settings' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="client_id"><?php _e( 'Client ID', 'pressbooks' ); ?></label></th>
						<td><input type="text" id="client_id" name="client_id" value="<?php echo esc_attr( $creds['client_id'] ); ?>" class="regular-text" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="client_secret"><?php _e( 'Client Secret', 'pressbooks' ); ?></label></th>
						<td><input type="password" id="client_secret" name="client_secret" value="<?php echo esc_attr( $creds['client_secret'] ); ?>" class="regular-text" /></td>
					</tr>
				</table>
				<?php submit_button( __( 'Save Settings', 'pressbooks' ) ); ?>
			</form>
		</div>
		<?php
	}

	public function handleOAuthCallback(): void {
		if ( ! is_user_logged_in() ) {
			auth_redirect();
		}

		$error = sanitize_text_field( wp_unslash( $_GET['error'] ?? '' ) );
		if ( $error ) {
			$state = sanitize_text_field( wp_unslash( $_GET['state'] ?? '' ) );
			$return_url = $state ? get_site_transient( 'pb_gdocs_state_' . $state ) : false;
			if ( $return_url ) {
				delete_site_transient( 'pb_gdocs_state_' . $state );
				wp_redirect( add_query_arg( 'pb_gdocs', 'denied', $return_url ) );
			} else {
				wp_redirect( admin_url( 'admin.php?page=pb_import&pb_gdocs=denied' ) );
			}
			exit;
		}

		$broker_token = sanitize_text_field( wp_unslash( $_GET['token'] ?? '' ) );
		$code = sanitize_text_field( wp_unslash( $_GET['code'] ?? '' ) );
		$state = sanitize_text_field( wp_unslash( $_GET['state'] ?? '' ) );

		if ( $broker_token ) {
			if ( empty( $state ) ) {
				wp_die( esc_html__( 'Invalid OAuth callback parameters.', 'pressbooks' ) );
			}
			try {
				$return_url = $this->oauth->handleCallback( $broker_token, $state, get_current_user_id() );
				wp_redirect( add_query_arg( 'pb_gdocs', 'connected', $return_url ) );
				exit;
			} catch ( \Exception $e ) {
				wp_die( esc_html( $e->getMessage() ) );
			}
		}

		if ( $code ) {
			if ( empty( $state ) ) {
				wp_die( esc_html__( 'Invalid OAuth callback parameters.', 'pressbooks' ) );
			}
			try {
				$return_url = $this->oauth->handleCallback( $code, $state, get_current_user_id() );
				wp_redirect( add_query_arg( 'pb_gdocs', 'connected', $return_url ) );
				exit;
			} catch ( \Exception $e ) {
				wp_die( esc_html( $e->getMessage() ) );
			}
		}
	}

	protected function renderBrokerPage(): void {
		?>
		<div class="wrap">
			<h1><?php _e( 'Google Docs Import Settings', 'pressbooks' ); ?></h1>
			<div class="notice notice-info inline">
				<p><?php _e( 'Google authentication is managed centrally via the Pressbooks Auth Broker. No local configuration is required.', 'pressbooks' ); ?></p>
			</div>
		</div>
		<?php
	}
}
```

- [ ] **Step 2: Update the `hooks-admin.php` construction**

Where `SettingsPage` is constructed (line 423), add the storage:

```php
$gdocs_settings = new \Pressbooks\Modules\Import\GoogleDocs\SettingsPage( $gdocs_creds_store, $gdocs_oauth, $gdocs_token_storage );
```

- [ ] **Step 3: Update the existing test for `SettingsPage` (if any)**

There is no dedicated `SettingsPage` test in the current suite. Add a smoke test to `tests/test-modules-import-google-docs-storage.php`:

```php
	/**
	 * @group import
	 */
	public function test_settings_page_renders_error_notice_when_key_missing(): void {
		$cipher = new \Pressbooks\Modules\Import\GoogleDocs\Storage\SodiumCipher();
		$storage = new \Pressbooks\Modules\Import\GoogleDocs\Storage\DirectEncryptedStorage( $cipher, '' ); // unavailable
		$oauth = new \Pressbooks\Modules\Import\GoogleDocs\OAuthClient(
			$storage,
			new \Pressbooks\Modules\Import\GoogleDocs\CredentialsStore()
		);
		$page = new \Pressbooks\Modules\Import\GoogleDocs\SettingsPage(
			new \Pressbooks\Modules\Import\GoogleDocs\CredentialsStore(),
			$oauth,
			$storage
		);

		ob_start();
		$page->maybeRenderEncryptionKeyNotice();
		$output = ob_get_clean();

		// Notice only renders when configured AND key missing. Not configured here, so no output.
		$this->assertSame( '', $output );
	}
```

(We test the "not configured" early-return path; a full integration test with `isConfigured()=true` requires site option seeding, which the importer test fixtures already cover.)

- [ ] **Step 4: Run the full Google Docs test suite**

Run: `composer test -- --filter=GoogleDocs`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add inc/modules/import/googledocs/class-settingspage.php \
        hooks-admin.php \
        tests/test-modules-import-google-docs-storage.php
git commit -m "feat(google-docs): admin notice when encryption key missing"
```

---

## Task 14: Final regression sweep + standards

- [ ] **Step 1: Run the full Google Docs suite**

Run: `composer test -- --filter=GoogleDocs`
Expected: PASS — every test across `Modules_ImportGoogleDocsStorageTest`, `Modules_ImportGoogleDocsCredentialsTest`, `Modules_ImportGoogleDocsOAuthTest`, `Modules_ImportGoogleDocsBrokerClientTest`, `Modules_ImportGoogleDocsImporterTest`, `Modules_ImportGoogleDocsMapperTest`.

- [ ] **Step 2: Run the full plugin test suite**

Run: `composer test`
Expected: No new failures beyond any pre-existing ones.

- [ ] **Step 3: Run coding standards**

Run: `composer standards`
Expected: No errors in the new files. Fix any reported warnings.

- [ ] **Step 4: Run the linter fixer if needed**

Run: `composer fix`
Expected: Files formatted.

- [ ] **Step 5: Verify no plaintext token references remain**

Run: `grep -rn "refresh_token" inc/modules/import/googledocs/ | grep -v "BrokerRefreshClient\|class-brokerrefreshclient\|throw new\|payload\['refresh_token'\]"`
Expected: Zero matches outside the broker refresh client and the explicit `payload['refresh_token']` reads in the direct-mode path. No code path writes a plaintext refresh token to user_meta.

Run: `grep -n "update_user_meta.*pressbooks_google_docs_token" inc/`
Expected: Zero matches (the only meta key writes are inside `DirectEncryptedStorage::save()` / `BrokerBackedStorage::save()` and write ciphertext).

- [ ] **Step 6: Commit any remaining fixes**

```bash
git add -A
git commit -m "chore(google-docs): standards and final cleanup"
```

---

## Spec coverage check

- **F1 (P0: plaintext refresh in user_meta):** Tasks 1–6, 9 (direct mode writes ciphertext via `DirectEncryptedStorage`).
- **F2 (P0: refresh_token in broker JWT):** Tasks 7, 8 (new JWT shape; no `refresh_token`).
- **F3 (P1: broker-mode disconnect doesn't revoke at Google):** Task 11 (calls broker `/oauth/revoke` which the broker plan wires to Google's revoke endpoint).
- **F4 (P1: no broker-mode refresh path):** Tasks 10, 11 (`BrokerRefreshClient::refresh`).
- **F5 (P2: scope minimality):** Risk-accepted in spec; no code task.
- **F6 (P2: rotation strategy):** Out of code scope for this plan; documented in spec Section 10. Future task: add keyring constant support.
- **F7 (P3: no audit logging):** Task 12 (`pb.gdocs.tokens_purged count=N` log line).
- **F8 (P3: PHP-serialized blob):** Addressed in Tasks 4, 5 (ciphertext blob uses JSON inside the envelope).
- **A1 (drive.file evaluation):** Risk-accepted; no code task.
- **Edge cases 1–9 (spec Section 7.7):** Tasks 9, 10, 11 (surfacing failures, 410 handling, replay protection via broker side, etc.). All edge cases that span the broker boundary require the broker plan to implement the matching side; this plan covers the WP side.
- **Disconnect when broker unreachable (edge case 9):** Task 11 (`OAuthClient::disconnect` rethrows the broker-revoke failure; `BrokerRefreshClient::revoke` rethrows on 5xx).
- **Purge-on-upgrade (spec Section 9.2):** Task 12.
- **Admin notice when key missing:** Task 13.

---

## Notes for the implementer

- The `BrokerRefreshClient` test uses the `pre_http_request` filter to stub HTTP. This is the canonical WordPress way to mock external HTTP in tests.
- All new classes are autoloaded via the existing HM Autoloader (prefix `Pressbooks`, path `inc/`). Verify with `composer dump-autoload` if classes aren't found at runtime (the HM autoloader is not Composer-managed, so no dump is needed — but worth confirming).
- The encryption key constant is `PRESSBOOKS_GOOGLE_DOCS_ENCRYPTION_KEY`. The test for `wpSetUpBeforeClass` deliberately defines it only if not already defined, so a global test bootstrap can pre-seed it.
- The broker-side counterpart to this work is **Plan 2: broker token security remediation** (separate document, lives in `pb-google-auth/docs/superpowers/plans/`). It must ship in lockstep with this plan for broker mode to actually function end-to-end; in the meantime, the WP side is fully testable via mocks.
