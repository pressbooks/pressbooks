# Google Docs Import Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let a Pressbooks user import content from a Google Doc (via paste URL) into book chapters with structure, formatting, images, and tables preserved.

**Architecture:** New importer module at `inc/modules/import/google-docs/` with 6 classes. Extends the existing `Import` base class. Registers via `pb_select_import_type` and `pb_initialize_import` filters. OAuth tokens stored per-user in `user_meta`, client credentials in network options. The Docs API JSON response is parsed by a custom mapper into sanitized HTML chapters.

**Tech Stack:** PHP 8.3, WordPress Multisite, `google/apiclient ^2.15`, Pressbooks import module system.

**Spec:** `docs/superpowers/specs/2026-04-16-google-docs-import-design.md`

---

## File structure

### New files

| File | Responsibility |
|---|---|
| `inc/modules/import/google-docs/class-credentials-store.php` | Read/write Google OAuth client credentials (network option) and per-user tokens (user meta) |
| `inc/modules/import/google-docs/class-oauth-client.php` | OAuth authorize redirect, callback handler, token refresh, disconnect/revoke |
| `inc/modules/import/google-docs/class-settings-page.php` | Network admin settings page for client_id/secret |
| `inc/modules/import/google-docs/class-docs-mapper.php` | Parse Google Docs API JSON → array of chapter records with HTML + image refs |
| `inc/modules/import/google-docs/class-docs-fetcher.php` | Wrappers around docs.documents.get, drive.files.get, image download |
| `inc/modules/import/google-docs/class-google-docs.php` | Main importer class extending `Import`. TYPE_OF, setCurrentImportOption, import |
| `tests/test-modules-import-google-docs-mapper.php` | Unit tests for DocsMapper |
| `tests/test-modules-import-google-docs-credentials.php` | Unit tests for CredentialsStore |
| `tests/test-modules-import-google-docs-oauth.php` | Unit tests for OAuthClient (mocked Google client) |
| `tests/fixtures/google-docs/*.json` | Captured Docs API JSON fixtures |
| `tests/fixtures/google-docs/*.expected.html` | Expected HTML output per fixture |

### Modified files

| File | Change |
|---|---|
| `composer.json` | Add `google/apiclient: ^2.15` to require |
| `inc/modules/import/class-import.php` | Add early intercept in `setImportOptions()` for `google-docs` type_of (bypass file upload requirement); add case in `doImportGenerator()` switch |
| `templates/admin/import.php` | No change needed — uses `pb_select_import_type` filter |

---

## Task 1: Add google/apiclient dependency

**Files:**
- Modify: `composer.json`

- [ ] **Step 1: Add dependency to composer.json**

In `composer.json`, add to the `require` section:

```json
"google/apiclient": "^2.15"
```

- [ ] **Step 2: Install**

Run: `composer update google/apiclient --with-dependencies`
Expected: Successful install, lock file updated.

- [ ] **Step 3: Commit**

```bash
git add composer.json composer.lock
git commit -m "feat(google-docs-import): add google/apiclient dependency"
```

---

## Task 2: CredentialsStore — test and implement

**Files:**
- Create: `inc/modules/import/google-docs/class-credentials-store.php`
- Create: `tests/test-modules-import-google-docs-credentials.php`

- [ ] **Step 1: Write the failing test**

Create `tests/test-modules-import-google-docs-credentials.php`:

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
	public function test_get_user_token_returns_null_when_not_set(): void {
		$store = new \Pressbooks\Modules\Import\GoogleDocs\CredentialsStore();
		$this->assertNull( $store->getUserToken( 1 ) );
	}

	/**
	 * @group import
	 */
	public function test_save_and_get_user_token(): void {
		$store = new \Pressbooks\Modules\Import\GoogleDocs\CredentialsStore();
		$token = [
			'access_token'  => 'at-123',
			'refresh_token' => 'rt-456',
			'expires_at'    => time() + 3600,
			'scopes'        => 'documents.readonly drive.readonly',
			'connected_at'  => time(),
		];
		$user_id = self::factory()->user->create();
		$store->saveUserToken( $user_id, $token );
		$saved = $store->getUserToken( $user_id );
		$this->assertSame( 'at-123', $saved['access_token'] );
		$this->assertSame( 'rt-456', $saved['refresh_token'] );
	}

	/**
	 * @group import
	 */
	public function test_delete_user_token(): void {
		$store = new \Pressbooks\Modules\Import\GoogleDocs\CredentialsStore();
		$user_id = self::factory()->user->create();
		$store->saveUserToken( $user_id, [ 'access_token' => 'x' ] );
		$store->deleteUserToken( $user_id );
		$this->assertNull( $store->getUserToken( $user_id ) );
	}

	/**
	 * @group import
	 */
	public function test_user_is_connected(): void {
		$store = new \Pressbooks\Modules\Import\GoogleDocs\CredentialsStore();
		$user_id = self::factory()->user->create();
		$this->assertFalse( $store->isUserConnected( $user_id ) );
		$store->saveUserToken( $user_id, [ 'access_token' => 'x', 'refresh_token' => 'y' ] );
		$this->assertTrue( $store->isUserConnected( $user_id ) );
	}
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `vendor/bin/phpunit --filter Modules_ImportGoogleDocsCredentialsTest`
Expected: FAIL — class not found.

- [ ] **Step 3: Write the implementation**

Create `inc/modules/import/google-docs/class-credentials-store.php`:

```php
<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Modules\Import\GoogleDocs;

class CredentialsStore {

	const NETWORK_OPTION_KEY = 'pressbooks_google_docs_oauth';
	const USER_META_KEY = 'pressbooks_google_docs_token';

	/**
	 * Get OAuth client credentials from network option.
	 *
	 * @return array{client_id: string, client_secret: string}
	 */
	public function getClientCredentials(): array {
		$option = get_site_option( self::NETWORK_OPTION_KEY, [] );
		return [
			'client_id'     => $option['client_id'] ?? '',
			'client_secret' => $option['client_secret'] ?? '',
		];
	}

	/**
	 * Save OAuth client credentials to network option.
	 *
	 * @param string $client_id
	 * @param string $client_secret
	 * @return bool
	 */
	public function saveClientCredentials( string $client_id, string $client_secret ): bool {
		return update_site_option( self::NETWORK_OPTION_KEY, [
			'client_id'     => $client_id,
			'client_secret' => $client_secret,
		] );
	}

	/**
	 * Check if client credentials are configured.
	 *
	 * @return bool
	 */
	public function isConfigured(): bool {
		$creds = $this->getClientCredentials();
		return ! empty( $creds['client_id'] ) && ! empty( $creds['client_secret'] );
	}

	/**
	 * Get stored OAuth token for a user.
	 *
	 * @param int $user_id
	 * @return array|null
	 */
	public function getUserToken( int $user_id ): ?array {
		$token = get_user_meta( $user_id, self::USER_META_KEY, true );
		return ! empty( $token ) && is_array( $token ) ? $token : null;
	}

	/**
	 * Save OAuth token for a user.
	 *
	 * @param int $user_id
	 * @param array $token
	 * @return bool
	 */
	public function saveUserToken( int $user_id, array $token ): bool {
		return (bool) update_user_meta( $user_id, self::USER_META_KEY, $token );
	}

	/**
	 * Delete stored OAuth token for a user.
	 *
	 * @param int $user_id
	 * @return bool
	 */
	public function deleteUserToken( int $user_id ): bool {
		return delete_user_meta( $user_id, self::USER_META_KEY );
	}

	/**
	 * Check if a user has a stored token with a refresh_token.
	 *
	 * @param int $user_id
	 * @return bool
	 */
	public function isUserConnected( int $user_id ): bool {
		$token = $this->getUserToken( $user_id );
		return $token !== null && ! empty( $token['refresh_token'] );
	}
}
```

- [ ] **Step 4: Run the test to verify it passes**

Run: `vendor/bin/phpunit --filter Modules_ImportGoogleDocsCredentialsTest`
Expected: All 7 tests PASS.

- [ ] **Step 5: Commit**

```bash
git add inc/modules/import/google-docs/class-credentials-store.php tests/test-modules-import-google-docs-credentials.php
git commit -m "feat(google-docs-import): add CredentialsStore for client creds and user tokens"
```

---

## Task 3: OAuthClient — test and implement

**Files:**
- Create: `inc/modules/import/google-docs/class-oauth-client.php`
- Create: `tests/test-modules-import-google-docs-oauth.php`

- [ ] **Step 1: Write the failing test**

Create `tests/test-modules-import-google-docs-oauth.php`:

```php
<?php

use Pressbooks\Modules\Import\GoogleDocs\CredentialsStore;
use Pressbooks\Modules\Import\GoogleDocs\OAuthClient;

class Modules_ImportGoogleDocsOAuthTest extends \WP_UnitTestCase {

	private CredentialsStore $store;
	private int $user_id;

	public function set_up(): void {
		parent::set_up();
		$this->store = new CredentialsStore();
		$this->store->saveClientCredentials( 'test-client-id', 'test-client-secret' );
		$this->user_id = self::factory()->user->create();
	}

	public function tear_down(): void {
		delete_site_option( CredentialsStore::NETWORK_OPTION_KEY );
		delete_user_meta( $this->user_id, CredentialsStore::USER_META_KEY );
		parent::tear_down();
	}

	/**
	 * @group import
	 */
	public function test_get_authorize_url_contains_required_params(): void {
		$oauth = new OAuthClient( $this->store );
		$url = $oauth->getAuthorizeUrl( 'https://example.com/return' );
		$this->assertStringContainsString( 'accounts.google.com', $url );
		$this->assertStringContainsString( 'client_id=test-client-id', $url );
		$this->assertStringContainsString( 'access_type=offline', $url );
		$this->assertStringContainsString( 'documents.readonly', urldecode( $url ) );
		$this->assertStringContainsString( 'drive.readonly', urldecode( $url ) );
	}

	/**
	 * @group import
	 */
	public function test_get_authorize_url_stores_state_transient(): void {
		$oauth = new OAuthClient( $this->store );
		$url = $oauth->getAuthorizeUrl( 'https://example.com/return' );
		parse_str( parse_url( $url, PHP_URL_QUERY ), $params );
		$state = $params['state'] ?? '';
		$this->assertNotEmpty( $state );
		$return = get_transient( 'pb_gdocs_state_' . $state );
		$this->assertSame( 'https://example.com/return', $return );
	}

	/**
	 * @group import
	 */
	public function test_extract_doc_id_from_url(): void {
		$this->assertSame(
			'1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgVE2upms',
			OAuthClient::extractDocId( 'https://docs.google.com/document/d/1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgVE2upms/edit' )
		);
		$this->assertSame(
			'1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgVE2upms',
			OAuthClient::extractDocId( 'https://docs.google.com/document/d/1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgVE2upms/edit#heading=h.abc' )
		);
		$this->assertNull( OAuthClient::extractDocId( 'https://docs.google.com/spreadsheets/d/abc/edit' ) );
		$this->assertNull( OAuthClient::extractDocId( 'not-a-url' ) );
	}

	/**
	 * @group import
	 */
	public function test_get_authed_client_throws_when_no_token(): void {
		$this->expectException( \Pressbooks\Modules\Import\GoogleDocs\ReauthorizationRequiredException::class );
		$oauth = new OAuthClient( $this->store );
		$oauth->getAuthedClient( $this->user_id );
	}

	/**
	 * @group import
	 */
	public function test_get_authed_client_returns_client_when_token_valid(): void {
		$this->store->saveUserToken( $this->user_id, [
			'access_token'  => 'valid-token',
			'refresh_token' => 'rt',
			'expires_at'    => time() + 3600,
		] );
		$oauth = new OAuthClient( $this->store );
		$client = $oauth->getAuthedClient( $this->user_id );
		$this->assertInstanceOf( \Google\Client::class, $client );
		$this->assertSame( 'valid-token', $client->getAccessToken()['access_token'] );
	}
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `vendor/bin/phpunit --filter Modules_ImportGoogleDocsOAuthTest`
Expected: FAIL — class not found.

- [ ] **Step 3: Write the implementation**

Create `inc/modules/import/google-docs/class-oauth-client.php`:

```php
<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Modules\Import\GoogleDocs;

