# Google Docs Import — `[GT]` Glossary Terms

**Date:** 2026-06-29
**Ticket:** [pressbooks#4505](https://github.com/pressbooks/pressbooks/issues/4505)
**Branch:** `feat/pb-lab-04-2026` (amended into the Google Docs importer, PR [pressbooks#4432](https://github.com/pressbooks/pressbooks/pull/4432))

## Summary

During Google Docs import, recognize inline `[GT]term[/GT]` markers in the running
content, read term definitions from a dedicated **Glossary** section (introduced by an
H3 heading titled "Glossary"), create Pressbooks `glossary` custom-post-type entries, and
replace each inline marker with the `[pb_glossary id="N"]term[/pb_glossary]` shortcode so
the terms surface through Pressbooks' existing glossary back matter and management screen.

No separate Glossary page/chapter is created — the Glossary section is **consumed** to
populate terms and never imported as its own content.

## Goals / Acceptance

- `[GT]…[/GT]` in running content is recognized as a glossary reference during import.
- The Glossary section (H3 titled "Glossary") is parsed for `Term: Definition` entries,
  including multiline definitions.
- Each unique term produces a single `glossary` entry (case-insensitive de-dupe), even
  when referenced many times across chapters.
- Every `[GT]term[/GT]` occurrence is replaced with a shortcode linking to that one entry.
- A term's definition comes from its matching entry in the Glossary section.
- A `[GT]` marker with no matching entry still produces a linked entry, with an empty
  definition for the author to fill in later.
- The Glossary section is consumed and not imported as its own page/chapter.
- Imported terms surface in the existing glossary back matter and management screen.
- The literal `[GT]`/`[/GT]` markers never appear in imported content.
- Multi-word and parenthesized terms (e.g. `operating system (OS)`) match correctly.

## Architecture (Approach A)

The import loop in `GoogleDocs::import()` saves chapters one at a time, but terms must be
resolved **book-wide** before any chapter is saved (a `[GT]` marker in one chapter may
reference a definition in another chapter's Glossary section). This requires a two-pass
design, mirroring the WXR importer's existing two-phase glossary remap
(`inc/modules/import/wordpress/class-wxr.php:826-868`).

### New class: `GlossaryParser` (pure PHP, no WordPress)

- **File:** `inc/modules/import/googledocs/class-glossaryparser.php`
- **Namespace:** `Pressbooks\Modules\Import\GoogleDocs`
- **Input:** the `$chapters_data` array produced by `DocsMapper::toChapters()`.
- **Output** (result object/array):
  - `terms` — map `normalizedKey => ['title' => original-cased term, 'definition' => string]`
    for every entry found in the Glossary section.
  - `markerTerms` — set of normalized terms referenced by `[GT]…[/GT]` in the chapters
    being saved.
  - `strippedBodies` — chapter bodies with the Glossary `<h3>` section removed.

Keeping this class free of WordPress calls makes the tricky parsing fully unit-testable,
matching the existing `DocsMapper` test style, and keeps `DocsMapper` a pure converter.

### Glue in `GoogleDocs::import()`

File `inc/modules/import/googledocs/class-googledocs.php`.

**Pre-pass** — right after `toChapters()` (`:79`), before the save loop:

1. Run `GlossaryParser` over the mapped chapters.
2. Prime a case-insensitive lookup from `Glossary::init()->getGlossaryTerms()` (keyed by
   title) so re-imports reuse existing terms rather than duplicating.
3. For every resolved term (glossary entries ∪ `[GT]` markers with no entry → empty
   definition) not already present, create it:
   `wp_insert_post(['post_title' => title, 'post_content' => definition,
   'post_type' => 'glossary', 'post_status' => 'publish'])`.
4. Call `getGlossaryTerms(true)` once to bust the static cache. Build
   `normalizedKey => postID`.
5. Write `strippedBodies` back into `$chapters_data` so the save loop uses them.

**Save loop** — after `tidy()` (`:95`), before `wp_insert_post()` (`:111`):

- Replace markers via
  `preg_replace_callback('/\[GT\](.+?)\[\/GT\]/s', …)` → `[pb_glossary id="N"]{verbatim inner}[/pb_glossary]`,
  done post-`tidy()` so the shortcode is not mangled.
- **Skip-empty safeguard:** if a chapter body is effectively empty after stripping +
  replacement (only whitespace/empty tags), skip `wp_insert_post()` for it.

## Parsing rules

- **Marker detection:** regex `/\[GT\](.+?)\[\/GT\]/s`. The captured inner text is the
  displayed term; the **lookup key** is `mb_strtolower(trim(strip_tags(inner)))`. The
  displayed text is preserved verbatim in the replacement, keeping each occurrence's
  original casing/format.
- **Glossary section:** locate the first `<h3>` whose trimmed text equals `glossary`
  (case-insensitive). The section is every block after it up to the next `<h1>/<h2>/<h3>`
  or end of chapter. Parsed via `DOMDocument` for robustness.
- **Entry boundaries (colon + plausible key):** split the section into lines (each `<p>`,
  and on `<br>`). A line **starts a new entry** when it contains a colon AND the text
  before the first colon, trimmed, is a *plausible key*: non-empty, ≤ 60 chars, ≤ 6 words,
  not ending in sentence punctuation (`.?!`). Otherwise the line is a **continuation**,
  appended to the current definition (joined with `<br>`). Term = text before the first
  colon; definition = text after it plus continuations.
- **De-dupe:** case-insensitive on the normalized key; the first occurrence's casing wins
  for the post title.
- **Matching `[GT]` ↔ entries:** by normalized key. A `[GT]` term with no matching entry
  still produces a published `glossary` post with empty content. A glossary entry never
  referenced by any `[GT]` marker still becomes a term.
- **Definitions** are shaped to the allowed markup; `sanitizeGlossaryTerm()`
  (`inc/shortcodes/glossary/class-glossary.php:323`) enforces the `a, br, em, p, strong,
  sub, sup` allowlist on save regardless.

## Scope reconciliation

- **Glossary-section detection** runs over **all** mapped chapters (found wherever it
  lives, even an unselected chapter).
- **`[GT]` collection / replacement** runs over the chapters being **saved**.
- **Terms created** = all glossary entries ∪ `[GT]` terms (in saved chapters) lacking an
  entry. This avoids orphan terms from unselected chapters while always finding the
  definitions.
- Glossary handling is automatic ("under the hood"): the definitions section is never
  surfaced as a selectable/importable chapter. When the Glossary is authored under its own
  H1, stripping leaves that chapter empty and the skip-empty safeguard prevents it from
  importing.

## Edge cases

- Term at sentence start vs mid-sentence.
- Same term referenced many times → a single post.
- Multi-word and parenthesized terms (e.g. `operating system (OS)`).
- Multiline definitions.
- `[GT]` term missing from the Glossary section → empty published post.
- Glossary entry never referenced inline → still created.
- Continuation line containing a colon → stays a continuation via the plausible-key test.

## Test plan

- **Unit tests** for `GlossaryParser` — new `tests/test-modules-import-google-docs-glossary-parser.php`
  (pure PHP, fast): marker detection variants, section detection + boundary, multiline,
  colon-in-continuation, case-insensitive de-dupe, parenthesized/multi-word, missing-entry,
  unreferenced-entry, section stripping.
- **Integration tests** in `tests/test-modules-import-google-docs-importer.php`: glossary
  posts created with correct titles/definitions, ids wired into `[pb_glossary]` shortcodes,
  no `[GT]` literals remain, de-dupe across chapters, glossary-only chapter skipped, terms
  are `post_type=glossary` + published (surface in back matter).
- **New fixtures** under `tests/fixtures/google-docs/` (Google Docs API JSON shape), e.g.
  `with-glossary-terms.json`.

## Out of scope

- Creating or importing a Glossary page — definitions feed the existing glossary back matter.
- Inline definition parsing from text surrounding the marker.
- Markers other than `[GT]…[/GT]`.

## Key references

- Importer flow: `inc/modules/import/googledocs/class-googledocs.php` (`import()` `:58`,
  `toChapters()` call `:79`, `tidy()` `:95`, `wp_insert_post()` `:111`).
- Converter: `inc/modules/import/googledocs/class-docsmapper.php` (`toChapters()` `:25`).
- Glossary CPT + shortcode: `inc/shortcodes/glossary/class-glossary.php`
  (`SHORTCODE` `:18`, `getGlossaryTerms()` `:98`, `sanitizeGlossaryTerm()` `:323`).
- WXR two-phase precedent: `inc/modules/import/wordpress/class-wxr.php:826-868`.
