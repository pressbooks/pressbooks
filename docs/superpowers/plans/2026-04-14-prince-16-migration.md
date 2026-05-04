# Prince XML 16 Migration Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Migrate Pressbooks PDF exports from Prince XML 15.x to Prince XML 16.x, remediating three breaking changes while maintaining visual consistency across all PDF output formats (digital PDF, print PDF, DocRaptor).

**Architecture:** Changes span two repositories — the standalone Buckram SCSS framework (where all CSS-level fixes live) and the Pressbooks plugin (version checks, DocRaptor pipeline, H5P list styles). A clean break to Prince 16+ is made with no backward compatibility for Prince 15.

**Tech Stack:** PHP 8.3+, WordPress Multisite, SCSS (Buckram), Prince XML 16.x, DocRaptor API

**Tracking Issue:** https://github.com/pressbooks/pressbooks/issues/4418

---

## Repositories

| Repo | Path | Branch | Role |
|------|------|--------|------|
| Buckram (standalone) | `/Users/arzola/code/opensource/buckram/` | `dev` | SCSS framework — all CSS-level fixes go here |
| Pressbooks plugin | `/Users/arzola/code/pbdev/web/app/plugins/pressbooks/` | `dev` | Plugin-level fixes (version checks, DocRaptor, H5P) |
| pressbooks-book theme | `/Users/arzola/code/pbdev/web/app/themes/pressbooks-book/` | — | Downstream Buckram consumer (no direct changes needed) |

> **Important:** Buckram changes MUST be made in the standalone repo, then released as a new Buckram version. The copy at `pressbooks-book/packages/buckram/` is a downstream consumer and should NOT be edited directly.

---

## Breaking Changes Summary

| # | Change | Prince 15 Behavior | Prince 16 Behavior | Risk |
|---|--------|--------------------|--------------------|------|
| 1 | `box-decoration-break` default | `clone` (non-standard) | `slice` (CSS spec compliant) | **HIGH** — all bordered/padded elements across page breaks |
| 2 | List indentation mechanism | `margin` based | `padding` based | **MEDIUM** — most base cases safe, 3 gaps found |
| 3 | `@page:first` scope | First page of each `prince-page-group` | First page of entire document only | **HIGH** — all running content suppression affected |

---

## File Map

### Buckram (standalone repo: `/Users/arzola/code/opensource/buckram/`)

| File | Change Type | What Changes |
|------|-------------|--------------|
| `assets/styles/components/specials/_textboxes.scss` | Modify | Add `box-decoration-break: clone` to `.textbox` base rule (~line 98); add `padding-left` to `.textbox ol, .textbox ul` (lines 117-129) |
| `assets/styles/components/specials/_pullquotes.scss` | Modify | Add `box-decoration-break: clone` to `aside`/`.aside` rules (~line 11) |
| `assets/styles/components/elements/_blockquotes.scss` | Modify | Add `box-decoration-break: clone` to `blockquote` base rule |
| `assets/styles/components/elements/_lists.scss` | Audit | Verify `%ol`/`%ul` placeholders have both `margin-left` and `padding-left` (lines 10-32) — already safe |
| `assets/styles/components/specials/_footnotes.scss` | Modify | Add `padding-left` to `.endnotes ol` (lines 134-136) |
| `assets/styles/components/structure/_running-content.scss` | Modify | Change 6 `@page <name>:first` rules to `@page <name>:first-of-group` (lines 83-135) |
| `assets/styles/components/structure/_mixins.scss` | Modify | Update `page-structure` mixin to emit `:first-of-group` instead of `:first` in ~12 generated rules (lines 350-500) |
| `assets/styles/components/structure/_numbering.scss` | Audit | Verify 14 `prince-page-group: start` declarations are retained (lines 11-85) — NOT deprecated in Prince 16 |
| `assets/styles/components/structure/_blank.scss` | Audit | Verify `@page:blank` rules still function correctly (lines 90-146) |
| `assets/styles/variables/_specials.scss` | Modify | Add `$box-decoration-break` variable with `clone` default |
| `assets/styles/variables/_elements.scss` | Audit | Verify list margin/padding default variable values (lines 760-800) |

### Pressbooks Plugin (`/Users/arzola/code/pbdev/web/app/plugins/pressbooks/`)

