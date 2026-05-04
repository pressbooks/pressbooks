# Spec: Single Dollar Sign LaTeX Delimiter Support

**Date:** 2026-04-02
**Author:** Pressbooks <code@pressbooks.com>

---

## Problem

Authors who write math-heavy content sometimes prefer to use single dollar signs (`$x^2$`) as LaTeX inline delimiters, matching the traditional TeX convention. Pressbooks currently supports `\( ... \)`, `[latex]...[/latex]`, `$$ ... $$`, and `\[ ... \]`, but not single `$`. Authors must use an unfamiliar syntax or change their workflow.

## Goal

Allow authors to opt into single dollar sign (`$...$`) inline LaTeX math rendering in webbooks, EPUB exports, and PDF (Prince) exports.

---

## Requirements

### Functional

1. A new boolean setting **"Use single dollar signs as inline math delimiters"** must appear on the **Settings > MathJax** admin page within each book.
2. The setting defaults to **disabled (false)** for all books (new and existing).
3. When enabled:
   - `$x^2$` is rendered as inline LaTeX math in **webbooks** (client-side MathJax v3).
   - `$x^2$` is converted to a rendered `<img>` tag during **EPUB and PDF exports** (server-side via pb-mathjax microservice).
   - Pages containing `$...$` patterns trigger MathJax script loading (i.e. `sectionHasMath()` returns true).
4. When disabled, `$...$` is treated as plain text (no change from current behavior).
5. **False-positive mitigation:** The opening `$` must not be immediately followed by whitespace, and the closing `$` must not be immediately preceded by whitespace. This means `$ 5,000` and `USD $4,000 today` do NOT trigger math rendering.
6. Single `$` delimiters produce **inline** math only (no block/display math). The `<div class="display-math">` wrapper is not applied.
7. `$$...$$` (display math) continues to work unchanged whether or not the new setting is enabled.
8. The `\$` escape sequence can be used by authors to display a literal dollar sign when the setting is enabled (MathJax v3 handles this natively; the export regex must skip `\$`).
9. The admin page syntax reference section shows a `$...$` example when the setting is enabled.
10. A filter hook `pb_mathjax_use_single_dollar` allows developers to override the setting programmatically.

### Non-Functional

- No new files are created. All changes are contained within `inc/class-mathjax.php`, `templates/admin/mathjax.blade.php`, and `tests/test-mathjax.php`.
- Backward compatible: no behavioral change for books where the setting is off.
- The stored option continues to use the existing `pb_mathjax` WordPress option key (extended with the new key).

---

## Data Model

The `pb_mathjax` WordPress option (stored per book site) is extended:

**Before:**
```php
['fg' => '000000']
```

**After:**
```php
['fg' => '000000', 'use_single_dollar' => false]
```

`getOptions()` returns `use_single_dollar` defaulting to `false`.
`saveOptions()` reads a `use_single_dollar` checkbox POST field (present = true, absent = false).

---

## Behavior Details

### Client-Side (Webbook)

`addHeaders()` conditionally adds `['$', '$']` to the `tex.inlineMath` array in the `window.MathJax` JS config:

```javascript
// When use_single_dollar is enabled:
tex: {
    inlineMath: [['\\(', '\\)'], ['[latex]','[/latex]'], ['$', '$']],
    ...
}
```

MathJax v3 natively handles the no-adjacent-whitespace heuristic for `$` delimiters (a `$` immediately followed by a space is not treated as an opening delimiter).

### Server-Side Export

`replaceLatexDelimitersOnExports()` gains a 4th regex pattern, applied only when `use_single_dollar` is enabled:

```
Pattern: /(?<!\\)\$(?!\s|\$)(.+?)(?<!\s)\$(?!\$)/
```

Breakdown:
- `(?<!\\)` — opening `$` not preceded by backslash (respects `\$` escape)
- `(?!\s|\$)` — opening `$` not followed by whitespace or another `$`
- `(.+?)` — non-greedy capture (no `s` modifier = single-line only)
- `(?<!\s)` — closing `$` not preceded by whitespace
- `(?!\$)` — closing `$` not followed by `$` (avoids `$$` collision)

