# Google Docs Import — Design

**Status:** Draft for PB Lab slice
**Date:** 2026-04-16
**Related:** `docs/pb-lab-google-docs-import/00-brief.md`, `docs/pb-lab-google-docs-import/01-kickoff-decisions.md`

## 1. Goal

Let a Pressbooks user import content from a Google Doc into a book with reasonable structure and formatting preserved, via the existing Import screen. This is the smallest viable slice that demonstrates real user value and technical feasibility for PB Lab.

## 2. Decisions (from kickoff)

| # | Decision | Choice |
|---|---|---|
| 1 | Authentication | OAuth 2.0 user flow |
| 2 | Token storage | Per-user (network-wide) in `user_meta` |
| 3 | Google client credentials | Network admin settings page |
| 4 | Source selection | Paste Google Doc URL |
| 5 | Entry point | New option on the existing Import screen |
| 6 | Conversion strategy | Docs API structured JSON → custom HTML mapper |
| 7 | Chapter split | Split on `HEADING_1` |
| 8 | MVP content | Text + structure + inline images + simple tables |
| 9 | Google API client | `google/apiclient` (official SDK) |
| 10 | Testing | Unit tests on mapper using captured JSON fixtures |

Non-goals for this slice: comments, suggestions, tracked changes, drawings, equations, footnotes, merged-cell tables, multi-network OAuth hub, re-import / sync, production hardening of secret storage.

## 3. Architecture

```
inc/modules/import/google-docs/
├── class-google-docs.php       # GoogleDocs extends Import (TYPE_OF = 'google-docs')
├── class-oauth-client.php      # OAuth flow: authorize, callback, refresh, revoke
├── class-credentials-store.php # Network option (client_id/secret) + per-user token
├── class-docs-fetcher.php      # docs.documents.get + drive.files.get/export wrappers
├── class-docs-mapper.php       # Google Docs JSON → sanitized HTML (+ inline image refs)
└── class-settings-page.php     # Network admin screen for client_id/secret
```

Namespace: `Pressbooks\Modules\Import\GoogleDocs\*` (matches existing `Ooxml`, `Odf`, etc.).

### 3.1 Data flow

```
[Import screen] ── user picks "Google Docs" + pastes URL
     │
     ├─ not connected? → OAuthClient::authorize() → Google consent → callback
     │                  → store refresh token in user_meta → redirect back
     │
     └─ connected? → DocsFetcher::fetch(docId)
                      ├─ docs.documents.get   (structured JSON)
                      ├─ drive.files.get       (title, mimeType check)
                      └─ cache JSON at uploads/imports/gdoc-{id}-{hash}.json
                          │
                          ▼
                    GoogleDocs::setCurrentImportOption(['file' => <json path>, ...])
                          │  (existing Import contract; "file" is the cached JSON)
                          ▼
                    DocsMapper::toChapters(json)
                          │  → [ ['id'=>'slug','title'=>'...','html'=>'...','images'=>[...]] ]
                          │  (split on HEADING_1)
                          ▼
                    Existing Pressbooks chapter-selection UI
                          │
                          ▼
                    GoogleDocs::import() → for each selected chapter:
                       - download inline images via DocsFetcher::downloadImage()
                       - sideload to WP media library
                       - rewrite <img src> in HTML
                       - wp_insert_post()
```

### 3.2 Dependencies

- Composer: `google/apiclient: ^2.15`.
- No new JS dependencies (plain form POST + server-side redirects).

### 3.3 Storage

| Location | Key | Contents |
|---|---|---|
| `wp_sitemeta` (network) | `pressbooks_google_docs_oauth` | `['client_id' => ..., 'client_secret' => ...]` (plaintext MVP; follow-up: encrypt) |
| `wp_usermeta` | `pressbooks_google_docs_token` | `['refresh_token', 'access_token', 'expires_at', 'scopes', 'connected_at']` |
| Book `uploads/imports/` | `gdoc-{docId}-{hash}.json` | Cached Docs API response during the import session |

### 3.4 Redirect URI

`https://<network-host>/wp-admin/network/settings.php?page=pb_network_google_docs&pb_oauth_callback=1`