| File | Change Type | What Changes |
|------|-------------|--------------|
| `inc/utility/namespace.php` | Modify | Bump minimum Prince version from `11` to `16` (line 329) |
| `inc/modules/export/prince/class-docraptor.php` | Modify | Update DocRaptor pipeline from `9.2` to `11` (line 134) |
| `inc/interactive/class-h5p.php` | Modify | Add `padding-left` to H5P extracted content list styles (lines 410-413) |
| `docs/prince-16-migration-guide.md` | Create | Stakeholder-facing explanation document |

### pressbooks-book Theme (`/Users/arzola/code/pbdev/web/app/themes/pressbooks-book/`)

| File | Change Type | What Changes |
|------|-------------|--------------|
| `package.json` | Modify (post-release) | Update Buckram dependency from `^1.8.8` to new version after Buckram release |
| `packages/buckram/` | Temporary | Symlink or npm-link to local Buckram during development (Task 8). Restored via `npm install` after Buckram release. |

> **How Buckram flows into PDF exports:** `npm install` → `node_modules/buckram` → postinstall copies to `packages/buckram/` → Pressbooks PHP SCSS compiler (`Pressbooks\Sass::pathToGlobals()`) reads `packages/buckram/assets/styles/` as an include path at runtime → theme SCSS files `@import 'variables/...'` and `@import 'components/...'` resolve to Buckram via this path.

### No Changes Required

| Scope | Reason |
|-------|--------|
| All 25 Pressbooks themes | Themes use variable overrides only; no direct `@page:first`, `box-decoration-break`, or list padding rules. Verified via sampling of McLuhan, Clarke, Malala, Jacobs. |
| `gridonic/princexml-php` library | v1.2.1 is a thin CLI wrapper; no Prince-version-specific logic. Compatible as-is. |
| Cover generator SCSS (`class-pdf.php`) | Has 2 `prince-page-group: start` but no `:first` rules nearby. Not affected. |

---

## Task 1: Add `box-decoration-break: clone` to All Bordered/Padded Elements

**Risk:** HIGH — This is the most visually impactful breaking change.
**Repo:** Buckram (standalone)
**Strategy:** Add `box-decoration-break: clone` universally to restore Prince 15 behavior. Expose as a SCSS variable for theme-level override.

### Context

Prince 15 defaulted `box-decoration-break` to `clone` (non-standard). Prince 16 follows the CSS spec default of `slice`. With `slice`, when an element with borders/padding/background spans a page break, the decoration is "sliced" — borders and backgrounds only appear on the first fragment. With `clone`, each fragment gets the full decoration (all borders, padding, background).

**Zero** explicit `box-decoration-break` declarations exist anywhere in the codebase today. Everything relied on Prince 15's non-standard default.

### Affected Elements

| Element | Border/Padding/BG | `page-break-inside: avoid` | Risk |
|---------|-------------------|---------------------------|------|
| `.textbox` (all variants) | Yes — borders, padding, backgrounds | **No** | **HIGH** |
| `.textbox.shaded` | Yes — background color | **No** | **HIGH** |
| Educational variants (`.learning-objectives`, `.key-takeaways`, `.exercises`, `.examples`) | Yes — inherits `.textbox` | **No** | **HIGH** |
| `.bcc-*` variants | Yes — inherits `.textbox` | **No** | **HIGH** |
| `aside` / `.aside` / `.sidebar` | Yes — 1px solid top/bottom borders | **No** | **MEDIUM-HIGH** |
| `blockquote` | No defaults, but theme-configurable | **No** | **MEDIUM** |
| Pullquotes | Yes | **Yes** — safe | LOW |

### Step 1: Add SCSS variable

Add a `$box-decoration-break` variable to Buckram's specials variables file.

**File:** `assets/styles/variables/_specials.scss`

```scss
$box-decoration-break: clone !default;
```

- [ ] Add `$box-decoration-break: clone !default;` to `assets/styles/variables/_specials.scss`, in the textbox variables section.

### Step 2: Apply to `.textbox` base rule

**File:** `assets/styles/components/specials/_textboxes.scss` (~line 98)

Add to the `.textbox` base rule (which all textbox variants inherit from):

```scss
.textbox {
  // ... existing styles ...
  box-decoration-break: $box-decoration-break;
}
```

- [ ] Add `box-decoration-break: $box-decoration-break;` to the `.textbox` base rule in `_textboxes.scss`.