Result is wrapped as inline math (no `display-math` div).

### Content Detection

`sectionHasMath()` gains an additional check when `use_single_dollar` is enabled:

```php
if ( $options['use_single_dollar'] ) {
    $has_math = $has_math || (bool) preg_match(
        '/(?<!\\\\)\$(?!\s|\$).+?(?<!\s)\$(?!\$)/',
        $content
    );
}
```

### Filter Hook

```php
/**
 * Override whether single dollar sign delimiters are enabled.
 *
 * @since  [next version]
 * @param  bool $use_single_dollar
 * @return bool
 */
apply_filters( 'pb_mathjax_use_single_dollar', $options['use_single_dollar'] );
```

---

## Admin UI

New table row added to `templates/admin/mathjax.blade.php`:

```html
<tr>
    <th scope="row">
        <label for="mathjax-use-single-dollar">
            {{ __( 'Single dollar sign delimiter', 'pressbooks' ) }}
        </label>
    </th>
    <td>
        <input type="checkbox" name="use_single_dollar" id="mathjax-use-single-dollar"
               value="1" {{ $use_single_dollar ? 'checked' : '' }} />
        <p>
            {{ __( 'When enabled, $x^2$ will be treated as inline LaTeX math.', 'pressbooks' ) }}
            {!! sprintf( __( 'Example: %s', 'pressbooks' ), '<code>$e^{i \\pi} + 1 = 0$</code>' ) !!}
        </p>
        <p>
            {{ __( 'The opening $ must not be followed by a space and the closing $ must not be preceded by a space, so currency like "$ 5,000" is not affected.', 'pressbooks' ) }}
            {!! __( 'Use <code>\\$</code> to display a literal dollar sign.', 'pressbooks' ) !!}
        </p>
    </td>
</tr>
```

The syntax reference section conditionally shows the `$ ... $` example:

```html
@if ( $use_single_dollar )
<p>
    {!! sprintf( __( 'Single dollar sign syntax: %s', 'pressbooks' ), '<code>$e^{i \pi} + 1 = 0$</code>' ) !!}
</p>
@endif
```

---

## Test Cases

| Test | Scenario | Expected |
|------|----------|----------|
| `testSingleDollarOptionDefault` | Fresh options | `use_single_dollar` is `false` |
| `testSingleDollarOptionSave` | Save checkbox checked | `use_single_dollar` is `true` |
| `testSingleDollarOptionSaveUnchecked` | Save checkbox unchecked | `use_single_dollar` is `false` |
| `testSectionHasMathSingleDollarEnabled` | Content `$x^2$`, option enabled | Returns `true` |
| `testSectionHasMathSingleDollarDisabled` | Content `$x^2$`, option disabled | Returns `false` |
| `testSectionHasMathCurrencyNotMath` | Content `$ 5,000`, option enabled | Returns `false` |
| `testAddHeadersSingleDollarEnabled` | Option enabled | `window.MathJax` output contains `['$','$']` |
| `testAddHeadersSingleDollarDisabled` | Option disabled | `window.MathJax` output does NOT contain `['$','$']` |
| `testExportSingleDollar` | `$x^2$`, option enabled, `usePbMathJax = true` | Converted to `<img>` |
| `testExportSingleDollarDisabled` | `$x^2$`, option disabled | Left as-is |
| `testExportSingleDollarFalsePositive` | `$ 5,000 and $ 4,000`, option enabled | NOT converted (whitespace guard) |
| `testExportSingleDollarEscape` | `\$5,000`, option enabled | NOT converted (backslash escape) |
| `testExportDoubleDollarUnaffected` | `$$x^2$$`, option enabled | Converted as display math (unchanged behavior) |

---

## Out of Scope

- AsciiMath single-dollar support (separate feature if ever needed)
- Network-level (per-network) settings for this toggle
- Automatic migration of existing content containing `$...$`
