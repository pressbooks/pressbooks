# Pressbooks Borges — Typesense Search Integration Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a standalone Pressbooks plugin (`pressbooks-borges`) that integrates Typesense for fast, faceted, contextual search across a Pressbooks multisite network.

**Architecture:** Standalone PSR-4 plugin based on `pressbooks-plugin-scaffold`. Uses a background job queue table + WP-Cron for async indexing, Typesense PHP client for collection management, scoped search-only API keys for frontend access control, and Instantsearch.js for the search UI. Registers its service in the Pressbooks Container.

**Tech Stack:** PHP 8.3, PSR-4 autoloading, Laravel Eloquent (via Pressbooks Container), Laravel Blade (via Pressbooks Container), Laravel Pint (linting), Typesense PHP client, Vite (pressbooks-build-tools), Instantsearch.js + typesense-instantsearch-adapter, PHPUnit with WP_UnitTestCase.

**Spec:** `docs/superpowers/specs/2026-04-09-typesense-search-design.md`

---

## Phase 1: Plugin Scaffolding

### Task 1: Copy and configure the scaffold

**Files:**
- Create: `/Users/arzola/code/pbdev/web/app/plugins/pressbooks-borges/` (entire plugin directory)

- [ ] **Step 1: Copy the scaffold**

```bash
cp -r /Users/arzola/code/pbdev/web/app/plugins/pressbooks-plugin-scaffold /Users/arzola/code/pbdev/web/app/plugins/pressbooks-borges
```

- [ ] **Step 2: Remove scaffold-specific files**

```bash
cd /Users/arzola/code/pbdev/web/app/plugins/pressbooks-borges
rm -rf .git
rm configure.php
rm src/Controllers/DemoController.php
rm src/Views/TableView.php
rm -rf resources/views/demo
rm -rf src/Support/.gitkeep
rm -rf src/Models/.gitkeep
rm -rf src/Exceptions
```

- [ ] **Step 3: Rename the main plugin file**

```bash
mv pressbooks-plugin-scaffold.php pressbooks-borges.php
```

- [ ] **Step 4: Update `pressbooks-borges.php`**

Replace the entire file with:

```php
<?php

/**
 * Plugin Name: Pressbooks Borges
 * Plugin URI: https://pressbooks.org
 * Requires at least: 6.8
 * Requires Plugins: pressbooks
 * Description: Fast, faceted search for Pressbooks networks powered by Typesense.
 * x-release-please-start-version
 * Version: 0.1.0
 * x-release-please-end
 * Author: Pressbooks (Book Oven Inc.)
 * Author URI: https://pressbooks.org
 * Requires PHP: 8.3
 * Pressbooks tested up to: 6.16.0
 * Text Domain: pressbooks-borges
 * License: GPL v3 or later
 * Network: True
 */

use PressbooksBorges\Bootstrap;
use PressbooksBorges\Database\Migration;

register_activation_hook(__FILE__, [Migration::class, 'migrate']);

add_action('plugins_loaded', [Bootstrap::class, 'run']);
```

- [ ] **Step 5: Update `composer.json`**

Replace the entire file with:

```json
{
	"name": "pressbooks/pressbooks-borges",
	"license": "GPL-3.0-or-later",
	"type": "wordpress-plugin",
	"description": "Fast, faceted search for Pressbooks networks powered by Typesense.",
	"homepage": "https://github.com/pressbooks/pressbooks-borges",
	"authors": [
		{
			"name": "Book Oven Inc.",
			"email": "code@pressbooks.com",
			"homepage": "https://pressbooks.org"
		}
	],
	"keywords": [
		"ebooks",
		"publishing",
		"search",
		"typesense"
	],
	"support": {
		"email": "code@pressbooks.com",
		"issues": "https://github.com/pressbooks/pressbooks-borges/issues/",
		"source": "https://github.com/pressbooks/pressbooks-borges/"
	},
	"config": {
		"allow-plugins": {
			"composer/installers": true
		}
	},
	"autoload": {
		"psr-4": {
			"PressbooksBorges\\": "src/"
		}
	},
	"autoload-dev": {
		"psr-4": {
			"Tests\\": "tests/"
		}
	},
	"require": {
		"php": "^8.3",
		"pressbooks/frontend-tools": "^1.0.0",
		"typesense/typesense-php": "^1.0"
	},
	"require-dev": {
		"laravel/pint": "^1.10.6",
		"yoast/phpunit-polyfills": "^1.0.5"
	},
	"scripts": {
		"fix": [
			"./vendor/bin/pint"
		],
		"standards": [
			"./vendor/bin/pint --test"
		],
		"test": [
			"./vendor/bin/phpunit --configuration phpunit.xml"
		],
		"test-coverage": [
			"./vendor/bin/phpunit --configuration phpunit.xml --coverage-clover coverage.xml"
		]
	}
}
```

- [ ] **Step 6: Update `package.json`**

Replace the entire file with:

```json
{
  "name": "pressbooks-borges",
  "description": "Fast, faceted search for Pressbooks networks powered by Typesense.",
  "type": "module",
  "scripts": {
    "build": "pressbooks-build-tools build",
    "watch": "pressbooks-build-tools dev",
    "lint:scripts": "pressbooks-build-tools lint:scripts 'resources/assets/js/**/*.js'",
    "lint:styles": "pressbooks-build-tools lint:styles 'resources/assets/styles/**/*.css'",
    "fix:scripts": "pressbooks-build-tools fix:scripts 'resources/assets/js/**/*.js'",
    "fix:styles": "pressbooks-build-tools fix:styles 'resources/assets/styles/**/*.css'",
    "lint": "npm run lint:scripts && npm run lint:styles"
  },
  "repository": {
    "type": "git",
    "url": "git+ssh://git@github.com/pressbooks/pressbooks-borges.git"
  },
  "keywords": [
    "wordpress-plugin",
    "search",
    "typesense",
    "pressbooks"
  ],
  "author": "Pressbooks (Book Oven Inc.)",
  "license": "GPL-3.0-or-later",
  "bugs": {
    "url": "https://github.com/pressbooks/pressbooks-borges/issues"
  },
  "homepage": "https://github.com/pressbooks/pressbooks-borges#readme",
  "dependencies": {
    "typesense-instantsearch-adapter": "^2.0",
    "instantsearch.js": "^4.0"
  },
  "devDependencies": {
    "pressbooks-build-tools": "^5.0.0"
  },
  "eslintConfig": {
    "extends": "./node_modules/pressbooks-build-tools/config/eslint.cjs"
  },
  "stylelint": {
    "extends": "./node_modules/pressbooks-build-tools/config/stylelint.js"
  }
}
```

- [ ] **Step 7: Update `vite.config.js`**

Replace the entire file with:

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

- [ ] **Step 8: Update `phpunit.xml`**

Replace the testsuite name:

```xml
<?xml version="1.0"?>
<phpunit
	xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
	xsi:noNamespaceSchemaLocation="./vendor/phpunit/phpunit/phpunit.xsd"
	bootstrap="tests/bootstrap.php"
	colors="true"
	backupGlobals="false"
	convertErrorsToExceptions="true"
	convertNoticesToExceptions="true"
	convertWarningsToExceptions="true"
>
	<coverage processUncoveredFiles="true">
		<include>
			<directory suffix=".php">./src</directory>
		</include>
	</coverage>
	<testsuites>
		<testsuite name="Pressbooks Borges">
			<directory suffix="Test.php">./tests/</directory>
		</testsuite>
	</testsuites>
	<php>
		<const name="WP_TESTS_MULTISITE" value="1"/>
	</php>
</phpunit>
```

- [ ] **Step 9: Rename asset files**

```bash
mv resources/assets/js/pressbooks-plugin-scaffold.js resources/assets/js/pressbooks-borges.js
```

Update `resources/assets/js/pressbooks-borges.js` to:

```js
import '../styles/pressbooks-borges.css';
```

Rename the CSS file if it exists. If `resources/assets/styles/` has a `pressbooks-plugin-scaffold.css`, rename it to `pressbooks-borges.css`.

- [ ] **Step 10: Update `tests/bootstrap.php`**

```php
<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

$tests_dir = getenv('WP_TESTS_DIR');
if (! $tests_dir) {
    $tests_dir = '/tmp/wordpress-tests-lib';
}

require_once "{$tests_dir}/includes/functions.php";

tests_add_filter('muplugins_loaded', function () {
    require_once __DIR__ . '/../../pressbooks/pressbooks.php';
    require_once __DIR__ . '/../../pressbooks/tests/utils-trait.php';
});

require_once "{$tests_dir}/includes/bootstrap.php";
```

- [ ] **Step 11: Update `tests/TestCase.php`**

```php
<?php

namespace Tests;

use PressbooksBorges\Bootstrap;
use PressbooksBorges\Database\Migration;
use WP_UnitTestCase;

class TestCase extends WP_UnitTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        Migration::migrate();

        (new Bootstrap)->setUp();
    }
}
```

- [ ] **Step 12: Update `src/Bootstrap.php`**

Replace the entire file with:

```php
<?php

namespace PressbooksBorges;

use Pressbooks\Container;

final class Bootstrap
{
    private static ?Bootstrap $instance = null;

    public static function run(): void
    {
        if (! self::$instance) {
            self::$instance = new self;
            self::$instance->setUp();
        }
    }

    public function setUp(): void
    {
        $this->registerBlade();
    }

    private function registerBlade(): void
    {
        Container::get('Blade')->addNamespace(
            'PressbooksBorges',
            dirname(__DIR__) . '/resources/views'
        );
    }
}
```

- [ ] **Step 13: Update `src/Controllers/BaseController.php` namespace references**

Replace `PressbooksPluginScaffold` with `PressbooksBorges` in the namespace and Blade namespace:

```php
<?php

namespace PressbooksBorges\Controllers;

use Pressbooks\Container;

class BaseController
{
    protected mixed $view;

    public function __construct()
    {
        $this->view = Container::get('Blade');
    }

    protected function renderView(string $view, array $data = []): string
    {
        return $this->view->render(
            "PressbooksBorges::{$view}",
            $data
        );
    }
}
```

- [ ] **Step 14: Update `src/Database/Migration.php` namespace**

Replace `PressbooksPluginScaffold` with `PressbooksBorges`:

```php
<?php

namespace PressbooksBorges\Database;

use FilesystemIterator;
use Illuminate\Support\Collection;
use PressbooksBorges\Interfaces\MigrationInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class Migration
{
    public static function migrate(): bool
    {
        (new static)
            ->getMigrationFiles()
            ->sortKeys()
            ->each(fn (MigrationInterface $class) => $class->up());

        return true;
    }

    private function getMigrationFiles(): Collection
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                directory: __DIR__ . '/Migrations',
                flags: FilesystemIterator::SKIP_DOTS
            )
        );

        return Collection::make($iterator)
            ->filter(fn (SplFileInfo $record) => $record->isFile())
            ->map(fn (SplFileInfo $record) => require $record->getPathname());
    }
}
```

- [ ] **Step 15: Update `src/Interfaces/MigrationInterface.php`**

```php
<?php

namespace PressbooksBorges\Interfaces;

interface MigrationInterface
{
    public function up(): void;
}
```

- [ ] **Step 16: Remove old Migrations directory contents, keep it empty**

```bash
rm -rf src/Database/Migrations/.gitkeep
```

- [ ] **Step 17: Install dependencies**

```bash
cd /Users/arzola/code/pbdev/web/app/plugins/pressbooks-borges && composer install && npm install
```

- [ ] **Step 18: Initialize git and commit**

```bash
cd /Users/arzola/code/pbdev/web/app/plugins/pressbooks-borges
git init
git add -A
git commit -m "feat: Scaffold pressbooks-borges plugin from pressbooks-plugin-scaffold"
```

---

## Phase 2: Database Migration

### Task 2: Create the search index jobs table migration