### Step 3: Apply to `aside` / `.aside`

**File:** `assets/styles/components/specials/_pullquotes.scss` (~line 11)

```scss
aside,
.aside {
  // ... existing styles ...
  box-decoration-break: $box-decoration-break;
}
```

- [ ] Add `box-decoration-break: $box-decoration-break;` to the `aside`/`.aside` rule in `_pullquotes.scss`.

### Step 4: Apply to `blockquote`

**File:** `assets/styles/components/elements/_blockquotes.scss`

```scss
blockquote {
  // ... existing styles ...
  box-decoration-break: $box-decoration-break;
}
```

- [ ] Add `box-decoration-break: $box-decoration-break;` to the `blockquote` base rule in `_blockquotes.scss`.

### Step 5: Verify pullquotes are safe

Pullquotes have `page-break-inside: avoid`, so they should never span a page break. Confirm this is still the case.

- [ ] Verify `_pullquotes.scss` pullquote rules include `page-break-inside: avoid` or `break-inside: avoid`.

### Verification

```
Build Buckram SCSS successfully with no compilation errors.
Visual comparison: Generate PDF of a book with long textboxes that span page breaks.
Confirm borders/padding/background appear on BOTH page fragments (clone behavior).
```

---

## Task 2: Fix List Indentation Gaps

**Risk:** MEDIUM
**Repos:** Buckram (standalone) + Pressbooks plugin
**Strategy:** Add explicit `padding-left` wherever only `margin-left` is currently set on lists, to ensure indentation works under Prince 16's padding-based model.

### Context

Prince 16 changed list indentation from `margin`-based to `padding`-based, matching browser behavior. Buckram's base `%ol`/`%ul` placeholders (in `_lists.scss:10-32`) already set **both** `margin-left` and `padding-left`, so the base case is safe. Three gaps were identified where only `margin` is set.

### Gap 1: Textbox lists

**File:** Buckram `assets/styles/components/specials/_textboxes.scss` (lines 117-129)

Currently:
```scss
.textbox ol,
.textbox ul {
  margin-left: ...;
  // No padding-left
}
```

Fix: Add `padding-left` matching the margin value (or a sensible default).

- [ ] Add `padding-left` to `.textbox ol, .textbox ul` in `_textboxes.scss` (lines 117-129).

### Gap 2: Endnote lists

**File:** Buckram `assets/styles/components/specials/_footnotes.scss` (lines 134-136)

Currently:
```scss
.endnotes ol {
  margin-left: ...;
  // No padding-left
}
```

Fix: Add `padding-left` to match.

- [ ] Add `padding-left` to `.endnotes ol` in `_footnotes.scss` (lines 134-136).

### Gap 3: H5P extracted content

**File:** Pressbooks plugin `inc/interactive/class-h5p.php` (lines 410-413)

Currently sets `margin` on `li` elements but not `padding` on parent `ol`/`ul`.

- [ ] Add `padding-left` to H5P extracted content list styles in `class-h5p.php` (lines 410-413).

### Step 4: Audit base list placeholders (confirmation only)

**File:** Buckram `assets/styles/components/elements/_lists.scss` (lines 10-32)

- [ ] Confirm `%ol` and `%ul` placeholders set both `margin-left` and `padding-left`. No changes expected.

### Step 5: Audit list variable defaults

**File:** Buckram `assets/styles/variables/_elements.scss` (lines 760-800)

- [ ] Confirm list margin and padding variables have sensible defaults. No changes expected.

### Verification

```
Build Buckram SCSS successfully.
Visual comparison: Generate PDF with ordered/unordered lists inside textboxes, endnotes, and H5P content.
Confirm lists are indented correctly (not flush-left).
```

---

## Task 3: Update `@page:first` to `:first-of-group` in Running Content

**Risk:** HIGH — Most critical change. Affects all running headers/footers.
**Repo:** Buckram (standalone)
**Strategy:** Replace all `@page <name>:first` selectors with `@page <name>:first-of-group`. The `prince-page-group: start` declarations that define group boundaries are NOT deprecated and remain unchanged.

### Context

In Prince 15, `@page:first` matched the first page of each `prince-page-group`. In Prince 16, `@page:first` matches ONLY the very first page of the entire document (per CSS spec). The new Prince-specific `:first-of-group` pseudo-class restores the per-group behavior.

