# Prince 15/16 Rendering Engine Toggle — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add per-book PDF Theme Option to select Prince 15 or Prince 16 rendering, controlling both SCSS compilation and DocRaptor pipeline.

**Architecture:** Parameterize Buckram's `:first-of-group` page selector as a `$first-page-pseudo` Sass variable. Change `$box-decoration-break` default to `slice`. Add `pdf_prince_version` and `pdf_box_decoration_break` options to Pressbooks PDF Theme Options. Existing books migrate to Prince 15 mode (preserving output), new books default to Prince 16.

**Tech Stack:** PHP (WordPress options API, ScssPhp), SCSS (Buckram), JavaScript (conditional field visibility), DocRaptor API

---

## Task 1: Add `$first-page-pseudo` variable to Buckram

**Files:**
- Modify: `/Users/arzola/code/opensource/buckram/assets/styles/variables/_structure.scss:59-60`

- [ ] **Step 1: Add the variable after the first running content position block**

Insert after line 59 (after the `// TODO: Handle other positions based on recto-verso` comment):

```scss
/// Page pseudo-class used for the first page of each section group.
/// Prince 16+ uses 'first-of-group', Prince 15 and earlier use 'first'.
/// Set to 'first' to restore Prince 15 behavior where the first page
/// of each named page group was matched by :first.
/// @type String
/// @since 1.10.0
$first-page-pseudo: 'first-of-group' !default;
```

- [ ] **Step 2: Verify Buckram lints**

Run: `npm run lint` in `/Users/arzola/code/opensource/buckram`
Expected: PASS

- [ ] **Step 3: Commit**

```bash
git add assets/styles/variables/_structure.scss
git commit -m "feat: add \$first-page-pseudo variable for Prince version toggle"
```

---

## Task 2: Change `$box-decoration-break` default to `slice` in Buckram

**Files:**
- Modify: `/Users/arzola/code/opensource/buckram/assets/styles/variables/_elements.scss:763`

- [ ] **Step 1: Change the default value**

In `assets/styles/variables/_elements.scss`, change:

```scss
$box-decoration-break: clone !default;
```

to:

```scss
$box-decoration-break: slice !default;
```

Update the docblock above it to reflect the new default:

```scss
/// Box decoration break behavior for elements that may span page breaks (textboxes, asides, blockquotes).
/// Set to `clone` to restore Prince 15 behavior where borders, padding, and backgrounds
/// are repeated on each page fragment. Prince 16+ defaults to `slice` per CSS spec.
/// @type String
/// @since 1.9.0
$box-decoration-break: slice !default;
```

- [ ] **Step 2: Verify Buckram lints**

Run: `npm run lint` in `/Users/arzola/code/opensource/buckram`
Expected: PASS

- [ ] **Step 3: Commit**

```bash
git add assets/styles/variables/_elements.scss
git commit -m "feat: change \$box-decoration-break default to slice (Prince 16 spec)"
```

---

## Task 3: Parameterize `_running-content.scss` with `$first-page-pseudo`

**Files:**
- Modify: `/Users/arzola/code/opensource/buckram/assets/styles/components/structure/_running-content.scss`

- [ ] **Step 1: Replace all hardcoded `'first-of-group'` with the variable**

The file has these patterns to replace (shown with line numbers from current state):

1. Lines 54-55 — `page-numbers` mixin calls:
```scss
// Before:
@include page-numbers('first-of-group:left', ...);
@include page-numbers('first-of-group:right', ...);
// After:
@include page-numbers('#{$first-page-pseudo}:left', ...);
@include page-numbers('#{$first-page-pseudo}:right', ...);
```

2. Lines 65-66 — `runninghead` mixin calls:
```scss
// Before:
@include runninghead('first-of-group:left', ...);
@include runninghead('first-of-group:right', ...);
// After:
@include runninghead('#{$first-page-pseudo}:left', ...);
@include runninghead('#{$first-page-pseudo}:right', ...);
```

3. Lines 70-71 — `runningfoot` mixin calls:
```scss
// Before:
@include runningfoot('first-of-group:left', ...);
@include runningfoot('first-of-group:right', ...);
// After:
@include runningfoot('#{$first-page-pseudo}:left', ...);
@include runningfoot('#{$first-page-pseudo}:right', ...);
```

