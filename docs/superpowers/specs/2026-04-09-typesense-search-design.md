# Typesense Search Integration Design — Pressbooks Borges

**Date**: 2026-04-09
**Status**: Draft
**Plugin**: `pressbooks/pressbooks-borges` (standalone plugin)
**Scaffold**: Based on `pressbooks-plugin-scaffold` (PSR-4, Laravel Pint, Vite, Blade)

## Overview

**Pressbooks Borges** is a standalone Pressbooks plugin that integrates Typesense as a fast, typo-tolerant search engine for Pressbooks multisite networks. Named after Jorge Luis Borges — who imagined a library containing every possible book — the plugin provides contextual search across all books a user has access to, with scope-awareness (network-wide in admin, per-book in webbook). It indexes full text content, book metadata, and contributor information. Designed to be extensible so other plugins (LTI, etc.) can register their own content for indexing.

## Requirements

- Network-wide search across all books the current user has access to
- Per-book scoped search when the user is inside a book's webbook
- Full text content indexing for chapters, front-matter, back-matter, glossary
- Book metadata indexing (title, authors, license, subjects, keywords)
- Contributor/person indexing with cross-referencing to their works
- Global search bar in admin toolbar with instant dropdown results
- Dedicated search results page with faceted filtering
- Person-first result ranking (contributors before books before sections)
- Background job queue for indexing (no latency impact on saves)
- Scoped search-only API keys for frontend access control
- Plugin extensibility via WordPress filter hooks
- Flexible Typesense deployment (Cloud, EC2/ECS, or EKS)
- Scalable for medium networks (100-1,000 books, 10k-100k sections)
- Standalone plugin based on pressbooks-plugin-scaffold (PSR-4 autoloading)

## Plugin Structure

