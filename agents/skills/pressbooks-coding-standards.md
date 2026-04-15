# Pressbooks Coding Standards Skill

Use this skill when implementing or reviewing code in Pressbooks repositories.

## Core Rule
Always follow the coding standard configured for the repository you are working in.
Do not assume that all Pressbooks repositories use the same tooling or style rules.

Inspect the current repository and match its actual standards, structure, and conventions.

## Repository-Specific Guidance

### If working in `pressbooks/pressbooks`
This repository uses Pressbooks Coding Standards based on:
- Human Made Coding Standards
- WordPress Coding Standards
- Pressbooks-specific rules

Reference:
- `https://github.com/pressbooks/coding-standards/blob/production/Pressbooks/ruleset.xml`

Use:
```bash
composer fix
composer standards
```

## Expected underlying tools:
- composer fix → vendor/bin/phpcbf
- composer standards → vendor/bin/phpcs

## When working in pressbooks/pressbooks:
- match the existing core plugin style closely
- prefer consistency with surrounding code over idealized modernization
- be especially careful with WordPress boundary conventions
- avoid introducing patterns that feel more like newer Laravel-style plugins unless they clearly fit the surrounding code

## If working in other Pressbooks repositories

Some newer plugins may use different conventions, such as Laravel Pint / PSR-12 style.

In those cases:
- inspect the repository configuration
- use the repository’s configured commands and standards
- do not apply pressbooks/pressbooks assumptions blindly

## PHP and Naming Rules

Unless the current repository clearly dictates otherwise:
- use namespaces where the repository does so
- follow PSR-4 autoloading as configured
- use camelCase for methods and properties
- use UPPERCASE for constants
- use snake_case at WordPress boundaries
- use declare(strict_types=1); when appropriate and consistent with the surrounding code

## I18n 
- prepare all user-facing strings for translation
- use the correct text domain
- do not concatenate translated strings
- use placeholders and translator comments when helpful

## Security
- sanitize input
- escape output
- use nonces when needed
- perform capability checks
- treat imported content as untrusted until normalized and sanitized

## Change Discipline
- make small, surgical changes
- match surrounding code style
- do not refactor unrelated code
- do not introduce speculative abstractions
- remove only unused code introduced by your own changes

## PB Lab Guidance for This Feature

For the Google Docs Import PB Lab, assume the implementation lives in pressbooks/pressbooks unless explicitly stated otherwise.

That means:
- follow core plugin conventions
- use the core plugin coding standard
- optimize for consistency with existing core code
- avoid mixing in conventions from newer plugins unless intentionally approved