One URI per network. Each network configures its own Google Cloud OAuth client.

## 4. Docs JSON → HTML mapper

**Input:** `docs.documents.get` response — a `Document` with `body.content[]` of `StructuralElement`s (paragraph, table, sectionBreak, tableOfContents).

**Output:** ordered array of chapter records:

```php
[
  [
    'id'     => 'chapter-slug',
    'title'  => 'Chapter Title',
    'html'   => '<h2>…</h2><p>…</p>…',
    'images' => [ ['object_id' => 'kix.xyz', 'content_uri' => 'https://…', 'alt' => '…', 'title' => '…'] ],
  ],
  // …
]
```

### 4.1 Mapping rules

| Google Docs | HTML out |
|---|---|
| `HEADING_1` | Chapter boundary. Text becomes `post_title`; not emitted inline. |
| `HEADING_2`–`HEADING_6` | `<h2>`–`<h6>` |
| `TITLE` | Document title → used as import name + fallback intro chapter title; not emitted inline. |
| `SUBTITLE` | `<h2>` |
| `NORMAL_TEXT` paragraph | `<p>…</p>` |
| `textRun.textStyle.bold` | `<strong>` |
| `textRun.textStyle.italic` | `<em>` |
| `textRun.textStyle.underline` | `<u>` |
| `textRun.textStyle.link.url` | `<a href="…">` |
| Paragraph with `bullet`, glyph = bullet | `<ul><li>` |
| Paragraph with `bullet`, glyph = DECIMAL/ROMAN/ALPHA | `<ol><li>` |
| `inlineObjectElement` | `<img src="#gdoc-image-{objectId}" alt="{description}" title="{title}" width="{w}" height="{h}">` |
| `table` | `<table><tr><td>…</td></tr></table>` (no thead; merged cells → emit first cell, drop spanned, increment warning counter) |
| Anything unrecognized (drawings, equations, horizontal rules beyond `<hr>`, comments, suggestions) | Dropped, counted in per-chapter warnings |

### 4.2 Tricky cases

- **List grouping:** Docs flattens lists — every bullet is its own paragraph with a `bullet.listId`. The mapper buffers consecutive list paragraphs sharing a `listId` and emits one wrapping `<ul>`/`<ol>`. Nesting uses `bullet.nestingLevel`.
- **Chapter split:** Single forward pass. Hitting `HEADING_1` closes the current buffer and starts a new chapter. Content before the first `HEADING_1` becomes an intro chapter titled from the document `TITLE` (or "Introduction" if no title).
- **Image fetch deferral:** Mapper collects `inlineObject` references but does NOT fetch bytes. Actual downloads happen in `import()` for selected chapters only.
- **Sanitization:** Final HTML per chapter runs through the existing `HtmLawed` wrapper before hand-off, matching other importers.

### 4.3 Image metadata

| Docs field | Destination |
|---|---|
| `inlineObjectProperties.embeddedObject.description` | `<img alt>` + `_wp_attachment_image_alt` post meta |
| `inlineObjectProperties.embeddedObject.title` | `<img title>` + attachment `post_title` |
| `imageProperties.contentUri` | Short-lived URL to fetch image bytes in `import()` |
| `imageProperties.sourceUri` | Skipped for MVP |
| `size.width/height` (points) | `<img width height>` attrs |
| `embeddedObjectBorder`, margins | Skipped for MVP |

Alt fallback chain: `description` → `title` → empty string.

The mapper records `alt`, `title`, `content_uri`, and `object_id` for each image in the chapter's `images` array so `import()` can write them to both the `<img>` tag and the WP attachment post without re-parsing the JSON.

## 5. OAuth flow

### 5.1 Network settings page

Network Admin → Settings → "Google Docs Import". Fields:

- Client ID (text)
- Client Secret (password input)

Displays the Redirect URI to paste into Google Cloud Console, plus the required scopes:

- `https://www.googleapis.com/auth/documents.readonly`
- `https://www.googleapis.com/auth/drive.readonly` (needed for image `contentUri` download + mimeType check)

### 5.2 Per-user connect