4. Lines 83, 92, 101, 111, 120, 129 — `@page` declarations:
```scss
// Before:
@page front-matter:first-of-group {
@page introduction:first-of-group {
@page post-introduction:first-of-group {
@page part:first-of-group {
@page chapter:first-of-group {
@page back-matter:first-of-group {
// After:
@page front-matter:#{$first-page-pseudo} {
@page introduction:#{$first-page-pseudo} {
@page post-introduction:#{$first-page-pseudo} {
@page part:#{$first-page-pseudo} {
@page chapter:#{$first-page-pseudo} {
@page back-matter:#{$first-page-pseudo} {
```

5. Lines 87, 96, 105, 115, 124, 133 — `page-structure` mixin calls:
```scss
// Before:
@include page-structure('front-matter', 'first-of-group', ...);
@include page-structure('introduction', 'first-of-group', ...);
@include page-structure('post-introduction', 'first-of-group', ...);
@include page-structure('part', 'first-of-group', ...);
@include page-structure('chapter', 'first-of-group', ...);
@include page-structure('back-matter', 'first-of-group', ...);
// After:
@include page-structure('front-matter', $first-page-pseudo, ...);
@include page-structure('introduction', $first-page-pseudo, ...);
@include page-structure('post-introduction', $first-page-pseudo, ...);
@include page-structure('part', $first-page-pseudo, ...);
@include page-structure('chapter', $first-page-pseudo, ...);
@include page-structure('back-matter', $first-page-pseudo, ...);
```

- [ ] **Step 2: Verify Buckram lints**

Run: `npm run lint` in `/Users/arzola/code/opensource/buckram`
Expected: PASS

- [ ] **Step 3: Commit**

```bash
git add assets/styles/components/structure/_running-content.scss
git commit -m "refactor: parameterize first-of-group in running-content with \$first-page-pseudo"
```

---

## Task 4: Parameterize `_mixins.scss` with `$first-page-pseudo`

**Files:**
- Modify: `/Users/arzola/code/opensource/buckram/assets/styles/components/structure/_mixins.scss`

- [ ] **Step 1: Replace hardcoded `first-of-group` in string comparisons**

All `'first-of-group:left'` → `'#{$first-page-pseudo}:left'`
All `'first-of-group:right'` → `'#{$first-page-pseudo}:right'`

Specific replacements in `page-numbers` mixin (lines 17, 29, 62, 74):

```scss
// Before:
@if $page-position == 'first-of-group:left' {
} @else if $page-position == 'first-of-group:right' {
// After:
@if $page-position == '#{$first-page-pseudo}:left' {
} @else if $page-position == '#{$first-page-pseudo}:right' {
```

Same pattern in `runninghead` mixin (lines 220, 235):
```scss
// Before:
@if $page-position == 'first-of-group:left' {
} @else if $page-position == 'first-of-group:right' {
// After:
@if $page-position == '#{$first-page-pseudo}:left' {
} @else if $page-position == '#{$first-page-pseudo}:right' {
```

Same pattern in `runningfoot` mixin (lines 293, 308):
```scss
// Before:
@if $page-position == 'first-of-group:left' {
} @else if $page-position == 'first-of-group:right' {
// After:
@if $page-position == '#{$first-page-pseudo}:left' {
} @else if $page-position == '#{$first-page-pseudo}:right' {
```

- [ ] **Step 2: Replace hardcoded `first-of-group` in inequality checks**

In `page-structure` mixin, all `$page-position != 'first-of-group'` → `$page-position != $first-page-pseudo`

Lines 362, 412, 453, 479:
```scss
// Before:
@if $page-position != 'first-of-group' {
// After:
@if $page-position != $first-page-pseudo {
```

- [ ] **Step 3: Replace hardcoded `first-of-group` in `@page` selectors**

All `@page #{$page-type}:first-of-group:` → `@page #{$page-type}:#{$first-page-pseudo}:`

Specific lines to change:
```scss
// Before (appears at lines 387, 399, 432, 441, 464, 470, 490, 496):
@page #{$page-type}:first-of-group:right {
@page #{$page-type}:first-of-group:left {
// After:
@page #{$page-type}:#{$first-page-pseudo}:right {
@page #{$page-type}:#{$first-page-pseudo}:left {
```

- [ ] **Step 4: Verify Buckram lints**