use Google\Client as GoogleClient;

class ReauthorizationRequiredException extends \RuntimeException {}

class OAuthClient {

	const SCOPES = [
		'https://www.googleapis.com/auth/documents.readonly',
		'https://www.googleapis.com/auth/drive.readonly',
	];

	const STATE_TRANSIENT_TTL = 600; // 10 minutes

	/**
	 * @var CredentialsStore
	 */
	protected CredentialsStore $store;

	public function __construct( CredentialsStore $store ) {
		$this->store = $store;
	}

	/**
	 * Build a Google\Client configured with our credentials (unauthenticated).
	 *
	 * @return GoogleClient
	 */
	public function buildClient(): GoogleClient {
		$creds = $this->store->getClientCredentials();
		$client = new GoogleClient();
		$client->setClientId( $creds['client_id'] );
		$client->setClientSecret( $creds['client_secret'] );
		$client->setScopes( self::SCOPES );
		$client->setAccessType( 'offline' );
		$client->setPrompt( 'consent' );
		return $client;
	}

	/**
	 * Generate the authorization URL and store a state transient for CSRF protection.
	 *
	 * @param string $return_url URL to redirect to after OAuth completes.
	 * @return string Google authorization URL.
	 */
	public function getAuthorizeUrl( string $return_url ): string {
		$client = $this->buildClient();
		$client->setRedirectUri( $this->getRedirectUri() );

		$state = wp_generate_password( 32, false );
		set_transient( 'pb_gdocs_state_' . $state, $return_url, self::STATE_TRANSIENT_TTL );

		$client->setState( $state );
		return $client->createAuthUrl();
	}

	/**
	 * Handle the OAuth callback: verify state, exchange code, store tokens.
	 *
	 * @param string $code Authorization code from Google.
	 * @param string $state State parameter for CSRF verification.
	 * @param int $user_id WordPress user ID.
	 * @return string The return URL stored in the state transient.
	 * @throws \Exception If state is invalid or token exchange fails.
	 */
	public function handleCallback( string $code, string $state, int $user_id ): string {
		$return_url = get_transient( 'pb_gdocs_state_' . $state );
		if ( empty( $return_url ) ) {
			throw new \Exception( 'Invalid or expired OAuth state.' );
		}
		delete_transient( 'pb_gdocs_state_' . $state );

		$client = $this->buildClient();
		$client->setRedirectUri( $this->getRedirectUri() );
		$token = $client->fetchAccessTokenWithAuthCode( $code );

		if ( isset( $token['error'] ) ) {
			throw new \Exception( 'OAuth token exchange failed: ' . ( $token['error_description'] ?? $token['error'] ) );
		}

		$this->store->saveUserToken( $user_id, [
			'access_token'  => $token['access_token'],
			'refresh_token' => $token['refresh_token'] ?? '',
			'expires_at'    => time() + ( $token['expires_in'] ?? 3600 ),
			'scopes'        => implode( ' ', self::SCOPES ),
			'connected_at'  => time(),
		] );

		return $return_url;
	}

	/**
	 * Get an authenticated Google\Client for a user, refreshing the token if needed.
	 *
	 * @param int $user_id
	 * @return GoogleClient
	 * @throws ReauthorizationRequiredException If no token or refresh fails.
	 */
	public function getAuthedClient( int $user_id ): GoogleClient {
		$token = $this->store->getUserToken( $user_id );
		if ( ! $token ) {
			throw new ReauthorizationRequiredException( 'No stored token. User must reconnect.' );
		}

		$client = $this->buildClient();
		$client->setAccessToken( [
			'access_token'  => $token['access_token'],
			'refresh_token' => $token['refresh_token'] ?? '',
			'expires_in'    => max( 0, ( $token['expires_at'] ?? 0 ) - time() ),
			'created'       => ( $token['expires_at'] ?? time() ) - 3600,
		] );

		if ( $client->isAccessTokenExpired() ) {
			if ( empty( $token['refresh_token'] ) ) {
				$this->store->deleteUserToken( $user_id );
				throw new ReauthorizationRequiredException( 'Token expired and no refresh token available.' );
			}
			try {
				$new_token = $client->fetchAccessTokenWithRefreshToken( $token['refresh_token'] );
				if ( isset( $new_token['error'] ) ) {
					throw new \Exception( $new_token['error'] );
				}
				$this->store->saveUserToken( $user_id, [
					'access_token'  => $new_token['access_token'],
					'refresh_token' => $new_token['refresh_token'] ?? $token['refresh_token'],
					'expires_at'    => time() + ( $new_token['expires_in'] ?? 3600 ),
					'scopes'        => $token['scopes'] ?? '',
					'connected_at'  => $token['connected_at'] ?? time(),
				] );
				$client->setAccessToken( $new_token );
			} catch ( \Exception $e ) {
				$this->store->deleteUserToken( $user_id );
				throw new ReauthorizationRequiredException( 'Token refresh failed: ' . $e->getMessage() );
			}
		}

		return $client;
	}

	/**
	 * Disconnect: revoke the token with Google and delete local storage.
	 *
	 * @param int $user_id
	 */
	public function disconnect( int $user_id ): void {
		$token = $this->store->getUserToken( $user_id );
		if ( $token ) {
			try {
				$client = $this->buildClient();
				$client->revokeToken( $token['access_token'] ?? $token['refresh_token'] ?? '' );
			} catch ( \Exception $e ) {
				// Best effort — token may already be invalid.
			}
		}
		$this->store->deleteUserToken( $user_id );
	}

	/**
	 * Extract a Google Doc ID from a URL.
	 *
	 * Supports URLs like:
	 *   https://docs.google.com/document/d/DOC_ID/edit
	 *   https://docs.google.com/document/d/DOC_ID/edit#heading=h.abc
	 *
	 * @param string $url
	 * @return string|null Doc ID, or null if URL doesn't match.
	 */
	public static function extractDocId( string $url ): ?string {
		if ( preg_match( '#docs\.google\.com/document/d/([a-zA-Z0-9_-]+)#', $url ) ) {
			preg_match( '#docs\.google\.com/document/d/([a-zA-Z0-9_-]+)#', $url, $matches );
			return $matches[1];
		}
		return null;
	}

	/**
	 * Get the OAuth redirect URI for this network.
	 *
	 * @return string
	 */
	public function getRedirectUri(): string {
		return network_admin_url( 'settings.php?page=pb_network_google_docs&pb_oauth_callback=1' );
	}
}
```

- [ ] **Step 4: Run the test to verify it passes**

Run: `vendor/bin/phpunit --filter Modules_ImportGoogleDocsOAuthTest`
Expected: All 5 tests PASS.

- [ ] **Step 5: Commit**

```bash
git add inc/modules/import/google-docs/class-oauth-client.php tests/test-modules-import-google-docs-oauth.php
git commit -m "feat(google-docs-import): add OAuthClient with authorize, callback, refresh, disconnect"
```

---

## Task 4: Network settings page

**Files:**
- Create: `inc/modules/import/google-docs/class-settings-page.php`

- [ ] **Step 1: Write the implementation**

Create `inc/modules/import/google-docs/class-settings-page.php`:

```php
<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Modules\Import\GoogleDocs;

class SettingsPage {

	/**
	 * @var CredentialsStore
	 */
	protected CredentialsStore $store;

	/**
	 * @var OAuthClient
	 */
	protected OAuthClient $oauth;

	public function __construct( CredentialsStore $store, OAuthClient $oauth ) {
		$this->store = $store;
		$this->oauth = $oauth;
	}

	/**
	 * Register hooks for the network admin settings page and OAuth callback.
	 */
	public function hooks(): void {
		add_action( 'network_admin_menu', [ $this, 'addMenu' ] );
		add_action( 'network_admin_edit_pb_save_google_docs_settings', [ $this, 'saveSettings' ] );
	}

	/**
	 * Add the settings page under Network Admin → Settings.
	 */
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

