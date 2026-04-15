# Code Conventions Reference

## PHP Coding Standards

### Two Standards in Use

**Core plugin** (`pressbooks`, `pressbooks-book`): PHP_CodeSniffer with Pressbooks Coding Standards
```bash
composer fix        # vendor/bin/phpcbf --standard=phpcs.ruleset.xml
composer standards  # vendor/bin/phpcs --standard=phpcs.ruleset.xml
```
Based on Human Made + WordPress Coding Standards with [Pressbooks-specific rules](https://github.com/pressbooks/coding-standards).

**Newer plugins** (`pressbooks-lti`, `pressbooks-multi-institution`, etc.): Laravel Pint with PSR-12
```bash
composer fix        # vendor/bin/pint
composer standards  # vendor/bin/pint --test
```

### phpcs.ruleset.xml Key Rules

- Base: `pressbooks/coding-standards ^1.1`
- `WordPress.NamingConventions` excluded (allows non-snake_case properties)
- Squiz/Generic commenting rules excluded (TODO: re-enable)
- PHP Compatibility: 8.3–8.4
- Blade files (`*.blade.php`) excluded from checking
- PSR1 CamelCase method name exclusions for WP Core overrides:
  - `class-catalog-list-table.php`, `class-network-managers-list-table.php`, `class-pressbookstable.php`
  - `api/endpoints/controller/*`
  - `inc/modules/export/class-table.php`
- Side-effects exclusions: `pressbooks.php`, `functions.php`, `compatibility.php`, `hm-autoloader.php`

### Namespace Conventions

- Core: `Pressbooks\` namespace, classes in `inc/` directory
- Plugins: `PressbooksPluginName\` (e.g., `PressbooksLti\`, `PressbooksTos\`, `PressbooksMultiInstitution\`)
- PSR-4 autoloading configured in each `composer.json`
- `hm-autoloader.php` used alongside PSR-4 for legacy class loading

### Naming Rules

| Context | Convention | Example |
|---------|-----------|---------|
| Class methods | `camelCase` | `getBookStructure()` |
| Class properties | `camelCase` | `$bookTitle` |
| Class constants | `UPPERCASE` | `SUPPORTED_FORMATS` |
| WordPress hooks | `snake_case` with `pb_` prefix | `pb_book_updated` |
| WordPress filters | `snake_case` with `pb_` prefix | `pb_export_formats` |
| Post meta keys | `snake_case` with `pb_` prefix | `pb_section_type` |
| User meta keys | `snake_case` with `pb_` prefix | `pb_catalog_order` |
| Option names | `snake_case` with `pressbooks_` prefix | `pressbooks_theme_lock` |
| Functions (non-class) | `snake_case` | `pb_get_book_information()` |
| Template helpers | `snake_case` with `pb_` prefix | `pb_get_next()` |

### Language & Structure

- `declare(strict_types=1);` encouraged for new files
- Type hints on all parameters and return types
- PHPDoc for public APIs, hooks, complex data structures
- Array shape annotations in PHPDoc:
  ```php
  /** @return array{id: int, title: string, chapters: array<int, string>} */
  ```
- Prefer real objects over static utility classes
- If only static methods → write functions instead
- If mirroring WP Core classes → prefer functions over classes
- Hooks should delegate quickly to domain/service code
- Methods `private` by default

### Performance

- No queries inside loops
- Use transients for expensive operations
- Cache template parts when possible
- Lazy-load assets when appropriate
- Pressbooks uses WordPress object cache with custom `pb` group — clear during development

## Internationalization (i18n)

### Translation Functions

| Function | Use |
|----------|-----|
| `__('text', 'pressbooks')` | Return translated string |
| `_e('text', 'pressbooks')` | Echo translated string |
| `_n('one', '%d items', $count, 'pressbooks')` | Plural forms |
| `_x('Post', 'noun', 'pressbooks')` | Disambiguate identical strings |
| `_nx()` | Plurals + disambiguation |
| `esc_html__()` | Escape + translate (return) |
| `esc_html_e()` | Escape + translate (echo) |
| `esc_attr__()` | Escape for attributes + translate |

### Text Domains

- Core plugin: `'pressbooks'`
- Plugins: use their slug (`'pressbooks-lti'`, `'pressbooks-multi-institution'`)
- Themes: use their slug (`'pressbooks-book'`, `'pressbooks-aldine'`)

### Rules

**Never concatenate**:
```php
// Wrong
$msg = __('Hello', 'pressbooks') . ' ' . $name;

// Correct
$msg = sprintf(__('Hello %s', 'pressbooks'), $name);
```

**Always add translator comments**:
```php
/* translators: %s is the user's display name */
$greeting = sprintf(__('Welcome, %s!', 'pressbooks'), $name);

/* translators: 1: number of books, 2: author name */
sprintf(__('%1$d books by %2$s', 'pressbooks'), $count, $author);

/* translators: Label for checkbox in export options panel */
__('Include front matter', 'pressbooks');

/* translators: Keep this short - max 15 characters for button label */
__('Continue', 'pressbooks');

/* translators: "EPUB" is a file format name and should not be translated */
__('Export to EPUB', 'pressbooks');
```

**Date/time**: Use `date_i18n()` or `wp_date()`, not `date()` or `strftime()`.

**Don't assume English word order** — numbered placeholders for reordering:
```php
/* translators: 1: author name, 2: book title */
sprintf(__('%1$s wrote %2$s', 'pressbooks'), $author, $title);
```

### JavaScript i18n

```javascript
const { __, _n, _x } = wp.i18n;
const label = __('Save changes', 'pressbooks');
```

WordPress enqueues the script translations via `wp_set_script_translations()`.

### Supported Languages

Over 100 language variants supported for EPUB/Mobi metadata — see `inc/l10n/namespace.php` for `supported_languages()`.

## Accessibility (WCAG 2.1 AA)

### HTML

- Semantic elements: `<button>`, `<nav>`, `<article>`, `<section>`, `<main>`, `<aside>`, `<header>`, `<footer>`
- No `<div>` for interactive elements
- All images have `alt` text (empty `alt=""` for decorative images)
- Heading hierarchy: don't skip levels
- Lists: use `<ul>`, `<ol>`, `<dl>` for list content

### ARIA

- `aria-label` for elements without visible text (icon buttons, links)
- `aria-describedby` for supplementary help text
- `aria-sort` on sortable table headers (`none`, `ascending`, `descending`)
- `role="status"` on notification/success messages
- `role="alert"` on error messages
- `aria-live="polite"` for dynamic content updates
- `aria-expanded` for collapsible sections

### Keyboard

- All interactive elements must be keyboard-operable (Tab, Shift+Tab, Enter, Space, Escape)
- Visible focus indicators — never `outline: none` without replacement
- Focus management: move focus into modals on open, return to trigger on close
- Skip links for main content areas

### Forms

- Every `<input>` must have an associated `<label>` (via `for` attribute or wrapping)
- Error messages must be programmatically associated (`aria-describedby`, `aria-invalid`)
- Required fields indicated via `aria-required="true"` and visual indicator
- Group related fields with `<fieldset>` and `<legend>`

### Color & Contrast

- Text contrast: 4.5:1 minimum (normal text), 3:1 (large text/bold)
- No information conveyed by color alone
- Focus indicators: 3:1 contrast against adjacent colors
- Accessible color variables defined in `colors-pb-a11y.scss`

### Existing Patterns

Refer to `assets/src/scripts/a11y.js` for established patterns:
- Adding ARIA attributes to WP List Tables
- Color picker accessibility enhancements
- Date picker focus management
- MutationObserver for dynamic attribute cleanup
- Quicktags button labeling

## Security

### Output Escaping

| Function | Use for |
|----------|---------|
| `esc_html()` | HTML content |
| `esc_attr()` | HTML attributes |
| `esc_url()` | URLs |
| `esc_js()` | Inline JavaScript (avoid) |
| `wp_kses()` | Allow specific HTML tags |
| `wp_kses_post()` | Allow post-safe HTML |

### Input Sanitization

| Function | Use for |
|----------|---------|
| `sanitize_text_field()` | Plain text input |
| `absint()` | Non-negative integers |
| `sanitize_email()` | Email addresses |
| `sanitize_file_name()` | File names |
| `wp_kses()` | HTML with allowed tags |

### Authentication & Authorization

- Nonce verification: `wp_verify_nonce()`, `check_admin_referer()`, `check_ajax_referer()`
- Capability checks: `current_user_can()` before any privileged operation
- AJAX: `wp_ajax_{action}` / `wp_ajax_nopriv_{action}` hooks with nonce verification

### Database

- Always use `$wpdb->prepare()` for raw SQL
- Prefer Eloquent ORM for complex queries
- Never interpolate user input directly into SQL

## Blade Templates

### Organization

- Core templates: `/templates/` directory
- Plugin templates: `/resources/views/` directory
- Extension: `.blade.php`
- Cache: auto-generated PHP in cache directory

### Rules

- `{{ $var }}` for escaped output (default, safe)
- `{!! $var !!}` only for pre-sanitized HTML
- `@extends`, `@section`, `@include` for template structure
- No database queries or WordPress hooks in templates
- Pass data from PHP: `blade()->render('view', ['key' => $value])`
- Minimal conditional logic (`@if`, `@foreach`)
- Semantic HTML, no inline styles, BEM/semantic class names
- Translator comments: `{{-- translators: %s is the title --}}`

## JavaScript

### Conventions

- ES6+: `const`/`let`, arrow functions, template literals, destructuring, async/await
- Vanilla JS preferred over jQuery
- Alpine.js for interactive components: `x-data`, `x-show`, `x-on:click`
- `data-*` attributes for JS hooks, not CSS classes
- ESLint config: extends `pressbooks-build-tools/config/eslint.cjs`
- Globals allowed: `tinyMCE`, `ajaxurl`, `edButton`

### Build

- Vite (`vite.config.js`), outputs to `assets/dist/`
- 45+ JS entry points, 10+ SCSS entry points
- Dev: `npm run watch`
- Build: `npm run build`
- Lint: `npm run lint:scripts` (ESLint), `npm run lint:styles` (Stylelint)

## CSS / SCSS

- Separate stylesheets, never inline styles
- CSS custom properties preferred for new code
- Legacy SCSS acceptable, migrate when refactoring
- BEM or semantic naming (`.book-status--published`)
- No CSS frameworks unless Tailwind specifically needed
- Buckram: SCSS component library for book themes (`!default` variables for overrides)
- Aetna: Shared pattern library for Aldine and McLuhan

## Database

- Network tables: `$wpdb->base_prefix . 'table_name'`
- Match WordPress schemas for user data (`bigint(20)`, `varchar(60)`)
- Table creation: `dbDelta()` (requires `wp-admin/includes/upgrade.php`)
- Check existence: `maybe_create_table()` pattern
- Add indexes for query performance

## Common Gotchas

1. **Object cache**: Custom `pb` cache group — flush during development
2. **Theme directories**: Register via `pressbooks_register_theme_directory` hook
3. **Compatibility**: `compatibility.php` handles version-specific behavior
4. **Bootstrap files**: `pressbooks.php`, `functions.php` have side effects (excluded from PSR-1)
5. **PHP version**: Minimum 8.3, compatibility tested against 8.3–8.4
6. **Default theme**: `WP_DEFAULT_THEME` set dynamically in `pressbooks.php` via `PB_BOOK_THEME` constant or `pressbooks_default_book_theme` option
7. **SSL**: Verification disabled in development environment
8. **Sessions**: Custom session management via `\Pressbooks\session_start` on `plugins_loaded`
