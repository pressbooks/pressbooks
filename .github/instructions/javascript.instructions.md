---
description: "Use when writing or modifying JavaScript or CSS/SCSS files in Pressbooks. Covers ES6+ patterns, Alpine.js, vanilla JS, accessibility, CSS conventions, and asset building."
applyTo: ["**/*.js", "**/*.css", "**/*.scss"]
---

# JavaScript & CSS Conventions

## JavaScript
- ES6+ features: `const`/`let` (no `var`), arrow functions, template literals, destructuring
- Vanilla JS preferred over jQuery (legacy code may still use jQuery)
- Alpine.js for interactive components requiring state management
- Use `data-*` attributes for JavaScript hooks, not CSS classes
- WordPress i18n: `const { __ } = wp.i18n;` for translatable strings in JS
- Keep scripts modular and testable

## Accessibility in JS
- All interactive elements must be keyboard-operable
- Manage focus when content changes dynamically (modals, dropdowns, AJAX updates)
- Use ARIA live regions for status announcements (`role="status"`, `role="alert"`)
- Add `aria-label` to elements without visible text labels
- Follow patterns in `assets/src/scripts/a11y.js`:
  - `role="status"` on `.updated`, `.notice` divs
  - `role="alert"` on `.error` divs
  - `aria-sort` on sortable table headers
  - `aria-describedby` on date pickers

## Alpine.js
- Use `x-data`, `x-show`, `x-on:click`, `x-bind` directives in HTML
- Keep component state minimal and co-located with markup
- Prefer Alpine over heavier frameworks (React, Vue)

## CSS / SCSS
- Define all styles in separate stylesheets — never inline styles
- CSS custom properties (variables) preferred over SCSS variables for new code
- BEM or semantic naming conventions (`.book-status--published`, not `.red-text`)
- Organize by component or feature
- Legacy SCSS is acceptable; migrate to CSS properties when refactoring
- No CSS frameworks unless Tailwind is specifically needed

## Asset Building
- Build tool: Vite (`vite.config.js`), outputs to `assets/dist/`
- Entry points: JS in `assets/src/scripts/`, SCSS in `assets/src/styles/`
- Lint: `npm run lint:scripts` (ESLint), `npm run lint:styles` (Stylelint)
- Build: `npm run build`
- Dev: `npm run watch`