	/**
	 * Render the settings page.
	 */
	public function renderPage(): void {
		// Handle OAuth callback if present
		if ( isset( $_GET['pb_oauth_callback'] ) && isset( $_GET['code'] ) ) {
			$this->handleOAuthCallback();
			return;
		}

		$creds = $this->store->getClientCredentials();
		$redirect_uri = $this->oauth->getRedirectUri();
		?>
		<div class="wrap">
			<h1><?php _e( 'Google Docs Import Settings', 'pressbooks' ); ?></h1>

			<p><?php _e( 'Configure your Google Cloud OAuth credentials to enable Google Docs import.', 'pressbooks' ); ?></p>

			<h2><?php _e( 'Required Configuration in Google Cloud Console', 'pressbooks' ); ?></h2>
			<p><?php _e( 'Add the following Authorized Redirect URI to your Google Cloud OAuth client:', 'pressbooks' ); ?></p>
			<code><?php echo esc_html( $redirect_uri ); ?></code>

			<p><?php _e( 'Required OAuth scopes:', 'pressbooks' ); ?></p>
			<ul>
				<li><code>https://www.googleapis.com/auth/documents.readonly</code></li>
				<li><code>https://www.googleapis.com/auth/drive.readonly</code></li>
			</ul>

			<form method="post" action="<?php echo esc_url( network_admin_url( 'edit.php?action=pb_save_google_docs_settings' ) ); ?>">
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

	/**
	 * Handle saving settings via network admin edit.php.
	 */
	public function saveSettings(): void {
		check_admin_referer( 'pb_save_google_docs_settings' );

		if ( ! current_user_can( 'manage_network_options' ) ) {
			wp_die( __( 'Unauthorized.', 'pressbooks' ) );
		}

		$client_id = sanitize_text_field( $_POST['client_id'] ?? '' );
		$client_secret = sanitize_text_field( $_POST['client_secret'] ?? '' );
		$this->store->saveClientCredentials( $client_id, $client_secret );

		wp_safe_redirect( add_query_arg( [
			'page'    => 'pb_network_google_docs',
			'updated' => 'true',
		], network_admin_url( 'settings.php' ) ) );
		exit;
	}

	/**
	 * Handle the OAuth callback redirect from Google.
	 */
	protected function handleOAuthCallback(): void {
		$code = sanitize_text_field( $_GET['code'] ?? '' );
		$state = sanitize_text_field( $_GET['state'] ?? '' );

		if ( empty( $code ) || empty( $state ) ) {
			wp_die( __( 'Invalid OAuth callback parameters.', 'pressbooks' ) );
		}

		try {
			$return_url = $this->oauth->handleCallback( $code, $state, get_current_user_id() );
			wp_safe_redirect( add_query_arg( 'pb_gdocs', 'connected', $return_url ) );
			exit;
		} catch ( \Exception $e ) {
			wp_die( esc_html( $e->getMessage() ) );
		}
	}
}
```

- [ ] **Step 2: Manually verify the page renders**

After wiring (done in Task 8), visit Network Admin → Settings → Google Docs Import. Verify:
- Client ID / Secret fields render.
- Redirect URI is displayed.
- Save works.

- [ ] **Step 3: Commit**

```bash
git add inc/modules/import/google-docs/class-settings-page.php
git commit -m "feat(google-docs-import): add network admin settings page for OAuth credentials"
```

---

## Task 5: DocsFetcher

**Files:**
- Create: `inc/modules/import/google-docs/class-docs-fetcher.php`

- [ ] **Step 1: Write the implementation**

Create `inc/modules/import/google-docs/class-docs-fetcher.php`:

```php
<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Modules\Import\GoogleDocs;

use Google\Client as GoogleClient;
use Google\Service\Docs as DocsService;
use Google\Service\Drive as DriveService;

class DocsFetcher {

	/**
	 * @var GoogleClient
	 */
	protected GoogleClient $client;

	public function __construct( GoogleClient $client ) {
		$this->client = $client;
	}

	/**
	 * Fetch a Google Doc's structured JSON via the Docs API.
	 *
	 * @param string $doc_id
	 * @return array The raw document as an associative array.
	 * @throws \Exception If the doc can't be fetched.
	 */
	public function fetchDocument( string $doc_id ): array {
		$service = new DocsService( $this->client );
		$doc = $service->documents->get( $doc_id );
		return json_decode( json_encode( $doc->toSimpleObject() ), true );
	}

	/**
	 * Get basic file metadata from Drive API (title, mimeType).
	 *
	 * @param string $doc_id
	 * @return array{title: string, mimeType: string}
	 * @throws \Exception
	 */
	public function getFileMetadata( string $doc_id ): array {
		$service = new DriveService( $this->client );
		$file = $service->files->get( $doc_id, [ 'fields' => 'name,mimeType' ] );
		return [
			'title'    => $file->getName(),
			'mimeType' => $file->getMimeType(),
		];
	}

	/**
	 * Validate that a file is a Google Doc (not Sheets/Slides/etc).
	 *
	 * @param string $doc_id
	 * @return bool
	 */
	public function isGoogleDoc( string $doc_id ): bool {
		try {
			$meta = $this->getFileMetadata( $doc_id );
			return $meta['mimeType'] === 'application/vnd.google-apps.document';
		} catch ( \Exception $e ) {
			return false;
		}
	}

	/**
	 * Download an image from a content URI (from inlineObjects).
	 *
	 * @param string $content_uri The short-lived URI from the Docs API.
	 * @return string|false The raw image content, or false on failure.
	 */
	public function downloadImage( string $content_uri ) {
		$http = $this->client->authorize();
		try {
			$response = $http->get( $content_uri );
			if ( $response->getStatusCode() === 200 ) {
				return (string) $response->getBody();
			}
		} catch ( \Exception $e ) {
			// Fall through
		}
		return false;
	}