Currently in Buckram:
- **14** `prince-page-group: start` declarations in `_numbering.scss` (one per section type) — these define group boundaries and are NOT affected.
- **6** explicit `@page <name>:first` rules in `_running-content.scss` — these suppress running headers/footers on each section's first page.
- **~12** mixin-generated `@page <name>:first:right` / `:first:left` rules via the `page-structure` mixin in `_mixins.scss`.

All of these `:first` usages need to become `:first-of-group`.

### Step 1: Update explicit rules in `_running-content.scss`

**File:** `assets/styles/components/structure/_running-content.scss` (lines 83-135)

The 6 rules to update:

```scss
// BEFORE (Prince 15):
@page front-matter:first { ... }
@page introduction:first { ... }
@page chapter:first { ... }
@page back-matter:first { ... }
@page part:first { ... }
@page bibliography:first { ... }

// AFTER (Prince 16):
@page front-matter:first-of-group { ... }
@page introduction:first-of-group { ... }
@page chapter:first-of-group { ... }
@page back-matter:first-of-group { ... }
@page part:first-of-group { ... }
@page bibliography:first-of-group { ... }
```

- [ ] Replace all 6 `@page <name>:first` with `@page <name>:first-of-group` in `_running-content.scss`.

### Step 2: Update the `page-structure` mixin in `_mixins.scss`

**File:** `assets/styles/components/structure/_mixins.scss` (lines 350-500)

The `page-structure` mixin generates rules like:
```scss
@page #{$page-type}:first:right { ... }
@page #{$page-type}:first:left { ... }
```

These need to become:
```scss
@page #{$page-type}:first-of-group:right { ... }
@page #{$page-type}:first-of-group:left { ... }
```

- [ ] Update the `page-structure` mixin to emit `:first-of-group` instead of `:first` in all generated `@page` rules.
- [ ] Search for any other `:first` usage in the mixin file that may need updating.

### Step 3: Verify `prince-page-group: start` declarations are retained

**File:** `assets/styles/components/structure/_numbering.scss` (lines 11-85)

These 14 declarations define where page groups begin. They are NOT deprecated in Prince 16 and must remain.

- [ ] Confirm all 14 `prince-page-group: start` declarations are present and unchanged in `_numbering.scss`.

### Step 4: Verify no `:first` remains in Prince-related SCSS

- [ ] Search the entire Buckram codebase for `@page.*:first[^-]` to ensure no un-migrated `:first` pseudo-classes remain. The regex should NOT match `:first-of-group`.

### Verification

```
Build Buckram SCSS successfully.
Visual comparison: Generate PDF of a multi-chapter book.
Confirm: Running headers/footers are SUPPRESSED on the first page of each chapter/section.
Confirm: Running headers/footers APPEAR on the second and subsequent pages of each chapter/section.
```

---

## Task 4: Audit `@page:blank` Rules

**Risk:** LOW — Prince 16 release notes do not list changes to `:blank`, but worth verifying.
**Repo:** Buckram (standalone)

### Context

`@page:blank` rules in Buckram suppress running content on blank pages (inserted for recto/verso pagination). Prince 16 does not document changes to `:blank` behavior, but given the `:first` scope change, a quick audit is prudent.

**File:** `assets/styles/components/structure/_blank.scss` (lines 90-146)

- [ ] Review all `@page:blank` rules in `_blank.scss`.
- [ ] Verify they do not depend on `:first` or `prince-page-group` in ways affected by Prince 16.
- [ ] Test: Generate a PDF with forced blank pages (e.g., chapters starting on recto). Confirm blank pages have no running content.

### Verification

```
Visual comparison: Generate PDF with recto-start chapters.
Confirm blank pages are truly blank (no headers, footers, or page numbers).
```

---

## Task 5: Bump Minimum Prince Version

**Risk:** LOW
**Repo:** Pressbooks plugin

### Context

The plugin checks the installed Prince version before export. Currently the minimum is `11` (very old). Since we're making a clean break to Prince 16+, bump this.

**File:** `inc/utility/namespace.php` (line 329)

```php
// BEFORE:
if ( version_compare( $version, '11' ) < 0 ) {

// AFTER:
if ( version_compare( $version, '16' ) < 0 ) {
```