Run: `npm run lint` in `/Users/arzola/code/opensource/buckram`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add assets/styles/components/structure/_mixins.scss
git commit -m "refactor: parameterize first-of-group in mixins with \$first-page-pseudo"
```

---

## Task 5: Update Buckram stylelint config and version

**Files:**
- Modify: `/Users/arzola/code/opensource/buckram/package.json`

- [ ] **Step 1: Add `'first'` to ignored pseudo-classes**

In `package.json` stylelint config, update `ignorePseudoClasses`:

```json
"selector-pseudo-class-no-unknown": [true, {
  "ignorePseudoClasses": ["first-of-group", "first"]
}]
```

- [ ] **Step 2: Bump version**

Change `"version": "1.9.1"` to `"version": "1.10.0"`.

- [ ] **Step 3: Verify lint and build**

Run: `npm run test` in `/Users/arzola/code/opensource/buckram`
Expected: PASS

- [ ] **Step 4: Commit**

```bash
git add package.json
git commit -m "chore: bump to v1.10.0, add 'first' to stylelint ignorePseudoClasses"
```

---

## Task 6: Add PDF theme options for Prince version in Pressbooks plugin

**Files:**
- Modify: `/Users/arzola/code/pbdev/web/app/plugins/pressbooks/inc/modules/themeoptions/class-pdfoptions.php`

- [ ] **Step 1: Add options to `getDefaults()` (line ~1563)**

Add to the defaults array:

```php
'pdf_prince_version' => 'prince-16',
'pdf_box_decoration_break' => 'slice',
```

- [ ] **Step 2: Add to `getPredefinedOptions()` (line ~1821)**

Add to the array:

```php
'pdf_prince_version',
'pdf_box_decoration_break',
```

- [ ] **Step 3: Add to `getStringOptions()` (line ~1741)**

Add to the array:

```php
'pdf_prince_version',
'pdf_box_decoration_break',
```

- [ ] **Step 4: Bump `VERSION` constant from `2` to `3` (line 24)**

```php
const VERSION = 3;
```

- [ ] **Step 5: Add upgrade logic for version 3**

In the `upgrade()` method (line ~653), add after the existing version checks:

```php
if ( $version < 3 ) {
    $this->upgradePrinceVersion();
}
```

Add new method after `upgradeSectionOpenings()`:

```php
function upgradePrinceVersion() {
    $_option = $this->getSlug();
    $options = get_option( 'pressbooks_theme_options_' . $_option, $this->defaults );

    $options['pdf_prince_version'] = 'prince-15';
    $options['pdf_box_decoration_break'] = 'clone';

    update_option( 'pressbooks_theme_options_' . $_option, $options );
}
```

- [ ] **Step 6: Commit**

```bash
git add inc/modules/themeoptions/class-pdfoptions.php
git commit -m "feat: add pdf_prince_version and pdf_box_decoration_break PDF theme options"
```

---

## Task 7: Add render methods for Prince version fields

**Files:**
- Modify: `/Users/arzola/code/pbdev/web/app/plugins/pressbooks/inc/modules/themeoptions/class-pdfoptions.php`

- [ ] **Step 1: Register fields in `init()` method**

Add after the crop marks field registration (after line ~341), guarded by `$v2_compatible`:

```php
if ( $v2_compatible ) {
    add_settings_field(
        'pdf_prince_version',
        __( 'Prince Version', 'pressbooks' ),
        [ $this, 'renderPrinceVersionField' ],
        $_page,
        $_section,
        [
            'prince-16' => __( 'Prince 16', 'pressbooks' ),
            'prince-15' => __( 'Prince 15', 'pressbooks' ),
            'label_for' => 'pdf_prince_version',
        ]
    );

    add_settings_field(
        'pdf_box_decoration_break',
        __( 'Box Decoration Break', 'pressbooks' ),
        [ $this, 'renderBoxDecorationBreakField' ],
        $_page,
        $_section,
        [
            'slice' => __( 'Slice (CSS spec)', 'pressbooks' ),
            'clone' => __( 'Clone (repeat styling across page breaks)', 'pressbooks' ),
            'label_for' => 'pdf_box_decoration_break',
        ]
    );
}
```

- [ ] **Step 2: Add `renderPrinceVersionField()` method**

Place near other render methods (after `renderCropMarksField` around line ~1163):

```php
function renderPrinceVersionField( $args ) {
    unset( $args['label_for'], $args['class'] );
    $this->renderRadioButtons(
        [
            'id' => 'pdf_prince_version',
            'name' => 'pressbooks_theme_options_' . $this->getSlug(),
            'option' => 'pdf_prince_version',
            'value' => getset( $this->options, 'pdf_prince_version' ),
            'choices' => $args,
            'legend' => __( 'Prince Version', 'pressbooks' ),
        ]
    );
}
```

- [ ] **Step 3: Add `renderBoxDecorationBreakField()` method**

```php
function renderBoxDecorationBreakField( $args ) {
    unset( $args['label_for'], $args['class'] );
    $prince_version = getset( $this->options, 'pdf_prince_version', 'prince-16' );
    $this->renderRadioButtons(
        [
            'id' => 'pdf_box_decoration_break',
            'name' => 'pressbooks_theme_options_' . $this->getSlug(),
            'option' => 'pdf_box_decoration_break',
            'value' => getset( $this->options, 'pdf_box_decoration_break' ),
            'choices' => $args,
            'legend' => __( 'Box Decoration Break', 'pressbooks' ),
            'disabled' => ( $prince_version === 'prince-15' ),
        ]
    );
}
```

Note: For Prince 15, the box-decoration-break field is disabled since Prince 15 always clones. A future enhancement can add JS to toggle visibility/disabled state dynamically.

- [ ] **Step 4: Commit**

```bash
git add inc/modules/themeoptions/class-pdfoptions.php
git commit -m "feat: add render methods for Prince version and box-decoration-break fields"
```

---

## Task 8: Wire `scssOverrides()` for Prince version variables

**Files:**
- Modify: `/Users/arzola/code/pbdev/web/app/plugins/pressbooks/inc/modules/themeoptions/class-pdfoptions.php`

- [ ] **Step 1: Add Prince version Sass variable overrides**

In the `scssOverrides()` method, add immediately after the `$v2_compatible` / `$shape_shifter_compatible` check (after line ~1923, before the `// Global Options` comment):