Based on `pressbooks-plugin-scaffold`. The scaffold uses **PSR-4 autoloading** (`PressbooksBorges\` → `src/`), Laravel Pint for linting, Vite for assets, and Blade for templates via the Pressbooks Container.

```
pressbooks-borges/
├── pressbooks-borges.php              # Plugin entry point (activation hook, bootstrap)
├── composer.json                      # PSR-4 autoload, typesense-php dependency
├── package.json                       # Vite build, typesense-instantsearch-adapter
├── vite.config.js                     # Vite config for JS/CSS entry points
├── pint.json                          # Laravel Pint config (PSR-12)
├── phpunit.xml                        # PHPUnit config (WP_TESTS_MULTISITE=1)
├── .editorconfig                      # Tab indent for PHP, 2-space for JSON/YAML/SCSS
├── .nvmrc                             # Node >= 22
│
├── src/                               # PSR-4: PressbooksBorges\
│   ├── Bootstrap.php                  # Singleton bootstrap (registerActions, registerBlade, enqueueScripts)
│   │
│   ├── Search/
│   │   ├── SearchService.php          # Main service (registered in Pressbooks Container)
│   │   ├── TypesenseClient.php        # Typesense PHP client wrapper
│   │   └── KeyGenerator.php           # Scoped search-only key derivation
│   │
│   ├── Indexing/
│   │   ├── IndexJobProcessor.php      # WP-Cron job processor
│   │   ├── IndexerInterface.php       # Contract for indexers
│   │   ├── Indexers/
│   │   │   ├── SectionsIndexer.php    # Chapters, FM, BM, glossary
│   │   │   ├── BooksIndexer.php       # Book-level metadata
│   │   │   └── ContributorsIndexer.php # Contributor taxonomy terms
│   │   └── JobQueue/
│   │       └── SearchIndexJob.php     # Eloquent model for job queue table
│   │
│   ├── Api/
│   │   └── SearchEndpoint.php         # /pressbooks-borges/v1/search REST endpoint
│   │
│   ├── Admin/
│   │   ├── SearchAdmin.php            # Network admin settings page
│   │   ├── SearchBar.php              # Enqueues search bar + Instantsearch assets
│   │   └── SearchHealthCheck.php      # Health check for monitoring
│   │
│   ├── Controllers/
│   │   ├── BaseController.php         # From scaffold (Blade rendering via Container)
│   │   └── SearchResultsController.php # Dedicated search results page controller
│   │
│   ├── Database/
│   │   ├── Migration.php              # From scaffold (auto-discovers migrations)
│   │   └── Migrations/
│   │       └── 000001_create_search_index_jobs_table.php
│   │
│   ├── Interfaces/
│   │   └── MigrationInterface.php     # From scaffold
│   │
│   └── Support/
│       └── Helpers.php                # Utility functions (strip_html, env_or_constant, etc.)
│
├── resources/
│   ├── assets/
│   │   ├── js/
│   │   │   └── pressbooks-borges.js   # Instantsearch.js search bar + results page
│   │   └── styles/
│   │       └── pressbooks-borges.css  # Search UI styles (dropdown, results page)
│   └── views/
│       ├── search-results.blade.php   # Dedicated search results page template
│       └── admin/
│           └── settings.blade.php     # Network admin settings page template
│
├── tests/
│   ├── bootstrap.php                  # Loads pressbooks + test utils
│   ├── TestCase.php                   # Base test case
│   ├── Unit/
│   │   ├── SearchServiceTest.php
│   │   ├── KeyGeneratorTest.php
│   │   ├── SectionsIndexerTest.php
│   │   ├── BooksIndexerTest.php
│   │   └── ContributorsIndexerTest.php
│   └── Feature/
│       ├── SearchEndpointTest.php
│       ├── IndexJobProcessorTest.php
│       └── SearchBarTest.php
│
└── dist/                              # Vite build output (gitignored)
```

### Namespace Mapping (PSR-4)

```
PressbooksBorges\                           → src/
PressbooksBorges\Bootstrap                  → src/Bootstrap.php
PressbooksBorges\Search\SearchService       → src/Search/SearchService.php
PressbooksBorges\Indexing\IndexJobProcessor → src/Indexing/IndexJobProcessor.php
PressbooksBorges\Indexing\Indexers\*        → src/Indexing/Indexers/
PressbooksBorges\Api\SearchEndpoint         → src/Api/SearchEndpoint.php
PressbooksBorges\Admin\*                    → src/Admin/
PressbooksBorges\Database\Migration         → src/Database/Migration.php
```

### Plugin Entry Point (`pressbooks-borges.php`)

```php
<?php
/**
 * Plugin Name: Pressbooks Borges
 * Plugin URI: https://pressbooks.org
 * Requires at least: 6.8
 * Requires Plugins: pressbooks
 * Description: Fast, faceted search for Pressbooks networks powered by Typesense.
 * Version: 0.1.0
 * Author: Pressbooks (Book Oven Inc.)
 * Author URI: https://pressbooks.org
 * Requires PHP: 8.3
 * Text Domain: pressbooks-borges
 * License: GPL v3 or later
 * Network: True
 */

use PressbooksBorges\Bootstrap;
use PressbooksBorges\Database\Migration;

register_activation_hook(__FILE__, [Migration::class, 'migrate']);

add_action('plugins_loaded', [Bootstrap::class, 'run']);
```

### Bootstrap (`src/Bootstrap.php`)

Extends the scaffold pattern. Registers Blade namespace, hooks, services, and assets.

```php
namespace PressbooksBorges;

use Pressbooks\Container;

final class Bootstrap
{
    private static ?Bootstrap $instance = null;

    public static function run(): void
    {
        if (!self::$instance) {
            self::$instance = new self;
            self::$instance->setUp();
        }
    }

    public function setUp(): void
    {
        $this->registerBlade();
        $this->registerServices();
        $this->registerActions();
        $this->registerMenus();
        $this->enqueueScripts();
    }

    private function registerBlade(): void
    {
        Container::get('Blade')->addNamespace(
            'PressbooksBorges',
            dirname(__DIR__) . '/resources/views'
        );
    }

    private function registerServices(): void
    {
        Container::set('Borges\Search', function () {
            return new Search\SearchService(new Search\TypesenseClient());
        }, 'singleton');
    }

    private function registerActions(): void
    {
        Indexing\IndexJobProcessor::register();
        Admin\SearchBar::init();
        Api\SearchEndpoint::register();
    }

    private function registerMenus(): void
    {
        Admin\SearchAdmin::init();
    }

    private function enqueueScripts(): void
    {
        Admin\SearchBar::enqueueAssets();
    }
}
```

### Composer Dependencies

```json
{
    "name": "pressbooks/pressbooks-borges",
    "type": "wordpress-plugin",
    "require": {
        "php": "^8.3",
        "pressbooks/frontend-tools": "^1.0.0",
        "typesense/typesense-php": "^1.0"
    },
    "require-dev": {
        "laravel/pint": "^1.10.6",
        "yoast/phpunit-polyfills": "^1.0.5"
    },
    "autoload": {
        "psr-4": {
            "PressbooksBorges\\": "src/"
        }
    }
}
```

### NPM Dependencies

```json
{
    "name": "pressbooks-borges",
    "devDependencies": {
        "pressbooks-build-tools": "^5.0.0"
    },
    "dependencies": {
        "typesense-instantsearch-adapter": "^2.0",
        "instantsearch.js": "^4.0"
    }
}
```

## Architecture

### Components

```
WordPress (Pressbooks Borges Plugin)
├── Indexing\Indexers (hook into WP save_post, etc., queue jobs)
├── Indexing\JobQueue (pressbooks_borges_index_jobs table + WP-Cron)
├── Search\SearchService (Typesense PHP client wrapper, in Pressbooks Container)
├── Search\KeyGenerator (scoped search-only key derivation)
├── Api\SearchEndpoint (/pressbooks-borges/v1/search)
├── Admin\SearchAdmin (network admin settings + health check)
└── Admin\SearchBar (Instantsearch.js global bar + results page)
        │
        ▼
Typesense Server
├── pb_sections collection
├── pb_books collection
└── pb_contributors collection
```

### Service Registration

Borges registers its `SearchService` in the **Pressbooks Container** (not its own), so the main plugin and other plugins can access it:

```php
Container::get('Borges\Search')->search($query, $filters);
```

## Data Model

### Collection: `pb_sections`

All indexable content sections across all books.

| Field | Type | Facet | Sort | Notes |
|-------|------|-------|------|-------|
| `id` | string | | | `{blog_id}_{post_id}` (composite key) |
| `blog_id` | int64 | yes | yes | For access filtering |
| `post_id` | int64 | | | WordPress post ID |
| `post_type` | string | yes | | `chapter`, `front-matter`, `back-matter`, `glossary` |
| `post_status` | string | yes | | `publish`, `draft`, `private`, `web-only` |
| `title` | string | | | Section title |
| `short_title` | string | | | Optional short title (`pb_short_title` meta) |
| `content` | string | | | Stripped HTML, full text |
| `authors` | string[] | yes | | From `pb_authors` meta + contributor taxonomy |
| `section_license` | string | yes | | From `pb_section_license` meta |
| `language` | string | yes | | Book language (inherited from book metadata) |
| `parent_id` | int64 | | | Part ID (for chapters) |
| `menu_order` | int32 | | | Section position |
| `book_title` | string | | | Denormalized book title |
| `book_url` | string | | | Denormalized book URL |
| `updated_at` | int64 | | yes | Unix timestamp |

Default sort: `updated_at:desc`

### Collection: `pb_books`

Book-level metadata for network discovery.

| Field | Type | Facet | Sort | Notes |
|-------|------|-------|------|-------|
| `id` | string | | | `book_{blog_id}` |
| `blog_id` | int64 | yes | yes | For access filtering |
| `title` | string | | | Book title |
| `subtitle` | string | | | Book subtitle |
| `authors` | string[] | yes | | Book authors |
| `language` | string | yes | | Book language |
| `license` | string | yes | | Overall book license |
| `subjects` | string[] | yes | | Book subjects |
| `keywords` | string[] | | | Book keywords |
| `word_count` | int64 | | | Total word count |
| `cover_url` | string | | | Cover image URL |
| `book_url` | string | | | Public book URL |
| `is_public` | bool | yes | | Whether book is publicly visible |
| `in_directory` | bool | yes | | Whether book is in Pressbooks Directory |
| `updated_at` | int64 | | yes | Unix timestamp |

Default sort: `updated_at:desc`

### Collection: `pb_contributors`

Contributor/person taxonomy terms across the network.

| Field | Type | Facet | Sort | Notes |
|-------|------|-------|------|-------|
| `id` | string | | | `contributor_{term_id}` |
| `term_id` | int64 | | | WP taxonomy term ID |
| `name` | string | | | Full name |
| `slug` | string | | | URL slug |
| `contributor_type` | string[] | yes | | `author`, `editor`, `translator`, `reviewer`, `illustrator` |
| `description` | string | | | Biographical info |
| `blog_ids` | int64[] | yes | | All blogs where this contributor appears |
| `book_count` | int32 | | yes | Number of books contributed to |
| `section_count` | int32 | | | Number of sections authored |
| `profile_url` | string | | | Link to author page (if available) |

Default sort: `book_count:desc`

## Indexing Pipeline

### Hook Points

The plugin hooks into WordPress and Pressbooks actions from `Bootstrap::registerActions()`:

| WordPress Action | Indexer Response |
|---|---|
| `save_post` (chapter, front-matter, back-matter, glossary) | Queue `upsert_section` |
| `delete_post` (any indexed type) | Queue `delete_section` |
| `trash_post` / `untrash_post` | Queue `delete_section` / `upsert_section` |
| `transition_post_status` | Queue `upsert_section` or `delete_section` based on new status |
| `wp_initialize_site` (new book created) | Queue `reindex_book` for all initial content |
| `wp_update_site` (book metadata changed) | Queue `upsert_book` |
| `wp_delete_site` (book deleted) | Queue `delete_book` + delete all sections by blog_id |
| `edited_term` (contributor taxonomy) | Queue `upsert_contributor` + reindex associated books/sections |
| `created_term` / `delete_term` (contributor taxonomy) | Queue `upsert_contributor` / `delete_contributor` |

### Job Queue Table: `pressbooks_borges_index_jobs`

Created via the scaffold's migration system (`Database\Migrations\`).

| Column | Type | Default | Notes |
|--------|------|---------|-------|
| `id` | bigint, auto-increment | | Primary key |
| `blog_id` | bigint unsigned | | Target blog |
| `job_type` | varchar(30) | | `upsert_section`, `delete_section`, `upsert_book`, `delete_book`, `upsert_contributor`, `delete_contributor`, `reindex_book` |
| `payload` | longtext (JSON) | null | `{"post_id": 42, "collection": "pb_sections"}` |
| `status` | varchar(20) | `pending` | `pending`, `processing`, `completed`, `failed` |
| `attempts` | int | 0 | Retry counter |
| `error_message` | text | null | Last error on failure |
| `created_at` | timestamp | CURRENT_TIMESTAMP | Job created |
| `updated_at` | timestamp | CURRENT_TIMESTAMP (on update) | Last status change |
| `processed_at` | datetime | null | When completed or failed |

Indexes: `blog_id`, `status`, `created_at`.

### Migration File

```php
// src/Database/Migrations/000001_create_search_index_jobs_table.php
use PressbooksBorges\Interfaces\MigrationInterface;
use Illuminate\Database\Schema\Blueprint;

return new class implements MigrationInterface {
    public function up(): void
    {
        if (!app('db')->schema()->hasTable('pressbooks_borges_index_jobs')) {
            app('db')->schema()->create('pressbooks_borges_index_jobs', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('blog_id');
                $table->string('job_type', 30);
                $table->longText('payload')->nullable();
                $table->string('status', 20)->default('pending');
                $table->integer('attempts')->default(0);
                $table->text('error_message')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
                $table->dateTime('processed_at')->nullable();
                $table->index('blog_id');
                $table->index('status');
                $table->index('created_at');
            });
        }
    }
};
```

### Job Processing Flow

1. **Hook fires** -> `SearchService::enqueueJob($blogId, $jobType, $payload)` inserts a row as `pending`
2. **WP-Cron** (`pb_borges_index_processor`, runs every minute) picks up to 50 `pending` jobs ordered by `created_at`
3. **`IndexJobProcessor::process($job)`** calls the appropriate indexer method based on `job_type`
4. On success: `status` -> `completed`
5. On failure: `status` -> `failed`, `attempts++`, `error_message` set. If `attempts < 3`, reset to `pending` for retry on next cron run
6. Dead letter: Jobs with `attempts >= 3` stay `failed` permanently. Admin page shows failed count with error details

### Debouncing

`enqueueJob` deduplicates by checking for an existing `pending` job with the same `blog_id` + `job_type` + matching payload key (e.g., same `post_id`). If found, update `updated_at` instead of inserting a new row.

### Bulk Upsert

Indexers send documents to Typesense via the bulk import API (`/collections/{name}/documents/import`) with the `upsert` action. For `reindex_book` jobs, all sections are batched into a single import call (most books have fewer than 100 sections, well within Typesense's limits).

### Full Reindex

Network admin "Reindex All" action:

1. Truncates the queue table
2. Iterates all books via `get_sites()`
3. Queues `reindex_book` jobs for each blog
4. `reindex_book` job: deletes existing documents for that `blog_id` from Typesense, then upserts all sections + book metadata + contributors

## Access Control

### Key Generation Strategy

The `KeyGenerator` class uses Typesense's key derivation API. Scoped search-only keys are generated by deriving them from a parent API key with embedded `filter_by` constraints. No round-trip to Typesense server needed.

Keys are cached in WordPress transients (`pb_borges_key_{userId}_{hash}`) with a 50-minute TTL (slightly less than the key's 1-hour expiry) to avoid generating a new key on every page load.

### Context 1: Admin

User is in wp-admin, searching across all accessible books:

```
filter_by: blog_id:[1,5,12,37] && post_status:[publish,private,draft]
```

Blog IDs come from `get_blogs_of_user($userId)`.

### Context 2: Webbook (logged in)

User is reading a book, searching within it:

```
filter_by: blog_id:5 && post_status:[publish,web-only]
```

Only the current book, only published/web-only content.

### Context 3: Webbook (anonymous)

No user logged in, reading a public book:

```
filter_by: blog_id:5 && post_status:publish && is_public:true
```

Only published content from public books.

### API Keys

Two Typesense API keys are required, stored as WordPress constants or env variables:

- `TYPESENSE_ADMIN_KEY`: Full admin access for collection management and indexing. Never exposed to frontend.
- `TYPESENSE_SEARCH_ONLY_KEY`: Search-only permissions on `pb_sections`, `pb_books`, and `pb_contributors`. Used as the parent key for deriving scoped keys.

Follows the existing Pressbooks env var pattern (see `ALGOLIA_APP_ID`, `AWS_ACCESS_KEY_ID`).

## Frontend

### Global Search Bar

A persistent search input in the WordPress admin toolbar, available from any page.

Behavior:
- Uses `typesense-instantsearch-adapter` (official adapter for Instantsearch.js)
- Multi-search across all 3 collections: `pb_contributors` (first), `pb_books`, `pb_sections`
- Debounced search (300ms), minimum 2 characters
- Dropdown shows up to 5 contributors, 3 books, 5 sections
- Keyboard navigation (arrow keys, Enter to select, Esc to close)
- Hit highlighting on matched terms
- "See all results" link at bottom navigates to dedicated results page

### Dedicated Search Results Page

**Route**:
- Admin: `admin.php?page=pb_borges_search&q={query}`
- Webbook: `{book_url}/search/?q={query}`

Layout:
- Search input at top (pre-filled from `q` parameter)
- Left sidebar with facet filters: post type, book, author, license, contributor type
- Main area with paginated results (10 per page)
- Results grouped by collection: contributors first, then books, then sections
- Contributor results show cross-referenced works (books and sections they authored)
- Book results show title, subtitle, authors, license badge, word count
- Section results show title, content snippet with highlighting, book name, position

### Contributor-First Results

When a query matches a contributor name:

1. `pb_contributors` results display as prominent person cards
2. Each card shows: name, contributor types, book count, section count
3. Below the card, linked works are listed (books then sections)
4. `pb_books` results are filtered by `authors` field matching the query
5. `pb_sections` results are filtered by `authors` field matching the query

### Enqueueing

`Admin\SearchBar` hooks:
- `admin_enqueue_scripts` for admin context
- `wp_enqueue_scripts` for webbook context (only when `Book::isBook()`)

Uses the scaffold's Vite-based asset loading:

```php
Vite\enqueue_asset(
    WP_PLUGIN_DIR . '/pressbooks-borges/dist',
    'resources/assets/js/pressbooks-borges.js',
    ['handle' => 'pressbooks-borges']
);
```

Passes config via `wp_localize_script`:

```js
PBBorges = {
    typesense: {
        nodes: [{ host: 'search.example.com', port: 443, protocol: 'https' }],
        apiKey: '<scoped_key>',
        searchOnly: true,
    },
    collections: {
        sections: 'pb_sections',
        books: 'pb_books',
        contributors: 'pb_contributors',
    },
    context: 'admin' | 'webbook',
    currentBlogId: 5,
    resultsPageUrl: '/wp-admin/admin.php?page=pb_borges_search',
};
```

### Vite Config

```js
import { createWpViteConfig } from 'pressbooks-build-tools';
import { resolve } from 'path';

