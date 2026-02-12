# AI Coding Agent Instructions for Pressbooks

This document provides instructions for autonomous AI coding agents (e.g., Copilot coding agent, OpenAI Codex) working with the Pressbooks codebase.

## Project Overview

**Pressbooks** is an open source book publishing tool built on a WordPress multisite platform. It outputs books in multiple formats: PDF, EPUB, web, and XML flavours using a theming/templating system driven by CSS.

- **Type**: WordPress plugin (`wordpress-plugin` type in composer.json)
- **License**: GPL v3.0 or later
- **Default Branch**: `dev`
- **Multisite**: This plugin requires WordPress multisite and will not work on standard single-site WordPress installations

## Tech Stack & Requirements

### Core Technologies
- **PHP**: 8.3+ (primary language)
- **WordPress**: 6.8.3 multisite
- **Composer**: PHP dependency management
- **Node.js**: >= 18 with npm

### Build Tools & Asset Pipeline
- **Laravel Mix**: Webpack wrapper for asset compilation (see `webpack.mix.js`)
- **SCSS**: Stylesheet language
- **JavaScript**: ES6+ syntax

### Key Dependencies
- **Illuminate Components** (Laravel): Container, Database, Events, Filesystem, HTTP, View, Pagination, Support
- **AlpineJS**: Frontend interactivity
- **TinyMCE**: Editor (v4.9.11)
- **HtmLawed**: HTML sanitization
- **PrinceXML/DocRaptor**: PDF generation
- **PHPCompatibility**: PHP compatibility checking
- **SCSSPHP**: SCSS compiler

## Repository Structure

```
/
├── inc/                    # Core PHP source code (Pressbooks\ namespace)
│   ├── admin/             # Admin interface classes
│   ├── api/               # REST API endpoints
│   ├── cloner/            # Book cloning functionality
│   ├── covergenerator/    # Cover image generation
│   ├── datacollector/     # Data collection and analytics
│   ├── editor/            # Editor customizations
│   ├── entities/          # Entity classes
│   ├── health/            # Health check functionality
│   ├── htmlbook/          # HTMLBook format support
│   ├── image/             # Image processing
│   ├── interactive/       # Interactive content (H5P)
│   ├── l10n/              # Localization/internationalization
│   ├── log/               # Logging functionality
│   ├── media/             # Media handling
│   ├── metadata/          # Book metadata management
│   ├── modules/           # Core modules
│   ├── posttype/          # Custom post types
│   ├── redirect/          # Redirect handling
│   ├── registration/      # User registration
│   ├── sanitize/          # Input sanitization
│   ├── shortcodes/        # WordPress shortcodes
│   ├── theme/             # Theme management
│   ├── tracking/          # Analytics tracking
│   └── utility/           # Utility functions
├── assets/
│   ├── src/
│   │   ├── scripts/       # JavaScript source files
│   │   └── styles/        # SCSS source files
│   └── dist/              # Compiled assets (DO NOT EDIT)
├── templates/             # PHP template files (Blade syntax supported)
├── tests/                 # PHPUnit test files (test-*.php)
├── bin/                   # CLI scripts
├── languages/             # Translation files (.po, .pot)
├── symbionts/             # Bundled third-party dependencies
├── pressbooks.php         # Main plugin entry point
├── hooks.php              # Front-end WordPress hooks
├── hooks-admin.php        # Admin WordPress hooks
├── functions.php          # Utility functions
├── requires.php           # Front-end dependencies
├── requires-admin.php     # Admin dependencies
├── compatibility.php      # Compatibility checks
└── hm-autoloader.php      # Autoloader
```

