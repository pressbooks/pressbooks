# Pressbooks — Copilot Workspace Instructions

Pressbooks is an open-source **WordPress Multisite** plugin for creating and publishing books in multiple formats (EPUB, PDF, XHTML, XML). It uses the **Classic Editor** (not Gutenberg). Source: [github.com/pressbooks](https://github.com/pressbooks/).

## Quick Start

```bash
lando start                          # Spin up Lando/Docker environment
composer install && npm install      # Install dependencies
npm run build                        # Compile assets
# Before committing:
composer fix && composer standards   # Auto-fix & lint PHP
npm run lint                         # Lint JS/CSS
composer test                        # Run unit tests
```

## Architecture at a Glance

- **Multisite**: Network-activated plugin. Books are individual sites. Use `switch_to_blog()` / `restore_current_blog()` for cross-site work.
- **DI Container**: `\Pressbooks\Container::get('Blade')`, `app('ServiceName')` — Laravel Illuminate Container.
- **Service Provider**: [inc/class-serviceprovider.php](inc/class-serviceprovider.php) registers `Blade`, `Sass`, `Styles`, `GlobalTypography`, `db` (Eloquent), and more.
- **Blade Templating**: Laravel Blade for admin views. Templates in `/templates/`. Namespaced: `$blade->addNamespace('PressbooksLti', __DIR__ . '/resources/views')`.
- **Eloquent ORM**: For complex data models; raw `$wpdb` only where WordPress APIs require it.
- **Custom Post Types**: `front-matter`, `back-matter`, `chapter`, `part` — see [inc/class-book.php](inc/class-book.php).
- **Export System**: `inc/modules/export/` — EPUB, PDF (PrinceXML/DocRaptor), XHTML, WordPress WXR, ThinCC.
- **Import System**: `inc/modules/import/` — EPUB, HTML, ODF, OOXML, WordPress WXR.
- **Background Processing**: `inc/modules/backgroundprocessing/` — queue/worker pattern.
- **H5P Integration**: Interactive content via H5P adapters.

## Key Conventions

- **PHP 8.3+**, WordPress 6.8.3+. Strict types encouraged for new code.
- **Two coding standards**: Core uses PHPCS (`composer standards`); newer plugins use Laravel Pint.
- **Namespaces**: Core = `Pressbooks\`, plugins = `PressbooksPluginName\`. PSR-4 autoloading.
- **Naming**: `camelCase` methods/properties, `UPPERCASE` constants, `snake_case` at WP boundaries (hooks, meta, options).
- **Hook prefixes**: `pb_` for actions/filters/meta, `pressbooks_` for options.
- **i18n**: All user-facing strings must use `__()`, `_e()`, `_n()`, `_x()` with text domain `'pressbooks'`. Use `sprintf()` with translator comments, never concatenate translated strings.
- **Accessibility**: Follow WCAG 2.1 AA. Use semantic HTML, ARIA attributes, keyboard navigation. Test with screen readers. See `assets/src/scripts/a11y.js` for existing patterns.
- **Security**: Escape output (`esc_html()`, `esc_attr()`, `esc_url()`), sanitize input (`sanitize_text_field()`, `wp_kses()`), use nonces and capability checks.
- **Assets**: Vite build (`vite.config.js`), outputs to `assets/dist/`. Alpine.js for reactivity. Vanilla JS preferred over jQuery.
- **Tests**: Codeception + WP Browser. Files in `tests/`, named `test-*.php`, extend `\WP_UnitTestCase`. `@group` annotations. `utilsTrait` for helpers.
- **Git**: Conventional commits (`feat:`, `fix:`, `chore:`), PRs target `dev`, branch naming `feat/x`, `fix/x`, `chore/x`.

## Agent Output

Agents write reports to `.github/reports/` (gitignored). Use `@architect`, `@developer`, `@reviewer`, or `@tester` for role-specific workflows.

## Deep Reference

For detailed conventions, use the `/pressbooks-development` skill or see:
- [.github/CONTRIBUTING.md](.github/CONTRIBUTING.md) — contributor guidelines
- [phpcs.ruleset.xml](phpcs.ruleset.xml) — PHP coding standards rules
- [codeception.dist.yml](codeception.dist.yml) — test configuration
- [vite.config.js](vite.config.js) — asset build configuration
