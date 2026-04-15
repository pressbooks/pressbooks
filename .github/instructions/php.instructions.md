---
description: "Use when writing or modifying PHP files in Pressbooks. Covers coding standards, naming conventions, type safety, internationalization, accessibility, security, WordPress integration, and hook prefixing."
applyTo: "**/*.php"
---

# PHP Coding Standards

## Naming
- `camelCase` for class methods and properties
- `UPPERCASE` for class constants
- `snake_case` at WordPress boundaries: hook names, filter names, post meta keys, user meta keys, option names, globals
- Methods are `private` by default; use `protected` or `public` only when required

## Prefixing
- Action/filter hooks: `pb_` prefix (`add_action('pb_event', ...)`)
- Post meta keys: `pb_` prefix (`update_post_meta($id, 'pb_key', $val)`)
- User meta keys: `pb_` prefix
- Option names: `pressbooks_` prefix (`get_option('pressbooks_setting')`)

## Types & Structure
- `declare(strict_types=1);` for new files
- Type hints on all parameters and return types
- PHPDoc for public APIs, hooks, and complex data structures
- Array shape annotations in PHPDoc for associative arrays:
  ```php
  /** @return array{title: string, author: string, isbn: string} */
  ```
- PSR-4 autoloading: `Pressbooks\` namespace, classes in `inc/`
- Prefer real objects with clear responsibilities over static utility classes
- If a class would only have static methods, write a library of functions instead
- Prefer services over hook-heavy logic — hooks delegate to domain/service code

## Internationalization (Required)
- All user-facing strings: `__('Text', 'pressbooks')`, `_e()`, `_n()`, `_x()`
- Use `esc_html__()` and `esc_html_e()` for output escaping + translation
- Never concatenate translated strings — use `sprintf()`:
  ```php
  /* translators: %s is the book title */
  sprintf(__('Editing: %s', 'pressbooks'), $title);
  ```
- Always add translator comments before translation functions
- Use `_n()` for plurals, `_x()` for disambiguation
- Flag untranslatable terms:
  ```php
  /* translators: "EPUB" is a file format name and should not be translated */
  __('Export to EPUB', 'pressbooks');
  ```

## Accessibility
- Semantic HTML in rendered output (`<button>`, `<nav>`, `<article>`)
- ARIA attributes for interactive elements (`aria-label`, `aria-describedby`, `role`)
- Form fields must have associated `<label>` elements
- Dynamic content must manage focus appropriately
- Follow patterns in `assets/src/scripts/a11y.js`

## Security
- Escape output: `esc_html()`, `esc_attr()`, `esc_url()`, `wp_kses()`
- Sanitize input: `sanitize_text_field()`, `absint()`, `wp_kses()`
- Nonce verification: `wp_verify_nonce()`, `check_admin_referer()`
- Capability checks: `current_user_can()` before privileged operations
- Prepared statements for raw SQL: `$wpdb->prepare()`

## WordPress Multisite
- Network tables: `$wpdb->base_prefix` (not `$wpdb->prefix`)
- Cross-site work: `switch_to_blog($id)` → work → `restore_current_blog()`
- Network admin checks: `is_network_admin()`
- Network options: `get_site_option()` / `update_site_option()`
