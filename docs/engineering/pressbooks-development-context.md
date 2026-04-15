# Pressbooks Development Context
## Project Overview
This is a **WordPress Multisite development environment** for Pressbooks, an open-source book publishing platform. You're working with multiple interconnected plugins and themes:

- **Core Plugin**: `pressbooks/` - Main plugin with namespace `Pressbooks\`
- **Extension Plugins**: `pressbooks-lti/`, `pressbooks-multi-institution/`, `pressbooks-cas-sso/`, etc.
- **Book Themes**: `pressbooks-book/`, `pressbooks-malala/` - Frontend book rendering

Pressbooks is a WordPress multisite plugin for creating and publishing books. It enables users to publish books to the public web and produce exports in multiple formats, including EPUB, PDF, and various XML flavours. Pressbooks makes significant changes to the admin interface, web presentation layer and export routines of a standard WordPress installation, and uses the Classic Editor (rather than Gutenberg). Pressbooks is free and open source software, released under the GPL v3.0 license. Source code is hosted on [GitHub](https://github.com/pressbooks/).

## Development Environments
- Use Lando/Docker for local development. Do NOT assume standard LAMP/WordPress setup -- this is a specialized multisite environment. Full configuration details (including lando.yml file) available in https://github.com/pressbooks/setup-development-environment (for internal team members) or https://github.com/pressbooks/local-dev-environment (for open source contributors).
- Use bedrock for dependency management. Full configuration details available in https://github.com/pressbooks/pressbooksedu-bedrock (for internal team members) or https://github.com/pressbooks/pressbooksoss-bedrock (for open source contributors).
- Run `lando start` to spin up local development environment

## Quick Start Checklist
- [ ] Start environment: `lando start`
- [ ] Update to latest version of bedrock: `lando composer update`
- [ ] In the repo you're working in: `composer install && npm install`
- [ ] Build assets: `npm run build`
- [ ] Before commit: `composer fix && composer standards && npm run lint` and fix any linting/standards issues
- [ ] Before commit: `composer test` and fix any broken tests

### Key Patterns
- WordPress multisite architecture
- Uses `$wpdb->base_prefix` for network-wide tables
- Hooks into WordPress native actions (e.g., `activated_plugin`, `deactivated_plugin`)
- PSR-4 autoloading with namespaces
- Blade templates for admin views (via Laravel Blade)

## Architecture
This workspace contains the core plugin, themes, and various network-level plugins that comprise our enterprise offering of Pressbooks.
Plugins code are developed and organized in laravel style ways. Look for pressbooks-results-for-lms plugin for a good example of modern plugin folder structure, service provider pattern, and blade templates, etc.

### Repository Structure
- **/plugins/pressbooks**: Core plugin - main functionality
- **/plugins/pressbooks-***: Various plugins for network-level features (SSO, analytics, stats, etc.)
- **/themes/pressbooks-aldine**: Aldine, the default theme for the network root site (i.e. network homepage)
- **/themes/pressbooks-book**: McLuhan, the default book theme
- **/themes/pressbooks-**: Additional book themes (nearly all are child themes of McLuhan)

### Key Files to Reference in Core Pressbooks Plugin

- [inc/class-book.php](inc/class-book.php) - Book structure, content organization
- [inc/class-container.php](inc/class-container.php) - Dependency Injection container wrapper
- [inc/class-serviceprovider.php](inc/class-serviceprovider.php) - Service registration
- [functions.php](functions.php) - Template helper functions
- [.github/CONTRIBUTING.md](.github/CONTRIBUTING.md) - Full contributor guidelines

### Key Architectural Patterns

**Dependency Injection Container**: Pressbooks uses Laravel's Illuminate Container for service management. Access via:
```php
\Pressbooks\Container::get('Blade')  // Get service
\Pressbooks\Container::set('key', $val)  // Register service
app('ServiceName')  // Alternative using helper function
```

**Service Provider Pattern**: See [inc/class-serviceprovider.php](inc/class-serviceprovider.php). Services registered include: `Blade`, `Sass`, `GlobalTypography`, `Styles`, `ScopedStyles`, `db` (Eloquent)

**Blade Templating**: Uses Laravel Blade for views with namespaced templates:
```php
$blade = Container::get('Blade');
$blade->addNamespace('PressbooksLti', __DIR__ . '/resources/views');
$blade->render('PressbooksLti::settings._roles', $data);
```

**Database**: Uses Illuminate Eloquent ORM, not raw WordPress queries for complex data models

### WordPress Multisite Specifics

- **Network vs. Site**: Core pressbooks functionality is network-activated; individual "books" are sites
- **switch_to_blog()**: Common pattern for working across sites:
  ```php
  switch_to_blog($blog_id);
  // Do work
  restore_current_blog();
  ```
- **Book Structure**: Uses custom post types (front-matter, back-matter, chapter, part) organized via [inc/class-book.php](inc/class-book.php)
- Use `is_network_admin()` for network admin screens
- Network settings stored with `get_site_option()`

### Export System

Books export to multiple formats (PDF, EPUB, XHTML, XML) using PrinceXML (PDF) and custom exporters. See `inc/modules/export/` directory.

### Plugin Communication

- **Hooks system**: Extensive use of WordPress actions/filters with `pb_` or `pressbooks_` prefixes
- **Service container**: Shared services across plugins via Container pattern
- **Network integrations**: Many plugins add pages under unified "Network Integrations" menu (see [inc/admin/dashboard/namespace.php](inc/admin/dashboard/namespace.php))

### Dependency Management

**Always run after checkout**:
```bash
composer install  # PHP dependencies
npm install       # JavaScript dependencies
npm run build     # Compile assets
```

**For new plugin development**: Most newer plugins require `composer install` before activation to load Composer autoloader.

## Code Conventions
### PHP Coding Standards
### General Rule
Different Pressbooks repositories may use different coding standards and tooling.
Always inspect the repository you are working in and match its configured standard.

### `pressbooks/pressbooks` (core plugin)
The core plugin uses Pressbooks Coding Standards based on:
- Human Made Coding Standards
- WordPress Coding Standards
- Pressbooks-specific rules

Reference:
- `https://github.com/pressbooks/coding-standards/blob/production/Pressbooks/ruleset.xml`