	/**
	 * Fetch and cache the document JSON to a temp file.
	 *
	 * @param string $doc_id
	 * @param string $cache_dir Directory to save the cached JSON.
	 * @return string Path to the cached JSON file.
	 * @throws \Exception
	 */
	public function fetchAndCache( string $doc_id, string $cache_dir ): string {
		$doc = $this->fetchDocument( $doc_id );
		$hash = substr( md5( wp_json_encode( $doc ) ), 0, 8 );
		$path = rtrim( $cache_dir, '/' ) . "/gdoc-{$doc_id}-{$hash}.json";
		\Pressbooks\Utility\put_contents( $path, wp_json_encode( $doc, JSON_PRETTY_PRINT ) );
		return $path;
	}
}
```

- [ ] **Step 2: Commit**

```bash
git add inc/modules/import/google-docs/class-docs-fetcher.php
git commit -m "feat(google-docs-import): add DocsFetcher for Docs/Drive API calls and image download"
```

---

## Task 6: DocsMapper — test fixtures and implementation

This is the core parsing logic. Largest task.

**Files:**
- Create: `inc/modules/import/google-docs/class-docs-mapper.php`
- Create: `tests/test-modules-import-google-docs-mapper.php`
- Create: `tests/fixtures/google-docs/headings-only.json`
- Create: `tests/fixtures/google-docs/mixed-lists.json`
- Create: `tests/fixtures/google-docs/nested-lists.json`
- Create: `tests/fixtures/google-docs/simple-table.json`
- Create: `tests/fixtures/google-docs/with-images.json`
- Create: `tests/fixtures/google-docs/multi-chapter.json`
- Create: `tests/fixtures/google-docs/no-h1.json`
- Create: `tests/fixtures/google-docs/unsupported-content.json`

### Step 6a: Create test fixtures

- [ ] **Step 1: Create fixtures directory and initial fixture**

Create `tests/fixtures/google-docs/headings-only.json`:

```json
{
  "title": "Test Document",
  "body": {
    "content": [
      {
        "sectionBreak": {
          "sectionStyle": {}
        }
      },
      {
        "paragraph": {
          "elements": [
            {
              "textRun": {
                "content": "Chapter One\n",
                "textStyle": {}
              }
            }
          ],
          "paragraphStyle": {
            "namedStyleType": "HEADING_1"
          }
        }
      },
      {
        "paragraph": {
          "elements": [
            {
              "textRun": {
                "content": "This is the first paragraph.\n",
                "textStyle": {}
              }
            }
          ],
          "paragraphStyle": {
            "namedStyleType": "NORMAL_TEXT"
          }
        }
      },
      {
        "paragraph": {
          "elements": [
            {
              "textRun": {
                "content": "A Subheading\n",
                "textStyle": {}
              }
            }
          ],
          "paragraphStyle": {
            "namedStyleType": "HEADING_2"
          }
        }
      },
      {
        "paragraph": {
          "elements": [
            {
              "textRun": {
                "content": "More content under the subheading.\n",
                "textStyle": {}
              }
            }
          ],
          "paragraphStyle": {
            "namedStyleType": "NORMAL_TEXT"
          }
        }
      },
      {
        "paragraph": {
          "elements": [
            {
              "textRun": {
                "content": "Chapter Two\n",
                "textStyle": {}
              }
            }
          ],
          "paragraphStyle": {
            "namedStyleType": "HEADING_1"
          }
        }
      },
      {
        "paragraph": {
          "elements": [
            {
              "textRun": {
                "content": "Content in chapter two.\n",
                "textStyle": {}
              }
            }
          ],
          "paragraphStyle": {
            "namedStyleType": "NORMAL_TEXT"
          }
        }
      }
    ]
  },
  "inlineObjects": {}
}
```

- [ ] **Step 2: Create mixed-lists fixture**

Create `tests/fixtures/google-docs/mixed-lists.json`:

```json
{
  "title": "Lists Test",
  "body": {
    "content": [
      {
        "sectionBreak": { "sectionStyle": {} }
      },
      {
        "paragraph": {
          "elements": [{ "textRun": { "content": "Lists Chapter\n", "textStyle": {} } }],
          "paragraphStyle": { "namedStyleType": "HEADING_1" }
        }
      },
      {
        "paragraph": {
          "elements": [{ "textRun": { "content": "Bullet one\n", "textStyle": {} } }],
          "paragraphStyle": { "namedStyleType": "NORMAL_TEXT" },
          "bullet": {
            "listId": "kix.list1",
            "nestingLevel": 0
          }
        }
      },
      {
        "paragraph": {
          "elements": [{ "textRun": { "content": "Bullet two\n", "textStyle": {} } }],
          "paragraphStyle": { "namedStyleType": "NORMAL_TEXT" },
          "bullet": {
            "listId": "kix.list1",
            "nestingLevel": 0
          }
        }
      },
      {
        "paragraph": {
          "elements": [{ "textRun": { "content": "Normal paragraph between lists.\n", "textStyle": {} } }],
          "paragraphStyle": { "namedStyleType": "NORMAL_TEXT" }
        }
      },
      {
        "paragraph": {
          "elements": [{ "textRun": { "content": "Number one\n", "textStyle": {} } }],
          "paragraphStyle": { "namedStyleType": "NORMAL_TEXT" },
          "bullet": {
            "listId": "kix.list2",
            "nestingLevel": 0
          }
        }
      },
      {
        "paragraph": {
          "elements": [{ "textRun": { "content": "Number two\n", "textStyle": {} } }],
          "paragraphStyle": { "namedStyleType": "NORMAL_TEXT" },
          "bullet": {
            "listId": "kix.list2",
            "nestingLevel": 0
          }
        }
      }
    ]
  },
  "lists": {
    "kix.list1": {
      "listProperties": {
        "nestingLevels": [
          { "glyphType": "GLYPH_TYPE_UNSPECIFIED" }
        ]
      }
    },
    "kix.list2": {
      "listProperties": {
        "nestingLevels": [
          { "glyphType": "DECIMAL" }
        ]
      }
    }
  },
  "inlineObjects": {}
}
```

- [ ] **Step 3: Create nested-lists fixture**

Create `tests/fixtures/google-docs/nested-lists.json`:

```json
{
  "title": "Nested Lists",
  "body": {
    "content": [
      { "sectionBreak": { "sectionStyle": {} } },
      {
        "paragraph": {
          "elements": [{ "textRun": { "content": "Nested Chapter\n", "textStyle": {} } }],
          "paragraphStyle": { "namedStyleType": "HEADING_1" }
        }
      },
      {
        "paragraph": {
          "elements": [{ "textRun": { "content": "Top level\n", "textStyle": {} } }],
          "paragraphStyle": { "namedStyleType": "NORMAL_TEXT" },
          "bullet": { "listId": "kix.nest", "nestingLevel": 0 }
        }
      },
      {
        "paragraph": {
          "elements": [{ "textRun": { "content": "Nested item\n", "textStyle": {} } }],
          "paragraphStyle": { "namedStyleType": "NORMAL_TEXT" },
          "bullet": { "listId": "kix.nest", "nestingLevel": 1 }
        }
      },
      {
        "paragraph": {
          "elements": [{ "textRun": { "content": "Back to top\n", "textStyle": {} } }],
          "paragraphStyle": { "namedStyleType": "NORMAL_TEXT" },
          "bullet": { "listId": "kix.nest", "nestingLevel": 0 }
        }
      }
    ]
  },
  "lists": {
    "kix.nest": {
      "listProperties": {
        "nestingLevels": [
          { "glyphType": "GLYPH_TYPE_UNSPECIFIED" },
          { "glyphType": "GLYPH_TYPE_UNSPECIFIED" }
        ]
      }
    }
  },
  "inlineObjects": {}
}
```

- [ ] **Step 4: Create simple-table fixture**

Create `tests/fixtures/google-docs/simple-table.json`:

```json
{
  "title": "Table Test",
  "body": {
    "content": [
      { "sectionBreak": { "sectionStyle": {} } },
      {
        "paragraph": {
          "elements": [{ "textRun": { "content": "Table Chapter\n", "textStyle": {} } }],
          "paragraphStyle": { "namedStyleType": "HEADING_1" }
        }
      },
      {
        "table": {
          "rows": 2,
          "columns": 2,
          "tableRows": [
            {
              "tableCells": [
                {
                  "content": [
                    {
                      "paragraph": {
                        "elements": [{ "textRun": { "content": "Cell A1\n", "textStyle": {} } }],
                        "paragraphStyle": { "namedStyleType": "NORMAL_TEXT" }
                      }
                    }
                  ]
                },
                {
                  "content": [
                    {
                      "paragraph": {
                        "elements": [{ "textRun": { "content": "Cell B1\n", "textStyle": {} } }],
                        "paragraphStyle": { "namedStyleType": "NORMAL_TEXT" }
                      }
                    }
                  ]
                }
              ]
            },
            {
              "tableCells": [
                {
                  "content": [
                    {
                      "paragraph": {
                        "elements": [{ "textRun": { "content": "Cell A2\n", "textStyle": {} } }],
                        "paragraphStyle": { "namedStyleType": "NORMAL_TEXT" }
                      }
                    }
                  ]
                },
                {
                  "content": [
                    {
                      "paragraph": {
                        "elements": [{ "textRun": { "content": "Cell B2\n", "textStyle": {} } }],
                        "paragraphStyle": { "namedStyleType": "NORMAL_TEXT" }
                      }
                    }
                  ]
                }
              ]
            }
          ]
        }
      }
    ]
  },
  "inlineObjects": {}
}
```

- [ ] **Step 5: Create with-images fixture**

Create `tests/fixtures/google-docs/with-images.json`:

```json
{
  "title": "Images Test",
  "body": {
    "content": [
      { "sectionBreak": { "sectionStyle": {} } },
      {
        "paragraph": {
          "elements": [{ "textRun": { "content": "Image Chapter\n", "textStyle": {} } }],
          "paragraphStyle": { "namedStyleType": "HEADING_1" }
        }
      },
      {
        "paragraph": {
          "elements": [
            {
              "inlineObjectElement": {
                "inlineObjectId": "kix.img1",
                "textStyle": {}
              }
            },
            {
              "textRun": { "content": "\n", "textStyle": {} }
            }
          ],
          "paragraphStyle": { "namedStyleType": "NORMAL_TEXT" }
        }
      },
      {
        "paragraph": {
          "elements": [{ "textRun": { "content": "Text after the image.\n", "textStyle": {} } }],
          "paragraphStyle": { "namedStyleType": "NORMAL_TEXT" }
        }
      }
    ]
  },
  "inlineObjects": {
    "kix.img1": {
      "inlineObjectProperties": {
        "embeddedObject": {
          "title": "My Photo",
          "description": "A beautiful landscape",
          "imageProperties": {
            "contentUri": "https://lh3.googleusercontent.com/fake-image-uri",
            "sourceUri": "https://example.com/original.jpg"
          },
          "size": {
            "width": { "magnitude": 400, "unit": "PT" },
            "height": { "magnitude": 300, "unit": "PT" }
          }
        }
      }
    }
  }
}
```

- [ ] **Step 6: Create multi-chapter fixture**

Create `tests/fixtures/google-docs/multi-chapter.json`:

```json
{
  "title": "Multi Chapter Doc",
  "body": {
    "content": [
      { "sectionBreak": { "sectionStyle": {} } },
      {
        "paragraph": {
          "elements": [{ "textRun": { "content": "First Chapter\n", "textStyle": {} } }],
          "paragraphStyle": { "namedStyleType": "HEADING_1" }
        }
      },
      {
        "paragraph": {
          "elements": [{ "textRun": { "content": "Content one.\n", "textStyle": {} } }],
          "paragraphStyle": { "namedStyleType": "NORMAL_TEXT" }
        }
      },
      {
        "paragraph": {
          "elements": [{ "textRun": { "content": "Second Chapter\n", "textStyle": {} } }],
          "paragraphStyle": { "namedStyleType": "HEADING_1" }
        }
      },
      {
        "paragraph": {
          "elements": [{ "textRun": { "content": "Content two.\n", "textStyle": {} } }],
          "paragraphStyle": { "namedStyleType": "NORMAL_TEXT" }
        }
      },
      {
        "paragraph": {
          "elements": [{ "textRun": { "content": "Third Chapter\n", "textStyle": {} } }],
          "paragraphStyle": { "namedStyleType": "HEADING_1" }
        }
      },
      {
        "paragraph": {
          "elements": [{ "textRun": { "content": "Content three.\n", "textStyle": {} } }],
          "paragraphStyle": { "namedStyleType": "NORMAL_TEXT" }
        }
      }
    ]
  },
  "inlineObjects": {}
}
```

- [ ] **Step 7: Create no-h1 fixture**

Create `tests/fixtures/google-docs/no-h1.json`:

```json
{
  "title": "Document Without Chapters",
  "namedStyles": {
    "styles": [
      { "namedStyleType": "TITLE", "textStyle": {} }
    ]
  },
  "body": {
    "content": [
      { "sectionBreak": { "sectionStyle": {} } },
      {
        "paragraph": {
          "elements": [{ "textRun": { "content": "Document Without Chapters\n", "textStyle": {} } }],
          "paragraphStyle": { "namedStyleType": "TITLE" }
        }
      },
      {
        "paragraph": {
          "elements": [{ "textRun": { "content": "Just a paragraph.\n", "textStyle": {} } }],
          "paragraphStyle": { "namedStyleType": "NORMAL_TEXT" }
        }
      },
      {
        "paragraph": {
          "elements": [{ "textRun": { "content": "A sub heading\n", "textStyle": {} } }],
          "paragraphStyle": { "namedStyleType": "HEADING_2" }
        }
      },
      {
        "paragraph": {
          "elements": [{ "textRun": { "content": "More text.\n", "textStyle": {} } }],
          "paragraphStyle": { "namedStyleType": "NORMAL_TEXT" }
        }
      }
    ]
  },
  "inlineObjects": {}
}
```

- [ ] **Step 8: Create unsupported-content fixture**

Create `tests/fixtures/google-docs/unsupported-content.json`:

```json
{
  "title": "Unsupported Content",
  "body": {
    "content": [
      { "sectionBreak": { "sectionStyle": {} } },
      {
        "paragraph": {
          "elements": [{ "textRun": { "content": "Chapter With Unsupported\n", "textStyle": {} } }],
          "paragraphStyle": { "namedStyleType": "HEADING_1" }
        }
      },
      {
        "paragraph": {
          "elements": [{ "textRun": { "content": "Normal text.\n", "textStyle": {} } }],
          "paragraphStyle": { "namedStyleType": "NORMAL_TEXT" }
        }
      },
      {
        "paragraph": {
          "elements": [
            {
              "inlineObjectElement": {
                "inlineObjectId": "kix.drawing1",
                "textStyle": {}
              }
            },
            { "textRun": { "content": "\n", "textStyle": {} } }
          ],
          "paragraphStyle": { "namedStyleType": "NORMAL_TEXT" }
        }
      },
      {
        "paragraph": {
          "elements": [
            {
              "equation": {}
            },
            { "textRun": { "content": "\n", "textStyle": {} } }
          ],
          "paragraphStyle": { "namedStyleType": "NORMAL_TEXT" }
        }
      }
    ]
  },
  "inlineObjects": {
    "kix.drawing1": {
      "inlineObjectProperties": {
        "embeddedObject": {
          "embeddedDrawingProperties": {}
        }
      }
    }
  }
}
```

- [ ] **Step 9: Commit fixtures**

```bash
git add tests/fixtures/google-docs/
git commit -m "test(google-docs-import): add JSON fixtures for DocsMapper tests"
```

### Step 6b: Write tests and mapper

- [ ] **Step 10: Write the failing tests**

Create `tests/test-modules-import-google-docs-mapper.php`:

```php
<?php

use Pressbooks\Modules\Import\GoogleDocs\DocsMapper;

class Modules_ImportGoogleDocsMapperTest extends \WP_UnitTestCase {

	protected function loadFixture( string $name ): array {
		$path = __DIR__ . '/fixtures/google-docs/' . $name;
		return json_decode( file_get_contents( $path ), true );
	}

	/**
	 * @group import
	 */
	public function test_headings_only_splits_into_two_chapters(): void {
		$mapper = new DocsMapper();
		$result = $mapper->toChapters( $this->loadFixture( 'headings-only.json' ) );

		$this->assertCount( 2, $result );
		$this->assertSame( 'Chapter One', $result[0]['title'] );
		$this->assertSame( 'Chapter Two', $result[1]['title'] );

		$this->assertStringContainsString( '<p>This is the first paragraph.</p>', $result[0]['html'] );
		$this->assertStringContainsString( '<h2>A Subheading</h2>', $result[0]['html'] );
		$this->assertStringContainsString( '<p>More content under the subheading.</p>', $result[0]['html'] );
		$this->assertStringContainsString( '<p>Content in chapter two.</p>', $result[1]['html'] );

		// H1 should NOT appear in the HTML
		$this->assertStringNotContainsString( '<h1>', $result[0]['html'] );
		$this->assertStringNotContainsString( '<h1>', $result[1]['html'] );
	}

