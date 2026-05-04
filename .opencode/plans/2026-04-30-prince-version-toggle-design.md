# Prince 15/16 Rendering Engine Toggle

## Problem

Buckram currently hardcodes `$box-decoration-break: clone` (Prince 15 behavior) and `:first-of-group` page selectors (Prince 16 syntax). There is no way for a book to choose which Prince version's behavior to use. We need:

1. New books default to Prince 16 behavior (`slice`, `first-of-group`)
2. Existing books keep Prince 15 behavior (`clone`, `:first`) to preserve PDF output
3. A per-book PDF Theme Option dropdown to select the rendering engine

## Architecture

### Pipeline

The book-level `pdf_prince_version` option controls both SCSS compilation and the DocRaptor pipeline:

```
User selects Prince version in PDF Theme Options
       |
       v
scssOverrides() sets Sass variables:
  - $first-page-pseudo: 'first' or 'first-of-group'
  - $box-decoration-break: 'clone' or 'slice'
       |
       v
DocRaptor pipeline set to:
  - '10.1' for Prince 15
  - DOCRAPTOR_PIPELINE constant or '11' for Prince 16
       |
       v
Compiled CSS matches the Prince version that will render it
```

### Three repositories touched

| Repo | Changes |
|------|---------|
| **Buckram** | Parameterize `:first-of-group` as `$first-page-pseudo` variable, change `$box-decoration-break` default to `slice` |
| **Pressbooks plugin** | Add `pdf_prince_version` and `pdf_box_decoration_break` PDF theme options, wire Sass overrides, update DocRaptor pipeline logic |
| **pressbooks-book** | Bump buckram dependency |

---

## 1. Buckram Changes

### 1.1 New variable: `$first-page-pseudo`

**File: `assets/styles/variables/_structure.scss`**

```scss
/// Page pseudo-class used for the first page of each section group.
/// Prince 16+ uses 'first-of-group', Prince 15 and earlier use 'first'.
/// @type String
/// @since 1.10.0
$first-page-pseudo: 'first-of-group' !default;
```

### 1.2 Change default: `$box-decoration-break`

**File: `assets/styles/variables/_elements.scss`**

```scss
// Before:
$box-decoration-break: clone !default;
// After:
$box-decoration-break: slice !default;
```

### 1.3 Refactor: `components/structure/_running-content.scss`

Replace all hardcoded `'first-of-group'` with the variable:

- `'first-of-group:left'` → `'#{$first-page-pseudo}:left'`
- `'first-of-group:right'` → `'#{$first-page-pseudo}:right'`
- `@page <section>:first-of-group` → `@page <section>:#{$first-page-pseudo}`
- `'first-of-group'` in `page-structure()` calls → `$first-page-pseudo`

### 1.4 Refactor: `components/structure/_mixins.scss`

Replace all hardcoded `first-of-group` in:

- **`@page` selectors:** `@page #{$page-type}:first-of-group { ... }` → `@page #{$page-type}:#{$first-page-pseudo} { ... }`
- **String comparisons:** `'first-of-group:left'` → `'#{$first-page-pseudo}:left'`, `'first-of-group:right'` → `'#{$first-page-pseudo}:right'`
- **Inequality checks:** `$page-position != 'first-of-group'` → `$page-position != $first-page-pseudo`

Note: `$first-page-pseudo` must be accessible in mixins. It should be imported or passed as a global Sass variable (it will be available since the variable file is imported before the mixins are invoked).

### 1.5 Stylelint config

Add `'first'` to `ignorePseudoClasses` alongside `'first-of-group'`:

```json
"selector-pseudo-class-no-unknown": [true, {
  "ignorePseudoClasses": ["first-of-group", "first"]
}]
```

### 1.6 Version bump