Typical commands:
```bash
composer fix
composer standards
```
Underlying tools:
- composer fix → vendor/bin/phpcbf
- composer standards → vendor/bin/phpcs

When working in core:
- prioritize consistency with surrounding code
- be careful not to impose conventions from newer plugins
- prefer established core patterns unless there is a strong reason not to

### Other repositories

Some newer plugins may use Laravel Pint / PSR-12 style and different project structures.
Do not assume those standards apply to core.

### Language & Structure
- `declare(strict_types=1);` is encouraged for new code, but may not be present everywhere
- Use PSR-4 autoloading, with all namespaced code under \Pressbooks
- Use PHP namespaces for all new non-template code.
- Prefer real objects with clear responsibilities
    - Do not create static utility classes
    - If a class would only contain static methods, write a library of functions instead.
    - If a construct does not represent an object (for example, it mirrors core classes like \WP_User, \WP_Query, or \WP_Dependencies), prefer functions over classes.
  - Prefer services over hook-heavy logic. WordPress hooks should delegate quickly to domain or service code.

#### Style
- Use `camelCase` for class methods and properties.
- Use `UPPERCASE` for class constants.
- Use `snake_case` at WordPress boundaries (hooks, filters, globals, and stored data). 
- Methods should be private by default; use `protected` or `public` only when required.

#### Types & Documentation
- Use type hints wherever possible.
- Write accurate PHPDoc comments for public APIs, hooks, and complex data structures
- Use array shape annotations in PHPDoc when working with associative arrays.

#### Performance
- Avoid queries in loops
- Use transients for expensive operations
- Cache template parts when possible
- Lazy load assets when appropriate

#### Internationalization
All user-facing strings must be prepared for translation:
- Use `__()` for returning translated strings: `__('Text to translate', 'pressbooks')`
- Use `_e()` for echoing translated strings: `_e('Text to translate', 'pressbooks')`
- Use `_n()` for plural forms: `_n('One item', '%d items', $count, 'pressbooks')`
- Use `_x()` to disabiguate identical strings used in different contexts:
```php
// For a noun
_x('Post', 'noun', 'pressbooks');

// For a verb
_x('Post', 'verb: publish action', 'pressbooks');
```
- Use `esc_html__()` and `esc_html_e()` for output escaping + translation
- Always use the correct text domain (`'pressbooks'` for core, plugin slug for plugins, theme slug for themes)
- Never concatenate translated strings - use placeholders with sprintf():
  ```php
  // Wrong
  $message = __('Hello', 'pressbooks') . ' ' . $name;
  
  // Correct
  $message = sprintf(__('Hello %s', 'pressbooks'), $name);

- Help translators understand ambiguous strings. Add translator comments directly before translation functions:
  ```php
  /* translators: %s is the user's display name */
  $greeting = sprintf(__('Welcome, %s!', 'pressbooks'), $name);
  
  /* translators: Button label for saving draft content */
  _e('Save Draft', 'pressbooks');