	/**
	 * @group import
	 */
	public function test_mixed_lists_produces_ul_and_ol(): void {
		$mapper = new DocsMapper();
		$result = $mapper->toChapters( $this->loadFixture( 'mixed-lists.json' ) );

		$this->assertCount( 1, $result );
		$html = $result[0]['html'];

		$this->assertStringContainsString( '<ul>', $html );
		$this->assertStringContainsString( '<li>Bullet one</li>', $html );
		$this->assertStringContainsString( '<li>Bullet two</li>', $html );
		$this->assertStringContainsString( '</ul>', $html );

		$this->assertStringContainsString( '<p>Normal paragraph between lists.</p>', $html );

		$this->assertStringContainsString( '<ol>', $html );
		$this->assertStringContainsString( '<li>Number one</li>', $html );
		$this->assertStringContainsString( '<li>Number two</li>', $html );
		$this->assertStringContainsString( '</ol>', $html );
	}

	/**
	 * @group import
	 */
	public function test_nested_lists(): void {
		$mapper = new DocsMapper();
		$result = $mapper->toChapters( $this->loadFixture( 'nested-lists.json' ) );

		$this->assertCount( 1, $result );
		$html = $result[0]['html'];

		$this->assertStringContainsString( '<ul>', $html );
		$this->assertStringContainsString( '<li>Top level', $html );
		$this->assertStringContainsString( '<li>Nested item</li>', $html );
		$this->assertStringContainsString( '<li>Back to top</li>', $html );

		// Should have nested ul inside an li
		$this->assertMatchesRegularExpression( '/<li>Top level\s*<ul>\s*<li>Nested item<\/li>\s*<\/ul>\s*<\/li>/s', $html );
	}

	/**
	 * @group import
	 */
	public function test_simple_table(): void {
		$mapper = new DocsMapper();
		$result = $mapper->toChapters( $this->loadFixture( 'simple-table.json' ) );

		$this->assertCount( 1, $result );
		$html = $result[0]['html'];

		$this->assertStringContainsString( '<table>', $html );
		$this->assertStringContainsString( '<tr>', $html );
		$this->assertStringContainsString( '<td>Cell A1</td>', $html );
		$this->assertStringContainsString( '<td>Cell B1</td>', $html );
		$this->assertStringContainsString( '<td>Cell A2</td>', $html );
		$this->assertStringContainsString( '<td>Cell B2</td>', $html );
		$this->assertStringContainsString( '</table>', $html );
	}

	/**
	 * @group import
	 */
	public function test_with_images_produces_img_placeholder(): void {
		$mapper = new DocsMapper();
		$result = $mapper->toChapters( $this->loadFixture( 'with-images.json' ) );

		$this->assertCount( 1, $result );
		$html = $result[0]['html'];

		$this->assertStringContainsString( 'src="#gdoc-image-kix.img1"', $html );
		$this->assertStringContainsString( 'alt="A beautiful landscape"', $html );
		$this->assertStringContainsString( 'title="My Photo"', $html );
		$this->assertStringContainsString( 'width="400"', $html );
		$this->assertStringContainsString( 'height="300"', $html );

		// images array
		$this->assertCount( 1, $result[0]['images'] );
		$img = $result[0]['images'][0];
		$this->assertSame( 'kix.img1', $img['object_id'] );
		$this->assertSame( 'https://lh3.googleusercontent.com/fake-image-uri', $img['content_uri'] );
		$this->assertSame( 'A beautiful landscape', $img['alt'] );
		$this->assertSame( 'My Photo', $img['title'] );
	}

	/**
	 * @group import
	 */
	public function test_multi_chapter_splits_on_h1(): void {
		$mapper = new DocsMapper();
		$result = $mapper->toChapters( $this->loadFixture( 'multi-chapter.json' ) );

		$this->assertCount( 3, $result );
		$this->assertSame( 'First Chapter', $result[0]['title'] );
		$this->assertSame( 'Second Chapter', $result[1]['title'] );
		$this->assertSame( 'Third Chapter', $result[2]['title'] );
	}

	/**
	 * @group import
	 */
	public function test_no_h1_produces_single_intro_chapter(): void {
		$mapper = new DocsMapper();
		$result = $mapper->toChapters( $this->loadFixture( 'no-h1.json' ) );

		$this->assertCount( 1, $result );
		$this->assertSame( 'Document Without Chapters', $result[0]['title'] );
		$this->assertStringContainsString( '<p>Just a paragraph.</p>', $result[0]['html'] );
		$this->assertStringContainsString( '<h2>A sub heading</h2>', $result[0]['html'] );
	}

	/**
	 * @group import
	 */
	public function test_unsupported_content_is_dropped_with_warnings(): void {
		$mapper = new DocsMapper();
		$result = $mapper->toChapters( $this->loadFixture( 'unsupported-content.json' ) );

		$this->assertCount( 1, $result );
		$this->assertStringContainsString( '<p>Normal text.</p>', $result[0]['html'] );
		$this->assertStringNotContainsString( 'drawing', $result[0]['html'] );
		$this->assertStringNotContainsString( 'equation', $result[0]['html'] );

		$warnings = $result[0]['warnings'];
		$this->assertGreaterThan( 0, ( $warnings['dropped_drawings'] ?? 0 ) + ( $warnings['dropped_equations'] ?? 0 ) );
	}

	/**
	 * @group import
	 */
	public function test_text_styling_bold_italic_underline_link(): void {
		$doc = [
			'title' => 'Style Test',
			'body' => [
				'content' => [
					[ 'sectionBreak' => [ 'sectionStyle' => [] ] ],
					[
						'paragraph' => [
							'elements' => [
								[
									'textRun' => [
										'content' => 'bold',
										'textStyle' => [ 'bold' => true ],
									],
								],
								[
									'textRun' => [
										'content' => ' italic',
										'textStyle' => [ 'italic' => true ],
									],
								],
								[
									'textRun' => [
										'content' => ' underline',
										'textStyle' => [ 'underline' => true ],
									],
								],
								[
									'textRun' => [
										'content' => ' linked',
										'textStyle' => [ 'link' => [ 'url' => 'https://example.com' ] ],
									],
								],
								[
									'textRun' => [
										'content' => "\n",
										'textStyle' => [],
									],
								],
							],
							'paragraphStyle' => [ 'namedStyleType' => 'HEADING_1' ],
						],
					],
					[
						'paragraph' => [
							'elements' => [
								[
									'textRun' => [
										'content' => 'some ',
										'textStyle' => [],
									],
								],
								[
									'textRun' => [
										'content' => 'bold text',
										'textStyle' => [ 'bold' => true ],
									],
								],
								[
									'textRun' => [
										'content' => " here\n",
										'textStyle' => [],
									],
								],
							],
							'paragraphStyle' => [ 'namedStyleType' => 'NORMAL_TEXT' ],
						],
					],
				],
			],
			'inlineObjects' => [],
		];

		$mapper = new DocsMapper();
		$result = $mapper->toChapters( $doc );

		$this->assertCount( 1, $result );
		$html = $result[0]['html'];

		$this->assertStringContainsString( '<strong>bold text</strong>', $html );
		$this->assertStringContainsString( 'some <strong>bold text</strong> here', $html );
	}
}
```

- [ ] **Step 11: Run tests to verify they fail**

Run: `vendor/bin/phpunit --filter Modules_ImportGoogleDocsMapperTest`
Expected: FAIL — DocsMapper class not found.

- [ ] **Step 12: Write DocsMapper implementation**

Create `inc/modules/import/google-docs/class-docs-mapper.php`:

```php
<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Modules\Import\GoogleDocs;

class DocsMapper {

	/**
	 * Ordered glyph types that indicate an ordered list.
	 */
	const ORDERED_GLYPH_TYPES = [ 'DECIMAL', 'ZERO_DECIMAL', 'ALPHA', 'UPPER_ALPHA', 'ROMAN', 'UPPER_ROMAN' ];

	/**
	 * Parse a Google Docs API document response into an array of chapters.
	 *
	 * @param array $doc The full document JSON as an associative array.
	 * @return array Array of chapter records: [ ['id', 'title', 'html', 'images', 'warnings'], ... ]
	 */
	public function toChapters( array $doc ): array {
		$chapters = [];
		$current_chapter = null;
		$doc_title = $doc['title'] ?? 'Untitled';
		$inline_objects = $doc['inlineObjects'] ?? [];
		$lists = $doc['lists'] ?? [];
		$content = $doc['body']['content'] ?? [];

		// Buffer for list items
		$list_buffer = [];
		$current_list_id = null;

		foreach ( $content as $element ) {
			// Skip structural elements we don't handle
			if ( isset( $element['sectionBreak'] ) || isset( $element['tableOfContents'] ) ) {
				continue;
			}

			if ( isset( $element['paragraph'] ) ) {
				$para = $element['paragraph'];
				$style_type = $para['paragraphStyle']['namedStyleType'] ?? 'NORMAL_TEXT';
				$bullet = $para['bullet'] ?? null;

				// Chapter split on HEADING_1
				if ( $style_type === 'HEADING_1' ) {
					// Flush any open list
					if ( ! empty( $list_buffer ) ) {
						$this->appendToChapter( $current_chapter, $this->renderList( $list_buffer, $lists, $current_list_id ) );
						$list_buffer = [];
						$current_list_id = null;
					}
					// Close previous chapter
					if ( $current_chapter !== null ) {
						$chapters[] = $current_chapter;
					}
					$title = $this->extractPlainText( $para['elements'] ?? [] );
					$current_chapter = $this->newChapter( $title );
					continue;
				}

				// TITLE — used as doc title, skip inline rendering
				if ( $style_type === 'TITLE' ) {
					$title_text = $this->extractPlainText( $para['elements'] ?? [] );
					if ( ! empty( $title_text ) ) {
						$doc_title = $title_text;
					}
					// If no chapter started yet, update the fallback title
					if ( $current_chapter !== null && $current_chapter['title'] === $doc_title ) {
						$current_chapter['title'] = $doc_title;
					}
					continue;
				}

				// Auto-create intro chapter if none started yet
				if ( $current_chapter === null ) {
					$current_chapter = $this->newChapter( $doc_title );
				}

				// Handle list items
				if ( $bullet !== null ) {
					$list_id = $bullet['listId'] ?? '';
					$nesting = $bullet['nestingLevel'] ?? 0;

					// If different list, flush the old one
					if ( $current_list_id !== null && $current_list_id !== $list_id ) {
						$this->appendToChapter( $current_chapter, $this->renderList( $list_buffer, $lists, $current_list_id ) );
						$list_buffer = [];
					}

					$current_list_id = $list_id;
					$list_buffer[] = [
						'nesting' => $nesting,
						'html'    => $this->renderTextRuns( $para['elements'] ?? [], $inline_objects, $current_chapter ),
					];
					continue;
				}

				// Flush any open list before non-list content
				if ( ! empty( $list_buffer ) ) {
					$this->appendToChapter( $current_chapter, $this->renderList( $list_buffer, $lists, $current_list_id ) );
					$list_buffer = [];
					$current_list_id = null;
				}

				// Headings
				$heading_map = [
					'HEADING_2' => 'h2',
					'HEADING_3' => 'h3',
					'HEADING_4' => 'h4',
					'HEADING_5' => 'h5',
					'HEADING_6' => 'h6',
					'SUBTITLE'  => 'h2',
				];

				$text_html = $this->renderTextRuns( $para['elements'] ?? [], $inline_objects, $current_chapter );

				if ( isset( $heading_map[ $style_type ] ) ) {
					$tag = $heading_map[ $style_type ];
					$this->appendToChapter( $current_chapter, "<{$tag}>{$text_html}</{$tag}>" );
				} else {
					// Check if this paragraph contains only an image (no wrapping <p>)
					$only_image = $this->isImageOnlyParagraph( $para['elements'] ?? [] );
					if ( $only_image ) {
						$this->appendToChapter( $current_chapter, $text_html );
					} else {
						$this->appendToChapter( $current_chapter, "<p>{$text_html}</p>" );
					}
				}
			} elseif ( isset( $element['table'] ) ) {
				// Flush any open list
				if ( ! empty( $list_buffer ) ) {
					if ( $current_chapter === null ) {
						$current_chapter = $this->newChapter( $doc_title );
					}
					$this->appendToChapter( $current_chapter, $this->renderList( $list_buffer, $lists, $current_list_id ) );
					$list_buffer = [];
					$current_list_id = null;
				}
				if ( $current_chapter === null ) {
					$current_chapter = $this->newChapter( $doc_title );
				}
				$this->appendToChapter( $current_chapter, $this->renderTable( $element['table'], $inline_objects, $current_chapter ) );
			}
		}

		// Flush remaining list buffer
		if ( ! empty( $list_buffer ) && $current_chapter !== null ) {
			$this->appendToChapter( $current_chapter, $this->renderList( $list_buffer, $lists, $current_list_id ) );
		}

		// Close the last chapter
		if ( $current_chapter !== null ) {
			$chapters[] = $current_chapter;
		}

		return $chapters;
	}