### PHP File Naming Conventions
- Classes in `inc/` follow `class-*.php` naming convention
- Test files in `tests/` are prefixed with `test-*`
- All code under `inc/` uses the `Pressbooks\` PHP namespace

## Coding Standards

### PHP Standards
- Follow **Pressbooks coding standards** (`pressbooks/coding-standards` package)
- Enforced via PHPCS with `phpcs.ruleset.xml`
- Use **PHPDoc** for all function documentation
- Use **tabs** for indentation (see `.editorconfig`)
- Check standards: `composer standards`
- Auto-fix issues: `composer fix`

### JavaScript Standards
- ESLint config extends `pressbooks-build-tools`
- Use **ES6+ syntax**
- jQuery is available globally
- Check scripts: `npm run lint:scripts`
- Auto-fix scripts: `npm run lint:fix-scripts`

### SCSS Standards
- Stylelint config extends `pressbooks-build-tools`
- SCSS is the preferred stylesheet language
- Check styles: `npm run lint:styles`
- Auto-fix styles: `npm run lint:fix-styles`

### Editor Configuration
- Follow `.editorconfig` settings
- Tabs for indentation
- UTF-8 encoding
- LF line endings

## Testing

### PHP Tests
- **Framework**: PHPUnit with WordPress test framework
- **Base Class**: Extend `\WP_UnitTestCase`
- **Location**: `tests/` directory
- **Naming**: Files prefixed with `test-*`
- **Bootstrap**: `tests/bootstrap.php`
- **Configuration**: `phpunit.xml`
- **Multisite**: Tests run with `WP_TESTS_MULTISITE=1`

### Running Tests
```bash
composer test              # Run PHPUnit tests
composer test-coverage     # Run tests with coverage report
npm test                   # Run JS/CSS linting
```

### Coverage
- Includes `./inc` directory
- Excludes export templates
- Coverage reports generated in `./coverage-reports`
- **Important**: PRs that reduce code coverage will be asked to add tests

### Acceptance Tests
- Uses **Codeception** with WPWebDriver and WPDb modules
- Configuration: `codeception.dist.yml`

### Test Development
- Always add relevant unit tests for new code
- Tests should extend `\WP_UnitTestCase`
- Follow existing test patterns in `tests/` directory
- Use WordPress testing best practices

## Building Assets

### Installation
```bash
composer install    # Install PHP dependencies
npm install         # Install Node.js dependencies
```

### Development
```bash
npm run watch       # Watch for changes and rebuild
npm run build       # Build for production (runs mix --production)
```

### Asset Locations
- **Source JavaScript**: `assets/src/scripts/`
- **Source SCSS**: `assets/src/styles/`
- **Built Output**: `assets/dist/` (automatically generated)

## Git Conventions

### Commit Messages
- Use **Conventional Commits** format
- Present tense, imperative mood
- Limit first line to 72 characters
- Examples:
  - `feat: Add new export format`
  - `fix: Resolve metadata validation bug`
  - `chore: Update dependencies`
  - `docs: Update API documentation`
  - `test: Add unit tests for theme module`

### Branch Naming
- Feature branches: `feat/add-feature`
- Bug fixes: `fix/bug-fix`
- Chores: `chore/perform-chore`
- Documentation: `docs/update-docs`

### Release Management
- Managed via `release-please`
- Configuration: `release-please-config.json`
- Version tracking: `.release-please-manifest.json`

## WordPress-Specific Notes

### Multisite Context
- This plugin **requires WordPress multisite**
- Always consider multisite context when developing
- Network-activated by default (`Network: True` in plugin header)

### Custom Post Types
Pressbooks uses custom post types for book content:
- `chapter` - Book chapters
- `front-matter` - Introductory content
- `back-matter` - Appendices, bibliography, etc.
- `part` - Book parts/sections
- `glossary` - Glossary entries

### WordPress Hooks
- **Front-end hooks**: Registered in `hooks.php`
- **Admin hooks**: Registered in `hooks-admin.php`
- Use WordPress actions and filters appropriately
- Follow WordPress hook naming conventions

### REST API
- Custom endpoints under `pressbooks/v2` namespace
- Located in `inc/api/` directory
- Follow WordPress REST API standards

### Service Container
- Uses `Pressbooks\Container` class for dependency injection
- Built on Illuminate Container (Laravel)
- Access services: `\Pressbooks\Container::get('ServiceName')`
- Register services in container for proper dependency management

### Autoloading
- Uses `hm-autoloader.php` for PSR-4 autoloading
- Namespace: `Pressbooks\`
- Class files follow WordPress naming conventions

### Database
- Uses WordPress database API
- Illuminate Database component available for advanced queries
- Always use prepared statements
- Consider multisite database structure

### Security
- Use WordPress escaping functions: `esc_html()`, `esc_attr()`, `esc_url()`
- Use WordPress sanitization functions: `sanitize_text_field()`, etc.
- HTML sanitization via HtmLawed library
- Always validate and sanitize user input

## Important Warnings

### DO NOT EDIT
- ❌ Files in `assets/dist/` - compiled from `assets/src/`
- ❌ Files in `vendor/` - managed by Composer
- ❌ Files in `node_modules/` - managed by npm
- ❌ Files in `symbionts/` - unless updating bundled dependencies
- ❌ `composer.lock` or `package-lock.json` manually

### Export Formats
When working with export modules:
- **PDF**: Generated via PrinceXML or DocRaptor
- **EPUB**: Standards-compliant EPUB 3
- **Web**: HTML output with theme support
- **XML**: Various XML flavours (XHTML, HTMLBook, etc.)

Each format has specific requirements and CSS handling.

## Additional Resources

- **Developer Documentation**: https://pressbooks.org/dev-docs/
- **Coding Standards**: https://pressbooks.org/dev-guides/coding-standards/
- **User Documentation**: https://pressbooks.org/user-docs/
- **Community Forum**: https://pressbooks.community
- **GitHub Issues**: https://github.com/pressbooks/pressbooks/issues
- **Contributing Guide**: `.github/CONTRIBUTING.md`
- **Code of Conduct**: `.github/CODE_OF_CONDUCT.md`

## Quick Reference

### Common Commands
```bash
# PHP
composer install          # Install dependencies
composer test             # Run tests
composer standards        # Check coding standards
composer fix              # Auto-fix coding standards