```php
// --------------------------------------------------------------------
// Prince Version

$options_pdf = get_option( 'pressbooks_theme_options_pdf' );
$prince_version = $options_pdf['pdf_prince_version'] ?? 'prince-16';
$box_decoration = $options_pdf['pdf_box_decoration_break'] ?? 'slice';

if ( $v2_compatible ) {
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

Note: This reads from a separate `get_option` call for `pressbooks_theme_options_pdf` because later in the method (line ~1984) the same variable name `$options` is reassigned. Using `$options_pdf` avoids the conflict.

- [ ] **Step 2: Commit**

```bash
git add inc/modules/themeoptions/class-pdfoptions.php
git commit -m "feat: wire scssOverrides for Prince version Sass variables"
```

---

## Task 9: Update DocRaptor pipeline logic

**Files:**
- Modify: `/Users/arzola/code/pbdev/web/app/plugins/pressbooks/inc/modules/export/prince/class-docraptor.php:134`
- Modify: `/Users/arzola/code/pbdev/web/app/plugins/pressbooks/inc/covergenerator/class-generator.php:445`

- [ ] **Step 1: Update pipeline logic in `class-docraptor.php`**

Replace line 134:

```php
$doc->setPipeline( defined( 'DOCRAPTOR_PIPELINE' ) ? DOCRAPTOR_PIPELINE : '11' ); // Prince 16, see: https://docraptor.com/documentation/api#api_pipeline
```

With:

```php
$pdf_options = get_option( 'pressbooks_theme_options_pdf', [] );
$prince_version = $pdf_options['pdf_prince_version'] ?? 'prince-16';
$pipeline = defined( 'DOCRAPTOR_PIPELINE' ) ? DOCRAPTOR_PIPELINE : '11';
if ( $prince_version === 'prince-15' ) {
    $pipeline = '10.1';
}
$doc->setPipeline( $pipeline ); // See: https://docraptor.com/documentation/api#api_pipeline
```

- [ ] **Step 2: Update pipeline logic in `class-generator.php`**

Replace line 445:

```php
$doc->setPipeline( defined( 'DOCRAPTOR_PIPELINE' ) ? DOCRAPTOR_PIPELINE : '11' ); // Prince 16, see: https://docraptor.com/documentation/api#api_pipeline
```

With:

```php
$pdf_options = get_option( 'pressbooks_theme_options_pdf', [] );
$prince_version = $pdf_options['pdf_prince_version'] ?? 'prince-16';
$pipeline = defined( 'DOCRAPTOR_PIPELINE' ) ? DOCRAPTOR_PIPELINE : '11';
if ( $prince_version === 'prince-15' ) {
    $pipeline = '10.1';
}
$doc->setPipeline( $pipeline ); // See: https://docraptor.com/documentation/api#api_pipeline
```

- [ ] **Step 3: Commit**

```bash
git add inc/modules/export/prince/class-docraptor.php inc/covergenerator/class-generator.php
git commit -m "feat: derive DocRaptor pipeline from book's Prince version setting"
```

---

## Task 10: Bump buckram dependency in pressbooks-book

**Files:**
- Modify: `/Users/arzola/code/pbdev/web/app/themes/pressbooks-book/package.json`
- Modify: `/Users/arzola/code/pbdev/web/app/themes/pressbooks-book/package-lock.json` (via npm)

- [ ] **Step 1: Update buckram dependency**

In `package.json`, change:

```json
"buckram": "^1.9.0"
```

to:

```json
"buckram": "^1.10.0"
```

- [ ] **Step 2: Install and update lockfile**

Run: `npm install` in `/Users/arzola/code/pbdev/web/app/themes/pressbooks-book`

Note: This requires buckram 1.10.0 to be published to npm. If working locally before publish, use `npm link` or update the local `packages/buckram` symlink.

- [ ] **Step 3: Commit**

```bash
git add package.json package-lock.json
git commit -m "chore: bump buckram to ^1.10.0 for Prince version toggle support"
```

---

## Task 11: Add tests for new PDF options

**Files:**
- Modify: `/Users/arzola/code/pbdev/web/app/plugins/pressbooks/tests/test-pdfoptions.php`

- [ ] **Step 1: Add test for Prince 16 defaults**

```php
public function test_princeVersionDefaults() {
    $defaults = \Pressbooks\Modules\ThemeOptions\PDFOptions::getDefaults();
    $this->assertEquals( 'prince-16', $defaults['pdf_prince_version'] );
    $this->assertEquals( 'slice', $defaults['pdf_box_decoration_break'] );
}
```

- [ ] **Step 2: Add test for Prince 15 scssOverrides**

```php
public function test_scssOverridesPrince15() {
    $this->_book( 'pressbooks-luther' );

    update_option( 'pressbooks_theme_options_pdf', [
        'pdf_page_width' => 10,
        'pdf_page_height' => 10,
        'pdf_crop_marks' => 0,
        'pdf_hyphens' => 1,
        'pdf_toc' => 1,
        'pdf_prince_version' => 'prince-15',
    ] );
    update_option( 'pressbooks_theme_options_global', [
        'chapter_numbers' => 1,
    ] );

    $result = \Pressbooks\Modules\ThemeOptions\PDFOptions::scssOverrides( '' );
    $this->assertIsString( $result );
}
```

- [ ] **Step 3: Add test for predefined options includes new options**

```php
public function test_predefinedOptionsIncludePrinceVersion() {
    $predefined = \Pressbooks\Modules\ThemeOptions\PDFOptions::getPredefinedOptions();
    $this->assertContains( 'pdf_prince_version', $predefined );
    $this->assertContains( 'pdf_box_decoration_break', $predefined );
}
```

- [ ] **Step 4: Add test for upgrade migration**

```php
public function test_upgradePrinceVersion() {
    $this->_book( 'pressbooks-luther' );

    update_option( 'pressbooks_theme_options_pdf', [
        'pdf_page_width' => '5.5in',
        'pdf_page_height' => '8.5in',
    ] );

    $options = new \Pressbooks\Modules\ThemeOptions\PDFOptions( [] );
    $options->upgrade( 2 );

    $saved = get_option( 'pressbooks_theme_options_pdf' );
    $this->assertEquals( 'prince-15', $saved['pdf_prince_version'] );
    $this->assertEquals( 'clone', $saved['pdf_box_decoration_break'] );
}
```

- [ ] **Step 5: Run tests**

Run: `phpunit --group themeoptions`
Expected: All tests PASS

- [ ] **Step 6: Commit**

```bash
git add tests/test-pdfoptions.php
git commit -m "test: add tests for Prince version PDF theme options"
```