1. Import screen: user picks "Google Docs". If `get_user_meta($user_id, 'pressbooks_google_docs_token', true)` is empty → show **Connect Google account** button.
2. Button POSTs to `admin-post.php?action=pb_gdocs_authorize` (nonce-protected). Handler:
   - Generates `state = wp_create_nonce('pb_gdocs_' . $user_id) . ':' . hash('sha256', $return_url)`, stores `return_url` in a 10-min transient keyed by state.
   - Builds Google auth URL with `access_type=offline`, `prompt=consent`, both scopes.
   - `wp_redirect()` to Google.
3. Google redirects back to the network callback with `?code=…&state=…`.
4. `OAuthClient::handle_callback()` verifies `state`, retrieves return URL from transient, exchanges code via `google/apiclient`, writes token to user meta, redirects to origin book's import screen with `?pb_gdocs=connected`.

### 5.3 Token refresh

Single method `OAuthClient::get_authed_client($user_id): Google\Client`:

- Loads token from user meta.
- If expired (with 60s skew), refreshes via `refresh_token`, writes new `access_token` + `expires_at` back.
- On refresh failure (`invalid_grant`, revoked): deletes token, throws `ReauthorizationRequiredException`.

Every fetcher call goes through this method.

### 5.4 Disconnect

User profile (`show_user_profile` hook) adds a "Disconnect Google Docs" link → POSTs to `admin-post.php?action=pb_gdocs_disconnect` → calls Google `revokeToken` + deletes user meta.

### 5.5 Security

- Network option read via `get_site_option`; only rendered (secret masked) to super admins.
- User meta access gated on `current_user_can('edit_posts')` in the current book.
- All handlers: nonce + `check_admin_referer`.
- Client secret stored plaintext in MVP — **follow-up: encrypt at rest using an `AUTH_KEY`-derived key.**
- OAuth `state` uses a single-use WP nonce + 10-min transient.

### 5.6 Error states

| Condition | UX |
|---|---|
| No client_id configured | Admin notice: "Google Docs import is not configured. Ask a network admin to set it up." |
| User not connected | Connect button. |
| Token refresh fails | Notice: "Your Google connection expired. Reconnect." + button. |
| Doc not found / no permission | "This Google Doc couldn't be opened. Make sure you have access to it." |
| Wrong mime type (Sheets, Slides, etc.) | "That URL is not a Google Doc." |
| API quota exceeded | "Google is rate-limiting us. Try again in a few minutes." |

## 6. Import pipeline

`GoogleDocs::import($current_import)` mirrors the DOCX importer shape:

1. Load cached JSON from `$current_import['file']`.
2. Run `DocsMapper::toChapters()`.
3. For each chapter the user checked:
   1. Collect `inlineObject` references via placeholder `#gdoc-image-{objectId}`.
   2. For each image: `DocsFetcher::downloadImage($contentUri)` → tmp file → `media_handle_sideload()` with post parent = the chapter being created. Write `alt` to `_wp_attachment_image_alt`; set attachment `post_title` = image `title`.
   3. Rewrite `src="#gdoc-image-{objectId}"` → WP attachment URL in chapter HTML.
   4. Run HTML through the shared Pressbooks/`HtmLawed` sanitizer.
   5. `wp_insert_post()` with:
      - `post_type` = user's choice from the selection UI (`chapter` / `front-matter` / `back-matter` / `part`)
      - `post_status` = `draft`
      - `post_title` = mapped chapter title
      - `post_content` = sanitized HTML
      - `post_author` = current user
   6. Accumulate per-chapter warnings.
4. Delete cached JSON. Return `['imported' => N, 'warnings' => [...]]` for the success notice.

### 6.1 In-pipeline error handling

| Failure | Behavior |
|---|---|
| Token refresh fails mid-import | Abort loop; keep already-created posts; notice: "Connection to Google expired. N chapters were imported; reconnect and try again for the rest." |
| Image download fails | Keep text, drop `<img>`, increment warning ("N images couldn't be downloaded"). |
| `media_handle_sideload` fails | Same as above. |
| Docs/Drive 429 or 5xx on retry | Retry once with exponential backoff, then error per above. |
| `wp_insert_post` fails for a chapter | Log, skip, include in warnings, continue with rest. |

