---
description: "Use when writing or modifying test files in Pressbooks. Covers Codeception, WP_UnitTestCase, test patterns, mocking, coverage, and multisite test scenarios."
applyTo: "tests/**"
---

# Testing Conventions

## Framework
- Codeception + WP Browser (configured in `codeception.dist.yml`)
- Test suites: `unit`, `wpunit`, `functional`, `acceptance`
- Environment: `.env.testing`

## Test Files
- Location: `tests/` directory
- Naming: `test-{feature}.php`
- Base class: `\WP_UnitTestCase`
- Helper trait: `utilsTrait` provides test utilities

## Structure
```php
/**
 * @group {module-name}
 */
class FeatureTest extends \WP_UnitTestCase {
    use utilsTrait;

    public function test_method_does_expected_thing(): void {
        // Arrange
        // Act
        // Assert
    }
}
```

## Conventions
- Test method naming: `test_{descriptive_scenario_name}()`
- Use `@group` annotations for categorization
- One assertion concept per test (multiple assertions OK if testing one behavior)
- Use WordPress test factories: `$this->factory()->post->create()`, `$this->factory()->user->create()`
- Mock external dependencies (HTTP calls, AWS, DocRaptor, PrinceXML)
- Test both success and failure paths
- Test edge cases and boundary conditions

## Multisite Scenarios
- Test network admin vs. site admin contexts
- Test `switch_to_blog()` / `restore_current_blog()` pairs
- Test network options vs. site options
- Verify behavior across different site contexts

## Running Tests
```bash
composer test              # All tests
composer test-coverage     # With coverage (clover + HTML)
```

## Coverage
- PRs that reduce coverage are not accepted
- Coverage report: `coverage.xml` (Clover), `coverage-reports/` (HTML)
- CI: GitHub Actions with Codecov integration