export default createWpViteConfig({
    input: {
        app: resolve(__dirname, 'resources/assets/js/pressbooks-borges.js'),
    },
    outDir: 'dist',
});
```

## REST API

### Endpoint: `/pressbooks-borges/v1/search`

Registered via `Api\SearchEndpoint::register()` which hooks into `rest_api_init`.

**Parameters**:

| Param | Type | Default | Notes |
|-------|------|---------|-------|
| `q` | string | required | Search query (min 2 chars) |
| `per_page` | int | 10 | Results per page (max 50) |
| `page` | int | 1 | Page number |
| `collection` | string | `all` | `sections`, `books`, `contributors`, or `all` |
| `filter_post_type` | string[] | null | Facet filter |
| `filter_blog_id` | int[] | null | Facet filter |
| `filter_author` | string[] | null | Facet filter |
| `filter_license` | string[] | null | Facet filter |
| `filter_contributor_type` | string[] | null | Facet filter |

**Response**:

```json
{
    "results": {
        "contributors": { "hits": [...], "found": 1 },
        "books": { "hits": [...], "found": 3 },
        "sections": { "hits": [...], "found": 15 }
    },
    "total_found": 19,
    "search_time_ms": 12,
    "page": 1,
    "per_page": 10
}
```

The endpoint requires authentication (logged-in user) via WordPress cookies for admin context. For webbook context, unauthenticated requests are allowed but restricted to published content from public books only (same as Context 3 in Access Control). The endpoint generates a scoped key internally based on the user's context and executes the multi-search server-side. Acts as a proxy for the full-page results and programmatic API access.

## Admin Settings

**Location**: Network Admin -> Settings -> Pressbooks Borges

Settings stored as network option (`pb_borges_settings`):

| Setting | Type | Default | Notes |
|---------|------|---------|-------|
| `typesense_nodes` | string | required | Comma-separated `host:port:protocol` |
| `typesense_admin_key` | string | required | Collection management key |
| `typesense_search_key` | string | required | Parent search-only key for key derivation |
| `enabled_admin` | bool | true | Enable search in admin area |
| `enabled_webbook` | bool | true | Enable search in webbook frontend |
| `index_private_books` | bool | true | Index private book content (access controlled at query time) |
| `index_draft_content` | bool | false | Index draft sections |
| `max_retries` | int | 3 | Max retry attempts for failed index jobs |
| `batch_size` | int | 50 | Jobs processed per cron run |

### Health Check

`Admin\SearchHealthCheck` extends `Pressbooks\Health\Check`. Verifies:

- Typesense connection is alive (ping endpoint)
- Collections exist and have documents
- Queue backlog within limits (more than 1000 pending jobs triggers warning)

Registered in the existing health check system at `/pressbooks/v2/health-check`.

## Plugin Extensibility

### Filter Hooks

Register custom indexers:

```php
add_filter('pb_borges_indexers', function (array $indexers): array {
    $indexers[] = new MyLtiIndexer();
    return $indexers;
});
```

Register custom collection schemas:

```php
add_filter('pb_borges_collections', function (array $collections): array {
    $collections['pb_lti_content'] = [
        'name' => 'pb_lti_content',
        'fields' => [
            ['name' => 'title', 'type' => 'string'],
            ['name' => 'content', 'type' => 'string'],
            ['name' => 'blog_id', 'type' => 'int64', 'facet' => true],
            ['name' => 'lti_platform', 'type' => 'string', 'facet' => true],
        ],
        'default_sorting_field' => 'updated_at',
    ];
    return $collections;
});
```

Register frontend collection display:

```php
add_filter('pb_borges_frontend_collections', function (array $cols): array {
    $cols[] = [
        'name' => 'pb_lti_content',
        'label' => 'LTI Content',
        'icon' => '🔗',
        'hit_template' => '<div>...</div>',
    ];
    return $cols;
});
```

### Indexer Interface

```php
namespace PressbooksBorges\Indexing;

