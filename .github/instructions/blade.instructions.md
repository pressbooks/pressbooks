---
description: "Use when writing or modifying Blade template files in Pressbooks. Covers template conventions, escaping, accessibility, internationalization, and separation of concerns."
applyTo: "**/*.blade.php"
---

# Blade Template Conventions

## Structure
- Store templates in `/templates/` or `/resources/views/`
- Use `@extends`, `@section`, `@include` for template inheritance
- Name clearly: `admin.blade.php`, `settings.blade.php`

## Output & Security
- `{{ $variable }}` for escaped output (safe by default)
- `{!! $variable !!}` only for pre-sanitized HTML
- Never trust raw user input in templates

## Separation of Concerns
- Templates are presentational only — no database queries, no WordPress hooks
- Pass all data from PHP controllers/classes:
  ```php
  blade()->render('view-name', ['books' => $books]);
  ```
- Delegate complex logic to PHP before rendering
- Keep `@if` / `@foreach` minimal and readable

## Accessibility
- Use semantic HTML: `<button>`, `<nav>`, `<article>`, `<section>`, `<main>`
- Never use `<div>` for interactive elements — use proper elements
- Include `aria-label` on icon-only buttons
- Associate `<label>` elements with form inputs (`for` attribute)
- Use `aria-describedby` for supplementary help text
- Dynamic class for state:
  ```php
  <div class="book-status {{ $published ? 'book-status--published' : 'book-status--draft' }}">
  ```

## Internationalization
- Use `__()` and `_e()` with text domain `'pressbooks'`
- Add translator comments:
  ```php
  {{-- translators: %s is the book title --}}
  <h1>{{ sprintf(__('Editing: %s', 'pressbooks'), $title) }}</h1>
  ```
- Never concatenate translated strings

## Styling
- Never use inline styles — use CSS classes
- Avoid presentational class names — prefer semantic or BEM naming
- Define styles in separate stylesheet files