- Explain placeholder meanings and constraints: 
```php
/* translators: 1: number of books, 2: author name */
sprintf(__('%1$d books by %2$s', 'pressbooks'), $count, $author);
```
 - Describe UI location for context:
 ```php
/* translators: Label for checkbox in export options panel */
__('Include front matter', 'pressbooks');
```
- Note string length constraints for UI
```
/* translators: Keep this short - max 15 characters for button label */
__('Continue', 'pressbooks');
```
 - Flag strings with technical terms or special formatting:
 ```php
 /* translators: "EPUB" is a file format name and should not be translated */
__('Export to EPUB', 'pressbooks');
```
 
#### Security Guidelines
 - Escape output: use esc_html(), esc_attr(), esc_url()
 - Sanitize input: use sanitize_text_field(), wp_kses()
 - Use nonces for form submissions
 - Capability checks: current_user_can()

#### WordPress Integration and Prefixing
- Use the following prefixing conventions for hooks and stored data
    - Prefix action and filter hook names with pb_.
    - Prefix WP Post meta keys with pb_.
    - Prefix WP User meta keys with pb_.
    - Prefix WP Option names with pressbooks_.
- Pressbooks classes that extend WP Core classes may violate PSR1.Methods.CamelCapsMethodName. In those cases, explicitly include the relevant files in the repo's `phpcs.ruleset.xml`, for example:
```
<rule ref="PSR1.Methods.CamelCapsMethodName" >   
    <exclude-pattern>/inc/admin/class-catalog-list-table.php</exclude-pattern>
    <exclude-pattern>/api/endpoints/controller/*</exclude-pattern>
</rule>
```

#### Common Gotchas

1. **Caching**: Pressbooks uses WordPress object cache extensively with custom `pb` group. Always clear cache during development.

2. **Theme directories**: Custom theme registration via `pressbooks_register_theme_directory` action hook

3. **Compatibility file**: [compatibility.php](compatibility.php) handles version-specific WordPress compatibility - check here for version-dependent behavior

4. **Bootstrap files**: Main plugin files (pressbooks.php, pressbooks-lti.php) have side effects and are excluded from PSR-1 rules

5. **PHP version**: Requires **PHP 8.3**. Check composer.json `require.php` and `testVersion` in phpcs.ruleset.xml

#### Blade Templates
Pressbooks uses Blade templates (via Laravel Blade) for admin views and some frontend components, particularly in:
- pressbooks-network-analytics (`/templates`)
- pressbooks-plugins-config (`/resources/views`)
- Other network plugins with admin interfaces

##### File Organization
- Store templates in `/templates` or `/resources/views` directory
- Use `.blade.php` extension
- Name clearly: `admin.blade.php`, `settings.blade.php`, `booklist.blade.php`

##### Markup & Styling
- Use semantic HTML elements (`<button>`, `<nav>`, `<article>`, not `<div>` for everything)
- Never use inline styles: use CSS classes instead
- Define styles in separate stylesheet files, enqueued via WordPress or compiled with Laravel Mix
- Use dynamic classes for state/context:
  ```php
  <div class="book-status {{ $book->is_published ? 'published' : 'draft' }}">
  ```
- Avoid presentational class names; prefer semantic or BEM-style naming

##### Best Practices
- Use `{{ $variable }}` for escaped output (safe by default)
- Use `{!! $variable !!}` only for pre-sanitized HTML
- Use @extends, @section, @include for template inheritance and reusabability: e.g. `@include('partials.header')`
- Keep templates presentational - no database queries or WordPress hooks
- Pass all data from PHP controllers/classes:
  ```php
  return blade()->render('admin', [
      'books' => $books,
      'settings' => $settings,
  ]);
  ```
- Add translator comments for strings in Blade templates:
  ```php
  {{-- translators: %s is the book title --}}
  <h1>{{ sprintf(__('Editing: %s', 'pressbooks'), $title) }}</h1>
  ```
