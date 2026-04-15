---
name: pressbooks-development
description: "Pressbooks development context for the core WordPress multisite book publishing plugin. Use when: implementing features, fixing bugs, writing tests, reviewing code, working with the DI container, Blade templates, Eloquent models, export/import modules, custom post types, WordPress multisite, accessibility patterns, internationalization, or coding standards in the pressbooks plugin."
argument-hint: "Describe what you need help with in the Pressbooks codebase"
---

# Pressbooks Development

Comprehensive development context for the Pressbooks core plugin — a WordPress Multisite book publishing platform.

## When to Use

- Implementing features or fixing bugs in the core `pressbooks` plugin
- Working with the DI Container, Service Provider, Blade, or Eloquent
- Writing or modifying export/import modules
- Handling custom post types (front-matter, back-matter, chapter, part)
- Ensuring accessibility (WCAG 2.1 AA) compliance
- Internationalizing user-facing strings
- Writing or reviewing unit tests
- Following Pressbooks coding standards and Git workflow

## Procedure

1. **Identify the task type**: Is this architecture, implementation, review, or testing?
2. **Load the relevant reference**:
   - Architecture & patterns → [references/architecture.md](./references/architecture.md)
   - Coding standards & conventions → [references/code-conventions.md](./references/code-conventions.md)
   - Testing patterns & coverage → [references/testing-guide.md](./references/testing-guide.md)
   - Git, CI/CD, builds, workflows → [references/workflows.md](./references/workflows.md)
3. **Apply conventions**: Follow the standards and patterns from the references
4. **Verify**: Run quality checks before finishing (`composer fix`, `composer standards`, `composer test`, `npm run lint`, `npm run build`)

## Key Files

- `inc/class-book.php` — Book structure, CPTs, content organization
- `inc/class-container.php` — DI Container wrapper
- `inc/class-serviceprovider.php` — Service registration
- `hooks.php` — Frontend hook registrations
- `hooks-admin.php` — Admin hook registrations
- `functions.php` — Template helper functions
- `compatibility.php` — PHP/WordPress version checks
- `phpcs.ruleset.xml` — Coding standards rules
- `vite.config.js` — Asset build entry points
- `assets/src/scripts/a11y.js` — Accessibility patterns
- `.github/CONTRIBUTING.md` — Contributor guidelines