	/**
	 * Create a new empty chapter record.
	 *
	 * @param string $title
	 * @return array
	 */
	protected function newChapter( string $title ): array {
		return [
			'id'       => sanitize_title( $title ),
			'title'    => $title,
			'html'     => '',
			'images'   => [],
			'warnings' => [],
		];
	}

	/**
	 * Append HTML to a chapter buffer.
	 *
	 * @param array &$chapter
	 * @param string $html
	 */
	protected function appendToChapter( array &$chapter, string $html ): void {
		if ( ! empty( $chapter['html'] ) ) {
			$chapter['html'] .= "\n";
		}
		$chapter['html'] .= $html;
	}

	/**
	 * Extract plain text from an array of paragraph elements.
	 *
	 * @param array $elements
	 * @return string
	 */
	protected function extractPlainText( array $elements ): string {
		$text = '';
		foreach ( $elements as $el ) {
			if ( isset( $el['textRun']['content'] ) ) {
				$text .= $el['textRun']['content'];
			}
		}
		return trim( $text );
	}

	/**
	 * Render text runs within a paragraph into HTML, handling bold/italic/underline/link/images.
	 *
	 * @param array $elements
	 * @param array $inline_objects
	 * @param array &$chapter Reference to current chapter for tracking images/warnings.
	 * @return string
	 */
	protected function renderTextRuns( array $elements, array $inline_objects, array &$chapter ): string {
		$html = '';
		foreach ( $elements as $el ) {
			if ( isset( $el['textRun'] ) ) {
				$run = $el['textRun'];
				$content = $run['content'] ?? '';
				// Strip trailing newline that Google adds to every paragraph
				$content = rtrim( $content, "\n" );
				if ( $content === '' ) {
					continue;
				}
				$content = htmlspecialchars( $content, ENT_QUOTES, 'UTF-8' );
				$style = $run['textStyle'] ?? [];

				// Apply inline styles (order: link wraps bold/italic/underline)
				if ( ! empty( $style['bold'] ) ) {
					$content = "<strong>{$content}</strong>";
				}
				if ( ! empty( $style['italic'] ) ) {
					$content = "<em>{$content}</em>";
				}
				if ( ! empty( $style['underline'] ) && empty( $style['link'] ) ) {
					// Don't underline links — browsers do that already
					$content = "<u>{$content}</u>";
				}
				if ( ! empty( $style['link']['url'] ) ) {
					$url = esc_url( $style['link']['url'] );
					$content = "<a href=\"{$url}\">{$content}</a>";
				}

				$html .= $content;
			} elseif ( isset( $el['inlineObjectElement'] ) ) {
				$obj_id = $el['inlineObjectElement']['inlineObjectId'] ?? '';
				$obj = $inline_objects[ $obj_id ] ?? null;

				if ( $obj && isset( $obj['inlineObjectProperties']['embeddedObject'] ) ) {
					$embedded = $obj['inlineObjectProperties']['embeddedObject'];

					// Skip drawings
					if ( isset( $embedded['embeddedDrawingProperties'] ) ) {
						$chapter['warnings']['dropped_drawings'] = ( $chapter['warnings']['dropped_drawings'] ?? 0 ) + 1;
						continue;
					}

					// Image
					if ( isset( $embedded['imageProperties'] ) ) {
						$alt = $embedded['description'] ?? $embedded['title'] ?? '';
						$title = $embedded['title'] ?? '';
						$content_uri = $embedded['imageProperties']['contentUri'] ?? '';
						$width = (int) ( $embedded['size']['width']['magnitude'] ?? 0 );
						$height = (int) ( $embedded['size']['height']['magnitude'] ?? 0 );

						$chapter['images'][] = [
							'object_id'   => $obj_id,
							'content_uri' => $content_uri,
							'alt'         => $alt,
							'title'       => $title,
						];

						$alt_attr = esc_attr( $alt );
						$title_attr = esc_attr( $title );
						$html .= "<img src=\"#gdoc-image-{$obj_id}\" alt=\"{$alt_attr}\" title=\"{$title_attr}\" width=\"{$width}\" height=\"{$height}\" />";
					}
				}
			} elseif ( isset( $el['equation'] ) ) {
				$chapter['warnings']['dropped_equations'] = ( $chapter['warnings']['dropped_equations'] ?? 0 ) + 1;
			}
		}
		return $html;
	}

	/**
	 * Check if a paragraph contains only a single inline object (image-only paragraph).
	 *
	 * @param array $elements
	 * @return bool
	 */
	protected function isImageOnlyParagraph( array $elements ): bool {
		$has_image = false;
		foreach ( $elements as $el ) {
			if ( isset( $el['inlineObjectElement'] ) ) {
				$has_image = true;
			} elseif ( isset( $el['textRun'] ) ) {
				$content = trim( $el['textRun']['content'] ?? '' );
				if ( $content !== '' ) {
					return false;
				}
			}
		}
		return $has_image;
	}

	/**
	 * Render a buffered list into HTML.
	 *
	 * @param array $items Array of ['nesting' => int, 'html' => string].
	 * @param array $lists The document's lists metadata.
	 * @param string $list_id
	 * @return string
	 */
	protected function renderList( array $items, array $lists, string $list_id ): string {
		$tag = $this->getListTag( $lists, $list_id );
		return $this->buildNestedList( $items, 0, $tag );
	}

	/**
	 * Recursively build nested list HTML.
	 *
	 * @param array $items
	 * @param int $level Current nesting level.
	 * @param string $tag 'ul' or 'ol'.
	 * @return string
	 */
	protected function buildNestedList( array &$items, int $level, string $tag ): string {
		$html = "<{$tag}>";
		while ( ! empty( $items ) ) {
			$item = $items[0];
			if ( $item['nesting'] < $level ) {
				break;
			}
			if ( $item['nesting'] === $level ) {
				array_shift( $items );
				$html .= '<li>' . $item['html'];
				// Check if next item is deeper
				if ( ! empty( $items ) && $items[0]['nesting'] > $level ) {
					$html .= "\n" . $this->buildNestedList( $items, $level + 1, $tag );
				}
				$html .= '</li>';
			} else {
				// Deeper than expected without a parent — create parent wrapper
				$html .= '<li>' . $this->buildNestedList( $items, $item['nesting'], $tag ) . '</li>';
			}
		}
		$html .= "</{$tag}>";
		return $html;
	}

	/**
	 * Determine list tag based on glyph type.
	 *
	 * @param array $lists
	 * @param string $list_id
	 * @return string 'ul' or 'ol'
	 */
	protected function getListTag( array $lists, string $list_id ): string {
		$glyph = $lists[ $list_id ]['listProperties']['nestingLevels'][0]['glyphType'] ?? '';
		return in_array( $glyph, self::ORDERED_GLYPH_TYPES, true ) ? 'ol' : 'ul';
	}

	/**
	 * Render a table structural element into HTML.
	 *
	 * @param array $table
	 * @param array $inline_objects
	 * @param array &$chapter
	 * @return string
	 */
	protected function renderTable( array $table, array $inline_objects, array &$chapter ): string {
		$html = '<table>';
		foreach ( $table['tableRows'] ?? [] as $row ) {
			$html .= '<tr>';
			foreach ( $row['tableCells'] ?? [] as $cell ) {
				$cell_content = '';
				foreach ( $cell['content'] ?? [] as $el ) {
					if ( isset( $el['paragraph'] ) ) {
						$cell_content .= $this->renderTextRuns(
							$el['paragraph']['elements'] ?? [],
							$inline_objects,
							$chapter
						);
					}
				}
				$html .= '<td>' . $cell_content . '</td>';
			}
			$html .= '</tr>';
		}
		$html .= '</table>';
		return $html;
	}
}
```

- [ ] **Step 13: Run tests to verify they pass**

Run: `vendor/bin/phpunit --filter Modules_ImportGoogleDocsMapperTest`
Expected: All 9 tests PASS.

- [ ] **Step 14: Commit**

```bash
git add inc/modules/import/google-docs/class-docs-mapper.php tests/test-modules-import-google-docs-mapper.php
git commit -m "feat(google-docs-import): add DocsMapper with full text/list/table/image parsing"
```

---

## Task 7: GoogleDocs importer class

**Files:**
- Create: `inc/modules/import/google-docs/class-google-docs.php`

- [ ] **Step 1: Write the implementation**

Create `inc/modules/import/google-docs/class-google-docs.php`:

```php
<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Modules\Import\GoogleDocs;

use Pressbooks\Book;
use Pressbooks\Modules\Import\Import;

class GoogleDocs extends Import {