interface IndexerInterface
{
    public function getCollectionName(): string;
    public function getPostTypes(): array;
    public function transformDocument(int $blogId, int $postId): ?array;
    public function deleteDocument(int $blogId, int $postId): ?array;
}
```

`SearchService` iterates all registered indexers on `save_post` and dispatches to whichever one claims that post type. Return `null` from `transformDocument` to skip indexing a specific post.

### Action Hooks

| Action | Parameters | When Fired |
|--------|-----------|------------|
| `pb_borges_document_indexed` | `$collection, $blogId, $documentId` | After successful index |
| `pb_borges_document_deleted` | `$collection, $blogId, $documentId` | After document deleted from index |
| `pb_borges_reindex_started` | `$blogId` | When a book reindex begins |
| `pb_borges_reindex_completed` | `$blogId` | When a book reindex finishes |
| `pb_borges_job_failed` | `$jobId, $errorMessage` | When a job fails permanently (dead letter) |

## Environment Variables

| Variable | Required | Notes |
|----------|----------|-------|
| `TYPESENSE_NODES` | yes | Comma-separated `host:port:protocol` |
| `TYPESENSE_ADMIN_KEY` | yes | Admin API key for collection management |
| `TYPESENSE_SEARCH_ONLY_KEY` | yes | Search-only key for scoped key derivation |

These follow the existing Pressbooks env var pattern (see `ALGOLIA_APP_ID`, `AWS_ACCESS_KEY_ID` in `pressbooks/inc/utility/namespace.php`).

## Dependencies

### PHP

| Package | Version | Purpose |
|---------|---------|---------|
| `pressbooks/frontend-tools` | `^1.0.0` | Vite-based asset enqueueing |
| `typesense/typesense-php` | `^1.0` | Official Typesense PHP client |
| `laravel/pint` | `^1.10.6` | Code style (dev) |
| `yoast/phpunit-polyfills` | `^1.0.5` | Testing (dev) |

### JavaScript

| Package | Version | Purpose |
|---------|---------|---------|
| `typesense-instantsearch-adapter` | `^2.0` | Instantsearch adapter for Typesense |
| `instantsearch.js` | `^4.0` | Search UI widgets |
| `pressbooks-build-tools` | `^5.0.0` | Vite build pipeline (dev) |

## Scaling Considerations

For medium networks (100-1,000 books):

- **Typesense**: 3-node cluster (1 leader + 2 replicas) provides HA and read scaling. Each node needs approximately 2GB RAM for this index size.
- **Indexing load**: Average of approximately 500 sections per book = 50k-500k total documents. Full reindex at approximately 1000 docs/sec takes under 10 minutes.
- **Query load**: Scoped key generation is CPU-cheap (HMAC derivation, no network call). Typesense handles 100+ concurrent searches per node.
- **Queue**: 50 jobs/minute cron throughput handles steady-state indexing easily. Bulk reindex uses larger batch sizes.
- **Larger networks**: Shard by blog_id ranges across multiple Typesense clusters, or upgrade to Typesense Cloud's auto-scaling.
