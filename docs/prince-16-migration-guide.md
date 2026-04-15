# Prince XML 16 Migration Guide

> **Audience:** Pressbooks stakeholders, support staff, QA, and developers with custom themes.
>
> **Tracking Issue:** [pressbooks/pressbooks#4418](https://github.com/pressbooks/pressbooks/issues/4418)

## Overview

Prince XML is the engine that converts your Pressbooks content into PDF. We are upgrading from Prince 15 to Prince 16 to benefit from security patches, performance improvements, and better compliance with CSS standards.

**Bottom line: Your existing books will continue to look the same. No action is required for standard Pressbooks themes.**

---

## What Changed

### 1. Page Break Decorations

**Before (Prince 15):** When a textbox, sidebar, or highlighted section was long enough to span across a page break, borders, backgrounds, and padding appeared on both pages automatically. This was a non-standard behavior specific to Prince 15.

**After (Prince 16):** Prince 16 follows the official CSS specification, which only shows borders and backgrounds on the first page fragment. Without our update, long textboxes would appear to "lose" their styling on the second page.

**What we did:** We explicitly set the behavior back to "clone" mode, so borders, backgrounds, and padding continue to appear on every page fragment — matching what you've always seen.

### 2. List Indentation

**Before (Prince 15):** Lists (numbered and bulleted) used margin-based indentation, a method specific to Prince's older rendering engine.

**After (Prince 16):** Prince 16 uses padding-based indentation, matching how web browsers render lists.

**What we did:** We ensured all lists — including those inside textboxes, endnotes, and interactive content (H5P) — have the correct padding values so they remain properly indented.

### 3. Section First Pages (Running Headers & Footers)

**Before (Prince 15):** Running headers and footers (the small text at the top and bottom of each page showing chapter title, book title, or page number) were automatically hidden on the first page of each new chapter or section.

**After (Prince 16):** Prince 16 changed how "first page" is defined — it now means the very first page of the entire book, not the first page of each chapter.

**What we did:** We updated our stylesheets to use Prince 16's new "first page of each section" syntax, so running headers and footers continue to be hidden on the first page of every chapter, just as before.

### 4. Font Weight Rendering

**Before (Prince 15):** Some `@font-face` declarations used relative keywords (`lighter`, `bolder`) as weight descriptors. Prince 15 was lenient and accepted these despite them being invalid per the CSS specification. A side-effect was that body text could render slightly lighter than the font's intended Regular weight.

**After (Prince 16):** Prince 16 strictly validates `@font-face` descriptors. Invalid values like `lighter` corrupted the entire font family matching, causing only one variant to load for all weight requests (no bold, no light — just one weight).

**What we did:** We replaced all relative keywords with their correct numeric values (`lighter` → `300`, `bolder` → `800`) across all theme font definitions. Body text now renders at the true Regular weight as the font designer intended. This may appear very slightly heavier than Prince 15 output — the difference is subtle and reflects the correct rendering.

---

## What You Need To Do

### Standard Pressbooks Themes

**Nothing.** The update is automatic. All built-in themes (McLuhan, Clarke, Jacobs, Malala, Hamilton, etc.) are updated as part of this release.

### Custom Themes

If your theme uses standard Buckram variables and imports, **no changes are needed**. The fixes are applied at the framework level.

If your theme includes any of the following (rare), review those rules or contact support for guidance:

- Custom `@page:first` CSS rules — should be changed to `@page:first-of-group`
- Custom `box-decoration-break` declarations
- Custom list `margin-left` overrides without corresponding `padding-left`
- Custom `@font-face` declarations using `lighter` or `bolder` as `font-weight` descriptors

### Custom CSS (via the Pressbooks Custom Styles editor)

If you've added custom CSS that uses `@page:first`, `box-decoration-break`, or list margin overrides, review those rules. Contact support if unsure.

---

## Known Differences

These are expected and do not indicate a problem:

- **Minor text reflow:** Prince 16 includes an updated line-breaking engine. The same content may wrap slightly differently, causing minor shifts in where page breaks fall. This is cosmetic and expected.
- **Slightly heavier body text:** As described above, body text now renders at the font's true Regular weight. The difference from Prince 15 is subtle.

---

## Timeline & Rollout

| Phase | Description | Status |
|-------|-------------|--------|
| Development | Code changes to Buckram, Pressbooks plugin, and themes | Complete |
| QA Testing | Visual regression testing across themes and export formats | Pending |
| Staging Deployment | Deploy to staging environment for broader testing | Pending |
| Production Rollout | Roll out to all Pressbooks networks | Pending |

*Dates will be updated as the rollout progresses.*

---

## Appendix — Technical Details

### CSS Properties Changed

| Property | Prince 15 Behavior | Prince 16 Behavior | Fix Applied |
|----------|--------------------|--------------------|-------------|
| `box-decoration-break` | Defaulted to `clone` (non-standard) | Defaults to `slice` (CSS spec) | Explicit `box-decoration-break: clone` added to textboxes, asides, blockquotes |
| `@page <name>:first` | Matched first page of each `prince-page-group` | Matches first page of entire document only | Changed to `@page <name>:first-of-group` (new Prince 16 pseudo-class) |
| List indentation | `margin-left` controlled indentation | `padding-left` controls indentation | Added `padding-left` to textbox lists, endnote lists, H5P lists |
| `@font-face font-weight` | Accepted relative keywords (`lighter`, `bolder`) | Strictly validates — rejects relative keywords | Replaced with numeric values (`300`, `800`) |

### Repositories and PRs

| Repository | Change Summary |
|------------|----------------|
| **Buckram** | `box-decoration-break`, list padding, `@page:first-of-group`, stylelint config |
| **Pressbooks plugin** | Prince version bump (16+), DocRaptor pipeline 11, H5P list padding, oEmbed link fix, `get_permalink()` fallback |
| **pressbooks-book** | `@font-face` weight descriptors (EncodeSans, Raleway) |
| **pressbooks-hamilton** | `@font-face` weight descriptors (EncodeSans, Raleway) |

### Affected Buckram Files

| File | Change |
|------|--------|
| `variables/_elements.scss` | New `$box-decoration-break` variable |
| `variables/_specials.scss` | New textbox/endnote list padding variables |
| `components/specials/_textboxes.scss` | `box-decoration-break: clone`, list `padding-left` |
| `components/specials/_pullquotes.scss` | `box-decoration-break: clone` on `aside`/`.aside` |
| `components/elements/_blockquotes.scss` | `box-decoration-break: $box-decoration-break` |
| `components/specials/_footnotes.scss` | `padding-left` on `.endnotes ol` |
| `components/structure/_running-content.scss` | `:first` → `:first-of-group` (6 rules) |
| `components/structure/_mixins.scss` | `:first` → `:first-of-group` in generated rules |

### Prince 16 References

- [Prince 16 Release Notes](https://www.princexml.com/releases/)
- [Prince CSS Properties — `box-decoration-break`](https://www.princexml.com/doc/css-props/#prop-box-decoration-break)
- [Prince CSS At-Rules — `@page`](https://www.princexml.com/doc/paged/#at-page)
- [CSS Fonts Level 4 — `@font-face` descriptors](https://www.w3.org/TR/css-fonts-4/#font-face-rule)

### Additional Bug Fixes Included

Two pre-existing bugs (unrelated to Prince 16 but discovered during testing) were fixed in the same release:

1. **oEmbed link rewriting:** `optimizedFixInternalLinks()` in the XHTML export was incorrectly rewriting oEmbed/interactive content URLs to fragment-only anchors, breaking embedded content in PDF output.
2. **Shortlink fallback:** `wp_get_shortlink()` returns an empty string in some multisite configurations. Added `get_permalink()` fallback to prevent empty `href` attributes on interactive content links.