**Files:**
- Create: `src/Database/Migrations/000001_create_search_index_jobs_table.php`
- Create: `tests/Unit/MigrationTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Unit/MigrationTest.php`:

```php
<?php

namespace Tests\Unit;

use Tests\TestCase;

class MigrationTest extends TestCase
{
    public function test_jobs_table_exists_after_migration(): void
    {
        $this->assertTrue(
            app('db')->schema()->hasTable('pressbooks_borges_index_jobs')
        );
    }

    public function test_jobs_table_has_expected_columns(): void
    {
        $columns = app('db')->schema()->getColumnListing('pressbooks_borges_index_jobs');

        $this->assertContains('id', $columns);
        $this->assertContains('blog_id', $columns);
        $this->assertContains('job_type', $columns);
        $this->assertContains('payload', $columns);
        $this->assertContains('status', $columns);
        $this->assertContains('attempts', $columns);
        $this->assertContains('error_message', $columns);
        $this->assertContains('created_at', $columns);
        $this->assertContains('updated_at', $columns);
        $this->assertContains('processed_at', $columns);
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

```bash
cd /Users/arzola/code/pbdev/web/app/plugins/pressbooks-borges
lando vendor/bin/phpunit --configuration phpunit.xml tests/Unit/MigrationTest.php
```

Expected: FAIL — table `pressbooks_borges_index_jobs` does not exist.

- [ ] **Step 3: Write the migration**

Create `src/Database/Migrations/000001_create_search_index_jobs_table.php`:

```php
<?php

use PressbooksBorges\Interfaces\MigrationInterface;