- [ ] Change the minimum Prince version from `'11'` to `'16'` in `inc/utility/namespace.php` (line 329).
- [ ] Update any related error message to mention Prince 16 as the minimum.
- [ ] Search the plugin for any other Prince version references that may need updating.

### Verification

```bash
lando vendor/bin/phpunit --configuration phpunit.xml --filter prince tests/
```

---

## Task 6: Update DocRaptor Pipeline Version

**Risk:** LOW
**Repo:** Pressbooks plugin

### Context

DocRaptor's `pipeline` parameter controls which Prince version is used server-side. Pipeline `9.2` = Prince 14.3. Pipeline `11` = Prince 16. The user confirmed pipeline `11` maps to Prince 16.

**File:** `inc/modules/export/prince/class-docraptor.php` (line 134)

```php
// BEFORE:
'pipeline' => '9.2',

// AFTER:
'pipeline' => '11',
```

- [ ] Change DocRaptor pipeline from `'9.2'` to `'11'` in `class-docraptor.php` (line 134).
- [ ] Check if the same pipeline value is used in `class-docraptorprint.php` and update if so.

### Verification

```bash
lando vendor/bin/phpunit --configuration phpunit.xml --filter docraptor tests/
```

---

## Task 7: Prince PHP Library Compatibility Check

**Risk:** LOW
**Repo:** Pressbooks plugin (Composer dependency)

### Context

The `gridonic/princexml-php` library (v1.2.1, last updated 2016) is a thin CLI wrapper around the Prince binary. It constructs command-line arguments and calls the executable. It has no Prince-version-specific logic.

- [ ] Verify `gridonic/princexml-php` does not filter or reject any CLI flags used by the plugin.
- [ ] Verify Prince 16's CLI interface is backward-compatible with the arguments the plugin passes (check `class-pdf.php` CLI construction).
- [ ] If any incompatibility is found, document it and propose a fix (e.g., fork the library or replace with direct `proc_open` calls).

### Verification

```
Generate a PDF locally using Prince 16 CLI through the plugin's export flow.
Confirm the export completes without CLI errors.
```

---

## Task 8: Point pressbooks-book at Local Buckram for Development

**Risk:** LOW — Development environment setup only; must be reverted before release.
**Repos:** pressbooks-book theme + standalone Buckram

### Context

The `pressbooks-book` theme consumes Buckram via npm (`"buckram": "^1.8.8"` in `package.json`). During `npm install`, a `postinstall` script copies `node_modules/buckram` into `packages/buckram/`, which is the directory the Pressbooks PHP SCSS compiler actually reads at runtime (via `Pressbooks\Sass::pathToGlobals()`).

During development and testing of the Prince 16 migration, we need the theme to use our **local Buckram build** (from `/Users/arzola/code/opensource/buckram/`) instead of the pinned npm version. Without this, no Buckram CSS changes will be picked up during PDF exports.

### How It Works

```
Normal flow (production):
  npm install → node_modules/buckram (v1.8.8 from npm) → postinstall copies to packages/buckram/

Development flow (what we need):
  npm link or symlink → packages/buckram/ points to local Buckram repo → PHP SCSS compiler picks up local changes
```

### Option A: npm link (Recommended)

```bash
# In the standalone Buckram repo:
cd /Users/arzola/code/opensource/buckram
npm link

# In the pressbooks-book theme:
cd /Users/arzola/code/pbdev/web/app/themes/pressbooks-book
npm link buckram
```

Then re-run the postinstall copy to update `packages/buckram/`:

```bash
npx recursive-copy-cli node_modules/buckram packages/buckram -w
```

**Note:** After `npm link`, every time you change Buckram SCSS, you need to re-run the `recursive-copy-cli` command to sync `packages/buckram/` — OR replace `packages/buckram/` with a symlink directly (see Option B).

### Option B: Direct symlink of packages/buckram

Replace the `packages/buckram/` directory with a symlink to the local Buckram repo:

```bash
cd /Users/arzola/code/pbdev/web/app/themes/pressbooks-book
rm -rf packages/buckram
ln -s /Users/arzola/code/opensource/buckram packages/buckram
```

This is simpler for active development — every SCSS change in the standalone Buckram repo is instantly visible to the PHP SCSS compiler. No re-copy needed.

**Caution:** Do NOT commit this symlink. The `packages/buckram/` directory is tracked in git and should remain a real directory with copied files in the repository.