# JavaScript/CSS
npm install               # Install dependencies
npm run build             # Build for production
npm run watch             # Watch and rebuild
npm test                  # Run linting
npm run lint:scripts      # Lint JavaScript
npm run lint:fix-scripts  # Fix JavaScript issues
npm run lint:styles       # Lint SCSS
npm run lint:fix-styles   # Fix SCSS issues

# Git
git checkout dev          # Switch to default branch
git pull origin dev       # Pull latest changes
```

### Development Workflow
1. Create feature branch from `dev`
2. Make minimal, focused changes
3. Follow coding standards
4. Add/update tests
5. Run tests and linting
6. Build assets if modified
7. Commit with conventional commit message
8. Push and create pull request
9. Address code review feedback

## Architecture Notes

### Dependency Injection
- Use `Pressbooks\Container` for service resolution
- Register services in the container
- Inject dependencies via constructor
- Avoid static method calls where possible

### Template System
- PHP templates in `templates/` directory
- Blade syntax supported via Illuminate View
- Use `Container::get('Blade')` for rendering

### Event System
- Uses Illuminate Events
- WordPress hooks integration
- Register event listeners appropriately

### Database Abstraction
- WordPress database API for simple queries
- Illuminate Database for complex queries
- Use query builder when appropriate
- Always consider database performance

## Version Information

- **Current Version**: Check `pressbooks.php` for latest version
- **PHP Requirement**: 8.3+
- **WordPress Requirement**: 6.8.3 multisite
- **Node.js Requirement**: >= 18

---

When developing for Pressbooks, always consider:
1. WordPress multisite context
2. Multiple export formats and their requirements
3. Performance implications
4. Security best practices
5. User experience across admin and front-end
6. Backwards compatibility
7. Code maintainability and documentation