- Delegate complex logic to PHP classes before rendering
- Keep conditional logic minimal (`@if`, `@foreach`)
- Write tests for the controllers/services that render templates, not templates directly

### JavaScript & CSS
#### Dependencies
- Minimize external dependencies - only add when there's clear value
- Avoid jQuery - prefer vanilla JavaScript (legacy code may still use jQuery)
- When a framework is needed, use Alpine.js for reactive UI components
- Use Laravel Mix for asset compilation

#### JavaScript
- Use modern ES6+ features (const/let, arrow functions, template literals)
- Prefer declarative code over imperative DOM manipulation
- Use data attributes (`data-*`) for JavaScript hooks, not classes
- Keep scripts modular and testable
- Example vanilla JS pattern:
  ```javascript
  document.querySelectorAll('[data-toggle]').forEach(el => {
      el.addEventListener('click', (e) => {
          e.target.closest('.panel').classList.toggle('open');
      });
  });
  ```
#### Alpine.js (when needed)

- Use for interactive components requiring state management
- Keep Alpine directives in HTML attributes: x-data, x-show, x-on:click
- Prefer Alpine over adding heavyweight frameworks like React/Vue

#### CSS:
- Define all styles in separate stylesheets
- Never use inline styles in templates or JavaScript
- Prefer CSS custom properties (variables) over SCSS variables for new code.
- Legacy code may use SCSS, but migrate to CSS properties when refactoring
- Use BEM or semantic naming conventions
- Organize styles by component or feature
- Avoid frameworks where possible. If a CSS framework would be helpful, use Tailwind.

### Database
- Network-wide tables must use `$wpdb->base_prefix`
- Match WordPress core table schemas for user data (e.g., `bigint(20)`, `varchar(60)` for user_login)
- Use `dbDelta()` for table creation (requires `wp-admin/includes/upgrade.php`)
- Add appropriate indexes to support query performance

 ### Pressbooks API
Pressbooks provides a REST API built on the WordPress REST API.
- Root site endpoints: https://NETWORK.URL/wp-json
- Book-specific endpoints: https://NETWORK.URL/BOOKTITLE/wp-json
- Book API index: https://NETWORK.URL/BOOKTITLE/wp-json/pressbooks/v2
- Responses include a _links node based on HAL (Hypertext Application Language).
- Supported HTTP methods: GET, POST, PUT, PATCH, DELETE, OPTIONS.
- Authentication must be configured by the developer; permissions are respected by WordPress.
- The API is self-documenting: send an OPTIONS request to any endpoint to retrieve a JSON Schema-compatible description.
- Use the _embed parameter to reduce HTTP requests by including embeddable resources.
- Pagination information is exposed via response headers.
- API responses are rendered in a generic format and do not map directly to database column names.

## Common Workflows

### Plugin Development
1. Make changes in the plugin repository.
2. Test locally in Lando environment.
3. Check for PHP errors (for example, via `get_errors` or `debug.log`).
4. Write unit tests for new functionality. Pull requests that reduce overall code coverage are generally not accepted.
5 . Run `composer fix`, `composer standards`, and `composer test` and resolve any issues.
6. Run `npm run lint` and `npm run build` and resolve any issues.
7. Open a pull request against `dev` branch.  

### Code Quality Commands

```bash
# Run all quality checks before committing
composer test      # Unit tests
composer standards # Linting (phpcs or pint)
npm run lint       # JavaScript/CSS linting
```

### Asset Building
**Two build systems** coexist for different plugins. We're moving towards Vite for newer plugins, but core Pressbooks and older plugins still use Laravel Mix (Webpack).

1. **Laravel Mix (Webpack)** - Used by core pressbooks and older plugins:
   ```bash
   npm run build  # Production build
   npm run watch  # Development with BrowserSync
   ```
   - Config: [webpack.mix.js](webpack.mix.js)
   - Outputs to `assets/dist/`

2. **Vite** - Used by newer plugins (pressbooks-lti, pressbooks-plugins-config):
   ```bash
   npm run build  # Production build
   npm run dev    # Development server
   ```
   - Config: `vite.config.js`
   - Uses `@kucrut/vite-for-wp` package
   - Outputs to `dist/`

1. At the command prompt from the Pressbooks plugin directory, `npm i` or `npm install` to install build tools. 
2. Lint Javascript and SCSS assets with `npm run lint`.
3. Build assets using `npm run build` or `npm run build:production` (the production build adds a version hash for cache busting).