### Steps

- [ ] Choose Option A (npm link) or Option B (symlink) for local development.
- [ ] Set up the local Buckram link in `pressbooks-book`.
- [ ] Verify the link works: make a trivial SCSS change in local Buckram, generate a PDF export, and confirm the change appears.
- [ ] Document the setup in the PR description so other developers can replicate it.

### Reverting (Before Release)

After testing is complete and Buckram is released as a new npm version:

```bash
# If using Option B (symlink):
cd /Users/arzola/code/pbdev/web/app/themes/pressbooks-book
rm packages/buckram
npm install
# postinstall will restore packages/buckram/ from the new npm version

# If using Option A (npm link):
cd /Users/arzola/code/pbdev/web/app/themes/pressbooks-book
npm unlink buckram
npm install
```

- [ ] After Buckram is released, revert the local link and update `package.json` to the new Buckram version.
- [ ] Run `npm install` to restore `packages/buckram/` from the new npm version.
- [ ] Verify `packages/buckram/` contains the released version, not a symlink.

---

## Task 9: Visual Regression Testing

**Risk:** N/A — This is the verification task.
**Repos:** Both
**Depends on:** Task 8 (local Buckram must be linked before testing)

### Prerequisite: Generate Baseline PDFs with Prince 15

**Do this BEFORE making any code changes.** These baselines are the "before" snapshots used to verify visual consistency after the migration.

#### Test Book Requirements

The test book(s) must exercise all three breaking changes. Ensure the content includes:
- At least one **long textbox** (standard, shaded, and/or educational variant) that spans a page break — tests `box-decoration-break`
- **Ordered and unordered lists inside a textbox** — tests list indentation
- **Lists in endnotes** — tests endnote list indentation
- A **multi-chapter book (3+ chapters)** with at least one chapter spanning 2+ pages — tests running header/footer suppression on first pages
- Chapters configured to **start on recto** — tests blank page behavior

If no existing test book covers all of these, add the missing content to a test book before exporting.

#### Themes to Export

1. **McLuhan** (default, minimal overrides)
2. **Clarke** (has custom `@page` rules — most likely to surface issues)

#### Export Formats

| Format | Purpose |
|--------|---------|
| **Digital PDF** (Prince local) | Primary comparison target |
| **Print PDF** (Prince local) | Tests recto-start / blank page behavior |
| **DocRaptor Digital PDF** (pipeline 9.2) | Optional — baseline for DocRaptor comparison after pipeline update |

#### Naming Convention

Save all baselines in a consistent location with clear names:

```
baseline-prince15-mcluhan-digital.pdf
baseline-prince15-mcluhan-print.pdf
baseline-prince15-clarke-digital.pdf
baseline-prince15-clarke-print.pdf
baseline-prince15-mcluhan-docraptor.pdf  (optional)
```

#### Steps

- [ ] Verify Prince 15 is the currently installed version.
- [ ] Ensure the test book(s) contain content that exercises all 3 breaking changes (textboxes spanning pages, lists in textboxes/endnotes, multi-chapter with running content).
- [ ] Export Digital PDF with McLuhan theme → save as `baseline-prince15-mcluhan-digital.pdf`.
- [ ] Export Print PDF with McLuhan theme → save as `baseline-prince15-mcluhan-print.pdf`.
- [ ] Export Digital PDF with Clarke theme → save as `baseline-prince15-clarke-digital.pdf`.
- [ ] Export Print PDF with Clarke theme → save as `baseline-prince15-clarke-print.pdf`.
- [ ] (Optional) Export DocRaptor PDF → save as `baseline-prince15-mcluhan-docraptor.pdf`.
- [ ] **Record page counts** for each baseline PDF (total pages). These will be compared against Prince 16 output to detect content reflow.
- [ ] Store all baselines in a known location for later comparison.

### Context

After all code changes, generate PDFs across a matrix of content types and compare visually to the Prince 15 baselines.

### Test Matrix

