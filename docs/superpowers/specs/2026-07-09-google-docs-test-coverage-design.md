# Google Docs Importer — Test Coverage Expansion Design

**Date:** 2026-07-09  
**Branch:** feat/pb-lab-04-2026  
**Scope:** Increase test coverage across all four undertested areas of the Google Docs importer.

---

## Problem

The Google Docs importer has solid coverage for main paths but leaves four areas with significant gaps:

1. **`DocsMapper` edge cases** — pure-PHP parser has happy-path coverage but misses several rendering paths in `applyTextStyle()`, `finalize()`, `renderInlineObject()`, `renderElements()`, `collectTableImageMeta()`, and `styleToTag()`.
2. **`DocsFetcher`** — zero tests; wraps Google API calls with no error handling tested.
3. **`OAuthClient` direct mode** — broker-mode tests are comprehensive but direct-mode paths (`disconnect()`, Google callback, expired token refresh) are untested.
4. **`SettingsPage`** — only the encryption-key notice is tested; the OAuth callback handler and credential save flow have no coverage.

---

## Approach: Two Independent Tracks

### Track 1 — Fixture-Driven (DocsMapper)

Add test methods to the existing `tests/test-modules-import-google-docs-mapper.php`. No new test infrastructure needed — `DocsMapper` is pure PHP with no WP dependencies. Most tests use inline data arrays; no new fixture files required.

### Track 2 — Stub-Driven (DocsFetcher + OAuthClient + SettingsPage)

Two new test files + additions to an existing file. Uses:
- **Guzzle `MockHandler`** for DocsFetcher (Google PHP client uses Guzzle internally, not WP HTTP)
- **PHPUnit mock objects** for `\Google\Client` in OAuthClient direct-mode tests, via `getMockBuilder()->onlyMethods(['buildClient'])`
- **`wp_redirect` filter interception** + exception catching for SettingsPage callback tests

---

## Track 1: DocsMapper Edge Cases (10 tests)

All added to `tests/test-modules-import-google-docs-mapper.php`.

### 1. `test_bold_and_italic_combined`
- **Path:** `applyTextStyle()` — bold + italic flags both true on same text run
- **Assert:** Output contains `<strong><em>text</em></strong>`

### 2. `test_link_suppresses_underline`
- **Path:** `applyTextStyle()` L381 — `underline: true` with `link` present
- **Assert:** Output contains `<a href="...">` but no `<u>` wrapper (link takes precedence)

### 3. `test_empty_paragraph_produces_no_output`
- **Path:** `renderParagraph()` L193 — `trim($text) === ''`
- **Assert:** Body contains no `<p>` tag for the empty paragraph

### 4. `test_list_type_switch_at_same_level`
- **Path:** `finalize()` L628–635 — two list items at nesting=0 with different list IDs, first `ul`, second `ol`
- **Assert:** Body contains `</ul>` followed by `<ol>`, items in correct lists

### 5. `test_three_level_nested_list`
- **Path:** `finalize()` L640–653 — items at nesting levels 0, 1, 2
- **Assert:** Three levels of `<ul>` nesting, correct placeholder `<li>` wrappers for intermediate levels, `<ul>` and `</ul>` counts balance

### 6. `test_inline_drawing_produces_warning`
- **Path:** `renderInlineObject()` L404–408 — `embeddedDrawingProperties` present on inline (not positioned) object
- **Assert:** Body contains no `<img>`, `getWarnings()` contains the drawing-skipped message

### 7. `test_equation_element_produces_warning`
- **Path:** `renderElements()` L349 — `equation` key on an element
- **Assert:** Body contains no equation markup, `getWarnings()` contains `'Equation element skipped (unsupported).'`

### 8. `test_images_in_table_cells_collected`
- **Path:** `collectTableImageMeta()` L139 — inline image inside a table cell
- **Assert:** `chapters[0]['images']` contains the image entry with correct `object_id` and `content_uri`

### 9. `test_subtitle_style_renders_as_h2`
- **Path:** `styleToTag()` L329 — `SUBTITLE` namedStyleType
- **Assert:** Body contains `<h2>` for the subtitle paragraph

