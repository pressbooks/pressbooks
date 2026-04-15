# Workflows Reference

## Development Environment

### Lando/Docker Setup

```bash
# Start the environment
lando start

# Update bedrock dependencies
lando composer update

# SSH into the container
lando ssh
```

Do NOT assume standard LAMP/WordPress — this is a specialized multisite environment with custom configuration.

- Internal team: [setup-development-environment](https://github.com/pressbooks/setup-development-environment)
- Open source: [local-dev-environment](https://github.com/pressbooks/local-dev-environment)
- Bedrock (internal): [pressbooksedu-bedrock](https://github.com/pressbooks/pressbooksedu-bedrock)
- Bedrock (open source): [pressbooksoss-bedrock](https://github.com/pressbooks/pressbooksoss-bedrock)

### Quick Start Checklist

```bash
lando start                          # 1. Start environment
lando composer update                # 2. Update bedrock
composer install && npm install      # 3. Install plugin dependencies
npm run build                        # 4. Compile assets

# Before committing:
composer fix && composer standards   # 5. Fix & lint PHP
npm run lint                         # 6. Lint JS/CSS
composer test                        # 7. Run tests
```

## Code Quality Commands

### PHP (Core plugin — PHPCS)

```bash
# Auto-fix coding standard issues
composer fix
# Equivalent: vendor/bin/phpcbf --standard=phpcs.ruleset.xml *.php inc/ bin/

# Check coding standards
composer standards
# Equivalent: vendor/bin/phpcs --standard=phpcs.ruleset.xml inc/ bin/

# Run all tests
composer test
# Equivalent: vendor/bin/phpunit --configuration phpunit.xml

# Run tests with coverage
composer test-coverage
```

### PHP (Newer plugins — Pint)

```bash
# Auto-fix
composer fix
# Equivalent: vendor/bin/pint

# Check standards
composer standards
# Equivalent: vendor/bin/pint --test
```

### JavaScript & CSS

```bash
# Lint JavaScript
npm run lint:scripts
# Equivalent: ESLint on assets/src/scripts/**/*.js

# Lint Styles
npm run lint:styles
# Equivalent: Stylelint on assets/src/styles/**

# Combined lint
npm run lint
```

## Asset Building

### Vite (Current — Core plugin)

Configuration: `vite.config.js` using `pressbooks-build-tools`

```bash
npm run build    # Production build → assets/dist/
npm run watch    # Development with hot reload
```

Entry points: 45+ JS files in `assets/src/scripts/`, 10+ SCSS files in `assets/src/styles/`

Static assets copied:
- `assets/src/fonts/*` → `fonts/`
- TinyMCE plugins → `scripts/`
- Paged.js polyfill → `scripts/`

### Vite (Newer plugins)

Configuration: `vite.config.js` using `@kucrut/vite-for-wp`

```bash
npm run build    # Production build → dist/
npm run dev      # Development server
```

### Node Requirement

Node.js >= 22

## Plugin Development Workflow

1. **Branch**: Create branch from `dev` — `feat/feature-name`, `fix/bug-name`, or `chore/task-name`
2. **Implement**: Make changes following coding standards
3. **Test locally**: Verify in Lando environment, check `debug.log` for errors
4. **Write tests**: Unit tests required. PRs that reduce coverage are rejected.
5. **Quality checks**:
   ```bash
   composer fix && composer standards  # PHP
   npm run lint                        # JS/CSS
   npm run build                       # Assets compile
   composer test                       # Tests pass
   ```
6. **Commit**: Conventional commit message
7. **Push & PR**: Open PR against `dev` branch

## Git Workflow

### Commit Messages

[Conventional Commits](https://www.conventionalcommits.org/) format:

```
feat: add EPUB3 metadata support
fix: resolve multisite activation issue
chore: update dependencies
docs: update API documentation [ci skip]
```

Rules:
- Present tense, imperative mood ("Add feature" not "Added feature")
- First line ≤ 72 characters
- `[ci skip]` for documentation-only changes
- Reference issues and PRs liberally

### Branch Strategy

| Branch | Purpose |
|--------|---------|
| `dev` | Default branch. Work in progress. All PRs target here. |
| `feat/name` | Feature development |
| `fix/name` | Bug fixes |
| `chore/name` | Maintenance tasks |
| Tags | Releases (semantic versioning) |

### PR Requirements

- Must include unit tests for new functionality
- Must maintain or improve code coverage
- Must pass all CI checks (standards, tests, linting)
- Must target `dev` branch
- Reference related issues and PRs
- Documentation-only changes: include `[ci skip]`

### Releases

- Changes in `dev` are tested, then tagged for release
- Semantic versioning (`6.39.1`)
- Download packaged releases (`pressbooks-6.39.1.zip`) from GitHub Releases, not source archives
- Release creation: `.github/workflows/create-release.yml`

## CI/CD (GitHub Actions)

### Workflows

| Workflow | File | Trigger |
|----------|------|---------|
| Tests | `tests.yml` | Push/PR to `dev` |
| Release | `create-release.yml` | Tag push |
| Translations | `crowdin.yml` | On demand |
| POT Update | `update-pot.yml` | On demand |

### Test Matrix

| PHP | WordPress |
|-----|-----------|
| 8.3 | 6.8.3 (minimum) |
| 8.4 | Latest |

### Other CI Tools

- **Dependabot**: `dependabot.yml` — automated dependency updates
- **Stale**: `stale.yml` — auto-close stale issues
- **Codecov**: Coverage reporting integrated with tests workflow

## Database Changes

1. Create table creation code using `dbDelta()`:
   ```php
   require_once ABSPATH . 'wp-admin/includes/upgrade.php';
   dbDelta($sql);
   ```
2. Use `maybe_create_table()` to check existence before creation
3. Network tables: `$wpdb->base_prefix . 'table_name'`
4. Test on both fresh installs and existing data
5. Match WordPress core schemas where applicable

## Debugging

### Logs

- Development: `debug.log` (WordPress debug log)
- VM: `/srv/www/example.com/logs/` (access and error logs)
- Diagnostics: `https://NETWORK.URL/wp-admin/options.php?page=pressbooks_diagnostics`

### Cache

- Pressbooks uses WordPress object cache with `pb` group
- Always clear cache during development
- WordPress transients for expensive operations

### Common Issues

1. **Plugin not loading**: Check `composer install` was run (autoloader needed)
2. **Styles broken**: Run `npm run build` to compile assets
3. **Tests failing**: Check `.env.testing` configuration, run `composer install`
4. **Standards failing**: Run `composer fix` first, then check remaining issues

## Theme Development

### Building Book Themes

- Parent theme: McLuhan (`pressbooks-book`) — provides reading interface
- Child themes customize via Buckram SCSS variables
- Buckram uses `!default` flag for layered overrides
- Shapeshifter: author-selectable fonts across web, ebook, PDF

### Key Commands

```bash
# In the theme directory:
composer install
npm install
npm run build
```

### Resources

- [Theme development guide](https://pressbooks.org/dev-guides/theme-development/)
- [Buckram](https://github.com/pressbooks/buckram) — SCSS components
- [Aetna](https://github.com/pressbooks/aetna) — Pattern library (docs: https://aetna.pressbooks.org/)