| Content Type | What to Check |
|-------------|---------------|
| Long textbox spanning page break | Borders/padding/background on both fragments |
| Shaded textbox spanning page break | Background color on both fragments |
| Educational textbox (learning-objectives) | Same as above |
| Sidebar / aside spanning page break | Top/bottom borders on both fragments |
| Blockquote with theme border | Border continuity across page break |
| Ordered list inside textbox | Proper indentation |
| Unordered list inside textbox | Proper indentation |
| Endnote list | Proper indentation |
| H5P extracted content with lists | Proper indentation |
| Multi-chapter book (3+ chapters) | Running headers suppressed on first page of each chapter |
| Chapter with > 2 pages | Running headers appear on pages 2+ |
| Part page | Running content suppressed on part first page |
| Front matter section | Running content behavior correct |
| Back matter / bibliography | Running content behavior correct |
| Book with recto-start chapters | Blank pages truly blank |

### Test Books

Use at least 2 themes:
1. **McLuhan** (default, minimal overrides)
2. **Clarke** (has custom `@page` rules — most likely to surface issues)

### Process

- [ ] Confirm Prince 15 baselines were generated (see Prerequisite above).
- [ ] Confirm Task 8 is complete (local Buckram linked in pressbooks-book).
- [ ] Build Buckram with all changes.
- [ ] Install Prince 16 locally.
- [ ] Generate test PDFs with Prince 16 (after changes) using the same test book(s) and themes.
- [ ] **Page count comparison:** Record total page count for each Prince 15 baseline and Prince 16 output. Any difference indicates content reflow and should be investigated before proceeding to visual comparison. Use the table below to track.
- [ ] Compare each row in the test matrix against the Prince 15 baselines.
- [ ] Document any visual differences and determine if they are regressions or expected improvements.

#### Page Count Comparison

| PDF | Prince 15 Pages | Prince 16 Pages | Diff | Notes |
|-----|----------------|----------------|------|-------|
| McLuhan Digital | | | | |
| McLuhan Print | | | | |
| Clarke Digital | | | | |
| Clarke Print | | | | |

A page count difference of **0** is the target. Any non-zero diff should be investigated:
- **+pages:** Something is taking more space (e.g., `box-decoration-break: clone` adding decoration where `slice` removed it, or list padding adding width that causes wrapping).
- **-pages:** Something is taking less space (e.g., a missed `box-decoration-break` element where `slice` is now active, collapsing decoration).

---

## Task 10: Follow-Up Tickets (Out of Scope)

These improvements are enabled by Prince 16 but are NOT part of this migration. They should be tracked as separate issues.

### Accessibility / Tagged PDF

Prince 16 introduces new properties for tagged PDF and accessibility:
- `prince-pdf-tag-type` — assign PDF structure tags to HTML elements
- `prince-pdf-role-map` — map custom tags to standard PDF roles
- `@prince-pdf` at-rule — define tagged PDF structure
- CSS `content:` alt-text syntax — `content: url(image.png) / "alt text"`

Current state: **Zero** usage of any tagged PDF properties in the codebase. ~80+ `content:` declarations in Buckram are candidates for alt-text syntax.

- [ ] Create ticket: "Explore Prince 16 tagged PDF properties for improved PDF/UA compliance"
- [ ] Create ticket: "Add CSS alt-text to generated content declarations in Buckram"

### DocRaptor Pipeline Deprecation Monitoring

DocRaptor periodically deprecates old pipelines. Now that we're on pipeline `11`, monitor for future deprecations.

- [ ] Create ticket: "Monitor DocRaptor pipeline deprecation schedule"

---

## Task 11: Stakeholder Migration Guide

**Risk:** N/A — Documentation task.
**Repo:** Pressbooks plugin

### Context

Create a plain-language document explaining the Prince 16 migration for non-technical stakeholders, clients, and support staff. The document should reassure readers that existing books will continue to look the same, explain what changed and why, and include a technical appendix for developers.

**File:** `docs/prince-16-migration-guide.md`

### Document Structure

#### Section 1: Overview
- What is Prince XML (one sentence: "the engine that converts your book into PDF")
- Why we're upgrading (security patches, performance improvements, better standards compliance)
- **Bottom line:** "Your existing books will continue to look the same. No action is required."

#### Section 2: What Changed — Page Break Decorations
**Before/After description:**
- **Before (Prince 15):** When a textbox, sidebar, or highlighted section was long enough to span across a page break, borders, backgrounds, and padding appeared on both pages automatically. This was a non-standard behavior specific to Prince 15.
- **After (Prince 16):** Prince 16 follows the official CSS specification, which only shows borders and backgrounds on the first page fragment. Without our update, long textboxes would appear to "lose" their styling on the second page.
- **What we did:** We explicitly set the behavior back to "clone" mode, so borders, backgrounds, and padding continue to appear on every page fragment — matching what you've always seen.