	const TYPE_OF = 'google-docs';

	/**
	 * @var DocsMapper
	 */
	protected DocsMapper $mapper;

	/**
	 * @var DocsFetcher|null
	 */
	protected ?DocsFetcher $fetcher = null;

	/**
	 * @var array Warnings accumulated during import.
	 */
	protected array $import_warnings = [];

	public function __construct() {
		$this->mapper = new DocsMapper();
	}

	/**
	 * Set the current import option from a cached JSON file.
	 *
	 * The $upload array for Google Docs looks like:
	 *   ['file' => '/path/to/gdoc-xxx.json', 'url' => 'https://docs.google.com/...', 'type' => 'application/json']
	 *
	 * @param array $upload
	 * @return bool
	 */
	function setCurrentImportOption( array $upload ): bool {
		if ( ! file_exists( $upload['file'] ) ) {
			return false;
		}

		$json = json_decode( file_get_contents( $upload['file'] ), true );
		if ( empty( $json ) || empty( $json['body']['content'] ) ) {
			return false;
		}

		$chapters_data = $this->mapper->toChapters( $json );
		$chapter_titles = [];
		foreach ( $chapters_data as $ch ) {
			$chapter_titles[] = $ch['title'];
		}

		if ( empty( $chapter_titles ) ) {
			$chapter_titles[] = '__UNKNOWN__';
		}

		$option = [
			'file'                => $upload['file'],
			'url'                 => $upload['url'] ?? null,
			'file_type'           => 'application/json',
			'type_of'             => self::TYPE_OF,
			'chapters'            => $chapter_titles,
			'post_types'          => [],
			'allow_parts'         => false,
			'default_post_status' => 'draft',
		];

		return update_option( 'pressbooks_current_import', $option );
	}

	/**
	 * Import selected chapters from the cached Google Doc JSON.
	 *
	 * @param array $current_import
	 * @return bool
	 */
	function import( array $current_import ): bool {
		if ( ! file_exists( $current_import['file'] ) ) {
			return false;
		}

		$json = json_decode( file_get_contents( $current_import['file'] ), true );
		if ( empty( $json ) ) {
			return false;
		}

		$chapters_data = $this->mapper->toChapters( $json );
		$chapter_parent = $this->getChapterParent();

		foreach ( $current_import['chapters'] as $id => $chapter_title ) {
			if ( ! $this->flaggedForImport( $id ) ) {
				continue;
			}

			if ( ! isset( $chapters_data[ $id ] ) ) {
				continue;
			}

			$ch = $chapters_data[ $id ];
			$html = $ch['html'];

			// Download and sideload images
			$html = $this->processImages( $html, $ch['images'] ?? [] );

			// Sanitize HTML
			$html = $this->tidy( $html );

			$post_type = $this->determinePostType( $id );
			$post_status = $current_import['default_post_status'] ?? 'draft';

			$new_post = [
				'post_title'   => wp_strip_all_tags( $ch['title'] ),
				'post_content' => $html,
				'post_type'    => $post_type,
				'post_status'  => $post_status,
			];

			if ( 'chapter' === $post_type ) {
				$new_post['post_parent'] = $chapter_parent;
			}

			$pid = wp_insert_post( add_magic_quotes( $new_post ) );
			if ( $pid && ! is_wp_error( $pid ) ) {
				update_post_meta( $pid, 'pb_show_title', 'on' );
				Book::consolidatePost( $pid, get_post( $pid ) );
			} else {
				$this->import_warnings[] = sprintf(
					__( 'Failed to import chapter: %s', 'pressbooks' ),
					$ch['title']
				);
			}

			// Collect warnings
			if ( ! empty( $ch['warnings'] ) ) {
				foreach ( $ch['warnings'] as $type => $count ) {
					$this->import_warnings[] = sprintf( '%d %s skipped in "%s"', $count, str_replace( '_', ' ', $type ), $ch['title'] );
				}
			}
		}

		return true;
	}

	/**
	 * Process image placeholders: download from Google, sideload to WP, rewrite src.
	 *
	 * @param string $html
	 * @param array $images Array of image records from DocsMapper.
	 * @return string HTML with rewritten image sources.
	 */
	protected function processImages( string $html, array $images ): string {
		if ( empty( $images ) || $this->fetcher === null ) {
			return $html;
		}

		foreach ( $images as $img ) {
			$placeholder = '#gdoc-image-' . $img['object_id'];
			$image_data = $this->fetcher->downloadImage( $img['content_uri'] );

			if ( $image_data === false ) {
				// Remove the img tag or leave placeholder
				$html = str_replace( "src=\"{$placeholder}\"", 'src=""', $html );
				$this->import_warnings[] = sprintf(
					__( 'Could not download image: %s', 'pressbooks' ),
					$img['alt'] ?: $img['object_id']
				);
				continue;
			}

			$tmp_file = $this->createTmpFile();
			\Pressbooks\Utility\put_contents( $tmp_file, $image_data );

			// Determine filename from content URI
			$filename = 'gdoc-image-' . sanitize_file_name( $img['object_id'] ) . '.png';
			$filename = $this->properImageExtension( $tmp_file, $filename );

			$pid = media_handle_sideload( [
				'name'     => $filename,
				'tmp_name' => $tmp_file,
			], 0 );

			if ( is_wp_error( $pid ) ) {
				$this->import_warnings[] = sprintf(
					__( 'Could not sideload image: %s', 'pressbooks' ),
					$img['alt'] ?: $img['object_id']
				);
				$html = str_replace( "src=\"{$placeholder}\"", 'src=""', $html );
				continue;
			}

			// Set alt text on the attachment
			if ( ! empty( $img['alt'] ) ) {
				update_post_meta( $pid, '_wp_attachment_image_alt', $img['alt'] );
			}
			if ( ! empty( $img['title'] ) ) {
				wp_update_post( [
					'ID'         => $pid,
					'post_title' => $img['title'],
				] );
			}

			$src = wp_get_attachment_url( $pid );
			if ( $src ) {
				$html = str_replace( "src=\"{$placeholder}\"", "src=\"{$src}\"", $html );
			}
		}

		return $html;
	}

	/**
	 * Set the DocsFetcher instance (used for image downloads during import).
	 *
	 * @param DocsFetcher $fetcher
	 */
	public function setFetcher( DocsFetcher $fetcher ): void {
		$this->fetcher = $fetcher;
	}

