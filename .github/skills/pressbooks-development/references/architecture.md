# Architecture Reference

## Project Overview

Pressbooks is a **WordPress Multisite** plugin for creating and publishing books. Network-activated on a multisite installation, where each "book" is an individual site.

- **Core Plugin**: `pressbooks/` — namespace `Pressbooks\`
- **Extension Plugins**: `pressbooks-lti/`, `pressbooks-multi-institution/`, `pressbooks-cas-sso/`, etc.
- **Book Themes**: `pressbooks-book/` (McLuhan, parent theme), child themes for format customization
- **Network Theme**: `pressbooks-aldine/` (Aldine, root site theme)

**Requirements**: PHP 8.3+, WordPress 6.8.3+, Network: True

## Dependency Injection Container

Lightweight wrapper around Laravel's Illuminate Container (`inc/class-container.php`):

```php
// Get a service
\Pressbooks\Container::get('Blade');

// Set a service (default: singleton)
\Pressbooks\Container::set('key', $value);
\Pressbooks\Container::set('key', $value, 'factory');  // Options: factory, bind, protect, instance, singleton

// Laravel-style helper
app('ServiceName');
app();  // Returns container instance
```

## Service Provider

`inc/class-serviceprovider.php` registers core services as singletons:

| Service | Purpose |
|---------|---------|
| `Blade` | Laravel Blade templating engine |
| `Sass` | SCSS compiler (scssphp) |
| `GlobalTypography` | Typography system |
| `Styles` | Theme styling engine |
| `ScopedStyles` | H5P CSS and scoped styles |
| `db` | Eloquent ORM database connection |
| `H5PPlugin` | H5P interactive content adapter |

Initialization: `ServiceProvider::init()` is called in `hooks.php`.

## Blade Templating

Laravel Blade for admin views and some frontend components:

```php
// Get the Blade service
$blade = \Pressbooks\Container::get('Blade');

// Register a namespace for plugin templates
$blade->addNamespace('PressbooksLti', __DIR__ . '/resources/views');

// Render a template with data
$blade->render('PressbooksLti::settings._roles', ['roles' => $roles]);
```

- **Template location**: `/templates/` directory in core, `/resources/views/` in plugins
- **Cache**: Generated PHP files stored in cache directory
- **Escaped output**: `{{ $var }}` (safe by default)
- **Raw output**: `{!! $var !!}` (only for pre-sanitized HTML)
- **Templates are presentational only**: No DB queries, no hooks — pass all data from controllers

## Eloquent ORM

Used for complex data models instead of raw `$wpdb`:

```php
$db = \Pressbooks\Container::get('db');
// Eloquent connection configured from WordPress globals
// Uses $wpdb->base_prefix for table prefix
```

Connection reads from `.env` or WordPress constants (DB_HOST, DB_NAME, DB_USER, DB_PASSWORD).

## WordPress Multisite Specifics

### Network vs. Site

- Core pressbooks is **network-activated** — affects all sites
- Individual "books" are WordPress sites within the multisite network
- Book detection: `Book::isBook()` checks if current site is a book

### Cross-Site Operations

```php
switch_to_blog($blog_id);
// Perform operations on the target site
restore_current_blog();  // ALWAYS pair these calls
```

### Network Admin

```php
if (is_network_admin()) {
    // Network admin screen logic
}

// Network-wide options
get_site_option('pressbooks_setting');
update_site_option('pressbooks_setting', $value);