return new class implements MigrationInterface {
    public function up(): void
    {
        if (app('db')->schema()->hasTable('pressbooks_borges_index_jobs')) {
            return;
        }

        app('db')->schema()->create('pressbooks_borges_index_jobs', function ($table) {
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
};
```

- [ ] **Step 4: Run the test to verify it passes**

```bash
lando vendor/bin/phpunit --configuration phpunit.xml tests/Unit/MigrationTest.php
```

Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add -A && git commit -m "feat: Add search index jobs table migration"
```

---

## Phase 3: Typesense Client & Collection Setup

### Task 3: TypesenseClient wrapper

**Files:**
- Create: `src/Search/TypesenseClient.php`
- Create: `tests/Unit/TypesenseClientTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Unit/TypesenseClientTest.php`:

```php
<?php

namespace Tests\Unit;

use PressbooksBorges\Search\TypesenseClient;
use Tests\TestCase;

class TypesenseClientTest extends TestCase
{
    private TypesenseClient $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = new TypesenseClient(
            nodes: [['host' => 'localhost', 'port' => '8108', 'protocol' => 'http']],
            adminApiKey: 'test-key'
        );
    }

    public function test_get_client_returns_typesense_client_instance(): void
    {
        $this->assertInstanceOf(
            \Typesense\Client::class,
            $this->client->getClient()
        );
    }

    public function test_get_admin_key_returns_configured_key(): void
    {
        $this->assertEquals('test-key', $this->client->getAdminKey());
    }

    public function test_get_search_key_returns_null_when_not_set(): void
    {
        $this->assertNull($this->client->getSearchKey());
    }

    public function test_get_search_key_returns_configured_key(): void
    {
        $client = new TypesenseClient(
            nodes: [['host' => 'localhost', 'port' => '8108', 'protocol' => 'http']],
            adminApiKey: 'admin-key',
            searchOnlyKey: 'search-key'
        );
        $this->assertEquals('search-key', $client->getSearchKey());
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

```bash
lando vendor/bin/phpunit --configuration phpunit.xml tests/Unit/TypesenseClientTest.php
```

Expected: FAIL — class `TypesenseClient` does not exist.

- [ ] **Step 3: Write the implementation**

Create `src/Search/TypesenseClient.php`:

```php
<?php

namespace PressbooksBorges\Search;

use Typesense\Client;

class TypesenseClient
{
    private Client $client;
    private string $adminKey;
    private ?string $searchOnlyKey;

    public function __construct(
        array $nodes,
        string $adminApiKey,
        ?string $searchOnlyKey = null,
    ) {
        $this->adminKey = $adminApiKey;
        $this->searchOnlyKey = $searchOnlyKey;
        $this->client = new Client([
            'api_key' => $adminApiKey,
            'nodes' => $nodes,
            'connection_timeout_seconds' => 5,
            'retry_interval_seconds' => 1,
        ]);
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function getAdminKey(): string
    {
        return $this->adminKey;
    }

    public function getSearchKey(): ?string
    {
        return $this->searchOnlyKey;
    }

    public static function fromSettings(): self
    {
        $settings = get_site_option('pb_borges_settings', []);

        $nodes = array_map(function (string $node) {
            [$host, $port, $protocol] = explode(':', $node, 3);
            return [
                'host' => $host,
                'port' => (int) $port,
                'protocol' => $protocol,
            ];
        }, explode(',', $settings['typesense_nodes'] ?? ''));

        return new self(
            nodes: $nodes,
            adminApiKey: $settings['typesense_admin_key'] ?? '',
            searchOnlyKey: $settings['typesense_search_key'] ?? null,
        );
    }
}
```

- [ ] **Step 4: Run the test to verify it passes**

```bash
lando vendor/bin/phpunit --configuration phpunit.xml tests/Unit/TypesenseClientTest.php
```

Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add -A && git commit -m "feat: Add TypesenseClient wrapper"
```

### Task 4: Collection schema definitions

**Files:**
- Create: `src/Search/Collections.php`
- Create: `tests/Unit/CollectionsTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Unit/CollectionsTest.php`:

```php
<?php

namespace Tests\Unit;

use PressbooksBorges\Search\Collections;
use Tests\TestCase;

class CollectionsTest extends TestCase
{
    public function test_sections_collection_has_required_fields(): void
    {
        $schema = Collections::sections();
        $fieldNames = array_map(fn ($f) => $f['name'], $schema['fields']);

        $this->assertEquals('pb_sections', $schema['name']);
        $this->assertContains('id', $fieldNames);
        $this->assertContains('blog_id', $fieldNames);
        $this->assertContains('post_id', $fieldNames);
        $this->assertContains('post_type', $fieldNames);
        $this->assertContains('title', $fieldNames);
        $this->assertContains('content', $fieldNames);
        $this->assertContains('authors', $fieldNames);
        $this->assertContains('updated_at', $fieldNames);
    }

    public function test_books_collection_has_required_fields(): void
    {
        $schema = Collections::books();
        $fieldNames = array_map(fn ($f) => $f['name'], $schema['fields']);

        $this->assertEquals('pb_books', $schema['name']);
        $this->assertContains('id', $fieldNames);
        $this->assertContains('blog_id', $fieldNames);
        $this->assertContains('title', $fieldNames);
        $this->assertContains('authors', $fieldNames);
        $this->assertContains('is_public', $fieldNames);
    }

    public function test_contributors_collection_has_required_fields(): void
    {
        $schema = Collections::contributors();
        $fieldNames = array_map(fn ($f) => $f['name'], $schema['fields']);

        $this->assertEquals('pb_contributors', $schema['name']);
        $this->assertContains('id', $fieldNames);
        $this->assertContains('name', $fieldNames);
        $this->assertContains('blog_ids', $fieldNames);
        $this->assertContains('book_count', $fieldNames);
    }

    public function test_all_returns_all_collections(): void
    {
        $all = Collections::all();

        $this->assertCount(3, $all);
        $this->assertArrayHasKey('pb_sections', $all);
        $this->assertArrayHasKey('pb_books', $all);
        $this->assertArrayHasKey('pb_contributors', $all);
    }

    public function test_all_applies_filter_for_custom_collections(): void
    {
        add_filter('pb_borges_collections', function (array $collections): array {
            $collections['pb_custom'] = [
                'name' => 'pb_custom',
                'fields' => [
                    ['name' => 'title', 'type' => 'string'],
                ],
                'default_sorting_field' => 'updated_at',
            ];
            return $collections;
        });

        $all = Collections::all();

        $this->assertArrayHasKey('pb_custom', $all);

        remove_all_filters('pb_borges_collections');
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

```bash
lando vendor/bin/phpunit --configuration phpunit.xml tests/Unit/CollectionsTest.php
```

Expected: FAIL — class `Collections` does not exist.

- [ ] **Step 3: Write the implementation**

Create `src/Search/Collections.php`:

```php
<?php

namespace PressbooksBorges\Search;

class Collections
{
    public static function sections(): array
    {
        return [
            'name' => 'pb_sections',
            'fields' => [
                ['name' => 'id', 'type' => 'string'],
                ['name' => 'blog_id', 'type' => 'int64', 'facet' => true, 'sort' => true],
                ['name' => 'post_id', 'type' => 'int64'],
                ['name' => 'post_type', 'type' => 'string', 'facet' => true],
                ['name' => 'post_status', 'type' => 'string', 'facet' => true],
                ['name' => 'title', 'type' => 'string'],
                ['name' => 'short_title', 'type' => 'string', 'optional' => true],
                ['name' => 'content', 'type' => 'string'],
                ['name' => 'authors', 'type' => 'string[]', 'facet' => true],
                ['name' => 'section_license', 'type' => 'string', 'facet' => true, 'optional' => true],
                ['name' => 'language', 'type' => 'string', 'facet' => true, 'optional' => true],
                ['name' => 'parent_id', 'type' => 'int64', 'optional' => true],
                ['name' => 'menu_order', 'type' => 'int32', 'optional' => true],
                ['name' => 'book_title', 'type' => 'string', 'optional' => true],
                ['name' => 'book_url', 'type' => 'string', 'optional' => true],
                ['name' => 'updated_at', 'type' => 'int64', 'sort' => true],
            ],
            'default_sorting_field' => 'updated_at',
        ];
    }

    public static function books(): array
    {
        return [
            'name' => 'pb_books',
            'fields' => [
                ['name' => 'id', 'type' => 'string'],
                ['name' => 'blog_id', 'type' => 'int64', 'facet' => true, 'sort' => true],
                ['name' => 'title', 'type' => 'string'],
                ['name' => 'subtitle', 'type' => 'string', 'optional' => true],
                ['name' => 'authors', 'type' => 'string[]', 'facet' => true],
                ['name' => 'language', 'type' => 'string', 'facet' => true, 'optional' => true],
                ['name' => 'license', 'type' => 'string', 'facet' => true, 'optional' => true],
                ['name' => 'subjects', 'type' => 'string[]', 'facet' => true, 'optional' => true],
                ['name' => 'keywords', 'type' => 'string[]', 'optional' => true],
                ['name' => 'word_count', 'type' => 'int64', 'optional' => true],
                ['name' => 'cover_url', 'type' => 'string', 'optional' => true],
                ['name' => 'book_url', 'type' => 'string', 'optional' => true],
                ['name' => 'is_public', 'type' => 'bool', 'facet' => true],
                ['name' => 'in_directory', 'type' => 'bool', 'facet' => true, 'optional' => true],
                ['name' => 'updated_at', 'type' => 'int64', 'sort' => true],
            ],
            'default_sorting_field' => 'updated_at',
        ];
    }

    public static function contributors(): array
    {
        return [
            'name' => 'pb_contributors',
            'fields' => [
                ['name' => 'id', 'type' => 'string'],
                ['name' => 'term_id', 'type' => 'int64'],
                ['name' => 'name', 'type' => 'string'],
                ['name' => 'slug', 'type' => 'string'],
                ['name' => 'contributor_type', 'type' => 'string[]', 'facet' => true],
                ['name' => 'description', 'type' => 'string', 'optional' => true],
                ['name' => 'blog_ids', 'type' => 'int64[]', 'facet' => true],
                ['name' => 'book_count', 'type' => 'int32', 'sort' => true],
                ['name' => 'section_count', 'type' => 'int32', 'optional' => true],
                ['name' => 'profile_url', 'type' => 'string', 'optional' => true],
            ],
            'default_sorting_field' => 'book_count',
        ];
    }

    public static function all(): array
    {
        $core = [
            'pb_sections' => self::sections(),
            'pb_books' => self::books(),
            'pb_contributors' => self::contributors(),
        ];

        return apply_filters('pb_borges_collections', $core);
    }
}
```

- [ ] **Step 4: Run the test to verify it passes**

```bash
lando vendor/bin/phpunit --configuration phpunit.xml tests/Unit/CollectionsTest.php
```

Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add -A && git commit -m "feat: Add Typesense collection schema definitions"
```

---

## Phase 4: Indexers

### Task 5: IndexerInterface

**Files:**
- Create: `src/Indexing/IndexerInterface.php`

- [ ] **Step 1: Write the interface**

Create `src/Indexing/IndexerInterface.php`:

```php
<?php

namespace PressbooksBorges\Indexing;

interface IndexerInterface
{
    public function getCollectionName(): string;

    public function getPostTypes(): array;

    public function transformDocument(int $blogId, int $postId): ?array;

    public function deleteDocument(int $blogId, int $postId): ?string;
}
```

- [ ] **Step 2: Commit**

```bash
git add -A && git commit -m "feat: Add IndexerInterface contract"
```

### Task 6: SectionsIndexer

**Files:**
- Create: `src/Indexing/Indexers/SectionsIndexer.php`
- Create: `tests/Unit/SectionsIndexerTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Unit/SectionsIndexerTest.php`:

```php
<?php

namespace Tests\Unit;

use PressbooksBorges\Indexing\Indexers\SectionsIndexer;
use Tests\TestCase;

class SectionsIndexerTest extends TestCase
{
    private SectionsIndexer $indexer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->indexer = new SectionsIndexer;
    }

    public function test_get_collection_name(): void
    {
        $this->assertEquals('pb_sections', $this->indexer->getCollectionName());
    }

    public function test_get_post_types_returns_expected_types(): void
    {
        $types = $this->indexer->getPostTypes();

        $this->assertContains('chapter', $types);
        $this->assertContains('front-matter', $types);
        $this->assertContains('back-matter', $types);
        $this->assertContains('glossary', $types);
    }

    public function test_transform_document_returns_null_for_nonexistent_post(): void
    {
        $result = $this->indexer->transformDocument(1, 999999);

        $this->assertNull($result);
    }

    public function test_delete_document_returns_composite_id(): void
    {
        $result = $this->indexer->deleteDocument(5, 42);

        $this->assertEquals('5_42', $result);
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

```bash
lando vendor/bin/phpunit --configuration phpunit.xml tests/Unit/SectionsIndexerTest.php
```

Expected: FAIL — class `SectionsIndexer` does not exist.

- [ ] **Step 3: Write the implementation**

Create `src/Indexing/Indexers/SectionsIndexer.php`:

```php
<?php

namespace PressbooksBorges\Indexing\Indexers;

use PressbooksBorges\Indexing\IndexerInterface;

class SectionsIndexer implements IndexerInterface
{
    public function getCollectionName(): string
    {
        return 'pb_sections';
    }

    public function getPostTypes(): array
    {
        return ['chapter', 'front-matter', 'back-matter', 'glossary'];
    }

    public function transformDocument(int $blogId, int $postId): ?array
    {
        $switched = false;
        if (get_current_blog_id() !== $blogId) {
            switch_to_blog($blogId);
            $switched = true;
        }

        $post = get_post($postId);

        if (! $post || ! in_array($post->post_type, $this->getPostTypes(), true)) {
            if ($switched) {
                restore_current_blog();
            }
            return null;
        }

        $bookTitle = '';
        $bookUrl = '';
        $language = '';

        $bookInfo = \Pressbooks\Book::getBookInformation($blogId);
        if ($bookInfo) {
            $bookTitle = $bookInfo['pb_title'] ?? '';
            $language = $bookInfo['pb_language'] ?? '';
        }
        $bookUrl = get_blogaddress_by_id($blogId);

        $authors = [];
        $pbAuthors = get_post_meta($postId, 'pb_authors', true);
        if ($pbAuthors) {
            $authors = array_map('trim', explode(',', $pbAuthors));
        }

        $terms = get_the_terms($postId, 'contributor');
        if ($terms && ! is_wp_error($terms)) {
            foreach ($terms as $term) {
                if (! in_array($term->name, $authors, true)) {
                    $authors[] = $term->name;
                }
            }
        }

        $content = wp_strip_all_tags($post->post_content);
        $content = preg_replace('/\s+/', ' ', $content);

        $document = [
            'id' => "{$blogId}_{$postId}",
            'blog_id' => $blogId,
            'post_id' => $postId,
            'post_type' => $post->post_type,
            'post_status' => $post->post_status,
            'title' => $post->post_title,
            'short_title' => get_post_meta($postId, 'pb_short_title', true) ?: null,
            'content' => $content,
            'authors' => $authors,
            'section_license' => get_post_meta($postId, 'pb_section_license', true) ?: null,
            'language' => $language ?: null,
            'parent_id' => $post->post_parent ?: null,
            'menu_order' => $post->menu_order,
            'book_title' => $bookTitle,
            'book_url' => $bookUrl,
            'updated_at' => strtotime($post->post_modified_gmt) ?: time(),
        ];

        if ($switched) {
            restore_current_blog();
        }

        return $document;
    }

    public function deleteDocument(int $blogId, int $postId): ?string
    {
        return "{$blogId}_{$postId}";
    }
}
```

- [ ] **Step 4: Run the test to verify it passes**

```bash
lando vendor/bin/phpunit --configuration phpunit.xml tests/Unit/SectionsIndexerTest.php
```

Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add -A && git commit -m "feat: Add SectionsIndexer for chapters, FM, BM, glossary"
```

### Task 7: BooksIndexer

**Files:**
- Create: `src/Indexing/Indexers/BooksIndexer.php`
- Create: `tests/Unit/BooksIndexerTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Unit/BooksIndexerTest.php`:

```php
<?php

namespace Tests\Unit;

use PressbooksBorges\Indexing\Indexers\BooksIndexer;
use Tests\TestCase;

class BooksIndexerTest extends TestCase
{
    private BooksIndexer $indexer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->indexer = new BooksIndexer;
    }

    public function test_get_collection_name(): void
    {
        $this->assertEquals('pb_books', $this->indexer->getCollectionName());
    }

    public function test_get_post_types_returns_empty_array(): void
    {
        $this->assertEmpty($this->indexer->getPostTypes());
    }

    public function test_delete_document_returns_book_id(): void
    {
        $result = $this->indexer->deleteDocument(5, 0);

        $this->assertEquals('book_5', $result);
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

```bash
lando vendor/bin/phpunit --configuration phpunit.xml tests/Unit/BooksIndexerTest.php
```

Expected: FAIL — class `BooksIndexer` does not exist.

- [ ] **Step 3: Write the implementation**

Create `src/Indexing/Indexers/BooksIndexer.php`:

```php
<?php

namespace PressbooksBorges\Indexing\Indexers;

use PressbooksBorges\Indexing\IndexerInterface;

class BooksIndexer implements IndexerInterface
{
    public function getCollectionName(): string
    {
        return 'pb_books';
    }

    public function getPostTypes(): array
    {
        return [];
    }

    public function transformDocument(int $blogId, int $postId = 0): ?array
    {
        $switched = false;
        if (get_current_blog_id() !== $blogId) {
            switch_to_blog($blogId);
            $switched = true;
        }

        $bookInfo = \Pressbooks\Book::getBookInformation($blogId);

        if (! $bookInfo) {
            if ($switched) {
                restore_current_blog();
            }
            return null;
        }

        $authors = [];
        if (! empty($bookInfo['pb_author'])) {
            $authors = array_map('trim', explode(',', $bookInfo['pb_author']));
        }

        $subjects = [];
        if (! empty($bookInfo['pb_subject'])) {
            $subjects = array_map('trim', explode(',', $bookInfo['pb_subject']));
        }

        $keywords = [];
        if (! empty($bookInfo['pb_keywords_tags'])) {
            $keywords = array_map('trim', explode(',', $bookInfo['pb_keywords_tags']));
        }

        $isPublic = (bool) get_blog_details($blogId)->public;
        $blogMeta = get_site_meta($blogId);
        $inDirectory = ! empty($blogMeta['pb_in_catalog']) && (bool) $blogMeta['pb_in_catalog'][0];

        $document = [
            'id' => "book_{$blogId}",
            'blog_id' => $blogId,
            'title' => $bookInfo['pb_title'] ?? '',
            'subtitle' => $bookInfo['pb_subtitle'] ?? null,
            'authors' => $authors,
            'language' => $bookInfo['pb_language'] ?? null,
            'license' => $bookInfo['pb_book_license'] ?? null,
            'subjects' => $subjects,
            'keywords' => $keywords,
            'word_count' => (int) ($blogMeta['pb_word_count'][0] ?? 0),
            'cover_url' => $bookInfo['pb_cover_image'] ?? null,
            'book_url' => get_blogaddress_by_id($blogId),
            'is_public' => $isPublic,
            'in_directory' => $inDirectory,
            'updated_at' => time(),
        ];

        if ($switched) {
            restore_current_blog();
        }

        return $document;
    }

    public function deleteDocument(int $blogId, int $postId = 0): ?string
    {
        return "book_{$blogId}";
    }
}
```

- [ ] **Step 4: Run the test to verify it passes**

```bash
lando vendor/bin/phpunit --configuration phpunit.xml tests/Unit/BooksIndexerTest.php
```

Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add -A && git commit -m "feat: Add BooksIndexer for book-level metadata"
```

### Task 8: ContributorsIndexer

**Files:**
- Create: `src/Indexing/Indexers/ContributorsIndexer.php`
- Create: `tests/Unit/ContributorsIndexerTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Unit/ContributorsIndexerTest.php`:

```php
<?php

namespace Tests\Unit;

use PressbooksBorges\Indexing\Indexers\ContributorsIndexer;
use Tests\TestCase;

class ContributorsIndexerTest extends TestCase
{
    private ContributorsIndexer $indexer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->indexer = new ContributorsIndexer;
    }

    public function test_get_collection_name(): void
    {
        $this->assertEquals('pb_contributors', $this->indexer->getCollectionName());
    }

    public function test_get_post_types_returns_empty_array(): void
    {
        $this->assertEmpty($this->indexer->getPostTypes());
    }

    public function test_delete_document_returns_contributor_id(): void
    {
        $result = $this->indexer->deleteDocument(0, 0, 42);

        $this->assertEquals('contributor_42', $result);
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

```bash
lando vendor/bin/phpunit --configuration phpunit.xml tests/Unit/ContributorsIndexerTest.php
```

Expected: FAIL — class `ContributorsIndexer` does not exist.

- [ ] **Step 3: Write the implementation**

Create `src/Indexing/Indexers/ContributorsIndexer.php`:

```php
<?php

namespace PressbooksBorges\Indexing\Indexers;

use PressbooksBorges\Indexing\IndexerInterface;

class ContributorsIndexer implements IndexerInterface
{
    public function getCollectionName(): string
    {
        return 'pb_contributors';
    }

    public function getPostTypes(): array
    {
        return [];
    }

    public function transformDocument(int $blogId, int $postId = 0, ?int $termId = null): ?array
    {
        if ($termId === null) {
            return null;
        }

        $term = get_term($termId, 'contributor');

        if (! $term || is_wp_error($term)) {
            return null;
        }

        $contributorTypes = [];
        $termMeta = get_term_meta($term->term_id);
        foreach ($termMeta as $key => $values) {
            if (str_starts_with($key, 'pb_contributor_') && ! empty($values[0])) {
                $contributorTypes[] = str_replace('pb_contributor_', '', $key);
            }
        }

        $document = [
            'id' => "contributor_{$term->term_id}",
            'term_id' => $term->term_id,
            'name' => $term->name,
            'slug' => $term->slug,
            'contributor_type' => $contributorTypes,
            'description' => $term->description ?: null,
            'blog_ids' => [],
            'book_count' => 0,
            'section_count' => 0,
        ];

        return $document;
    }

    public function deleteDocument(int $blogId, int $postId = 0, ?int $termId = null): ?string
    {
        if ($termId === null) {
            return null;
        }

        return "contributor_{$termId}";
    }
}
```

- [ ] **Step 4: Run the test to verify it passes**

```bash
lando vendor/bin/phpunit --configuration phpunit.xml tests/Unit/ContributorsIndexerTest.php
```

Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add -A && git commit -m "feat: Add ContributorsIndexer for contributor taxonomy"
```

---

## Phase 5: Index Job Processor

### Task 9: IndexJobProcessor — queue and process jobs

**Files:**
- Create: `src/Indexing/IndexJobProcessor.php`
- Create: `tests/Unit/IndexJobProcessorTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Unit/IndexJobProcessorTest.php`:

```php
<?php

namespace Tests\Unit;

use PressbooksBorges\Indexing\IndexJobProcessor;
use Tests\TestCase;

class IndexJobProcessorTest extends TestCase
{
    public function test_enqueue_job_inserts_pending_row(): void
    {
        IndexJobProcessor::enqueueJob(1, 'upsert_section', ['post_id' => 42]);

        $job = app('db')->table('pressbooks_borges_index_jobs')
            ->where('blog_id', 1)
            ->where('job_type', 'upsert_section')
            ->first();

        $this->assertNotNull($job);
        $this->assertEquals('pending', $job->status);
        $this->assertEquals(0, $job->attempts);
    }

    public function test_enqueue_job_deduplicates(): void
    {
        IndexJobProcessor::enqueueJob(1, 'upsert_section', ['post_id' => 42]);
        IndexJobProcessor::enqueueJob(1, 'upsert_section', ['post_id' => 42]);

        $count = app('db')->table('pressbooks_borges_index_jobs')
            ->where('blog_id', 1)
            ->where('job_type', 'upsert_section')
            ->count();

        $this->assertEquals(1, $count);
    }

    public function test_mark_failed_increments_attempts(): void
    {
        IndexJobProcessor::enqueueJob(1, 'upsert_section', ['post_id' => 99]);
        $job = app('db')->table('pressbooks_borges_index_jobs')
            ->where('blog_id', 1)
            ->first();

        IndexJobProcessor::markFailed($job->id, 'Something went wrong');

        $updated = app('db')->table('pressbooks_borges_index_jobs')->find($job->id);
        $this->assertEquals('failed', $updated->status);
        $this->assertEquals(1, $updated->attempts);
        $this->assertEquals('Something went wrong', $updated->error_message);
    }

    public function test_mark_completed_sets_status(): void
    {
        IndexJobProcessor::enqueueJob(1, 'upsert_section', ['post_id' => 1]);
        $job = app('db')->table('pressbooks_borges_index_jobs')
            ->where('blog_id', 1)
            ->first();

        IndexJobProcessor::markCompleted($job->id);

        $updated = app('db')->table('pressbooks_borges_index_jobs')->find($job->id);
        $this->assertEquals('completed', $updated->status);
        $this->assertNotNull($updated->processed_at);
    }

    public function test_get_pending_jobs_returns_limited_results(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            IndexJobProcessor::enqueueJob(1, 'upsert_section', ['post_id' => $i]);
        }

        $jobs = IndexJobProcessor::getPendingJobs(3);

        $this->assertCount(3, $jobs);
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

```bash
lando vendor/bin/phpunit --configuration phpunit.xml tests/Unit/IndexJobProcessorTest.php
```

Expected: FAIL — class `IndexJobProcessor` does not exist.

- [ ] **Step 3: Write the implementation**

Create `src/Indexing/IndexJobProcessor.php`:

```php
<?php

namespace PressbooksBorges\Indexing;

class IndexJobProcessor
{
    public static function register(): void
    {
        add_action('pb_borges_index_processor', [self::class, 'processQueue']);

        if (! wp_next_scheduled('pb_borges_index_processor')) {
            wp_schedule_event(time(), 'every_minute', 'pb_borges_index_processor');
        }
    }

    public static function enqueueJob(int $blogId, string $jobType, array $payload = []): void
    {
        $payloadJson = ! empty($payload) ? wp_json_encode($payload) : null;

        $existing = app('db')->table('pressbooks_borges_index_jobs')
            ->where('blog_id', $blogId)
            ->where('job_type', $jobType)
            ->where('status', 'pending')
            ->when($payloadJson, fn ($q) => $q->where('payload', $payloadJson))
            ->first();

        if ($existing) {
            app('db')->table('pressbooks_borges_index_jobs')
                ->where('id', $existing->id)
                ->update(['updated_at' => current_time('mysql', true)]);
            return;
        }

        app('db')->table('pressbooks_borges_index_jobs')->insert([
            'blog_id' => $blogId,
            'job_type' => $jobType,
            'payload' => $payloadJson,
            'status' => 'pending',
            'attempts' => 0,
            'created_at' => current_time('mysql', true),
            'updated_at' => current_time('mysql', true),
        ]);
    }

    public static function getPendingJobs(int $limit = 50): array
    {
        return app('db')->table('pressbooks_borges_index_jobs')
            ->where('status', 'pending')
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get()
            ->all();
    }

    public static function markProcessing(int $jobId): void
    {
        app('db')->table('pressbooks_borges_index_jobs')
            ->where('id', $jobId)
            ->update([
                'status' => 'processing',
                'updated_at' => current_time('mysql', true),
            ]);
    }

    public static function markCompleted(int $jobId): void
    {
        app('db')->table('pressbooks_borges_index_jobs')
            ->where('id', $jobId)
            ->update([
                'status' => 'completed',
                'processed_at' => current_time('mysql', true),
                'updated_at' => current_time('mysql', true),
            ]);
    }

    public static function markFailed(int $jobId, string $errorMessage): void
    {
        $job = app('db')->table('pressbooks_borges_index_jobs')->find($jobId);
        if (! $job) {
            return;
        }

        $settings = get_site_option('pb_borges_settings', []);
        $maxRetries = $settings['max_retries'] ?? 3;
        $newAttempts = $job->attempts + 1;

        if ($newAttempts < $maxRetries) {
            app('db')->table('pressbooks_borges_index_jobs')
                ->where('id', $jobId)
                ->update([
                    'status' => 'pending',
                    'attempts' => $newAttempts,
                    'error_message' => $errorMessage,
                    'updated_at' => current_time('mysql', true),
                ]);
        } else {
            app('db')->table('pressbooks_borges_index_jobs')
                ->where('id', $jobId)
                ->update([
                    'status' => 'failed',
                    'attempts' => $newAttempts,
                    'error_message' => $errorMessage,
                    'processed_at' => current_time('mysql', true),
                    'updated_at' => current_time('mysql', true),
                ]);

            do_action('pb_borges_job_failed', $jobId, $errorMessage);
        }
    }

    public static function processQueue(): void
    {
        $settings = get_site_option('pb_borges_settings', []);
        $batchSize = $settings['batch_size'] ?? 50;

        $jobs = self::getPendingJobs($batchSize);

        foreach ($jobs as $job) {
            self::markProcessing($job->id);
            try {
                self::processJob($job);
                self::markCompleted($job->id);
            } catch (\Throwable $e) {
                self::markFailed($job->id, $e->getMessage());
            }
        }
    }

    private static function processJob(object $job): void
    {
        $client = \PressbooksBorges\Search\TypesenseClient::fromSettings();
        $payload = $job->payload ? json_decode($job->payload, true) : [];

        $indexers = apply_filters('pb_borges_indexers', [
            new Indexers\SectionsIndexer(),
            new Indexers\BooksIndexer(),
            new Indexers\ContributorsIndexer(),
        ]);

        switch ($job->job_type) {
            case 'upsert_section':
                foreach ($indexers as $indexer) {
                    if (in_array('chapter', $indexer->getPostTypes(), true) ||
                        in_array('front-matter', $indexer->getPostTypes(), true)
                    ) {
                        $doc = $indexer->transformDocument($job->blog_id, $payload['post_id'] ?? 0);
                        if ($doc) {
                            $client->getClient()->collections[$indexer->getCollectionName()]->documents->upsert($doc);
                        }
                    }
                }
                break;

            case 'delete_section':
                foreach ($indexers as $indexer) {
                    $docId = $indexer->deleteDocument($job->blog_id, $payload['post_id'] ?? 0);
                    if ($docId) {
                        try {
                            $client->getClient()->collections[$indexer->getCollectionName()]->documents[$docId]->delete();
                        } catch (\Throwable $e) {
                            if (! str_contains($e->getMessage(), '404')) {
                                throw $e;
                            }
                        }
                    }
                }
                break;

            case 'upsert_book':
                foreach ($indexers as $indexer) {
                    if ($indexer->getCollectionName() === 'pb_books') {
                        $doc = $indexer->transformDocument($job->blog_id);
                        if ($doc) {
                            $client->getClient()->collections['pb_books']->documents->upsert($doc);
                        }
                    }
                }
                break;

            case 'delete_book':
                try {
                    $client->getClient()->collections['pb_books']->documents["book_{$job->blog_id}"]->delete();
                } catch (\Throwable $e) {
                    if (! str_contains($e->getMessage(), '404')) {
                        throw $e;
                    }
                }
                try {
                    $client->getClient()->collections['pb_sections']->documents->delete(['filter_by' => "blog_id:{$job->blog_id}"]);
                } catch (\Throwable $e) {
                    if (! str_contains($e->getMessage(), '404')) {
                        throw $e;
                    }
                }
                break;

            case 'upsert_contributor':
                foreach ($indexers as $indexer) {
                    if ($indexer->getCollectionName() === 'pb_contributors') {
                        $doc = $indexer->transformDocument(0, 0, $payload['term_id'] ?? null);
                        if ($doc) {
                            $client->getClient()->collections['pb_contributors']->documents->upsert($doc);
                        }
                    }
                }
                break;

            case 'delete_contributor':
                $termId = $payload['term_id'] ?? null;
                if ($termId) {
                    try {
                        $client->getClient()->collections['pb_contributors']->documents["contributor_{$termId}"]->delete();
                    } catch (\Throwable $e) {
                        if (! str_contains($e->getMessage(), '404')) {
                            throw $e;
                        }
                    }
                }
                break;

            case 'reindex_book':
                do_action('pb_borges_reindex_started', $job->blog_id);
                try {
                    $client->getClient()->collections['pb_sections']->documents->delete(['filter_by' => "blog_id:{$job->blog_id}"]);
                } catch (\Throwable $e) {
                    // Ignore 404s
                }
                $switched = false;
                if (get_current_blog_id() !== $job->blog_id) {
                    switch_to_blog($job->blog_id);
                    $switched = true;
                }
                $postTypes = ['chapter', 'front-matter', 'back-matter', 'glossary'];
                $docs = [];
                foreach ($postTypes as $pt) {
                    $posts = get_posts(['post_type' => $pt, 'posts_per_page' => -1, 'post_status' => 'any']);
                    foreach ($posts as $p) {
                        foreach ($indexers as $indexer) {
                            if (in_array($pt, $indexer->getPostTypes(), true)) {
                                $doc = $indexer->transformDocument($job->blog_id, $p->ID);
                                if ($doc) {
                                    $docs[] = $doc;
                                }
                            }
                        }
                    }
                }
                if (! empty($docs)) {
                    $client->getClient()->collections['pb_sections']->documents->import($docs, ['action' => 'upsert']);
                }
                foreach ($indexers as $indexer) {
                    if ($indexer->getCollectionName() === 'pb_books') {
                        $doc = $indexer->transformDocument($job->blog_id);
                        if ($doc) {
                            $client->getClient()->collections['pb_books']->documents->upsert($doc);
                        }
                    }
                }
                if ($switched) {
                    restore_current_blog();
                }
                do_action('pb_borges_reindex_completed', $job->blog_id);
                break;
        }
    }
}
```

- [ ] **Step 4: Run the test to verify it passes**

```bash
lando vendor/bin/phpunit --configuration phpunit.xml tests/Unit/IndexJobProcessorTest.php
```

Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add -A && git commit -m "feat: Add IndexJobProcessor with queue, dedup, and WP-Cron"
```

---

## Phase 6: Search Service

### Task 10: SearchService — central coordination

**Files:**
- Create: `src/Search/SearchService.php`
- Create: `tests/Unit/SearchServiceTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Unit/SearchServiceTest.php`:

```php
<?php

namespace Tests\Unit;

use PressbooksBorges\Search\SearchService;
use PressbooksBorges\Search\TypesenseClient;
use Tests\TestCase;

class SearchServiceTest extends TestCase
{
    public function test_get_indexers_returns_core_indexers_by_default(): void
    {
        $client = new TypesenseClient(
            [['host' => 'localhost', 'port' => '8108', 'protocol' => 'http']],
            'test-key'
        );
        $service = new SearchService($client);
        $indexers = $service->getIndexers();

        $collectionNames = array_map(fn ($i) => $i->getCollectionName(), $indexers);

        $this->assertContains('pb_sections', $collectionNames);
        $this->assertContains('pb_books', $collectionNames);
        $this->assertContains('pb_contributors', $collectionNames);
    }

    public function test_enqueue_section_job_delegates_to_processor(): void
    {
        $client = new TypesenseClient(
            [['host' => 'localhost', 'port' => '8108', 'protocol' => 'http']],
            'test-key'
        );
        $service = new SearchService($client);

        $service->enqueueUpsertSection(1, 42);

        $job = app('db')->table('pressbooks_borges_index_jobs')
            ->where('blog_id', 1)
            ->where('job_type', 'upsert_section')
            ->first();

        $this->assertNotNull($job);
        $payload = json_decode($job->payload, true);
        $this->assertEquals(42, $payload['post_id']);
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

```bash
lando vendor/bin/phpunit --configuration phpunit.xml tests/Unit/SearchServiceTest.php
```

Expected: FAIL — class `SearchService` does not exist.

- [ ] **Step 3: Write the implementation**

Create `src/Search/SearchService.php`:

```php
<?php

namespace PressbooksBorges\Search;

use PressbooksBorges\Indexing\IndexJobProcessor;
use PressbooksBorges\Indexing\Indexers\BooksIndexer;
use PressbooksBorges\Indexing\Indexers\ContributorsIndexer;
use PressbooksBorges\Indexing\Indexers\SectionsIndexer;

class SearchService
{
    private TypesenseClient $client;

    public function __construct(TypesenseClient $client)
    {
        $this->client = $client;
    }

    public function getClient(): TypesenseClient
    {
        return $this->client;
    }

    public function getIndexers(): array
    {
        return apply_filters('pb_borges_indexers', [
            new SectionsIndexer(),
            new BooksIndexer(),
            new ContributorsIndexer(),
        ]);
    }

    public function enqueueUpsertSection(int $blogId, int $postId): void
    {
        IndexJobProcessor::enqueueJob($blogId, 'upsert_section', ['post_id' => $postId]);
    }

    public function enqueueDeleteSection(int $blogId, int $postId): void
    {
        IndexJobProcessor::enqueueJob($blogId, 'delete_section', ['post_id' => $postId]);
    }

    public function enqueueUpsertBook(int $blogId): void
    {
        IndexJobProcessor::enqueueJob($blogId, 'upsert_book');
    }

    public function enqueueDeleteBook(int $blogId): void
    {
        IndexJobProcessor::enqueueJob($blogId, 'delete_book');
    }

    public function enqueueUpsertContributor(int $termId): void
    {
        IndexJobProcessor::enqueueJob(0, 'upsert_contributor', ['term_id' => $termId]);
    }

    public function enqueueDeleteContributor(int $termId): void
    {
        IndexJobProcessor::enqueueJob(0, 'delete_contributor', ['term_id' => $termId]);
    }

    public function enqueueReindexBook(int $blogId): void
    {
        IndexJobProcessor::enqueueJob($blogId, 'reindex_book');
    }

    public function ensureCollections(): void
    {
        $collections = Collections::all();
        $existingCollections = array_column(
            $this->client->getClient()->collections->retrieve(),
            'name'
        );

        foreach ($collections as $schema) {
            if (! in_array($schema['name'], $existingCollections, true)) {
                $this->client->getClient()->collections->create($schema);
            }
        }
    }
}
```

- [ ] **Step 4: Run the test to verify it passes**

```bash
lando vendor/bin/phpunit --configuration phpunit.xml tests/Unit/SearchServiceTest.php
```

Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add -A && git commit -m "feat: Add SearchService for central coordination"
```

---

## Phase 7: Access Control

### Task 11: KeyGenerator

**Files:**
- Create: `src/Search/KeyGenerator.php`
- Create: `tests/Unit/KeyGeneratorTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Unit/KeyGeneratorTest.php`:

```php
<?php

namespace Tests\Unit;

use PressbooksBorges\Search\KeyGenerator;
use Tests\TestCase;

class KeyGeneratorTest extends TestCase
{
    public function test_generate_search_key_returns_string(): void
    {
        $key = KeyGenerator::generateSearchKey(1);

        $this->assertIsString($key);
        $this->assertNotEmpty($key);
    }

    public function test_generate_search_key_caches_in_transient(): void
    {
        $key1 = KeyGenerator::generateSearchKey(1);
        $key2 = KeyGenerator::generateSearchKey(1);

        $this->assertEquals($key1, $key2);
    }

    public function test_generate_search_key_differs_per_user(): void
    {
        $key1 = KeyGenerator::generateSearchKey(1);
        $key2 = KeyGenerator::generateSearchKey(2);

        $this->assertNotEquals($key1, $key2);
    }

    public function test_build_admin_filter_includes_blog_ids(): void
    {
        $filter = KeyGenerator::buildAdminFilter([1, 5, 12]);

        $this->assertStringContainsString('blog_id:=[1,5,12]', $filter);
    }

    public function test_build_webbook_filter_scopes_to_single_blog(): void
    {
        $filter = KeyGenerator::buildWebbookFilter(5, true);

        $this->assertStringContainsString('blog_id:=5', $filter);
        $this->assertStringContainsString('publish', $filter);
    }

    public function test_build_anonymous_filter_restricts_to_public(): void
    {
        $filter = KeyGenerator::buildAnonymousFilter(5);

        $this->assertStringContainsString('blog_id:=5', $filter);
        $this->assertStringContainsString('publish', $filter);
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

```bash
lando vendor/bin/phpunit --configuration phpunit.xml tests/Unit/KeyGeneratorTest.php
```

Expected: FAIL — class `KeyGenerator` does not exist.

- [ ] **Step 3: Write the implementation**

Create `src/Search/KeyGenerator.php`:

```php
<?php

namespace PressbooksBorges\Search;

class KeyGenerator
{
    public static function generateSearchKey(int $userId, ?int $currentBlogId = null): string
    {
        $settings = get_site_option('pb_borges_settings', []);
        $parentKey = $settings['typesense_search_key'] ?? '';

        $cacheKey = "pb_borges_key_{$userId}_" . md5(json_encode($currentBlogId));
        $cached = get_transient($cacheKey);
        if ($cached !== false) {
            return $cached;
        }

        $filterBy = $currentBlogId !== null
            ? self::buildWebbookFilter($currentBlogId, is_user_logged_in())
            : self::buildAdminFilter(self::getUserBlogIds($userId));

        $scopedKey = self::deriveScopedKey($parentKey, [
            'filter_by' => $filterBy,
            'expires_at' => time() + 3600,
        ]);

        set_transient($cacheKey, $scopedKey, 50 * MINUTE_IN_SECONDS);

        return $scopedKey;
    }

    public static function buildAdminFilter(array $blogIds): string
    {
        $ids = implode(',', $blogIds);

        return "blog_id:=[{$ids}] && post_status:=[publish,private,draft]";
    }

    public static function buildWebbookFilter(int $blogId, bool $isLoggedIn): string
    {
        if ($isLoggedIn) {
            return "blog_id:={$blogId} && post_status:=[publish,web-only]";
        }

        return self::buildAnonymousFilter($blogId);
    }

    public static function buildAnonymousFilter(int $blogId): string
    {
        return "blog_id:={$blogId} && post_status:=publish";
    }

    public static function getUserBlogIds(int $userId): array
    {
        $blogs = get_blogs_of_user($userId);

        return array_map(fn ($blog) => (int) $blog->userblog_id, $blogs);
    }

    private static function deriveScopedKey(string $parentKey, array $parameters): string
    {
        $parameters['expires_at'] = (int) ($parameters['expires_at'] ?? time() + 3600);
        ksort($parameters);

        $base64 = base64_encode(json_encode($parameters));
        $base64 = rtrim($base64, '=');

        $hmac = hash_hmac('sha256', $base64, $parentKey);

        return "{$hmac}{$base64}";
    }
}
```

- [ ] **Step 4: Run the test to verify it passes**

```bash
lando vendor/bin/phpunit --configuration phpunit.xml tests/Unit/KeyGeneratorTest.php
```

Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add -A && git commit -m "feat: Add KeyGenerator for scoped search-only key derivation"
```

---

## Phase 8: REST API

### Task 12: SearchEndpoint REST controller

**Files:**
- Create: `src/Api/SearchEndpoint.php`
- Create: `tests/Feature/SearchEndpointTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/SearchEndpointTest.php`:

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;

class SearchEndpointTest extends TestCase
{
    public function test_search_endpoint_is_registered(): void
    {
        $routes = rest_get_server()->get_routes();

        $this->assertArrayHasKey('/pressbooks-borges/v1/search', $routes);
    }

    public function test_search_endpoint_requires_q_parameter(): void
    {
        $request = new \WP_REST_Request('GET', '/pressbooks-borges/v1/search');

        $response = rest_get_server()->dispatch($request);

        $this->assertEquals(400, $response->get_status());
    }

    public function test_search_endpoint_rejects_short_query(): void
    {
        $request = new \WP_REST_Request('GET', '/pressbooks-borges/v1/search');
        $request->set_param('q', 'a');

        $response = rest_get_server()->dispatch($request);

        $this->assertEquals(400, $response->get_status());
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

```bash
lando vendor/bin/phpunit --configuration phpunit.xml tests/Feature/SearchEndpointTest.php
```

Expected: FAIL — route not registered.

- [ ] **Step 3: Write the implementation**

Create `src/Api/SearchEndpoint.php`:

```php
<?php

namespace PressbooksBorges\Api;

use PressbooksBorges\Search\KeyGenerator;
use PressbooksBorges\Search\TypesenseClient;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

class SearchEndpoint
{
    public static function register(): void
    {
        add_action('rest_api_init', [self::class, 'registerRoutes']);
    }

    public static function registerRoutes(): void
    {
        register_rest_route('pressbooks-borges/v1', '/search', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [self::class, 'handleSearch'],
                'permission_callback' => [self::class, 'permissionCheck'],
            ],
        ]);
    }

    public static function permissionCheck(\WP_REST_Request $request): bool
    {
        return true;
    }

    public static function handleSearch(WP_REST_Request $request): WP_REST_Response
    {
        $q = $request->get_param('q');

        if (empty($q) || strlen($q) < 2) {
            return new WP_REST_Response([
                'code' => 'invalid_query',
                'message' => __('Query must be at least 2 characters.', 'pressbooks-borges'),
            ], 400);
        }

        $collection = $request->get_param('collection') ?? 'all';
        $perPage = min((int) ($request->get_param('per_page') ?? 10), 50);
        $page = max((int) ($request->get_param('page') ?? 1), 1);

        $userId = get_current_user_id();
        $currentBlogId = ($collection === 'webbook') ? get_current_blog_id() : null;

        $client = TypesenseClient::fromSettings();
        $searchKey = $userId
            ? KeyGenerator::generateSearchKey($userId, $currentBlogId)
            : KeyGenerator::generateAnonymousKey(get_current_blog_id());

        $searchRequests = [];
        $validCollections = ['pb_sections', 'pb_books', 'pb_contributors'];

        if ($collection === 'all') {
            $targetCollections = $validCollections;
        } else {
            $targetCollections = in_array("pb_{$collection}", $validCollections, true)
                ? ["pb_{$collection}"]
                : $validCollections;
        }

        foreach ($targetCollections as $col) {
            $searchRequests['searches'][] = [
                'collection' => $col,
                'q' => $q,
                'query_by' => $col === 'pb_sections' ? 'title,content,authors,book_title'
                    : ($col === 'pb_books' ? 'title,subtitle,authors,subjects,keywords'
                    : 'name,description'),
                'per_page' => $perPage,
                'page' => $page,
                'highlight_full_fields' => $col === 'pb_sections' ? 'title,content' : 'name,title',
            ];
        }

        try {
            $results = $client->getClient()->multi_search->perform($searchRequests);
        } catch (\Throwable $e) {
            return new WP_REST_Response([
                'code' => 'search_error',
                'message' => $e->getMessage(),
            ], 500);
        }

        $totalFound = 0;
        $formatted = [];
        foreach (($results['results'] ?? []) as $idx => $result) {
            $colName = $targetCollections[$idx] ?? 'unknown';
            $found = $result['found'] ?? 0;
            $totalFound += $found;
            $formatted[str_replace('pb_', '', $colName)] = [
                'hits' => $result['hits'] ?? [],
                'found' => $found,
            ];
        }

        return new WP_REST_Response([
            'results' => $formatted,
            'total_found' => $totalFound,
            'search_time_ms' => $results['search_time_ms'] ?? 0,
            'page' => $page,
            'per_page' => $perPage,
        ], 200);
    }
}
```

- [ ] **Step 4: Run the test to verify it passes**

```bash
lando vendor/bin/phpunit --configuration phpunit.xml tests/Feature/SearchEndpointTest.php
```

Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add -A && git commit -m "feat: Add REST API search endpoint"
```

---

## Phase 9: Admin Settings

### Task 13: SearchAdmin — network admin settings page

**Files:**
- Create: `src/Admin/SearchAdmin.php`
- Create: `resources/views/admin/settings.blade.php`
- Create: `tests/Feature/SearchAdminTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/SearchAdminTest.php`:

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;

class SearchAdminTest extends TestCase
{
    public function test_default_settings_exist(): void
    {
        $settings = get_site_option('pb_borges_settings');

        $this->assertIsArray($settings);
    }

    public function test_get_default_settings_returns_expected_keys(): void
    {
        $defaults = \PressbooksBorges\Admin\SearchAdmin::getDefaults();

        $this->assertArrayHasKey('enabled_admin', $defaults);
        $this->assertArrayHasKey('enabled_webbook', $defaults);
        $this->assertArrayHasKey('index_private_books', $defaults);
        $this->assertArrayHasKey('index_draft_content', $defaults);
        $this->assertArrayHasKey('max_retries', $defaults);
        $this->assertArrayHasKey('batch_size', $defaults);
        $this->assertTrue($defaults['enabled_admin']);
        $this->assertEquals(3, $defaults['max_retries']);
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

```bash
lando vendor/bin/phpunit --configuration phpunit.xml tests/Feature/SearchAdminTest.php
```

Expected: FAIL — class `SearchAdmin` does not exist.

- [ ] **Step 3: Write the implementation**

Create `src/Admin/SearchAdmin.php`:

```php
<?php

namespace PressbooksBorges\Admin;

class SearchAdmin
{
    private static ?self $instance = null;

    public static function init(): self
    {
        if (! self::$instance) {
            self::$instance = new self;
            self::hooks(self::$instance);
        }
        return self::$instance;
    }

    public static function hooks(self $obj): void
    {
        add_action('network_admin_menu', [$obj, 'addMenu']);
        add_action('admin_init', [$obj, 'registerSettings']);
    }

    public static function getDefaults(): array
    {
        return [
            'typesense_nodes' => '',
            'typesense_admin_key' => '',
            'typesense_search_key' => '',
            'enabled_admin' => true,
            'enabled_webbook' => true,
            'index_private_books' => true,
            'index_draft_content' => false,
            'max_retries' => 3,
            'batch_size' => 50,
        ];
    }

    public function addMenu(): void
    {
        add_submenu_page(
            'settings.php',
            __('Pressbooks Borges Search', 'pressbooks-borges'),
            __('Borges Search', 'pressbooks-borges'),
            'manage_network_options',
            'pb-borges-settings',
            [$this, 'renderSettingsPage']
        );
    }

    public function registerSettings(): void
    {
        register_setting('pb_borges_settings_group', 'pb_borges_settings', [
            'default' => self::getDefaults(),
            'sanitize_callback' => [$this, 'sanitizeSettings'],
        ]);
    }

    public function sanitizeSettings(array $input): array
    {
        $defaults = self::getDefaults();

        return [
            'typesense_nodes' => sanitize_text_field($input['typesense_nodes'] ?? ''),
            'typesense_admin_key' => sanitize_text_field($input['typesense_admin_key'] ?? ''),
            'typesense_search_key' => sanitize_text_field($input['typesense_search_key'] ?? ''),
            'enabled_admin' => ! empty($input['enabled_admin']),
            'enabled_webbook' => ! empty($input['enabled_webbook']),
            'index_private_books' => ! empty($input['index_private_books']),
            'index_draft_content' => ! empty($input['index_draft_content']),
            'max_retries' => absint($input['max_retries'] ?? $defaults['max_retries']),
            'batch_size' => absint($input['batch_size'] ?? $defaults['batch_size']),
        ];
    }

    public function renderSettingsPage(): void
    {
        echo \Pressbooks\Container::get('Blade')->render('PressbooksBorges::admin.settings', [
            'settings' => get_site_option('pb_borges_settings', self::getDefaults()),
            'defaults' => self::getDefaults(),
        ]);
    }
}
```

Create `resources/views/admin/settings.blade.php`:

```blade
<div class="wrap">
    <h1>{{ __('Pressbooks Borges Search Settings', 'pressbooks-borges') }}</h1>

    <form method="post" action="edit.php?action=pb_borges_save_settings">
        @csrf

        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="typesense_nodes">{{ __('Typesense Nodes', 'pressbooks-borges') }}</label>
                </th>
                <td>
                    <input type="text"
                           name="pb_borges_settings[typesense_nodes]"
                           id="typesense_nodes"
                           value="{{ $settings['typesense_nodes'] ?? '' }}"
                           class="regular-text"
                           placeholder="search.example.com:443:https" />
                    <p class="description">
                        {{ __('Comma-separated list of host:port:protocol', 'pressbooks-borges') }}
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="typesense_admin_key">{{ __('Admin API Key', 'pressbooks-borges') }}</label>
                </th>
                <td>
                    <input type="password"
                           name="pb_borges_settings[typesense_admin_key]"
                           id="typesense_admin_key"
                           value="{{ $settings['typesense_admin_key'] ?? '' }}"
                           class="regular-text" />
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="typesense_search_key">{{ __('Search-Only API Key', 'pressbooks-borges') }}</label>
                </th>
                <td>
                    <input type="password"
                           name="pb_borges_settings[typesense_search_key]"
                           id="typesense_search_key"
                           value="{{ $settings['typesense_search_key'] ?? '' }}"
                           class="regular-text" />
                </td>
            </tr>
            <tr>
                <th scope="row">{{ __('Enable in Admin', 'pressbooks-borges') }}</th>
                <td>
                    <input type="checkbox"
                           name="pb_borges_settings[enabled_admin]"
                           value="1"
                           @if(!empty($settings['enabled_admin'])) checked @endif />
                </td>
            </tr>
            <tr>
                <th scope="row">{{ __('Enable in Webbook', 'pressbooks-borges') }}</th>
                <td>
                    <input type="checkbox"
                           name="pb_borges_settings[enabled_webbook]"
                           value="1"
                           @if(!empty($settings['enabled_webbook'])) checked @endif />
                </td>
            </tr>
            <tr>
                <th scope="row">{{ __('Index Private Books', 'pressbooks-borges') }}</th>
                <td>
                    <input type="checkbox"
                           name="pb_borges_settings[index_private_books]"
                           value="1"
                           @if(!empty($settings['index_private_books'])) checked @endif />
                    <p class="description">
                        {{ __('Content is still access-controlled at query time.', 'pressbooks-borges') }}
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">{{ __('Index Draft Content', 'pressbooks-borges') }}</th>
                <td>
                    <input type="checkbox"
                           name="pb_borges_settings[index_draft_content]"
                           value="1"
                           @if(!empty($settings['index_draft_content'])) checked @endif />
                </td>
            </tr>
            <tr>
                <th scope="row">{{ __('Max Retries', 'pressbooks-borges') }}</th>
                <td>
                    <input type="number"
                           name="pb_borges_settings[max_retries]"
                           value="{{ $settings['max_retries'] ?? 3 }}"
                           min="1" max="10" class="small-text" />
                </td>
            </tr>
            <tr>
                <th scope="row">{{ __('Batch Size', 'pressbooks-borges') }}</th>
                <td>
                    <input type="number"
                           name="pb_borges_settings[batch_size]"
                           value="{{ $settings['batch_size'] ?? 50 }}"
                           min="10" max="500" class="small-text" />
                    <p class="description">
                        {{ __('Jobs processed per cron run.', 'pressbooks-borges') }}
                    </p>
                </td>
            </tr>
        </table>

        <h2>{{ __('Index Management', 'pressbooks-borges') }}</h2>
        <p>
            <button type="button" class="button" id="pb-borges-reindex-all">
                {{ __('Reindex All Books', 'pressbooks-borges') }}
            </button>
            <button type="button" class="button" id="pb-borges-create-collections">
                {{ __('Create Collections', 'pressbooks-borges') }}
            </button>
        </p>

        @php
            $failed = app('db')->table('pressbooks_borges_index_jobs')
                ->where('status', 'failed')
                ->count();
        @endphp

        @if($failed > 0)
            <div class="notice notice-warning">
                <p>
                    {{ sprintf(__('%d failed indexing jobs. Check the error details in the jobs table.', 'pressbooks-borges'), $failed) }}
                </p>
            </div>
        @endif

        <?php submit_button(); ?>
    </form>
</div>
```

- [ ] **Step 4: Run the test to verify it passes**

```bash
lando vendor/bin/phpunit --configuration phpunit.xml tests/Feature/SearchAdminTest.php
```

Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add -A && git commit -m "feat: Add network admin settings page"
```

### Task 14: SearchHealthCheck

**Files:**
- Create: `src/Admin/SearchHealthCheck.php`
- Create: `tests/Unit/SearchHealthCheckTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Unit/SearchHealthCheckTest.php`:

```php
<?php

namespace Tests\Unit;

use PressbooksBorges\Admin\SearchHealthCheck;
use Tests\TestCase;

class SearchHealthCheckTest extends TestCase
{
    public function test_health_check_extends_pressbooks_check(): void
    {
        $check = new SearchHealthCheck;

        $this->assertInstanceOf(\Pressbooks\Health\Check::class, $check);
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

```bash
lando vendor/bin/phpunit --configuration phpunit.xml tests/Unit/SearchHealthCheckTest.php
```

Expected: FAIL — class `SearchHealthCheck` does not exist.

- [ ] **Step 3: Write the implementation**

Create `src/Admin/SearchHealthCheck.php`:

```php
<?php

namespace PressbooksBorges\Admin;

use Pressbooks\Health\Check;
use Pressbooks\Health\Result;
use PressbooksBorges\Search\TypesenseClient;

class SearchHealthCheck extends Check
{
    public function run(): Result
    {
        $settings = get_site_option('pb_borges_settings', []);

        if (empty($settings['typesense_nodes'])) {
            return Result::failed('Typesense is not configured.');
        }

        try {
            $client = TypesenseClient::fromSettings();
            $client->getClient()->health->retrieve();

            $pendingJobs = app('db')->table('pressbooks_borges_index_jobs')
                ->where('status', 'pending')
                ->count();

            if ($pendingJobs > 1000) {
                return Result::failed("Typesense is healthy but {$pendingJobs} pending indexing jobs are backlogged.");
            }

            return $pendingJobs > 100
                ? Result::ok("Healthy. {$pendingJobs} pending jobs in queue.")->withData(['pending_jobs' => $pendingJobs])
                : Result::ok('Healthy.')->withData(['pending_jobs' => $pendingJobs]);
        } catch (\Throwable $e) {
            return Result::failed('Typesense connection failed: ' . $e->getMessage());
        }
    }
}
```

- [ ] **Step 4: Run the test to verify it passes**

```bash
lando vendor/bin/phpunit --configuration phpunit.xml tests/Unit/SearchHealthCheckTest.php
```

Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add -A && git commit -m "feat: Add SearchHealthCheck for monitoring"
```

---

## Phase 10: Frontend

### Task 15: SearchBar — asset enqueueing

**Files:**
- Create: `src/Admin/SearchBar.php`
- Create: `tests/Feature/SearchBarTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/SearchBarTest.php`:

```php
<?php

namespace Tests\Feature;

use PressbooksBorges\Admin\SearchBar;
use Tests\TestCase;

class SearchBarTest extends TestCase
{
    public function test_get_config_returns_expected_structure(): void
    {
        update_site_option('pb_borges_settings', [
            'typesense_nodes' => 'search.example.com:443:https',
            'typesense_search_key' => 'test-search-key',
            'enabled_admin' => true,
        ]);

        $config = SearchBar::getConfig(1, null);

        $this->assertArrayHasKey('typesense', $config);
        $this->assertArrayHasKey('collections', $config);
        $this->assertArrayHasKey('context', $config);
        $this->assertEquals('admin', $config['context']);
    }

    public function test_parse_nodes_handles_comma_separated(): void
    {
        $nodes = SearchBar::parseNodes('host1:443:https,host2:443:https');

        $this->assertCount(2, $nodes);
        $this->assertEquals('host1', $nodes[0]['host']);
        $this->assertEquals(443, $nodes[0]['port']);
        $this->assertEquals('https', $nodes[0]['protocol']);
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

```bash
lando vendor/bin/phpunit --configuration phpunit.xml tests/Feature/SearchBarTest.php
```

Expected: FAIL — class `SearchBar` does not exist.

- [ ] **Step 3: Write the implementation**

Create `src/Admin/SearchBar.php`:

```php
<?php

namespace PressbooksBorges\Admin;

use PressbooksBorges\Search\KeyGenerator;

class SearchBar
{
    public static function init(): void
    {
        add_action('admin_bar_menu', [self::class, 'addSearchBar'], 100);
    }

    public static function enqueueAssets(): void
    {
        add_action('admin_enqueue_scripts', [self::class, 'enqueueAdminAssets']);
        add_action('wp_enqueue_scripts', [self::class, 'enqueueWebbookAssets']);
    }

    public static function addSearchBar(\WP_Admin_Bar $wpAdminBar): void
    {
        $settings = get_site_option('pb_borges_settings', []);

        if (empty($settings['typesense_nodes'])) {
            return;
        }

        if (is_admin() && empty($settings['enabled_admin'])) {
            return;
        }

        if (! is_admin() && empty($settings['enabled_webbook'])) {
            return;
        }

        $wpAdminBar->add_node([
            'id' => 'pb-borges-search',
            'title' => '<input type="text" id="pb-borges-search-input" placeholder="' . esc_attr__('Search books and content...', 'pressbooks-borges') . '" />',
            'href' => '#',
        ]);
    }

    public static function enqueueAdminAssets(): void
    {
        $settings = get_site_option('pb_borges_settings', []);
        if (empty($settings['typesense_nodes']) || empty($settings['enabled_admin'])) {
            return;
        }

        self::doEnqueue('admin');
    }

    public static function enqueueWebbookAssets(): void
    {
        if (! function_exists('\\Pressbooks\\Book::isBook') || ! \Pressbooks\Book::isBook()) {
            return;
        }

        $settings = get_site_option('pb_borges_settings', []);
        if (empty($settings['typesense_nodes']) || empty($settings['enabled_webbook'])) {
            return;
        }

        self::doEnqueue('webbook');
    }

    private static function doEnqueue(string $context): void
    {
        $handle = 'pressbooks-borges';

        Vite\enqueue_asset(
            WP_PLUGIN_DIR . '/pressbooks-borges/dist',
            'resources/assets/js/pressbooks-borges.js',
            ['handle' => $handle]
        );

        $userId = get_current_user_id();
        $currentBlogId = $context === 'webbook' ? get_current_blog_id() : null;

        $config = self::getConfig($userId, $currentBlogId);

        wp_localize_script($handle, 'PBBorges', $config);
    }

    public static function getConfig(int $userId, ?int $currentBlogId): array
    {
        $settings = get_site_option('pb_borges_settings', []);

        $apiKey = $userId
            ? KeyGenerator::generateSearchKey($userId, $currentBlogId)
            : KeyGenerator::generateAnonymousKey(get_current_blog_id());

        return [
            'typesense' => [
                'nodes' => self::parseNodes($settings['typesense_nodes'] ?? ''),
                'apiKey' => $apiKey,
                'searchOnly' => true,
            ],
            'collections' => [
                'sections' => 'pb_sections',
                'books' => 'pb_books',
                'contributors' => 'pb_contributors',
            ],
            'context' => $currentBlogId !== null ? 'webbook' : 'admin',
            'currentBlogId' => $currentBlogId ?? get_current_blog_id(),
            'resultsPageUrl' => admin_url('admin.php?page=pb_borges_search'),
        ];
    }

    public static function parseNodes(string $nodesStr): array
    {
        if (empty($nodesStr)) {
            return [];
        }

        return array_map(function (string $node) {
            $parts = explode(':', $node, 3);
            return [
                'host' => $parts[0] ?? 'localhost',
                'port' => (int) ($parts[1] ?? 443),
                'protocol' => $parts[2] ?? 'https',
            ];
        }, array_filter(array_map('trim', explode(',', $nodesStr))));
    }
}
```

- [ ] **Step 4: Run the test to verify it passes**

```bash
lando vendor/bin/phpunit --configuration phpunit.xml tests/Feature/SearchBarTest.php
```

Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add -A && git commit -m "feat: Add SearchBar with asset enqueueing and config"
```

### Task 16: Frontend JavaScript — search bar and results page

**Files:**
- Modify: `resources/assets/js/pressbooks-borges.js`
- Create: `resources/assets/styles/pressbooks-borges.css`

- [ ] **Step 1: Write the JavaScript**

Replace `resources/assets/js/pressbooks-borges.js` with:

```js
import '../styles/pressbooks-borges.css';
import TypesenseInstantSearchAdapter from 'typesense-instantsearch-adapter';
import { searchBox, hits, pagination, refinementList, stats, configure } from 'instantsearch.js/es/widgets';
import instantsearch from 'instantsearch.js';

document.addEventListener('DOMContentLoaded', () => {
    if (typeof PBBorges === 'undefined' || ! PBBorges.typesense.nodes.length) {
        return;
    }

    const typesenseInstantsearchAdapter = new TypesenseInstantSearchAdapter({
        server: {
            nodes: PBBorges.typesense.nodes,
            apiKey: PBBorges.typesense.apiKey,
        },
        additionalSearchParameters: {
            query_by: 'title,content,authors,book_title',
        },
    });

    const searchClient = typesenseInstantsearchAdapter.searchClient;

    // Dropdown search (admin bar)
    const searchInput = document.getElementById('pb-borges-search-input');
    const dropdown = document.getElementById('pb-borges-dropdown');

    if (searchInput && dropdown) {
        let debounceTimer;

        searchInput.addEventListener('input', (e) => {
            clearTimeout(debounceTimer);
            const query = e.target.value.trim();

            if (query.length < 2) {
                dropdown.innerHTML = '';
                dropdown.classList.remove('active');
                return;
            }

            debounceTimer = setTimeout(async () => {
                try {
                    const results = await searchClient.search([
                        {
                            indexName: 'pb_contributors',
                            params: { query, hitsPerPage: 3 },
                        },
                        {
                            indexName: 'pb_books',
                            params: { query, hitsPerPage: 3 },
                        },
                        {
                            indexName: 'pb_sections',
                            params: { query, hitsPerPage: 5 },
                        },
                    ]);

                    renderDropdown(results.results);
                } catch (err) {
                    console.error('Borges search error:', err);
                }
            }, 300);
        });

        searchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                dropdown.classList.remove('active');
                searchInput.blur();
            }
        });

        document.addEventListener('click', (e) => {
            if (! dropdown.contains(e.target) && e.target !== searchInput) {
                dropdown.classList.remove('active');
            }
        });
    }

    function renderDropdown(results) {
        if (! dropdown) return;
        let html = '';

        const contributors = results[0]?.hits ?? [];
        const books = results[1]?.hits ?? [];
        const sections = results[2]?.hits ?? [];

        if (contributors.length) {
            html += '<div class="pb-borges-group"><div class="pb-borges-group-label">People</div>';
            contributors.forEach((hit) => {
                html += `<div class="pb-borges-hit pb-borges-hit--contributor">
                    <span class="pb-borges-icon">&#128100;</span>
                    <div class="pb-borges-hit-body">
                        <div class="pb-borges-hit-title">${hit._highlightResult?.name?.value ?? hit.document.name}</div>
                        <div class="pb-borges-hit-meta">${(hit.document.contributor_type ?? []).join(', ')} &middot; ${hit.document.book_count ?? 0} books</div>
                    </div>
                </div>`;
            });
            html += '</div>';
        }

        if (books.length) {
            html += '<div class="pb-borges-group"><div class="pb-borges-group-label">Books</div>';
            books.forEach((hit) => {
                html += `<div class="pb-borges-hit pb-borges-hit--book">
                    <span class="pb-borges-icon">&#128214;</span>
                    <div class="pb-borges-hit-body">
                        <div class="pb-borges-hit-title">${hit._highlightResult?.title?.value ?? hit.document.title}</div>
                        <div class="pb-borges-hit-meta">${(hit.document.authors ?? []).join(', ')}</div>
                    </div>
                </div>`;
            });
            html += '</div>';
        }

        if (sections.length) {
            html += '<div class="pb-borges-group"><div class="pb-borges-group-label">Sections</div>';
            sections.forEach((hit) => {
                const snippet = hit._highlightResult?.content?.value ?? '';
                html += `<div class="pb-borges-hit pb-borges-hit--section">
                    <span class="pb-borges-icon">&#128214;</span>
                    <div class="pb-borges-hit-body">
                        <div class="pb-borges-hit-title">${hit._highlightResult?.title?.value ?? hit.document.title}</div>
                        <div class="pb-borges-hit-meta">${hit.document.book_title ?? ''} &middot; ${hit.document.post_type}</div>
                        ${snippet ? `<div class="pb-borges-hit-snippet">${snippet}</div>` : ''}
                    </div>
                </div>`;
            });
            html += '</div>';
        }

        const totalFound = (results[0]?.found ?? 0) + (results[1]?.found ?? 0) + (results[2]?.found ?? 0);

        if (totalFound === 0) {
            html = '<div class="pb-borges-empty">No results found.</div>';
        } else {
            html += `<div class="pb-borges-see-all">
                <a href="${PBBorges.resultsPageUrl}&q=${encodeURIComponent(searchInput.value)}">See all ${totalFound} results &rarr;</a>
            </div>`;
        }

        dropdown.innerHTML = html;
        dropdown.classList.add('active');
    }

    // Full-page search (if on search results page)
    const searchPage = document.getElementById('pb-borges-search-page');
    if (searchPage) {
        const urlParams = new URLSearchParams(window.location.search);
        const initialQuery = urlParams.get('q') ?? '';

        const search = instantsearch({
            searchClient,
            indexName: 'pb_sections',
        });

        search.addWidgets([
            searchBox({
                container: '#pb-borges-searchbox',
                queryHook(query, search) {
                    search(query);
                },
            }),
            stats({
                container: '#pb-borges-stats',
            }),
            refinementList({
                container: '#pb-borges-filter-post-type',
                attribute: 'post_type',
            }),
            refinementList({
                container: '#pb-borges-filter-book',
                attribute: 'book_title',
                searchable: true,
            }),
            refinementList({
                container: '#pb-borges-filter-authors',
                attribute: 'authors',
                searchable: true,
            }),
            refinementList({
                container: '#pb-borges-filter-license',
                attribute: 'section_license',
            }),
            hits({
                container: '#pb-borges-hits',
                templates: {
                    item(hit) {
                        const snippet = hit._highlightResult?.content?.value ?? '';
                        return `<div class="pb-borges-result">
                            <h3>${hit._highlightResult?.title?.value ?? hit.title}</h3>
                            <div class="pb-borges-result-meta">${hit.book_title ?? ''} &middot; ${hit.post_type}</div>
                            ${snippet ? `<p class="pb-borges-result-snippet">${snippet}</p>` : ''}
                        </div>`;
                    },
                },
            }),
            pagination({
                container: '#pb-borges-pagination',
            }),
        ]);

        search.start();

        if (initialQuery) {
            const input = document.querySelector('#pb-borges-searchbox input');
            if (input) {
                input.value = initialQuery;
                input.dispatchEvent(new Event('input'));
            }
        }
    }
});
```

- [ ] **Step 2: Write the CSS**

Replace `resources/assets/styles/pressbooks-borges.css` with:

```css
/* Dropdown */
#pb-borges-dropdown {
    display: none;
    position: absolute;
    top: 100%;
    right: 0;
    width: 400px;
    max-height: 500px;
    overflow-y: auto;
    background: #fff;
    border: 1px solid #ddd;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    z-index: 100000;
    font-size: 13px;
}

#pb-borges-dropdown.active {
    display: block;
}

.pb-borges-group {
    border-bottom: 1px solid #eee;
}

.pb-borges-group-label {
    padding: 8px 12px 4px;
    font-size: 11px;
    font-weight: 600;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.pb-borges-hit {
    display: flex;
    gap: 8px;
    padding: 8px 12px;
    cursor: pointer;
    border-bottom: 1px solid #f5f5f5;
}

.pb-borges-hit:hover {
    background: #f0f6fc;
}

.pb-borges-icon {
    flex-shrink: 0;
    font-size: 16px;
}

.pb-borges-hit-title {
    font-weight: 500;
    color: #1d2327;
}

.pb-borges-hit-title mark {
    background: #ffd660;
    padding: 0 1px;
}

.pb-borges-hit-meta {
    font-size: 11px;
    color: #666;
    margin-top: 2px;
}

.pb-borges-hit-snippet {
    font-size: 12px;
    color: #50575e;
    margin-top: 4px;
    line-height: 1.4;
}

.pb-borges-hit-snippet mark {
    background: #ffd660;
    padding: 0 1px;
}

.pb-borges-empty {
    padding: 20px 12px;
    text-align: center;
    color: #666;
}

.pb-borges-see-all {
    padding: 10px 12px;
    text-align: center;
    border-top: 1px solid #eee;
}

.pb-borges-see-all a {
    color: #2271b1;
    text-decoration: none;
    font-weight: 500;
}

.pb-borges-see-all a:hover {
    text-decoration: underline;
}

/* Search results page */
#pb-borges-search-page {
    display: flex;
    gap: 24px;
    max-width: 1200px;
    margin: 20px auto;
    padding: 0 20px;
}

#pb-borges-filters {
    width: 250px;
    flex-shrink: 0;
}

#pb-borges-filters h3 {
    font-size: 13px;
    font-weight: 600;
    margin-bottom: 8px;
    color: #1d2327;
}

#pb-borges-results {
    flex: 1;
}

.pb-borges-result {
    padding: 16px 0;
    border-bottom: 1px solid #eee;
}

.pb-borges-result h3 {
    margin: 0 0 4px;
    font-size: 16px;
}

.pb-borges-result h3 a {
    color: #2271b1;
    text-decoration: none;
}

.pb-borges-result h3 a:hover {
    text-decoration: underline;
}

.pb-borges-result-meta {
    font-size: 12px;
    color: #666;
    margin-bottom: 4px;
}

.pb-borges-result-snippet {
    font-size: 13px;
    color: #50575e;
    line-height: 1.5;
    margin: 4px 0 0;
}

.pb-borges-result-snippet mark {
    background: #ffd660;
    padding: 0 1px;
}

/* Responsive */
@media (max-width: 768px) {
    #pb-borges-dropdown {
        position: fixed;
        top: 32px;
        left: 0;
        right: 0;
        width: 100%;
    }

    #pb-borges-search-page {
        flex-direction: column;
    }

    #pb-borges-filters {
        width: 100%;
    }
}
```

- [ ] **Step 3: Build assets**

```bash
cd /Users/arzola/code/pbdev/web/app/plugins/pressbooks-borges && npm run build
```

Expected: Build succeeds, output in `dist/`.

- [ ] **Step 4: Commit**

```bash
git add -A && git commit -m "feat: Add frontend search bar and results page JS/CSS"
```

### Task 17: Search results page template and controller

**Files:**
- Create: `resources/views/search-results.blade.php`
- Create: `src/Controllers/SearchResultsController.php`

- [ ] **Step 1: Create the Blade template**

Create `resources/views/search-results.blade.php`:

```blade
<div class="wrap">
    <h1>{{ __('Search', 'pressbooks-borges') }}</h1>

    <div id="pb-borges-search-page">
        <div id="pb-borges-filters">
            <h3>{{ __('Post Type', 'pressbooks-borges') }}</h3>
            <div id="pb-borges-filter-post-type"></div>

            <h3>{{ __('Book', 'pressbooks-borges') }}</h3>
            <div id="pb-borges-filter-book"></div>

            <h3>{{ __('Author', 'pressbooks-borges') }}</h3>
            <div id="pb-borges-filter-authors"></div>

            <h3>{{ __('License', 'pressbooks-borges') }}</h3>
            <div id="pb-borges-filter-license"></div>
        </div>

        <div id="pb-borges-results">
            <div id="pb-borges-searchbox"></div>
            <div id="pb-borges-stats"></div>
            <div id="pb-borges-hits"></div>
            <div id="pb-borges-pagination"></div>
        </div>
    </div>
</div>
```

- [ ] **Step 2: Create the controller**

Create `src/Controllers/SearchResultsController.php`:

```php
<?php

namespace PressbooksBorges\Controllers;

class SearchResultsController extends BaseController
{
    public function render(): void
    {
        echo $this->renderView('search-results');
    }
}
```

- [ ] **Step 3: Commit**

```bash
git add -A && git commit -m "feat: Add search results page template and controller"
```

---

## Phase 11: Bootstrap Integration

### Task 18: Wire everything together in Bootstrap

**Files:**
- Modify: `src/Bootstrap.php`

- [ ] **Step 1: Update Bootstrap.php**

Replace `src/Bootstrap.php` with:

```php
<?php

namespace PressbooksBorges;

use Pressbooks\Container;
use PressbooksBorges\Admin\SearchAdmin;
use PressbooksBorges\Admin\SearchBar;
use PressbooksBorges\Api\SearchEndpoint;
use PressbooksBorges\Indexing\IndexJobProcessor;
use PressbooksBorges\Search\SearchService;
use PressbooksBorges\Search\TypesenseClient;

final class Bootstrap
{
    private static ?Bootstrap $instance = null;

    public static function run(): void
    {
        if (! self::$instance) {
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
            return new SearchService(new TypesenseClient(
                nodes: [],
                adminApiKey: ''
            ));
        }, 'singleton');
    }

    private function registerActions(): void
    {
        $settings = get_site_option('pb_borges_settings', []);

        if (empty($settings['typesense_nodes'])) {
            return;
        }

        IndexJobProcessor::register();
        SearchEndpoint::register();

        $this->registerIndexingHooks();
    }

    private function registerIndexingHooks(): void
    {
        $settings = get_site_option('pb_borges_settings', []);
        $search = fn () => Container::get('Borges\Search');

        $indexedPostTypes = ['chapter', 'front-matter', 'back-matter', 'glossary'];

        add_action('save_post', function (int $postId, \WP_Post $post) use ($search, $indexedPostTypes, $settings) {
            if (! in_array($post->post_type, $indexedPostTypes, true)) {
                return;
            }
            if ($post->post_status === 'draft' && empty($settings['index_draft_content'])) {
                return;
            }
            $blogId = get_current_blog_id();
            $search()->enqueueUpsertSection($blogId, $postId);
        }, 10, 2);

        add_action('delete_post', function (int $postId) use ($search) {
            $post = get_post($postId);
            if (! $post) {
                return;
            }
            $indexedPostTypes = ['chapter', 'front-matter', 'back-matter', 'glossary'];
            if (in_array($post->post_type, $indexedPostTypes, true)) {
                $search()->enqueueDeleteSection(get_current_blog_id(), $postId);
            }
        });

        add_action('wp_initialize_site', function (\WP_Site $site) use ($search) {
            $search()->enqueueReindexBook($site->blog_id);
        });

        add_action('wp_update_site', function (\WP_Site $newSite) use ($search) {
            $search()->enqueueUpsertBook($newSite->blog_id);
        });

        add_action('wp_delete_site', function (\WP_Site $oldSite) use ($search) {
            $search()->enqueueDeleteBook($oldSite->blog_id);
        });

        add_action('edited_term', function (int $termId, int $ttId, string $taxonomy) use ($search) {
            if ($taxonomy === 'contributor') {
                $search()->enqueueUpsertContributor($termId);
            }
        }, 10, 3);

        add_action('created_term', function (int $termId, int $ttId, string $taxonomy) use ($search) {
            if ($taxonomy === 'contributor') {
                $search()->enqueueUpsertContributor($termId);
            }
        }, 10, 3);

        add_action('delete_term', function (int $term, int $ttId, string $taxonomy) use ($search) {
            if ($taxonomy === 'contributor') {
                $search()->enqueueDeleteContributor($term);
            }
        }, 10, 3);
    }

    private function registerMenus(): void
    {
        SearchAdmin::init();
    }

    private function enqueueScripts(): void
    {
        SearchBar::init();
        SearchBar::enqueueAssets();
    }
}
```

- [ ] **Step 2: Run all tests to verify nothing is broken**

```bash
lando vendor/bin/phpunit --configuration phpunit.xml
```

Expected: All tests PASS.

- [ ] **Step 3: Commit**

```bash
git add -A && git commit -m "feat: Wire all components together in Bootstrap"
```

---

## Phase 12: Final Verification

### Task 19: Run full test suite and linting

- [ ] **Step 1: Run PHP linting**

```bash
cd /Users/arzola/code/pbdev/web/app/plugins/pressbooks-borges
composer standards
```

Expected: No errors.

- [ ] **Step 2: Run all PHP tests**

```bash
lando vendor/bin/phpunit --configuration phpunit.xml
```

Expected: All tests PASS.

- [ ] **Step 3: Run JS linting**

```bash
npm run lint
```

Expected: No errors.

- [ ] **Step 4: Run JS build**

```bash
npm run build
```

Expected: Build succeeds.

- [ ] **Step 5: Final commit if any fixes were needed**

```bash
git add -A && git commit -m "fix: Lint and test fixes"
```