	/**
	 * Get warnings accumulated during import.
	 *
	 * @return array
	 */
	public function getImportWarnings(): array {
		return $this->import_warnings;
	}
}
```

- [ ] **Step 2: Commit**

```bash
git add inc/modules/import/google-docs/class-google-docs.php
git commit -m "feat(google-docs-import): add GoogleDocs importer class with chapter import and image sideloading"
```

---

## Task 8: Wire everything together — registration, hooks, form handling

**Files:**
- Modify: `inc/modules/import/class-import.php`
- Create or modify: bootstrap file (e.g. `pressbooks.php` or a service-provider-like hook file)

- [ ] **Step 1: Determine the hook registration location**

Check how other hooks are registered in the codebase. Look at `pressbooks.php` for `add_action` patterns or a service provider class.

Run: `grep -n "add_action\|add_filter" pressbooks.php | head -30`

The Google Docs import registration code will go wherever other import-related hooks are initialized.

- [ ] **Step 2: Add filter registrations**

Add the following registrations (in the appropriate bootstrap file, likely `pressbooks.php` or a dedicated init hook):

```php
// Google Docs Import
if ( is_admin() ) {
	$gdocs_creds_store = new \Pressbooks\Modules\Import\GoogleDocs\CredentialsStore();
	$gdocs_oauth = new \Pressbooks\Modules\Import\GoogleDocs\OAuthClient( $gdocs_creds_store );

	// Network admin settings page
	$gdocs_settings = new \Pressbooks\Modules\Import\GoogleDocs\SettingsPage( $gdocs_creds_store, $gdocs_oauth );
	$gdocs_settings->hooks();

	// Register import type in the dropdown
	add_filter( 'pb_select_import_type', function ( array $types ) {
		$types[ \Pressbooks\Modules\Import\GoogleDocs\GoogleDocs::TYPE_OF ] = __( 'Google Docs', 'pressbooks' );
		return $types;
	} );

	// Register importer instance for the custom type
	add_filter( 'pb_initialize_import', function ( $importers ) {
		if ( ! is_array( $importers ) ) {
			$importers = [];
		}
		$importers[] = new \Pressbooks\Modules\Import\GoogleDocs\GoogleDocs();
		return $importers;
	} );

	// OAuth authorize action
	add_action( 'admin_post_pb_gdocs_authorize', function () use ( $gdocs_oauth, $gdocs_creds_store ) {
		check_admin_referer( 'pb_gdocs_authorize' );
		if ( ! $gdocs_creds_store->isConfigured() ) {
			wp_die( __( 'Google Docs import is not configured.', 'pressbooks' ) );
		}
		$return_url = wp_get_referer() ?: admin_url( 'admin.php?page=pb_import' );
		$auth_url = $gdocs_oauth->getAuthorizeUrl( $return_url );
		wp_redirect( $auth_url );
		exit;
	} );

	// OAuth disconnect action
	add_action( 'admin_post_pb_gdocs_disconnect', function () use ( $gdocs_oauth ) {
		check_admin_referer( 'pb_gdocs_disconnect' );
		$gdocs_oauth->disconnect( get_current_user_id() );
		$return_url = wp_get_referer() ?: admin_url( 'admin.php?page=pb_import' );
		wp_safe_redirect( add_query_arg( 'pb_gdocs', 'disconnected', $return_url ) );
		exit;
	} );
}
```

- [ ] **Step 3: Handle form submission bypass for Google Docs type**

The existing `setImportOptions()` in `class-import.php` requires a file upload. For Google Docs, we need to intercept the form submission when `type_of === 'google-docs'` and handle it differently — fetching the doc from the API instead of uploading a file.

Add the following code **inside** `setImportOptions()` in `inc/modules/import/class-import.php`, right after the nonce check at line 392 and **before** the file upload handling at line 396:

```php
// Google Docs: intercept before file upload handling
if ( isset( $_POST['type_of'] ) && $_POST['type_of'] === GoogleDocs\GoogleDocs::TYPE_OF ) {
	return self::setGoogleDocsImportOptions();
}
```

Then add this new static method to the `Import` class:

```php
/**
 * Handle Google Docs import: fetch doc via API, cache JSON, set import option.
 *
 * @return bool
 */
static protected function setGoogleDocsImportOptions(): bool {
	$url = sanitize_text_field( getset( '_POST', 'import_http' ) );
	$doc_id = GoogleDocs\OAuthClient::extractDocId( $url );

	if ( ! $doc_id ) {
		$_SESSION['pb_errors'][] = __( 'Please enter a valid Google Docs URL.', 'pressbooks' );
		return false;
	}

	$store = new GoogleDocs\CredentialsStore();
	if ( ! $store->isConfigured() ) {
		$_SESSION['pb_errors'][] = __( 'Google Docs import is not configured. Ask a network admin to set it up.', 'pressbooks' );
		return false;
	}

	$oauth = new GoogleDocs\OAuthClient( $store );
	$user_id = get_current_user_id();

	if ( ! $store->isUserConnected( $user_id ) ) {
		$_SESSION['pb_errors'][] = __( 'Please connect your Google account first.', 'pressbooks' );
		return false;
	}

	try {
		$client = $oauth->getAuthedClient( $user_id );
	} catch ( GoogleDocs\ReauthorizationRequiredException $e ) {
		$_SESSION['pb_errors'][] = __( 'Your Google connection expired. Please reconnect.', 'pressbooks' );
		return false;
	}

	$fetcher = new GoogleDocs\DocsFetcher( $client );

	try {
		if ( ! $fetcher->isGoogleDoc( $doc_id ) ) {
			$_SESSION['pb_errors'][] = __( 'That URL is not a Google Doc.', 'pressbooks' );
			return false;
		}

		$imports_dir = wp_upload_dir()['basedir'] . '/imports';
		if ( ! is_dir( $imports_dir ) ) {
			wp_mkdir_p( $imports_dir );
		}

		$cached_path = $fetcher->fetchAndCache( $doc_id, $imports_dir );
	} catch ( \Google\Service\Exception $e ) {
		$code = $e->getCode();
		if ( $code === 404 || $code === 403 ) {
			$_SESSION['pb_errors'][] = __( "This Google Doc couldn't be opened. Make sure you have access to it.", 'pressbooks' );
		} elseif ( $code === 429 ) {
			$_SESSION['pb_errors'][] = __( 'Google is rate-limiting us. Try again in a few minutes.', 'pressbooks' );
		} else {
			$_SESSION['pb_errors'][] = __( 'Error fetching the Google Doc.', 'pressbooks' ) . ' ' . $e->getMessage();
		}
		return false;
	}

	$upload = [
		'file' => $cached_path,
		'url'  => $url,
		'type' => 'application/json',
	];

	$importer = new GoogleDocs\GoogleDocs();
	return $importer->setCurrentImportOption( $upload );
}
```

- [ ] **Step 4: Add the Google Docs case to the `doImportGenerator` switch**

In `inc/modules/import/class-import.php`, in the `doImportGenerator()` method, add a case before the `default`:

```php
case GoogleDocs\GoogleDocs::TYPE_OF:
	$importer = new GoogleDocs\GoogleDocs();
	// Set up the fetcher for image downloads during import
	$store = new GoogleDocs\CredentialsStore();
	$oauth = new GoogleDocs\OAuthClient( $store );
	try {
		$client = $oauth->getAuthedClient( get_current_user_id() );
		$importer->setFetcher( new GoogleDocs\DocsFetcher( $client ) );
	} catch ( GoogleDocs\ReauthorizationRequiredException $e ) {
		// Images will be skipped; text still imports
	}
	break;
```

- [ ] **Step 5: Add use statement**

At the top of `inc/modules/import/class-import.php`, the namespace imports already exist for the subdirectories. The Google Docs classes will be auto-resolved via `GoogleDocs\GoogleDocs::TYPE_OF` since they share the `Pressbooks\Modules\Import` namespace prefix. No additional `use` statement needed — the relative namespace `GoogleDocs\GoogleDocs` resolves correctly.

- [ ] **Step 6: Verify the import form shows Google Docs option**

Manually navigate to any book → Tools → Import. The dropdown should show "Google Docs" as an option.

When "Google Docs" is selected and `import_type=url` radio is chosen, the user pastes a Google Doc URL in the Source URL field. On submit, `setGoogleDocsImportOptions()` is called.

- [ ] **Step 7: Commit**

```bash
git add inc/modules/import/class-import.php pressbooks.php
git commit -m "feat(google-docs-import): wire Google Docs importer into import flow with OAuth actions"
```

---

## Task 9: Add Google account connection UI to import screen

**Files:**
- Modify: `templates/admin/import.php`

- [ ] **Step 1: Add connection status message and connect/disconnect button**

In `templates/admin/import.php`, add a block after the import form's `</table>` tag (around line 211, before `submit_button`) that conditionally shows the Google Docs connection status. This block is shown/hidden via Alpine.js based on the selected `type_of`:

```php
<?php
$gdocs_store = new \Pressbooks\Modules\Import\GoogleDocs\CredentialsStore();
$gdocs_is_configured = $gdocs_store->isConfigured();
$gdocs_is_connected = $gdocs_is_configured && $gdocs_store->isUserConnected( get_current_user_id() );
?>
<div x-data="{ typeOf: document.getElementById('type_of')?.value || '' }"
     x-init="document.getElementById('type_of')?.addEventListener('change', (e) => typeOf = e.target.value)"
     x-show="typeOf === 'google-docs'"
     x-cloak
     style="margin: 1em 0;">

	<?php if ( ! $gdocs_is_configured ) : ?>
		<div class="notice notice-warning inline">
			<p><?php _e( 'Google Docs import is not configured. Ask a network admin to set it up under Network Admin → Settings → Google Docs Import.', 'pressbooks' ); ?></p>
		</div>
	<?php elseif ( ! $gdocs_is_connected ) : ?>
		<div class="notice notice-info inline">
			<p><?php _e( 'Connect your Google account to import from Google Docs.', 'pressbooks' ); ?></p>
		</div>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'pb_gdocs_authorize' ); ?>
			<input type="hidden" name="action" value="pb_gdocs_authorize" />
			<?php submit_button( __( 'Connect Google Account', 'pressbooks' ), 'secondary', 'submit', false ); ?>
		</form>
	<?php else : ?>
		<div class="notice notice-success inline">
			<p>
				<?php _e( 'Google account connected.', 'pressbooks' ); ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
					<?php wp_nonce_field( 'pb_gdocs_disconnect' ); ?>
					<input type="hidden" name="action" value="pb_gdocs_disconnect" />
					<button type="submit" class="button-link"><?php _e( 'Disconnect', 'pressbooks' ); ?></button>
				</form>
			</p>
		</div>
	<?php endif; ?>
</div>
```

- [ ] **Step 2: Show success/error notices from OAuth redirect**

At the top of `templates/admin/import.php` (after the `<h1>` tag), add:

```php
<?php if ( isset( $_GET['pb_gdocs'] ) ) : ?>
	<?php if ( $_GET['pb_gdocs'] === 'connected' ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php _e( 'Google account connected successfully. You can now import from Google Docs.', 'pressbooks' ); ?></p>
		</div>
	<?php elseif ( $_GET['pb_gdocs'] === 'disconnected' ) : ?>
		<div class="notice notice-info is-dismissible">
			<p><?php _e( 'Google account disconnected.', 'pressbooks' ); ?></p>
		</div>
	<?php endif; ?>
<?php endif; ?>
```

- [ ] **Step 3: Commit**

```bash
git add templates/admin/import.php
git commit -m "feat(google-docs-import): add Google account connect/disconnect UI to import screen"
```

---

## Task 10: End-to-end manual test and cleanup

**Files:**
- Create: `docs/pb-lab-google-docs-import/02-manual-test-plan.md`

- [ ] **Step 1: Run all tests**

Run: `vendor/bin/phpunit --group import`
Expected: All import-related tests pass, including the new Google Docs tests.

- [ ] **Step 2: Run full test suite**

Run: `vendor/bin/phpunit`
Expected: No regressions.

- [ ] **Step 3: Write manual test plan**

Create `docs/pb-lab-google-docs-import/02-manual-test-plan.md`:

```markdown
# Google Docs Import — Manual Test Plan

## Prerequisites
1. A Google Cloud project with OAuth 2.0 client configured:
   - Authorized redirect URI matching your network's callback URL
   - OAuth consent screen with `documents.readonly` and `drive.readonly` scopes
2. A Pressbooks network with Google Docs Import configured (Network Admin → Settings → Google Docs Import)
3. A test Google Doc with:
   - Multiple H1 headings (to test chapter split)
   - H2–H6 headings
   - Bold, italic, underline text
   - Links
   - Bulleted and numbered lists (including nested)
   - At least one inline image with alt text set
   - A simple table (2+ rows, 2+ columns)

## Test Steps

### 1. Network admin settings
- [ ] Navigate to Network Admin → Settings → Google Docs Import
- [ ] Verify Client ID / Client Secret fields are shown
- [ ] Verify the Redirect URI is displayed
- [ ] Enter valid credentials and save
- [ ] Verify credentials persist after page reload

### 2. User connection flow
- [ ] Navigate to any book → Tools → Import
- [ ] Select "Google Docs" from the Import Type dropdown
- [ ] Verify "Connect Google Account" button appears
- [ ] Click "Connect Google Account"
- [ ] Verify redirect to Google consent screen
- [ ] Grant access
- [ ] Verify redirect back to import screen with success notice
- [ ] Verify "Google account connected" message appears

### 3. Import flow
- [ ] With Google Docs selected, choose "Import from URL" radio
- [ ] Paste a valid Google Doc URL
- [ ] Click "Begin Import"
- [ ] Verify chapter selection screen appears with correct chapter titles (split on H1)
- [ ] Select all chapters
- [ ] Click Import
- [ ] Verify chapters are created as draft posts
- [ ] Verify headings H2–H6 are preserved
- [ ] Verify bold/italic/underline are preserved
- [ ] Verify links are preserved
- [ ] Verify bulleted and numbered lists are correct
- [ ] Verify images are present with correct alt text
- [ ] Verify tables render correctly

### 4. Error handling
- [ ] Try importing a Google Sheets URL → expect "That URL is not a Google Doc" error
- [ ] Try importing a URL you don't have access to → expect "couldn't be opened" error
- [ ] Try importing with invalid/gibberish URL → expect "valid Google Docs URL" error

### 5. Disconnect
- [ ] Click "Disconnect" on the import screen
- [ ] Verify disconnect notice
- [ ] Verify "Connect Google Account" button reappears

## Known Limitations
- See spec: `docs/superpowers/specs/2026-04-16-google-docs-import-design.md` section 9
```

- [ ] **Step 4: Commit**

```bash
git add docs/pb-lab-google-docs-import/02-manual-test-plan.md
git commit -m "docs(google-docs-import): add manual test plan for lab demo"
```

- [ ] **Step 5: Final review**

Review all new files for:
- Consistent namespace usage
- No leftover debug code
- No hardcoded credentials or test values
- Proper nonce usage in all form handlers