// Network-wide tables
$table = $wpdb->base_prefix . 'custom_table';  // NOT $wpdb->prefix
```

## Custom Post Types

Book content is organized using custom post types (see `inc/class-book.php`):

| CPT | Purpose |
|-----|---------|
| `front-matter` | Front matter sections (preface, introduction, etc.) |
| `back-matter` | Back matter sections (appendix, bibliography, etc.) |
| `chapter` | Book chapters |
| `part` | Parts/divisions that group chapters |

Extended custom post types via `johnbillion/extended-cpts` package.

## Hook Organization

### `hooks.php` (Frontend & General)
- Loads `requires.php` and symbionts
- Initializes ServiceProvider (`ServiceProvider::init()`)
- Registers activation, session, and SSL hooks
- Checks `Book::isBook()` for conditional registration

### `hooks-admin.php` (Admin-Specific)
- Loads `requires-admin.php`
- Admin bar customization (replace My Sites, remove Update, remove New Content)
- Event Streams (SSE) initialization
- Gutenberg removal (`remove_action('try_gutenberg_panel', ...)`)
- Admin footer branding

### Hook Prefixes
- Actions/filters: `pb_` prefix
- Plugin-specific: `pressbooks_` prefix (e.g., `pressbooks_register_theme_directory`)

## Export System

Abstract base class with format-specific implementations (`inc/modules/export/`):

| Format | Class Location | Engine |
|--------|---------------|--------|
| EPUB 3 | `epub/` | Custom PHP |
| PDF | `prince/` | PrinceXML |
| PDF (cloud) | `prince/class-docraptor.php` | DocRaptor API |
| PDF (print) | `prince/class-docraptorprint.php` | DocRaptor API |
| XHTML | `xhtml/` | Custom PHP |
| WordPress WXR | `wordpress/` | WordPress export |
| ThinCC | `thincc/` | Custom format |

Shared trait: `class-handlecontributors.php` for contributor metadata.

## Import System

Import from multiple formats (`inc/modules/import/`):

| Format | Location |
|--------|----------|
| EPUB | `epub/` |
| HTML | `html/` |
| OpenDocument | `odf/` |
| Microsoft Office | `ooxml/` |
| WordPress WXR | `wordpress/` |

## Additional Modules

| Module | Location | Purpose |
|--------|----------|---------|
| Background Processing | `inc/modules/backgroundprocessing/` | Queue/worker pattern for long tasks |
| Search & Replace | `inc/modules/searchandreplace/` | Content find/replace |
| Theme Options | `inc/modules/themeoptions/` | Theme customization settings |

## Admin Interface

The admin is organized in `inc/admin/` with these major areas:

| Area | Purpose |
|------|---------|
| `dashboard/` | Network Integrations menu, admin dashboard |
| `organize/` | Book content organization (drag/drop chapter ordering) |
| `covergenerator/` | Cover image generation |
| `branding/` | Site branding (favicon, logo) |
| `menus/` | Custom menu system (SideBar, TopBar) |
| `network/` | Network admin features |
| `networkmanagers/` | Network manager user management |
| `diagnostics/` | System diagnostics page |
| `metaboxes/` | Custom metaboxes for book content |

## Plugin Communication

1. **Hooks**: Extensive use of `pb_` and `pressbooks_` prefixed actions/filters
2. **Service Container**: Shared services across plugins via `Container`
3. **Network Integrations**: Plugins add pages under unified menu (see `inc/admin/dashboard/namespace.php`)

## Constants

Defined in `pressbooks.php`:

| Constant | Purpose |
|----------|---------|
| `PB_PLUGIN_DIR` | Plugin directory path (respects symlinks) |
| `PB_PLUGIN_URL` | Plugin URL |
| `PB_PLUGIN_VERSION` | Current plugin version |

## Key Dependencies

| Package | Purpose |
|---------|---------|
| `illuminate/*` (8 modules) | Laravel Container, DB, Events, Views, Pagination |
| `aws/aws-sdk-php` | AWS integration |
| `docraptor/docraptor` | Cloud PDF generation |
| `gridonic/princexml-php` | Local PDF generation |
| `h5p/h5p-core` | Interactive content |
| `scssphp/scssphp` | SCSS compilation |
| `masterminds/html5` | HTML5 parsing |
| `vanilla/htmlawed` | HTML sanitization |
| `monolog/monolog` | Logging |
| `symfony/process` | Process execution |
| `johnbillion/extended-cpts` | Extended custom post types |

## Accessibility Architecture

- Primary JS: `assets/src/scripts/a11y.js` — enhances ARIA attributes across admin
- CSS: `colors-pb-a11y.scss` — accessible color scheme
- External: editoria11y-accessibility-checker plugin (2.1.12)
- Patterns: `role="status"` on notices, `role="alert"` on errors, `aria-sort` on tables, `aria-label` on form controls, MutationObserver for dynamic attribute management

## Theme Architecture

- **McLuhan** (`pressbooks-book`): Parent book theme — provides reading interface, inherited by all child themes
- **Aldine** (`pressbooks-aldine`): Network root site theme
- **Child themes**: Customize book rendering via custom Buckram variables
- **Buckram**: SCSS component library with `!default` flag for layered overrides
- **Aetna**: Shared front-end pattern library (docs: https://aetna.pressbooks.org/)
- **Shapeshifter**: Author-selectable header/body fonts across web, ebook, and PDF
- Theme registration: `pressbooks_register_theme_directory` action hook