#### Section 3: What Changed — List Indentation
**Before/After description:**
- **Before (Prince 15):** Lists (numbered and bulleted) used margin-based indentation, a method specific to Prince's older rendering engine.
- **After (Prince 16):** Prince 16 uses padding-based indentation, matching how web browsers render lists.
- **What we did:** We ensured all lists — including those inside textboxes, endnotes, and interactive content — have the correct padding values so they remain properly indented.

#### Section 4: What Changed — Section First Pages
**Before/After description:**
- **Before (Prince 15):** Running headers and footers (the small text at the top/bottom of each page showing chapter title, book title, or page number) were automatically hidden on the first page of each new chapter or section.
- **After (Prince 16):** Prince 16 changed how "first page" is defined — it now means the very first page of the entire book, not the first page of each chapter.
- **What we did:** We updated our stylesheets to use Prince 16's new "first page of each section" syntax, so running headers and footers continue to be hidden on the first page of every chapter, just as before.

#### Section 5: What You Need To Do
- **Standard Pressbooks themes:** Nothing. The update is automatic.
- **Custom themes:** If your theme uses standard Buckram variables and imports, no changes needed. If your theme includes custom `@page:first` CSS rules (rare), contact support for guidance.
- **Custom CSS:** If you've added custom CSS that uses `@page:first`, `box-decoration-break`, or list margin overrides, review those rules. Contact support if unsure.

#### Section 6: Timeline & Rollout
- Placeholder for dates and rollout phases.

#### Section 7: Appendix — Technical Details
- CSS properties changed: `box-decoration-break`, `@page:first` → `:first-of-group`, list `margin` → `padding`
- Prince 16 release notes links
- Affected Buckram files (summary table)
- Link to this migration plan
- Prince 16 documentation references

### Steps

- [ ] Draft `docs/prince-16-migration-guide.md` following the structure above.
- [ ] Review with stakeholders for tone and completeness.
- [ ] Finalize after Prince 16 testing is complete (update timeline section with real dates).

---

## Execution Order

Tasks can be partially parallelized. Recommended order:

```
Phase 0 — Baseline capture (DO THIS FIRST, before any code changes):
  Task 9 prerequisite: Generate Prince 15 baseline PDFs

Phase 1 — Buckram CSS fixes (can run in parallel):
  Task 1: box-decoration-break
  Task 2: List indentation gaps (Buckram portion)
  Task 3: @page:first → :first-of-group
  Task 4: @page:blank audit

Phase 2 — Plugin fixes (can run in parallel):
  Task 2: List indentation gaps (H5P portion)
  Task 5: Minimum Prince version bump
  Task 6: DocRaptor pipeline update
  Task 7: Prince PHP library check

Phase 3 — Dev environment setup:
  Task 8: Point pressbooks-book at local Buckram (prerequisite for Phase 4)

Phase 4 — Verification:
  Task 9: Visual regression testing (depends on Phase 0 + 1 + 2 + 3)

Phase 5 — Documentation & Follow-up:
  Task 11: Stakeholder migration guide (can start during Phase 4)
  Task 10: Follow-up tickets (after migration ships)
```

---

## Risk Summary

| Risk Level | Items |
|------------|-------|
| **HIGH** | `box-decoration-break` default change (Task 1); `@page:first` scope change (Task 3) |
| **MEDIUM** | List indentation mechanism change (Task 2) |
| **LOW** | Version bump (Task 5); DocRaptor pipeline (Task 6); PHP library (Task 7); `@page:blank` (Task 4) |

## Definition of Done

- [ ] All Buckram SCSS compiles without errors
- [ ] All 11 tasks completed (or explicitly deferred with tickets)
- [ ] Visual regression tests pass across the test matrix (Task 9)
- [ ] PHPUnit tests pass: `lando composer test`
- [ ] New Buckram version released
- [ ] `pressbooks-book` updated to consume new Buckram version (local link reverted, `package.json` updated)
- [ ] DocRaptor pipeline updated and tested
- [ ] Stakeholder migration guide reviewed and published
- [ ] GitHub issue #4418 closed
