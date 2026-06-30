# Design — Google Docs import: positioned (floating) images

- **Issue:** New bug discovered during #4506 / PR #4432 testing — floating images dropped on import. (Distinct from #4506, which is lists in table cells.)
- **Branch:** `feat/pb-lab-04-2026` (folded in alongside the list fixes, PR #4432)
- **Date:** 2026-06-30
- **Status:** Approved for planning
- **Touches:** `inc/modules/import/googledocs/class-docsmapper.php` only (+ tests/fixtures)

## 1. Problem & ground truth

A floating image with the caption "Figure 1.1: The United States Supreme Court." imports with its
caption text present but **the image missing**. Confirmed against real Google Docs API JSON
(`gdoc-1vFon8n8…-7338f4bf.json`):

- The image is a **positioned object** `kix.he6ar3rdivr8`:
  - `positionedObjectProperties.embeddedObject.imageProperties.contentUri` (downloadable),
  - `title = "Figure 1.1: The United States Supreme Court."`,
  - `description = "Figure 1.1 The United States Supreme Court building."`,
  - layout `BREAK_LEFT_RIGHT`.
- It is **anchored** to `body.content[21]`, a `NORMAL_TEXT` paragraph whose own text is the caption
  (`paragraph.positionedObjectIds = ["kix.he6ar3rdivr8"]`). The caption paragraph imports normally as
  a `<p>`; only the image is lost.
- The doc's other positioned object `a.484828672.d.129` is a tiny (`8.85×14.75pt`, `BEHIND_TEXT`)
  **drawing** — a decorative artifact to skip.

### Root cause
`DocsMapper::toChapters()` walks only `paragraph` / `table` / `sectionBreak` and reads images only
from `inlineObjects` (`collectImageMeta` / `renderInlineObject` look solely at `inlineObjectElement`).
**Positioned objects are never read**, so a positioned image yields no `<img>`, no placeholder, and no
download metadata — it is dropped entirely. Inline images (e.g. `i.3`) still import fine.

### Importer is already compatible (mapper-only fix)
`GoogleDocs::processImages()` (`class-googledocs.php:126, 178-193`) iterates the chapter's `images[]`
array, builds `#gdoc-image-{object_id}`, downloads `content_uri`, and replaces — independent of whether
the image was inline or positioned. So emitting the same placeholder and adding the same `images[]`
entry shape makes downloads work with **no importer change**.

## 2. Scope

**In scope:** positioned **images** anchored to body paragraphs are rendered as `<img>` and queued for
download.

**Out of scope:**
- Positioned **drawings**/shapes (skipped, consistent with inline drawing handling).
- `<figure>/<figcaption>` semantics or caption pairing (the anchor paragraph is not reliably a
  caption; the existing caption paragraph already renders as its own `<p>`).
- Reproducing float/wrap/positioning layout.
- Positioned objects anchored **inside table cells** (v1 limitation — `renderCellContent` will not scan
  `positionedObjectIds`). Documented; revisit only if real docs need it.

## 3. Design

### 3.1 Thread positioned objects through `toChapters()`
Read `$positioned_objects = $doc['positionedObjects'] ?? []` alongside the existing
`$inline_objects` / `$lists` (`class-docsmapper.php:28-30`).

### 3.2 New helper
```php
renderPositionedImages( array $ids, array $positioned_objects, array &$images ): string
```
For each `$id` in `$ids`:
- Resolve `$obj = $positioned_objects[$id]['positionedObjectProperties']['embeddedObject'] ?? []`.
- **Image** — `isset($obj['imageProperties']['contentUri'])`:
  - `$alt = $obj['description'] ?: ($obj['title'] ?? '')` (mirrors `renderInlineObject`).
  - Append to `$images`: `['object_id'=>$id, 'content_uri'=>$obj['imageProperties']['contentUri'],
    'alt'=>$alt, 'title'=>$obj['title'] ?? '']` (same keys `collectImageMeta` produces).
  - Append `'<img src="' . htmlspecialchars('#gdoc-image-'.$id, ENT_QUOTES,'UTF-8') . '" alt="' .
    htmlspecialchars($alt, ENT_QUOTES,'UTF-8') . '" />'` to the output.
- **Drawing** — `isset($obj['embeddedDrawingProperties'])`: skip; `$this->warnings[] = "Positioned
  drawing skipped (unsupported): {$id}";`.
- Otherwise: skip.

Returns the concatenated `<img>` HTML (empty string when nothing qualifies).

### 3.3 Emit at the anchor (before the paragraph)
In the `paragraph` branch of `toChapters()` (`class-docsmapper.php:43-72`), after the existing
`collectImageMeta(...)` and before `renderParagraph(...)`:
```php
$current_body .= $this->renderPositionedImages( $para['positionedObjectIds'] ?? [], $positioned_objects, $current_images );
$current_body .= $this->renderParagraph( $para, $style_type, $inline_objects, $lists );
```
For the figure this yields `<img … alt="Figure 1.1 … building." />` immediately followed by
`<p>Figure 1.1: The United States Supreme Court.</p>` — image restored directly above its caption line.

The HEADING_1 chapter-split branch (`:47-61`) is unaffected: positioned images are only emitted on
non-heading body paragraphs via the code above (a heading anchoring an image is a rare edge; image is
simply not emitted there in v1, no regression vs today).

## 4. Edge cases
- **Multiple `positionedObjectIds`** on one paragraph → loop emits each in order.
- **Drawing** positioned object → skipped + warning (the `BEHIND_TEXT` artifact).
- **Image without `contentUri`** → skipped (no placeholder, no metadata).
- **Empty anchor paragraph** (just `\n`) → `renderParagraph` returns `''`; the `<img>` still emits, so a
  bare image with no stray empty `<p>`.
- **Alt text** → `description`, falling back to `title`, then `''`.

## 5. Test plan
Run via `lando composer test -- --filter <name>`.

**Unit (`tests/test-modules-import-google-docs-mapper.php`, inline docs):**
- Paragraph with `positionedObjectIds` → an image in `positionedObjects`: body contains
  `<img src="#gdoc-image-{id}" alt="…" />` positioned **before** the caption `<p>`; and
  `$chapters[0]['images']` has one entry with the right `object_id`, `content_uri`, `alt`.
- Positioned **drawing** → no `<img>`; `getWarnings()` contains the skip warning.
- Image with `title` but empty/absent `description` → `alt` uses `title`.
- Paragraph with no `positionedObjectIds` → output unchanged (regression guard).

**Integration (fixture `tests/fixtures/google-docs/positioned-image.json`):**
- Models the real shape: a body paragraph carrying `positionedObjectIds` whose text is the caption, plus
  a `positionedObjects` map with one image (`imageProperties.contentUri`, `title`, `description`).
- Assert the chapter body contains the `<img>` placeholder before the caption `<p>`, and `images[]`
  carries the entry the importer needs.

## 6. Acceptance
- A floating (positioned) image imports as an `<img>` queued for download via the existing
  `#gdoc-image-*` mechanism (no importer change).
- Its caption paragraph still renders (unchanged).
- Positioned drawings are skipped with a warning.
- No regression to inline images, normal paragraphs, headings, or tables.

## 7. Risks & mitigations
- **Wrong/duplicate placement:** emitting before the paragraph keeps image-then-caption order; no caption
  assumption, so no mis-wrapping. — Low.
- **Download shape mismatch:** mitigated by reusing `collectImageMeta`'s exact `images[]` keys; an
  integration assertion checks the entry. — Low.
- **Table-cell-anchored positioned images:** explicitly out of scope (documented). — Accepted.