Bump buckram to `1.10.0` (minor — new `!default` variables are additive, no breaking change for consumers who don't override).

---

## 2. Pressbooks Plugin Changes

### 2.1 New PDF theme options

**File: `inc/modules/themeoptions/class-pdfoptions.php`**

Two new options added to `getDefaults()`:

```php
'pdf_prince_version' => 'prince-16',
'pdf_box_decoration_break' => 'slice',
```

**Option details:**

| Option | Type | Values | Default (new books) |
|--------|------|--------|-------------------|
| `pdf_prince_version` | predefined | `prince-16`, `prince-15` | `prince-16` |
| `pdf_box_decoration_break` | predefined | `slice`, `clone` | `slice` |

Add both to `getPredefinedOptions()` and `getStringOptions()`.

### 2.2 UI fields

**`renderPrinceVersionField()`** — dropdown:
- "Prince 16" (default)
- "Prince 15"

**`renderBoxDecorationBreakField()`** — dropdown, conditionally visible:
- "Slice (CSS spec)" — borders/backgrounds only on first page fragment
- "Clone (repeat styling)" — borders/backgrounds repeat on every page fragment
- Hidden when `pdf_prince_version` is `prince-15` (Prince 15 always clones)
- JS toggles visibility based on the prince version dropdown

Both fields registered in `init()` near the existing Section Openings / Crop Marks fields (page-layout-related section). Guarded by `$v2_compatible` check.

### 2.3 `scssOverrides()` changes

Added before the existing "Global Options" section:

```php
// Prince version settings
if ( $v2_compatible ) {
    $prince_version = $options['pdf_prince_version'] ?? 'prince-16';
    $box_decoration = $options['pdf_box_decoration_break'] ?? 'slice';

    $styles->getSass()->setVariables( [
        'first-page-pseudo' => 'first-of-group',
        'box-decoration-break' => $box_decoration,
    ] );

    if ( $prince_version === 'prince-15' ) {
        $styles->getSass()->setVariables( [
            'first-page-pseudo' => 'first',
            'box-decoration-break' => 'clone',
        ] );
    }
}
```

### 2.4 DocRaptor pipeline logic

**File: `inc/modules/export/prince/class-docraptor.php`** (line ~134)

```php
$pdf_options = get_option( 'pressbooks_theme_options_pdf', [] );
$prince_version = $pdf_options['pdf_prince_version'] ?? 'prince-16';

// Default: use constant or '11' (Prince 16)
$pipeline = defined( 'DOCRAPTOR_PIPELINE' ) ? DOCRAPTOR_PIPELINE : '11';

// Override for Prince 15
if ( $prince_version === 'prince-15' ) {
    $pipeline = '10.1';
}

$doc->setPipeline( $pipeline );
```

**File: `inc/covergenerator/class-generator.php`** (line ~445) — same logic.

### 2.5 Migration

Bump `VERSION` constant from `2` to `3` in `PDFOptions`.

Add `upgrade()` logic (or extend existing):

```php
// Existing books: default to prince-15 to preserve their current PDF output
if ( ! isset( $options['pdf_prince_version'] ) ) {
    $options['pdf_prince_version'] = 'prince-15';
    $options['pdf_box_decoration_break'] = 'clone';
    update_option( 'pressbooks_theme_options_pdf', $options );
}
```

### 2.6 `check_prince_install()` version check

**File: `inc/utility/namespace.php`** — keep the `>= 16` check as-is. Self-hosted Prince must be 16+. The `pdf_prince_version` option controls SCSS compilation only; the actual binary is what's installed.

---

## 3. pressbooks-book Theme Changes

- Bump buckram dependency from `^1.9.0` to `^1.10.0` in `package.json`
- Run `npm install` to update `package-lock.json`
- No theme-level SCSS changes needed

---

## 4. Default Values by Scenario

| Scenario | `pdf_prince_version` | `pdf_box_decoration_break` | SCSS `$first-page-pseudo` | SCSS `$box-decoration-break` | DocRaptor pipeline |
|----------|---------------------|---------------------------|--------------------------|------------------------------|--------------------|
| New book | `prince-16` | `slice` | `first-of-group` | `slice` | `DOCRAPTOR_PIPELINE` or `11` |
| Existing book (migrated) | `prince-15` | `clone` (hidden) | `first` | `clone` | `10.1` |
| User switches to Prince 16 | `prince-16` | `slice` | `first-of-group` | `slice` | `DOCRAPTOR_PIPELINE` or `11` |
| User switches to Prince 16 + clone | `prince-16` | `clone` | `first-of-group` | `clone` | `DOCRAPTOR_PIPELINE` or `11` |
| User switches back to Prince 15 | `prince-15` | (hidden, `clone`) | `first` | `clone` | `10.1` |

---

## 5. DocRaptor Pipeline Decision Flow

```
Is pdf_prince_version = 'prince-15'?
  YES → pipeline = '10.1'
  NO  → pipeline = DOCRAPTOR_PIPELINE constant (if defined) or '11'
```

The `DOCRAPTOR_PIPELINE` constant serves as a forward-compatibility escape hatch for Prince 16+ — server admins can opt into newer pipelines (e.g. `11.1` for Prince 16.4) without a Pressbooks release.

---

## 6. Files Changed Summary

### Buckram
- `assets/styles/variables/_structure.scss` — add `$first-page-pseudo`
- `assets/styles/variables/_elements.scss` — change `$box-decoration-break` default to `slice`
- `assets/styles/components/structure/_running-content.scss` — use `$first-page-pseudo`
- `assets/styles/components/structure/_mixins.scss` — use `$first-page-pseudo`
- `package.json` — version bump, stylelint config

### Pressbooks plugin
- `inc/modules/themeoptions/class-pdfoptions.php` — new options, render methods, scssOverrides, migration
- `inc/modules/export/prince/class-docraptor.php` — pipeline logic
- `inc/covergenerator/class-generator.php` — pipeline logic

### pressbooks-book
- `package.json` — buckram dependency bump
- `package-lock.json` — updated lockfile