### Database Changes
- Create migration or table creation code
- Use `maybe_create_table()` pattern to check for table existence
- Test changes on both fresh installs and existing installs

### Git Workflow
- **Commit messages**: Use [Conventional Commits](https://www.conventionalcommits.org/): `feat:`, `fix:`, `chore:`
    - Use the present tense ("Add feature" not "Added feature")
    - Use the imperative mood ("Move cursor to..." not "Moves cursor to...").
    - Limit the first line to 72 characters or less.
- **Branch naming**: `dev` is default branch and represents work in progress. 
    - Follow this pattern for PR branches: `feat/add-feature`, `fix/bug-fix`, `chore/perform-chore`
    - When changes in `dev` have been fully tested and determined ready for release, a release is tagged and created. 
    - Other branches are used for feature development prior to merging into `dev` and should be used with caution. 
- **PR requirements**: Must include unit tests, maintain code coverage, pass all linting
    - Reference issues and pull requests liberally.
    - If the change only affects documentation, include `[ci skip]` in the commit description to avoid running automated tests.
    - All PRs must target `dev` and pass our CI/CD checks and coding standards before merging. 
- Tags represent releases. For installations, download packaged releases (for example, `pressbooks-6.6.0.zip`) from the repository's Releases page rather than source archives.
- Use semantic versioning for release numbering.

## Testing
- Unit tests are built with WP-CLI and PHPUnit 9.x, following WordPress conventions.
- Test suites run automatically on commit via GitHub Actions, with coverage reporting via Codecov. 
- Our test matrix typically includes production PHP / WordPress versions, and the next/latest versions of PHP and WordPress. 
- Current minimum requirements: PHP 8.3 and WordPress 6.8.3. 
- Tests live under `/tests/*` and typically cover code in `/inc/*`

### Testing Conventions

- **Framework**: Uses Codeception + WP Browser
- **Test files**: Located in `tests/` directory, named `test-*.php`
- **Base class**: Extends `\WP_UnitTestCase` (WordPress unit test framework)
- **Run tests**:
  ```bash
  composer test           # Run all tests
  composer test-coverage  # With coverage report
  ```
- **Test organization**: Use `@group` annotations for categorization
- **Helper trait**: `utilsTrait` provides test utilities in many test files

## Logs
Access and error logs are located on the VM in `/srv/www/example.com/logs/`

## Theme Development
### Core themes
- Aldine is the primary theme for the network root site (i.e. the network homepage).
- Pressbooks-book (McLuhan) is the primary parent theme for books.

McLuhan provides a user interface that controls the appearance and reading interface for all Pressbooks webbooks. This user interface is inherited, unchanged, by all child themes. Child themes simply customize how book content is rendered on the web and in export formats like EPUB and PDF, typically through the use of custom Buckram variables.

For details on theme development, see the [Pressbooks developer guide](https://pressbooks.org/dev-guides/theme-development/)

### Design components: Aetna & Buckram
[Aetna](https://github.com/pressbooks/aetna) is a shared front-end pattern library and style guide used by Aldine and McLuhan. Documentation is available at https://aetna.pressbooks.org/. 

[Buckram](https://github.com/pressbooks/buckram) is a set of opinionated SCSS components for books, used by McLuhan and its child themes. Buckram relies on SASS variables and the `!default` flag to allow layered overrides. Book themes import Buckram’s component files and default variables via SASS imports, and then override specific variables with custom values.
This enables theme-level customization and user-controlled options such as [Shapeshifter](https://pressbooks.org/blog/2019/09/24/font-selector-theme-option/), which allows authors to select custom header and body fonts across web, ebook, and PDF outputs. 

## Additional Resources
- Main repo: https://github.com/pressbooks/pressbooks
- WordPress Multisite docs: https://developer.wordpress.org/advanced-administration/multisite/

When given a task or problem to solve it will incude sometimes the Designer Agent input and the Architect Agent input, you will analyze the plan, requirements and determine the best approach to address it. You will write clean and efficient code, following best practices and coding standards. If you encounter any issues or errors, you will debug them systematically to identify the root cause and implement a solution. You will also write good quality tests (unit, integration, end-to-end, etc), always mocking external dependencies, and ensure that your code is well-tested and maintainable. You will also be responsible for documenting your code and any relevant information about the implementation to ensure that it is easily understandable by other developers. Ensure README.md files are updated with any new features or changes to existing functionality.