### 10. `test_multiple_footnotes_in_same_paragraph`
- **Path:** `renderFootnoteReference()` — two footnote references in one paragraph's elements array
- **Assert:** Both `[footnote]` shortcodes appear inline within the same `<p>` tag

---

## Track 2: Stub-Driven Tests

### DocsFetcher (new file `tests/test-modules-import-google-docs-fetcher.php`)

**Stub strategy:** Construct a `\Google\Client` with `GuzzleHttp\Handler\MockHandler` via `$client->setHttpClient(new \GuzzleHttp\Client(['handler' => $stack]))`. Pass this client to `new DocsFetcher($client)`.

For `downloadImage`: PHPUnit-mock `\Google\Client`, stub `authorize()` to return a mock Guzzle client.

#### 1. `test_fetch_document_returns_php_array`
- Guzzle mock returns a minimal valid Google Docs API JSON response (title + one paragraph)
- **Assert:** Return value is a PHP array with `title` and `body` keys

#### 2. `test_get_file_metadata_returns_name_and_mime_type`
- Guzzle mock returns Drive API file response with `name` and `mimeType`
- **Assert:** Returns `['title' => 'My Doc', 'mimeType' => 'application/vnd.google-apps.document']`

#### 3. `test_download_image_returns_body_on_200`
- PHPUnit mock: `$client->authorize()` → mock Guzzle client; `$guzzle->get()` → `Response(200, [], 'image bytes')`
- **Assert:** Returns string `'image bytes'`

#### 4. `test_download_image_returns_false_on_non_200`
- Mock Guzzle returns `Response(404)`
- **Assert:** Returns `false`

#### 5. `test_download_image_returns_false_on_exception`
- Mock Guzzle `get()` throws `\Exception`
- **Assert:** Returns `false` (exception swallowed)

#### 6. `test_fetch_and_cache_writes_json_file_and_returns_path`
- Guzzle mock returns valid Docs API JSON; temp dir is `sys_get_temp_dir()`
- **Assert:** Returned path exists, file contents are valid JSON, filename matches pattern `gdoc-{id}-{hash}.json`
- **Cleanup:** `unlink()` the file in `tearDown`

---

### OAuthClient Direct Mode (8 additions to `tests/test-modules-import-google-docs-oauth.php`)

**Stub strategy:** Use `getMockBuilder(OAuthClient::class)->setConstructorArgs([...])->onlyMethods(['buildClient'])->getMock()` to inject a PHPUnit-mocked `\Google\Client` without network calls. Use `DirectEncryptedStorage` with a real encryption key for storage assertions.

#### 7. `test_disconnect_is_noop_when_no_token`
- No token in storage
- **Assert:** `disconnect()` returns without throwing

#### 8. `test_disconnect_deletes_token_in_direct_mode`
- Token in storage; mock `\Google\Client::revokeToken()` succeeds (no-op)
- **Assert:** Token no longer in storage after `disconnect()`

#### 9. `test_disconnect_throws_when_google_revoke_fails`
- Mock `revokeToken()` throws `\Exception('Network error')`
- **Assert:** `disconnect()` throws `\RuntimeException` with message containing the original error

#### 10. `test_handle_callback_direct_mode_stores_token_and_returns_url`
- State transient pre-set; mock `fetchAccessTokenWithAuthCode()` returns valid token array
- **Assert:** Token saved to storage; return value equals the stored return URL

#### 11. `test_handle_callback_direct_mode_throws_on_token_error`
- Mock `fetchAccessTokenWithAuthCode()` returns `['error' => 'invalid_grant', 'error_description' => 'Token expired']`
- **Assert:** Throws `\RuntimeException` containing `'Token exchange failed'`

#### 12. `test_get_authed_client_refreshes_expired_direct_token`
- Storage holds expired token (past `expires_at`) with `refresh_token` present
- Mock `isAccessTokenExpired()` → `true`; mock `fetchAccessTokenWithRefreshToken()` → new valid token
- **Assert:** New token saved to storage; returns a `\Google\Client` instance

