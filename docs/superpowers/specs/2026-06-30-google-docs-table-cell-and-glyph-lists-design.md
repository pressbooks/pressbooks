# Design — Google Docs import: lists in table-cell callout boxes & literal-glyph pseudo-bullets

- **Ticket:** [pressbooks#4506](https://github.com/pressbooks/pressbooks/issues/4506) — "Google Docs import flattens lists inside textboxes into a run-on paragraph"
- **Branch:** `feat/pb-lab-04-2026` (amended into the Google Docs importer work, PR #4432)
- **Date:** 2026-06-30
- **Status:** Approved for planning
- **Touches:** `inc/modules/import/googledocs/class-docsmapper.php` only (+ tests/fixtures)

## 1. Problem & ground truth

The ticket reports that a "Learning Objectives" box imports as a single run-on paragraph
(`Define crime.Examine the criminal act.Distinguish…`) with no bullets and no item
boundaries, and hypothesizes the container is a **textbox / positioned object**.

Inspection of the **real Google Docs API JSON** from an actual import
(`gdoc-1vFon8n8…-979b4f9f.json`) **disproves the textbox hypothesis** and reveals two
distinct, independent defects:

| Source box | Real structure (from JSON) | Defect |
|---|---|---|
| LEARNING OBJECTIVES | `body.content[4]` = `table`, 2×1. Row 0/cell 0 = `HEADING_3` "LEARNING OBJECTIVES". Row 1/cell 0 = 8 real list items (`bullet.listId=kix.list.100`, nesting 0, glyph `●` → `ul`). | Table-cell rendering flattens cell paragraphs. |
| KEY TERMS | `body.content[71]` = `table`, 2×1. Row 0/cell 0 = `HEADING_4` "KEY TERMS". Row 1/cell 0 = 14 real list items (`kix.list.99`, `●` → `ul`). | Same as above. |
| ✔ checkmarks | `body.content[69]` = **single** `NORMAL_TEXT` paragraph, `bullet=None`. `✔` are **literal characters** in `textRun` content. Lines separated by `\x0b` (U+000B vertical-tab soft line breaks). Inline italics live in separate runs; `\x0b` can occur mid-run. | Soft breaks collapse + glyphs never become a list. |

The two `positionedObjects` in this doc are a **drawing** and an **image** — both
text-free. **No positioned-object walker is needed.** The ticket's "textbox" framing and
its "don't reproduce textbox border/shading" out-of-scope note are both moot: the callouts
are ordinary `<table>` elements that already render as tables.

### Root causes in code (`class-docsmapper.php`)

1. **Table cells bypass the block pipeline.** `renderTable()` (`:370-376`) calls
   `renderElements()` directly on each cell paragraph and concatenates the raw text — it
   never calls `renderParagraph()` or `finalize()`. So inside a cell, `HEADING_n` loses its
   tag and `bullet` paragraphs lose both list structure and item boundaries. Main-body
   paragraphs are fine because they *do* go through `renderParagraph()` + `finalize()`.

2. **Soft line breaks + literal glyphs are not handled.** `renderTextRun()` (`:229-256`)
   only `rtrim`s a trailing `\n`; internal `\x0b` survives, collapses to nothing in HTML, and
   adjacent lines run together. Literal bullet glyphs (`✔`, `●`, …) typed by the author are
   never recognized as list markers.

## 2. Scope

**In scope (confirmed by JSON):**
- **Fix 1 (A2):** Lists/headings/multi-paragraph content inside **table cells** render as
  proper block HTML.
- **Fix 2:** **Literal-glyph pseudo-bullets** within a **single paragraph that uses `\x0b`
  soft line breaks** become a real `<ul>`; non-glyph soft breaks become `<br>`.

**Out of scope (explicit):**
- Positioned-object / textbox / drawing text extraction (no such text exists here).
- Reproducing callout border/shading/box styling.
- **Standalone-paragraph pseudo-bullets** — each glyph item typed as its *own* `\n`
  paragraph (no `\x0b`). Not present in the sample; would require cross-paragraph grouping.
  **Deferred** as a possible follow-up ticket.
- **Numbered** pseudo-lists (`1.`/`a.` typed literally). Bullets only.

## 3. Fix 1 — table-cell block rendering (A2)

In `renderTable()`, replace the cell-content loop (`:370-376`) with a decision:

```
$paras = paragraphs in $cell['content'];
$needs_block =
    any paragraph has 'bullet'
 OR any paragraph style maps to a heading tag (styleToTag() !== null)
 OR count(paras) > 1;

if ( $needs_block ) {
    $cell_body = '';
    foreach ( $cell['content'] as $element ) {
        if ( isset($element['paragraph']) ) {
            $style = $element['paragraph']['paragraphStyle']['namedStyleType'] ?? 'NORMAL_TEXT';
            $cell_body .= $this->renderParagraph( $element['paragraph'], $style, $inline_objects, $lists );
        }
    }
    $cell_html = $this->finalize( $cell_body );   // resolves <!--LIST--> markers
} else {
    // unchanged: bare inline text for a simple single-paragraph cell
    $cell_html = trim( renderElements of the single paragraph );
}
$html .= '<td' . $attrs . '>' . $cell_html . '</td>';
```

**Rationale / guarantees:**
- Simple single-paragraph data cells (`<td>Cell A1</td>`) stay **byte-identical** →
  existing table tests (`:75-79`, `:277-278`) untouched, no regression on normal tables.
- The callout boxes get: `HEADING_4` → `<h4>`, 14 bullets → `<ul><li>…</li></ul>` with
  boundaries and nesting preserved — satisfies all ACs.
- Reuses already-tested heading + list + nesting logic; no duplicated parsing.
- Nested tables inside a cell remain unsupported (pre-existing limitation; cell loop only
  processes `paragraph` elements). Out of scope; no behavior change.

## 4. Fix 2 — literal-glyph pseudo-bullet lists

Applies only when a paragraph's concatenated run text contains `\x0b`. Paragraphs without
`\x0b` take the **existing** code path unchanged (zero regression).

### 4.1 Refactor (no behavior change)
Extract the link/underline/italic/bold wrapping from `renderTextRun()` into:

```
applyTextStyle( string $text, array $style ): string
```

`renderTextRun()` keeps its current contract: `rtrim` trailing `\n`, empty→`''`, else
`applyTextStyle()`. No output change for existing callers.

### 4.2 Split a paragraph into logical lines
New helper:

```
splitElementsIntoLines( array $elements, array $inline_objects ): array  // array of HTML line strings
```

- Iterate `$elements` in order, maintaining `$current_line` (HTML string).
- For a `textRun`: take its content, `rtrim` a trailing `\n`, split on `\x0b`. For each
  segment except the last → `applyTextStyle(segment, style)` appended to `$current_line`,
  then push `$current_line` and reset. The last segment is appended without pushing.
- For an `inlineObjectElement` / `footnoteReference`: render as today and append to
  `$current_line`.
- After the loop, push the final `$current_line`.
- Result: one entry per logical line, with inline formatting preserved per line.

### 4.3 Classify lines & emit
New helper:

```
glyphListItem( string $line_html ): ?string  // stripped <li> inner if the line is a bullet, else null
```

- Bullet test on the line's **leading visible text** (after optional leading whitespace):
  - **Always** a bullet: `●`, `•`, `○`, `▪`, `◦`, `‣`, `⁃`, `✔`, `✓`, `✗`, `➜`, `➤`.
  - `-`, `–`, `*` are bullets **only when immediately followed by a space**.
- If a bullet, strip the leading `^\s*<glyph>\s?` from the line HTML and return the
  remainder (trimmed); else return `null`. (Lines that begin with an inline tag before the
  glyph fail the test and are treated as non-bullet — acceptable edge.)

In `renderParagraph()`, for a `NORMAL_TEXT` paragraph whose text contains `\x0b`:
- Build `$lines = splitElementsIntoLines(...)`.
- Walk lines, grouping **maximal runs of consecutive bullet lines**:
  - Run length **≥ 2** → emit each as a list item via the existing marker:
    `makeListItem( $inner, 'glyph', 0, 'ul' )` (synthetic `listId = "glyph"`, nesting 0,
    type `ul`). `finalize()` then builds the `<ul>` and merges the consecutive markers.
  - Run length **< 2**, or non-bullet lines → accumulate into a `<p>`, joining consecutive
    such lines with `<br>`. Flush the `<p>` when a qualifying bullet run starts or at the end.
- A non-bullet line between two bullet runs flushes the `<p>` (a non-marker line), which
  makes `finalize()` close the current list → two separate `<ul>`s. Correct.

**Checkmark result:** all 7 lines are `✔` bullets, consecutive, ≥2 → one `<ul>` with 7
`<li>`, inline `<em>` preserved (`<li>Standardize definitions and clarify <em>what the
credential represents</em></li>`, …). No run-on text.

### 4.4 Interaction notes
- Synthetic `listId` `"glyph"` contains no `:`, so it's safe for the `finalize()` regex
  (`/^<!--LIST:([^:]+):(\d+):(\w+)-->/`).
- Headings and real `bullet` paragraphs are **not** line-split here (heading path and real
  list path unchanged). A real list item that itself contains `\x0b` is a rare edge; left
  as-is (no regression vs today). May be noted as a follow-up.
- Fix 2 lives in `renderParagraph()`, so it **also applies inside table cells** (Fix 1
  routes cell paragraphs through `renderParagraph()`): a glyph pseudo-list inside a callout
  box is handled for free.

## 5. Test plan

All tests run via `lando composer test -- --filter <name>` (host `vendor/bin/phpunit`
cannot reach the DB; `lando` is required).

### 5.1 Mapper unit tests (`tests/test-modules-import-google-docs-mapper.php`)
**Fix 1:**
- Callout table (heading row + bullet row) → cell contains `<h3>`/`<h4>` and a single
  `<ul>` with one `<li>` per item, no run-on, item text intact.
- Numbered list in a cell (`DECIMAL` glyph) → `<ol>`.
- Nested list in a cell → nesting preserved.
- **Regression:** existing `simple-table` / `merged-cells-table` assertions
  (`<td>Cell A1</td>`, `<td>B2</td>`, merged-cell warning) still pass unchanged.
- Multi-paragraph plain-text cell → `<p>…</p><p>…</p>` (no longer concatenated).

**Fix 2:**
- Soft-break paragraph with ≥2 `✔` lines → one `<ul>` with correct `<li>`s; inline `<em>`
  preserved; no `\x0b` and no run-on remain.
- Mixed glyphs (`●`, `✔`) consecutive → still one `<ul>`.
- `-`/`*` with trailing space → bullets; `-`/`*` without trailing space (e.g. `*emphasis*`,
  `well-known`) → **not** a bullet.
- Single lone bullet line among prose → **not** a list; rendered as `<p>` with `<br>`.
- Non-glyph soft-break paragraph → `<p>` with `<br>` between lines (no run-on, no list).
- Paragraph **without** `\x0b` → output identical to current behavior (fast-path guard).

### 5.2 Integration test (`tests/test-modules-import-google-docs-importer.php`)
- New fixture(s) under `tests/fixtures/google-docs/` modeling the real shapes:
  - `callout-table-list.json` — 2×1 table, heading row + real-bullet row.
  - `soft-break-glyph-list.json` — single paragraph with `\x0b` and literal `✔` lines.
- Assert the imported chapter body contains the `<h_n>` + `<ul><li>` for the callout and the
  `<ul><li>` for the glyph list; assert no `\x0b` and no run-on concatenation survive.

## 6. Acceptance criteria coverage (ticket #4506)

- Bulleted list inside box → `<ul><li>` — **Fix 1** (real bullets) / **Fix 2** (glyphs). ✔
- Numbered list inside box → `<ol><li>` — **Fix 1** (`DECIMAL`). ✔
- Item boundaries preserved — both fixes. ✔
- Nested items preserve nesting — **Fix 1** via `finalize()`. ✔
- Heading/label on its own line — **Fix 1** (`HEADING_n` → `<h_n>`). ✔
- No regression on normal lists/tables — fast-path guards + simple-cell branch; existing
  tests unchanged. ✔

## 7. Risks & mitigations
- **Changing all table-cell output:** mitigated by the simple-cell branch (only list/heading/
  multi-paragraph cells change). Existing assertions verify no drift.
- **Glyph false positives:** mitigated by the ≥2-consecutive threshold and the
  space-required rule for `-`/`*`.
- **Line-split losing inline formatting:** mitigated by per-segment `applyTextStyle()` and a
  dedicated test asserting `<em>` survives across a split.
