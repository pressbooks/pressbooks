# Testing Guide

## Framework Stack

| Tool | Purpose |
|------|---------|
| **Codeception** | Test runner and framework |
| **WP Browser** | WordPress-specific Codeception module (^3.0) |
| **PHPUnit 9.x** | Test assertions and lifecycle |
| **Yoast PHPUnit Polyfills** | Compatibility layer (^1.1) |

Configuration: `codeception.dist.yml`, `phpunit.xml`
Environment: `.env.testing`

## Test Suites

| Suite | Purpose | Config |
|-------|---------|--------|
| `unit` | Isolated logic, no WordPress | `tests/unit.suite.yml` |
| `wpunit` | WordPress integration tests | `tests/wpunit.suite.yml` |
| `functional` | Functional/HTTP tests | `tests/functional.suite.yml` |
| `acceptance` | Browser-based acceptance | `tests/acceptance.suite.yml` |

## File Conventions

- **Location**: `tests/` directory
- **Naming**: `test-{feature}.php` (e.g., `test-book.php`, `test-export.php`)
- **Base class**: `\WP_UnitTestCase`
- **Helper trait**: `utilsTrait` — provides test utilities across many test files
- **Annotations**: `@group {module}` for categorization

## Running Tests

```bash
# All tests
composer test

# With coverage report (Clover XML + HTML)
composer test-coverage

# Specific test file
vendor/bin/phpunit --configuration phpunit.xml tests/test-book.php

# Specific test method
vendor/bin/phpunit --configuration phpunit.xml --filter test_method_name

# Specific group
vendor/bin/phpunit --configuration phpunit.xml --group export
```

## Test Structure

```php
<?php

/**
 * @group book
 */
class BookTest extends \WP_UnitTestCase {

    use utilsTrait;

    /**
     * Set up test fixtures.
     */
    public function set_up(): void {
        parent::set_up();
        // Test setup
    }

    /**
     * Tear down test fixtures.
     */
    public function tear_down(): void {
        // Cleanup
        parent::tear_down();
    }

    public function test_book_structure_returns_expected_format(): void {
        // Arrange
        $book_id = $this->factory()->post->create(['post_type' => 'chapter']);

        // Act
        $result = Book::getBookStructure();

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('front-matter', $result);
    }

    /**
     * @group export
     */
    public function test_export_generates_valid_epub(): void {
        // ...
    }
}
```

## WordPress Test Factories

```php
// Create posts
$post_id = $this->factory()->post->create([
    'post_type' => 'chapter',
    'post_title' => 'Test Chapter',
    'post_status' => 'publish',
]);

// Create users
$user_id = $this->factory()->user->create([
    'role' => 'administrator',
]);

// Create terms
$term_id = $this->factory()->term->create([
    'taxonomy' => 'front-matter-type',
]);

// Create multiple
$post_ids = $this->factory()->post->create_many(5, [
    'post_type' => 'chapter',
]);
```

## Mocking

### External Services

Always mock external dependencies — never make real HTTP calls in tests:

```php
// Mock HTTP responses
add_filter('pre_http_request', function ($preempt, $args, $url) {
    if (str_contains($url, 'api.docraptor.com')) {
        return [
            'response' => ['code' => 200],
            'body' => 'mock-pdf-content',
        ];
    }
    return $preempt;
}, 10, 3);
```

### Services to Mock

- **DocRaptor/PrinceXML** — PDF generation
- **AWS SDK** — S3, SES, etc.
- **HTTP requests** — External API calls
- **File system** — Use WordPress temp directory functions only
- **Email** — Use `wp_mail` filter to capture

## Multisite Testing

```php
public function test_network_option_persists(): void {
    update_site_option('pressbooks_setting', 'value');
    $this->assertEquals('value', get_site_option('pressbooks_setting'));
}

public function test_switch_to_blog_restores_context(): void {
    $original_blog = get_current_blog_id();
    $new_blog = $this->factory()->blog->create();

    switch_to_blog($new_blog);
    // Assertions for the new blog context
    restore_current_blog();

    $this->assertEquals($original_blog, get_current_blog_id());
}
```

## Coverage

### Requirements

- PRs that reduce overall coverage are **not accepted**
- Coverage reports: `coverage.xml` (Clover), `coverage-reports/` (HTML)
- CI: GitHub Actions with Codecov integration

### Generating Reports

```bash
composer test-coverage
# Generates: coverage.xml (Clover format)
# Generates: coverage-reports/ (HTML browseable report)
```

### What to Cover

- All public methods of new classes
- Hook callbacks with significant logic
- Edge cases: empty input, null values, boundary conditions
- Error paths: invalid data, missing dependencies, permission failures
- Multisite scenarios: network admin vs. site admin, cross-blog operations

## CI/CD Matrix

Tests run automatically via GitHub Actions on every commit:

| Dimension | Values |
|-----------|--------|
| PHP | 8.3 (production), 8.4 (latest) |
| WordPress | 6.8.3 (minimum), latest |

### CI Steps

1. Install dependencies (`composer install`)
2. Run coding standards (`composer standards`)
3. Run tests (`composer test`)
4. Upload coverage to Codecov

## Test Organization by Module

| Module | Test File Pattern | Group |
|--------|-------------------|-------|
| Book structure | `test-book.php` | `book` |
| Export (general) | `test-export.php` | `export` |
| Export (EPUB) | `test-epub.php` | `export`, `epub` |
| Export (PDF) | `test-pdf.php` | `export`, `pdf` |
| Import | `test-import.php` | `import` |
| Admin | `test-admin-*.php` | `admin` |
| API | `test-api-*.php` | `api` |
| Users | `test-users.php` | `users` |
| Metadata | `test-metadata.php` | `metadata` |

## Common Test Utilities (utilsTrait)

The `utilsTrait` provides shared helpers used across test files. Check the trait source for available methods — common patterns include:

- Creating book structures with parts and chapters
- Setting up test users with specific capabilities
- Generating test content with metadata
- Filesystem helpers for export/import tests