## 7. Testing

### 7.1 Unit tests

Location: `tests/phpunit/modules/import/google-docs/`.

- **`DocsMapperTest`** — one test per fixture under `tests/fixtures/google-docs/`:
  - `headings-only.json` → expected HTML + chapter splits.
  - `mixed-lists.json` → `<ul>` and `<ol>` grouping; consecutive items collapsed.
  - `nested-lists.json` → nesting via `bullet.nestingLevel`.
  - `simple-table.json` → `<table>` with rows/cells, no thead.
  - `table-with-merged-cells.json` → merged cells dropped, warning counted.
  - `with-images.json` → `<img>` placeholders + alt/title/width/height preserved; images list correct.
  - `multi-chapter.json` → multiple H1s → multiple chapter entries.
  - `no-h1.json` → single intro chapter titled from document title.
  - `unsupported-content.json` → drawings + equations dropped, warning counters set.
- **`OAuthClientTest`** — token persistence; refresh on expiry; exception when revoked (Google client mocked).
- **`CredentialsStoreTest`** — network-option + user-meta round-trip.

No live Google API calls in tests.

### 7.2 Manual test plan

Documented at `docs/pb-lab-google-docs-import/03-manual-test-plan.md`, including the demo doc URL(s).

### 7.3 Fixture capture utility

`bin/capture-gdoc-fixture.php` — lab-only CLI that, given a doc URL + a locally-stored token, dumps the raw Docs API JSON to `tests/fixtures/google-docs/`. Not loaded at runtime. Documented but minimal polish.

## 8. Files delivered

### New

- `inc/modules/import/google-docs/class-google-docs.php`
- `inc/modules/import/google-docs/class-oauth-client.php`
- `inc/modules/import/google-docs/class-credentials-store.php`
- `inc/modules/import/google-docs/class-docs-fetcher.php`
- `inc/modules/import/google-docs/class-docs-mapper.php`
- `inc/modules/import/google-docs/class-settings-page.php`
- `tests/phpunit/modules/import/google-docs/DocsMapperTest.php`
- `tests/phpunit/modules/import/google-docs/OAuthClientTest.php`
- `tests/phpunit/modules/import/google-docs/CredentialsStoreTest.php`
- `tests/fixtures/google-docs/*.json` + `*.expected.html`
- `bin/capture-gdoc-fixture.php`
- `docs/pb-lab-google-docs-import/03-manual-test-plan.md`

### Modified

- `inc/modules/import/class-import.php` — register `GoogleDocs\GoogleDocs::TYPE_OF` in the two switch statements.
- Import screen template — add 4th radio option ("Google Docs").
- `composer.json` — add `google/apiclient: ^2.15`.
- `pressbooks.php` bootstrap — register network settings page + `admin-post.php` action handlers (`pb_gdocs_authorize`, `pb_gdocs_disconnect`, OAuth callback dispatcher).

## 9. Known limitations (ship-and-document)

- Client secret stored plaintext. *Follow-up:* encrypt at rest.
- Single network = single Google Cloud client (no multi-network OAuth hub).
- No comments, suggestions, drawings, equations, or footnotes.
- Tables: no merged cells, no thead.
- Lists: flat + nested bullets/numbers only; mixed-type siblings at the same level not supported.
- No re-import / sync — each import is one-shot.
- Large docs (>10MB JSON) not specifically optimized; may hit PHP memory limits.
- `google/apiclient` adds ~30MB to vendor/. Follow-up: trim via `Google_Task_Composer::cleanup`.

## 10. Definition of done for the lab slice

- A super admin can configure client ID/secret on a single network.
- A book user can connect their Google account from the Import screen.
- Pasting a valid Google Doc URL produces a chapter-selection screen.
- Selected chapters are imported as draft posts with preserved headings (H2–H6), paragraphs, bold/italic/underline, links, lists, simple tables, and inline images with alt/title.
- Unsupported content is silently dropped with a surfaced per-import warning summary.
- Unit tests pass against captured JSON fixtures covering all mapping rules above.
- `docs/pb-lab-google-docs-import/03-manual-test-plan.md` documents the demo walkthrough and known limitations.