#### 13. `test_get_authed_client_throws_when_no_refresh_token`
- Storage holds expired token with no `refresh_token`
- Mock `isAccessTokenExpired()` → `true`
- **Assert:** Throws `ReauthorizationRequiredException`; token deleted from storage

#### 14. `test_get_authed_client_throws_when_direct_refresh_returns_error`
- Storage holds expired token with `refresh_token`; mock `fetchAccessTokenWithRefreshToken()` → `['error' => 'invalid_grant']`
- **Assert:** Throws `ReauthorizationRequiredException`; token deleted from storage

---

### SettingsPage (new file `tests/test-modules-import-google-docs-settings-page.php`)

**Stub strategy:** All tests mock `OAuthClient` and `CredentialsStore` via PHPUnit mocks. For methods calling `wp_redirect()` + `exit`, add a `wp_redirect` filter in `setUp` that captures the redirect URL in a property and throws a `\Exception` (caught in each test) to prevent `exit` from running.

```php
// In setUp:
add_filter('wp_redirect', function($url) {
    $this->lastRedirect = $url;
    throw new \Exception('wp_redirect:' . $url);
}, 10, 1);
```

#### 15. `test_handle_callback_redirects_with_denied_when_google_error_and_valid_state`
- `$_GET = ['error' => 'access_denied', 'state' => 'abc']`; state transient set to `https://example.com/import`
- **Assert:** Redirect URL contains `pb_gdocs=denied` and the return URL base

#### 16. `test_handle_callback_redirects_to_admin_when_error_and_no_state`
- `$_GET = ['error' => 'access_denied']`; no state transient
- **Assert:** Redirect URL contains `pb_import` (admin import page fallback)

#### 17. `test_handle_callback_processes_broker_token_and_redirects_connected`
- `$_GET = ['token' => 'a.b.c', 'state' => 'xyz']`; mock `OAuthClient::handleCallback()` returns `https://example.com/import`
- **Assert:** Redirect URL contains `pb_gdocs=connected`

#### 18. `test_handle_callback_processes_code_and_redirects_connected`
- `$_GET = ['code' => 'auth_code', 'state' => 'xyz']`; mock `OAuthClient::handleCallback()` returns return URL
- **Assert:** Redirect URL contains `pb_gdocs=connected`

#### 19. `test_render_page_saves_credentials_on_valid_post`
- `$_POST = ['client_id' => 'my-id', 'client_secret' => 'my-secret', '_wpnonce' => wp_create_nonce('pb_save_google_docs_settings')]`
- Test setup must grant the current user `manage_network_options` (super-admin) so `check_admin_referer()` passes
- Mock `CredentialsStore::saveClientCredentials()` and assert it is called with correct args
- **Assert:** Mock expectation satisfied; no exception thrown

---

## File Summary

| File | Change | Tests added |
|---|---|---|
| `tests/test-modules-import-google-docs-mapper.php` | Add methods | 10 |
| `tests/test-modules-import-google-docs-fetcher.php` | New file | 6 |
| `tests/test-modules-import-google-docs-oauth.php` | Add methods | 8 |
| `tests/test-modules-import-google-docs-settings-page.php` | New file | 5 |
| **Total** | | **29** |

---

## Risk & Mitigations

| Risk | Mitigation |
|---|---|
| Guzzle `MockHandler` API differs between Guzzle 6 and 7 | Check `composer.json` for `guzzlehttp/guzzle` version before writing test helpers |
| `exit` in `handleOAuthCallback` terminates test runner | `wp_redirect` filter throws exception before `exit` is reached |
| `\Google\Client::isAccessTokenExpired()` is final/unmockable | If final, use a token payload with past `expires_at` and let the real method evaluate it |
| `fetchDocument`/`getFileMetadata` Guzzle response format must match Google API response schema | Use minimal fixture that satisfies `toSimpleObject()` deserialization |

---

## Out of Scope

- `renderPage()` HTML output assertions (brittle, low value)
- `SettingsPage::renderBrokerPage()` output (trivially simple HTML, no logic)
- `OAuthClient::fromEnvironment()` (factory method, tests via downstream usage)
- `DocsFetcher` error handling refactor (none exists; tests document current behavior)
