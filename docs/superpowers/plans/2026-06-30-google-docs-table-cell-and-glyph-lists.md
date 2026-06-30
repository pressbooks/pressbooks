# Google Docs Table-Cell & Literal-Glyph List Import — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make Google Docs import preserve list/heading structure inside table-cell callout boxes, and convert literal-glyph pseudo-bullet lines (with `\x0b` soft breaks) into real `<ul>` lists.

**Architecture:** All changes are confined to `inc/modules/import/googledocs/class-docsmapper.php`. Fix 1 routes table-cell content through the existing `renderParagraph()` + `finalize()` block pipeline (only when a cell holds a list/heading/multiple paragraphs/soft-break paragraph). Fix 2 adds soft-break (`\x0b`) line splitting in `renderParagraph()` for `NORMAL_TEXT` paragraphs, grouping ≥2 consecutive bullet-glyph lines into list-item markers that `finalize()` resolves.

**Tech Stack:** PHP 8.3, WordPress test framework (`WP_UnitTestCase`), PHPUnit.

## Global Constraints

- **Test runner:** `lando composer test -- --filter <name>` (the host `vendor/bin/phpunit` cannot reach the DB; Lando is required).
- **Touched file:** `inc/modules/import/googledocs/class-docsmapper.php` only (plus `tests/`).
- **Coding standards:** snake_case variables; the canonical gate is `lando composer standards` (lints `inc/ bin/`, ruleset `phpcs.ruleset.xml`). Keep production code clean under it.
- **No regression:** existing table tests assert bare cell text (`<td>Cell A1</td>`); simple single-paragraph cells must stay byte-identical.
- **Glyph set (always bullets):** `● • ○ ▪ ◦ ‣ ⁃ ✔ ✓ ✗ ➜ ➤`. Plus `-`, `–`, `*` **only when followed by a space**.
- **List threshold:** a maximal run of **≥2** consecutive glyph lines becomes a `<ul>`; lone glyph lines stay text.
- **Soft-break char:** `\x0b` (U+000B vertical tab). Bullets only — numbered pseudo-lists are out of scope.

---

### Task 1: Extract `applyTextStyle()` from `renderTextRun()` (refactor)

Pure extract-method refactor enabling Fix 2's per-segment styling. No behavior change; guarded by the existing `test_text_styling`.

**Files:**
- Modify: `inc/modules/import/googledocs/class-docsmapper.php:229-256` (`renderTextRun`)
- Test (existing regression guard): `tests/test-modules-import-google-docs-mapper.php::test_text_styling`

**Interfaces:**
- Produces: `applyTextStyle( string $text, array $style ): string` — wraps `$text` in `<a>/<u>/<em>/<strong>` per `$style` (link, underline, italic, bold); returns `''` for empty `$text`.
- Produces: `renderTextRun( array $run ): string` — unchanged contract (rtrim trailing `\n`, delegate to `applyTextStyle`).

- [ ] **Step 1: Establish green baseline**

Run: `lando composer test -- --filter test_text_styling`
Expected: PASS (1 test).

- [ ] **Step 2: Refactor — extract `applyTextStyle`**

Replace `renderTextRun()` (`:229-256`) with:

```php
	/**
	 * Render a single text run with styling.
	 */
	protected function renderTextRun( array $run ): string {
		$text = rtrim( $run['content'] ?? '', "\n" );
		if ( $text === '' ) {
			return '';
		}
		return $this->applyTextStyle( $text, $run['textStyle'] ?? [] );
	}

	/**
	 * Wrap text in inline formatting tags based on a Google Docs textStyle.
	 */
	protected function applyTextStyle( string $text, array $style ): string {
		if ( $text === '' ) {
			return '';
		}

		// Apply link
		if ( ! empty( $style['link']['url'] ) ) {
			$url = $style['link']['url'];
			$text = '<a href="' . htmlspecialchars( $url, ENT_QUOTES, 'UTF-8' ) . '">' . $text . '</a>';
		}

		// Apply formatting (innermost first)
		if ( ! empty( $style['underline'] ) && empty( $style['link'] ) ) {
			$text = '<u>' . $text . '</u>';
		}
		if ( ! empty( $style['italic'] ) ) {
			$text = '<em>' . $text . '</em>';
		}
		if ( ! empty( $style['bold'] ) ) {
			$text = '<strong>' . $text . '</strong>';
		}

		return $text;
	}
```

- [ ] **Step 3: Verify no behavior change**

Run: `lando composer test -- --filter test_text_styling`
Expected: PASS.

- [ ] **Step 4: Run the full mapper suite**

Run: `lando composer test -- --filter Modules_ImportGoogleDocsMapperTest`
Expected: PASS (all existing tests green).

- [ ] **Step 5: Commit**

```bash
git add inc/modules/import/googledocs/class-docsmapper.php
git commit -m "refactor(google-docs): extract applyTextStyle from renderTextRun"
```

---

### Task 2: Fix 1 — block-render table cells with lists/headings/multiple paragraphs

**Files:**
- Modify: `inc/modules/import/googledocs/class-docsmapper.php:370-386` (cell loop inside `renderTable`)
- Test: `tests/test-modules-import-google-docs-mapper.php`

**Interfaces:**
- Consumes: `renderParagraph()`, `finalize()`, `renderElements()`, `styleToTag()` (existing).
- Produces: `renderCellContent( array $cell, array $inline_objects, array $lists ): string` — returns a cell's inner HTML; bare trimmed text for a simple single plain paragraph, otherwise block HTML (`<h_n>`, `<p>`, `<ul>/<ol>`).

- [ ] **Step 1: Write the failing tests**

Add to `tests/test-modules-import-google-docs-mapper.php` (before the closing `}`):

```php
	/**
	 * @group import
	 */
	public function test_table_cell_with_heading_and_bullet_list(): void {
		$doc = [
			'title' => 'Callout',
			'body' => [ 'content' => [
				[ 'sectionBreak' => [ 'sectionStyle' => [] ] ],
				[ 'paragraph' => [ 'elements' => [ [ 'textRun' => [ 'content' => "Chapter\n", 'textStyle' => [] ] ] ], 'paragraphStyle' => [ 'namedStyleType' => 'HEADING_1' ] ] ],
				[ 'table' => [ 'tableRows' => [
					[ 'tableCells' => [ [ 'content' => [
						[ 'paragraph' => [ 'elements' => [ [ 'textRun' => [ 'content' => "LEARNING OBJECTIVES\n", 'textStyle' => [] ] ] ], 'paragraphStyle' => [ 'namedStyleType' => 'HEADING_3' ] ] ],
					] ] ] ],
					[ 'tableCells' => [ [ 'content' => [
						[ 'paragraph' => [ 'elements' => [ [ 'textRun' => [ 'content' => "Define crime.\n", 'textStyle' => [] ] ] ], 'paragraphStyle' => [ 'namedStyleType' => 'NORMAL_TEXT' ], 'bullet' => [ 'listId' => 'kix.list.100', 'nestingLevel' => 0 ] ] ],
						[ 'paragraph' => [ 'elements' => [ [ 'textRun' => [ 'content' => "Examine the act.\n", 'textStyle' => [] ] ] ], 'paragraphStyle' => [ 'namedStyleType' => 'NORMAL_TEXT' ], 'bullet' => [ 'listId' => 'kix.list.100', 'nestingLevel' => 0 ] ] ],
					] ] ] ],
				] ] ],
			] ],
			'inlineObjects' => [],
			'lists' => [ 'kix.list.100' => [ 'listProperties' => [ 'nestingLevels' => [ [ 'glyphSymbol' => '●' ] ] ] ] ],
		];

		$mapper = new DocsMapper();
		$chapters = $mapper->toChapters( $doc );
		$body = $chapters[0]['body'];

		$this->assertStringContainsString( '<h3>LEARNING OBJECTIVES</h3>', $body );
		$this->assertStringContainsString( '<ul>', $body );
		$this->assertStringContainsString( '<li>Define crime.</li>', $body );
		$this->assertStringContainsString( '<li>Examine the act.</li>', $body );
		$this->assertStringNotContainsString( 'Define crime.Examine', $body );
	}

	/**
	 * @group import
	 */
	public function test_table_cell_with_ordered_list(): void {
		$doc = [
			'title' => 'Callout',
			'body' => [ 'content' => [
				[ 'sectionBreak' => [ 'sectionStyle' => [] ] ],
				[ 'paragraph' => [ 'elements' => [ [ 'textRun' => [ 'content' => "Chapter\n", 'textStyle' => [] ] ] ], 'paragraphStyle' => [ 'namedStyleType' => 'HEADING_1' ] ] ],
				[ 'table' => [ 'tableRows' => [
					[ 'tableCells' => [ [ 'content' => [
						[ 'paragraph' => [ 'elements' => [ [ 'textRun' => [ 'content' => "Step one.\n", 'textStyle' => [] ] ] ], 'paragraphStyle' => [ 'namedStyleType' => 'NORMAL_TEXT' ], 'bullet' => [ 'listId' => 'kix.list.7', 'nestingLevel' => 0 ] ] ],
						[ 'paragraph' => [ 'elements' => [ [ 'textRun' => [ 'content' => "Step two.\n", 'textStyle' => [] ] ] ], 'paragraphStyle' => [ 'namedStyleType' => 'NORMAL_TEXT' ], 'bullet' => [ 'listId' => 'kix.list.7', 'nestingLevel' => 0 ] ] ],
					] ] ] ],
				] ] ],
			] ],
			'inlineObjects' => [],
			'lists' => [ 'kix.list.7' => [ 'listProperties' => [ 'nestingLevels' => [ [ 'glyphType' => 'DECIMAL' ] ] ] ] ],
		];

		$mapper = new DocsMapper();
		$chapters = $mapper->toChapters( $doc );
		$body = $chapters[0]['body'];

		$this->assertStringContainsString( '<ol>', $body );
		$this->assertStringContainsString( '<li>Step one.</li>', $body );
		$this->assertStringContainsString( '<li>Step two.</li>', $body );
	}
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `lando composer test -- --filter test_table_cell_with_heading_and_bullet_list`
Expected: FAIL — body contains run-on `LEARNING OBJECTIVESDefine crime.Examine the act.` and no `<h3>`/`<ul>`.

- [ ] **Step 3: Implement `renderCellContent` and call it from `renderTable`**

In `renderTable()`, replace the cell-content block (`:370-386`) — from `$cell_content = '';` through the `$html .= '<td' . $attrs . '>' . trim( $cell_content ) . '</td>';` line — with:

```php
				$cell_html = $this->renderCellContent( $cell, $inline_objects, $lists );

				$attrs = '';
				if ( $col_span > 1 ) {
					$attrs .= ' colspan="' . $col_span . '"';
				}
				if ( $row_span > 1 ) {
					$attrs .= ' rowspan="' . $row_span . '"';
				}

				$html .= '<td' . $attrs . '>' . $cell_html . '</td>';
```

Then add this new method (e.g. directly after `renderTable()`):

```php
	/**
	 * Render the inner HTML of a single table cell.
	 *
	 * Simple single plain-text paragraphs render as bare text (legacy behavior).
	 * Cells containing a list, a heading, multiple paragraphs, or a soft-break
	 * paragraph are rendered through the block pipeline so structure is kept.
	 */
	protected function renderCellContent( array $cell, array $inline_objects, array $lists ): string {
		$paragraphs = [];
		foreach ( $cell['content'] ?? [] as $element ) {
			if ( isset( $element['paragraph'] ) ) {
				$paragraphs[] = $element['paragraph'];
			}
		}

		$needs_block = count( $paragraphs ) > 1;
		if ( ! $needs_block ) {
			foreach ( $paragraphs as $p ) {
				$style = $p['paragraphStyle']['namedStyleType'] ?? 'NORMAL_TEXT';
				if ( isset( $p['bullet'] ) || $this->styleToTag( $style ) !== null || $this->paragraphHasSoftBreak( $p['elements'] ?? [] ) ) {
					$needs_block = true;
					break;
				}
			}
		}

		if ( ! $needs_block ) {
			$text = '';
			foreach ( $paragraphs as $p ) {
				$text .= $this->renderElements( $p['elements'] ?? [], $inline_objects );
			}
			return trim( $text );
		}

		$cell_body = '';
		foreach ( $paragraphs as $p ) {
			$style = $p['paragraphStyle']['namedStyleType'] ?? 'NORMAL_TEXT';
			$cell_body .= $this->renderParagraph( $p, $style, $inline_objects, $lists );
		}
		return $this->finalize( $cell_body );
	}
```

Also add this helper (used here and in Tasks 3–4); place it near `renderParagraph()`:

```php
	/**
	 * True if any text run in the elements contains a soft line break (vertical tab).
	 */
	protected function paragraphHasSoftBreak( array $elements ): bool {
		foreach ( $elements as $el ) {
			if ( isset( $el['textRun']['content'] ) && strpos( $el['textRun']['content'], "\x0b" ) !== false ) {
				return true;
			}
		}
		return false;
	}
```

- [ ] **Step 4: Run the new tests to verify they pass**

Run: `lando composer test -- --filter test_table_cell_with_heading_and_bullet_list`
Then: `lando composer test -- --filter test_table_cell_with_ordered_list`
Expected: PASS.

- [ ] **Step 5: Verify no table regression**

Run: `lando composer test -- --filter test_tables`
Then: `lando composer test -- --filter test_simple_table_no_span_attributes`
Then: `lando composer test -- --filter test_merged_cells_rowspan`
Expected: PASS (bare `<td>Cell A1</td>`, `<td>B2</td>`, `<td>C3</td>` still produced).

- [ ] **Step 6: Commit**

```bash
git add inc/modules/import/googledocs/class-docsmapper.php tests/test-modules-import-google-docs-mapper.php
git commit -m "feat(google-docs): render lists and headings inside table cells (#4506)"
```

---

### Task 3: Fix 2a — split soft-break paragraphs into `<br>`-joined lines

Delivers the line-splitting infrastructure and the non-glyph behavior: a `NORMAL_TEXT` paragraph containing `\x0b` no longer runs together — its lines are joined with `<br>`. Glyph→list conversion comes in Task 4.

**Files:**
- Modify: `inc/modules/import/googledocs/class-docsmapper.php:161-188` (`renderParagraph`)
- Test: `tests/test-modules-import-google-docs-mapper.php`

**Interfaces:**
- Consumes: `applyTextStyle()` (Task 1), `paragraphHasSoftBreak()` (Task 2), `renderInlineObject()`, `renderFootnoteReference()`.
- Produces: `splitElementsIntoLines( array $elements, array $inline_objects ): array` — array of per-line HTML strings, split on `\x0b`, inline formatting preserved per line.
- Produces: `renderMultilineParagraph( array $elements, array $inline_objects ): string` — Task 3 version returns one `<p>` with lines joined by `<br>`. (Replaced in Task 4.)

- [ ] **Step 1: Write the failing test**

Add to `tests/test-modules-import-google-docs-mapper.php`:

```php
	/**
	 * @group import
	 */
	public function test_soft_break_paragraph_uses_line_breaks(): void {
		$doc = [
			'title' => 'Soft breaks',
			'body' => [ 'content' => [
				[ 'sectionBreak' => [ 'sectionStyle' => [] ] ],
				[ 'paragraph' => [ 'elements' => [ [ 'textRun' => [ 'content' => "Chapter\n", 'textStyle' => [] ] ] ], 'paragraphStyle' => [ 'namedStyleType' => 'HEADING_1' ] ] ],
				[ 'paragraph' => [
					'elements' => [
						[ 'textRun' => [ 'content' => "First line\x0bSecond line\x0bThird line\n", 'textStyle' => [] ] ],
					],
					'paragraphStyle' => [ 'namedStyleType' => 'NORMAL_TEXT' ],
				] ],
			] ],
			'inlineObjects' => [],
		];

		$mapper = new DocsMapper();
		$chapters = $mapper->toChapters( $doc );
		$body = $chapters[0]['body'];

		$this->assertStringContainsString( '<p>First line<br>Second line<br>Third line</p>', $body );
		$this->assertStringNotContainsString( "\x0b", $body );
		$this->assertStringNotContainsString( 'First lineSecond', $body );
	}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `lando composer test -- --filter test_soft_break_paragraph_uses_line_breaks`
Expected: FAIL — body shows `First lineSecond lineThird line` run together (the `\x0b` collapses).

- [ ] **Step 3: Add the split helpers and wire into `renderParagraph`**

Replace `renderParagraph()` (`:161-188`) with:

```php
	/**
	 * Render a paragraph element to HTML.
	 */
	protected function renderParagraph( array $para, string $style_type, array $inline_objects, array $lists ): string {
		// Handle list items
		if ( isset( $para['bullet'] ) ) {
			$list_id = $para['bullet']['listId'];
			$nesting = $para['bullet']['nestingLevel'] ?? 0;
			$text = $this->renderElements( $para['elements'] ?? [], $inline_objects );
			$list_type = $this->getListType( $list_id, $nesting, $lists );

			return $this->makeListItem( $text, $list_id, $nesting, $list_type );
		}

		// Handle heading styles
		$tag = $this->styleToTag( $style_type );
		if ( $tag !== null ) {
			$text = $this->renderElements( $para['elements'] ?? [], $inline_objects );
			return "<{$tag}>{$text}</{$tag}>\n";
		}

		$elements = $para['elements'] ?? [];

		// Normal text with manual (soft) line breaks
		if ( $this->paragraphHasSoftBreak( $elements ) ) {
			return $this->renderMultilineParagraph( $elements, $inline_objects );
		}

		$text = $this->renderElements( $elements, $inline_objects );
		if ( trim( $text ) === '' ) {
			return '';
		}

		return "<p>{$text}</p>\n";
	}

	/**
	 * Split paragraph elements into per-line HTML strings on \x0b soft line breaks,
	 * preserving inline formatting within each line.
	 */
	protected function splitElementsIntoLines( array $elements, array $inline_objects ): array {
		$lines = [];
		$current = '';
		foreach ( $elements as $el ) {
			if ( isset( $el['textRun'] ) ) {
				$content = rtrim( $el['textRun']['content'] ?? '', "\n" );
				$style = $el['textRun']['textStyle'] ?? [];
				$segments = explode( "\x0b", $content );
				$last = count( $segments ) - 1;
				foreach ( $segments as $i => $segment ) {
					$current .= $this->applyTextStyle( $segment, $style );
					if ( $i !== $last ) {
						$lines[] = $current;
						$current = '';
					}
				}
			} elseif ( isset( $el['inlineObjectElement'] ) ) {
				$current .= $this->renderInlineObject( $el['inlineObjectElement']['inlineObjectId'] ?? '', $inline_objects );
			} elseif ( isset( $el['footnoteReference'] ) ) {
				$current .= $this->renderFootnoteReference( $el['footnoteReference'], $inline_objects );
			}
		}
		$lines[] = $current;
		return $lines;
	}

	/**
	 * Render a NORMAL_TEXT paragraph that contains soft line breaks.
	 * (Task 3: join lines with <br>. Replaced in Task 4 to detect glyph lists.)
	 */
	protected function renderMultilineParagraph( array $elements, array $inline_objects ): string {
		$lines = array_map( 'trim', $this->splitElementsIntoLines( $elements, $inline_objects ) );
		$lines = array_values( array_filter( $lines, static fn( $l ) => $l !== '' ) );
		if ( empty( $lines ) ) {
			return '';
		}
		return '<p>' . implode( '<br>', $lines ) . "</p>\n";
	}
```

- [ ] **Step 4: Run the test to verify it passes**

Run: `lando composer test -- --filter test_soft_break_paragraph_uses_line_breaks`
Expected: PASS.

- [ ] **Step 5: Verify no regression**

Run: `lando composer test -- --filter Modules_ImportGoogleDocsMapperTest`
Expected: PASS (all tests, incl. `test_text_styling`, `test_tables`, Task 2 tests).

- [ ] **Step 6: Commit**

```bash
git add inc/modules/import/googledocs/class-docsmapper.php tests/test-modules-import-google-docs-mapper.php
git commit -m "feat(google-docs): preserve soft line breaks as <br> in paragraphs (#4506)"
```

---

### Task 4: Fix 2b — convert consecutive bullet-glyph lines into `<ul>`

**Files:**
- Modify: `inc/modules/import/googledocs/class-docsmapper.php` (`renderMultilineParagraph`; add `stripBulletGlyph` + glyph constant)
- Test: `tests/test-modules-import-google-docs-mapper.php`

**Interfaces:**
- Consumes: `splitElementsIntoLines()`, `makeListItem()`, `finalize()` (markers resolved at body/cell finalize time).
- Produces: `stripBulletGlyph( string $line ): ?string` — if the trimmed line starts with a bullet glyph (always-set, or `-`/`–`/`*` + space), returns the remainder with the glyph stripped and left-trimmed; else `null`.
- Produces: `renderMultilineParagraph()` — final version: emits `<!--LIST:glyph:0:ul-->` markers for maximal runs of ≥2 bullet lines, and `<p>`/`<br>` blocks for everything else.

- [ ] **Step 1: Write the failing tests**

Add to `tests/test-modules-import-google-docs-mapper.php`:

```php
	/**
	 * @group import
	 */
	public function test_checkmark_glyph_lines_become_list(): void {
		$doc = [
			'title' => 'Glyphs',
			'body' => [ 'content' => [
				[ 'sectionBreak' => [ 'sectionStyle' => [] ] ],
				[ 'paragraph' => [ 'elements' => [ [ 'textRun' => [ 'content' => "Chapter\n", 'textStyle' => [] ] ] ], 'paragraphStyle' => [ 'namedStyleType' => 'HEADING_1' ] ] ],
				[ 'paragraph' => [
					'elements' => [
						[ 'textRun' => [ 'content' => '✔ Standardize definitions and clarify ', 'textStyle' => [] ] ],
						[ 'textRun' => [ 'content' => 'what the credential represents', 'textStyle' => [ 'italic' => true ] ] ],
						[ 'textRun' => [ 'content' => "\x0b ✔ Articulate purpose\x0b ✔ Embed evidence\n", 'textStyle' => [] ] ],
					],
					'paragraphStyle' => [ 'namedStyleType' => 'NORMAL_TEXT' ],
				] ],
			] ],
			'inlineObjects' => [],
		];

		$mapper = new DocsMapper();
		$chapters = $mapper->toChapters( $doc );
		$body = $chapters[0]['body'];

		$this->assertStringContainsString( '<ul>', $body );
		$this->assertStringContainsString( '<li>Standardize definitions and clarify <em>what the credential represents</em></li>', $body );
		$this->assertStringContainsString( '<li>Articulate purpose</li>', $body );
		$this->assertStringContainsString( '<li>Embed evidence</li>', $body );
		$this->assertStringNotContainsString( '✔', $body );
		$this->assertStringNotContainsString( "\x0b", $body );
	}

	/**
	 * @group import
	 */
	public function test_dash_and_asterisk_require_trailing_space(): void {
		$doc = [
			'title' => 'Dashes',
			'body' => [ 'content' => [
				[ 'sectionBreak' => [ 'sectionStyle' => [] ] ],
				[ 'paragraph' => [ 'elements' => [ [ 'textRun' => [ 'content' => "Chapter\n", 'textStyle' => [] ] ] ], 'paragraphStyle' => [ 'namedStyleType' => 'HEADING_1' ] ] ],
				[ 'paragraph' => [ 'elements' => [ [ 'textRun' => [ 'content' => "- apples\x0b- oranges\n", 'textStyle' => [] ] ] ], 'paragraphStyle' => [ 'namedStyleType' => 'NORMAL_TEXT' ] ] ],
				[ 'paragraph' => [ 'elements' => [ [ 'textRun' => [ 'content' => "well-known fact\x0bself-evident truth\n", 'textStyle' => [] ] ] ], 'paragraphStyle' => [ 'namedStyleType' => 'NORMAL_TEXT' ] ] ],
			] ],
			'inlineObjects' => [],
		];

		$mapper = new DocsMapper();
		$chapters = $mapper->toChapters( $doc );
		$body = $chapters[0]['body'];

		$this->assertStringContainsString( '<li>apples</li>', $body );
		$this->assertStringContainsString( '<li>oranges</li>', $body );
		// No trailing space after the dash -> not a list, stays text with <br>.
		$this->assertStringContainsString( '<p>well-known fact<br>self-evident truth</p>', $body );
	}

	/**
	 * @group import
	 */
	public function test_single_glyph_line_is_not_a_list(): void {
		$doc = [
			'title' => 'Lone glyph',
			'body' => [ 'content' => [
				[ 'sectionBreak' => [ 'sectionStyle' => [] ] ],
				[ 'paragraph' => [ 'elements' => [ [ 'textRun' => [ 'content' => "Chapter\n", 'textStyle' => [] ] ] ], 'paragraphStyle' => [ 'namedStyleType' => 'HEADING_1' ] ] ],
				[ 'paragraph' => [ 'elements' => [ [ 'textRun' => [ 'content' => "Intro line\x0b✔ a single checked note\n", 'textStyle' => [] ] ] ], 'paragraphStyle' => [ 'namedStyleType' => 'NORMAL_TEXT' ] ] ],
			] ],
			'inlineObjects' => [],
		];

		$mapper = new DocsMapper();
		$chapters = $mapper->toChapters( $doc );
		$body = $chapters[0]['body'];

		$this->assertStringNotContainsString( '<ul>', $body );
		$this->assertStringContainsString( '<p>Intro line<br>✔ a single checked note</p>', $body );
	}
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `lando composer test -- --filter test_checkmark_glyph_lines_become_list`
Expected: FAIL — Task 3 produces `<p>✔ Standardize…<br>✔ Articulate purpose<br>✔ Embed evidence</p>` (no `<ul>`, glyphs retained).

- [ ] **Step 3: Add glyph detection and rewrite `renderMultilineParagraph`**

Add the glyph constant near the top of the class (after the `protected array $footnotes` declarations):

```php
	/** @var string[] Leading glyphs always treated as literal bullet markers. */
	protected const BULLET_GLYPHS = [ '●', '•', '○', '▪', '◦', '‣', '⁃', '✔', '✓', '✗', '➜', '➤' ];
```

Add the detection helper (near `renderMultilineParagraph`):

```php
	/**
	 * If the line begins with a literal bullet glyph, return the line with the
	 * glyph stripped; otherwise return null. '-', '–' and '*' count only when
	 * followed by a space.
	 */
	protected function stripBulletGlyph( string $line ): ?string {
		$trimmed = ltrim( $line );

		foreach ( self::BULLET_GLYPHS as $glyph ) {
			if ( mb_strpos( $trimmed, $glyph ) === 0 ) {
				return ltrim( mb_substr( $trimmed, mb_strlen( $glyph ) ) );
			}
		}

		foreach ( [ '-', '–', '*' ] as $glyph ) {
			if ( mb_strpos( $trimmed, $glyph ) === 0 && mb_substr( $trimmed, mb_strlen( $glyph ), 1 ) === ' ' ) {
				return ltrim( mb_substr( $trimmed, mb_strlen( $glyph ) ) );
			}
		}

		return null;
	}
```

Replace `renderMultilineParagraph()` (the Task 3 version) with:

```php
	/**
	 * Render a NORMAL_TEXT paragraph containing soft line breaks.
	 *
	 * A maximal run of >= 2 consecutive bullet-glyph lines becomes a <ul> (via
	 * list-item markers resolved in finalize()); other lines are joined with <br>
	 * inside a <p>.
	 */
	protected function renderMultilineParagraph( array $elements, array $inline_objects ): string {
		$lines = array_map( 'trim', $this->splitElementsIntoLines( $elements, $inline_objects ) );
		$lines = array_values( array_filter( $lines, static fn( $l ) => $l !== '' ) );
		if ( empty( $lines ) ) {
			return '';
		}

		$items = array_map( fn( $l ) => $this->stripBulletGlyph( $l ), $lines );

		$out = '';
		$buffer = [];
		$flush = function () use ( &$buffer, &$out ): void {
			if ( ! empty( $buffer ) ) {
				$out .= '<p>' . implode( '<br>', $buffer ) . "</p>\n";
				$buffer = [];
			}
		};

		$i = 0;
		$n = count( $lines );
		while ( $i < $n ) {
			if ( $items[ $i ] !== null ) {
				$j = $i;
				while ( $j < $n && $items[ $j ] !== null ) {
					$j++;
				}
				if ( $j - $i >= 2 ) {
					$flush();
					for ( $k = $i; $k < $j; $k++ ) {
						$out .= $this->makeListItem( $items[ $k ], 'glyph', 0, 'ul' );
					}
					$i = $j;
					continue;
				}
				// Lone bullet line: keep as text (glyph retained).
				$buffer[] = $lines[ $i ];
				$i++;
				continue;
			}
			$buffer[] = $lines[ $i ];
			$i++;
		}
		$flush();

		return $out;
	}
```

- [ ] **Step 4: Run the new tests to verify they pass**

Run: `lando composer test -- --filter test_checkmark_glyph_lines_become_list`
Then: `lando composer test -- --filter test_dash_and_asterisk_require_trailing_space`
Then: `lando composer test -- --filter test_single_glyph_line_is_not_a_list`
Expected: PASS.

- [ ] **Step 5: Verify the Task 3 `<br>` test still passes (non-glyph path)**

Run: `lando composer test -- --filter test_soft_break_paragraph_uses_line_breaks`
Then: `lando composer test -- --filter Modules_ImportGoogleDocsMapperTest`
Expected: PASS (full suite green).

- [ ] **Step 6: Commit**

```bash
git add inc/modules/import/googledocs/class-docsmapper.php tests/test-modules-import-google-docs-mapper.php
git commit -m "feat(google-docs): convert literal glyph pseudo-bullets to lists (#4506)"
```

---

### Task 5: Realistic fixtures + end-to-end mapper tests

Adds fixtures modeled on the real imported JSON so the whole `toChapters()` pipeline is exercised on production-shaped data (callout table + soft-break glyph list together).

**Files:**
- Create: `tests/fixtures/google-docs/callout-table-list.json`
- Create: `tests/fixtures/google-docs/soft-break-glyph-list.json`
- Test: `tests/test-modules-import-google-docs-mapper.php`

**Interfaces:**
- Consumes: `loadFixture()` (existing), `toChapters()`.

- [ ] **Step 1: Create the callout-table fixture**

Create `tests/fixtures/google-docs/callout-table-list.json`:

```json
{
  "title": "Callout Table",
  "body": {
    "content": [
      {"sectionBreak": {"sectionStyle": {}}},
      {"paragraph": {"elements": [{"textRun": {"content": "Chapter One\n", "textStyle": {}}}], "paragraphStyle": {"namedStyleType": "HEADING_1"}}},
      {"table": {"rows": 2, "columns": 1, "tableRows": [
        {"tableCells": [
          {"content": [{"paragraph": {"elements": [{"textRun": {"content": "KEY TERMS\n", "textStyle": {}}}], "paragraphStyle": {"namedStyleType": "HEADING_4"}}}]}
        ]},
        {"tableCells": [
          {"content": [
            {"paragraph": {"elements": [{"textRun": {"content": "Crime\n", "textStyle": {}}}], "paragraphStyle": {"namedStyleType": "NORMAL_TEXT"}, "bullet": {"listId": "kix.list.99", "nestingLevel": 0}}},
            {"paragraph": {"elements": [{"textRun": {"content": "Tort\n", "textStyle": {}}}], "paragraphStyle": {"namedStyleType": "NORMAL_TEXT"}, "bullet": {"listId": "kix.list.99", "nestingLevel": 0}}},
            {"paragraph": {"elements": [{"textRun": {"content": "Mens Rea\n", "textStyle": {}}}], "paragraphStyle": {"namedStyleType": "NORMAL_TEXT"}, "bullet": {"listId": "kix.list.99", "nestingLevel": 0}}}
          ]}
        ]}
      ]}}
    ]
  },
  "inlineObjects": {},
  "lists": {"kix.list.99": {"listProperties": {"nestingLevels": [{"glyphSymbol": "●"}]}}}
}
```

- [ ] **Step 2: Create the soft-break glyph fixture**

Create `tests/fixtures/google-docs/soft-break-glyph-list.json` (note `\u000b` = `\x0b`):

```json
{
  "title": "Soft Break Glyphs",
  "body": {
    "content": [
      {"sectionBreak": {"sectionStyle": {}}},
      {"paragraph": {"elements": [{"textRun": {"content": "Chapter One\n", "textStyle": {}}}], "paragraphStyle": {"namedStyleType": "HEADING_1"}}},
      {"paragraph": {"elements": [
        {"textRun": {"content": "✔ Standardize definitions\u000b ✔ Articulate purpose\u000b ✔ Embed evidence\u000b ✔ Provide materials\n", "textStyle": {}}}
      ], "paragraphStyle": {"namedStyleType": "NORMAL_TEXT"}}}
    ]
  },
  "inlineObjects": {}
}
```

- [ ] **Step 3: Write the fixture-driven tests**

Add to `tests/test-modules-import-google-docs-mapper.php`:

```php
	/**
	 * @group import
	 */
	public function test_callout_table_fixture_renders_heading_and_list(): void {
		$mapper = new DocsMapper();
		$chapters = $mapper->toChapters( $this->loadFixture( 'callout-table-list' ) );
		$body = $chapters[0]['body'];

		$this->assertStringContainsString( '<table>', $body );
		$this->assertStringContainsString( '<h4>KEY TERMS</h4>', $body );
		$this->assertStringContainsString( '<ul>', $body );
		$this->assertStringContainsString( '<li>Crime</li>', $body );
		$this->assertStringContainsString( '<li>Tort</li>', $body );
		$this->assertStringContainsString( '<li>Mens Rea</li>', $body );
		$this->assertStringNotContainsString( 'CrimeTort', $body );
	}

	/**
	 * @group import
	 */
	public function test_soft_break_glyph_fixture_renders_list(): void {
		$mapper = new DocsMapper();
		$chapters = $mapper->toChapters( $this->loadFixture( 'soft-break-glyph-list' ) );
		$body = $chapters[0]['body'];

		$this->assertStringContainsString( '<ul>', $body );
		$this->assertStringContainsString( '<li>Standardize definitions</li>', $body );
		$this->assertStringContainsString( '<li>Provide materials</li>', $body );
		$this->assertSame( 4, substr_count( $body, '<li>' ) );
		$this->assertStringNotContainsString( '✔', $body );
		$this->assertStringNotContainsString( "\x0b", $body );
	}
```

- [ ] **Step 4: Run the fixture tests**

Run: `lando composer test -- --filter test_callout_table_fixture_renders_heading_and_list`
Then: `lando composer test -- --filter test_soft_break_glyph_fixture_renders_list`
Expected: PASS.

- [ ] **Step 5: Run the full Google Docs suite**

Run: `lando composer test -- --filter Modules_ImportGoogleDocs`
Expected: PASS (mapper + importer + storage tests all green).

- [ ] **Step 6: Verify coding standards**

Run: `lando composer standards`
Expected: exit 0 (no violations in `inc/`).

- [ ] **Step 7: Commit**

```bash
git add tests/fixtures/google-docs/callout-table-list.json tests/fixtures/google-docs/soft-break-glyph-list.json tests/test-modules-import-google-docs-mapper.php
git commit -m "test(google-docs): fixtures for table-cell and glyph lists (#4506)"
```

---

## Self-Review

**Spec coverage:**
- Fix 1 (table-cell block rendering, simple-cell guard) → Task 2. ✔
- Fix 2 refactor (`applyTextStyle`) → Task 1. ✔
- Fix 2 line splitting + non-glyph `<br>` → Task 3. ✔
- Fix 2 glyph set, ≥2 threshold, `<ul>` via markers → Task 4. ✔
- Glyph in single-paragraph cell routed through block pipeline → Task 2 `needs_block` includes `paragraphHasSoftBreak`. ✔
- AC: numbered list in cell → `<ol>` → Task 2 ordered-list test. ✔
- AC: heading on its own line → Task 2 `<h3>`/`<h4>` assertions. ✔
- No regression on normal lists/tables → Task 2 Step 5 + Task 3/4 full-suite runs. ✔
- Realistic fixtures + end-to-end → Task 5. ✔
- Out of scope (positioned objects, standalone-paragraph glyphs, numbered pseudo-lists) → not implemented, by design. ✔

**Placeholder scan:** No TBD/TODO; every code step shows full code; every run step has an expected result.

**Type consistency:** `applyTextStyle(string,array):string`, `paragraphHasSoftBreak(array):bool`, `renderCellContent(array,array,array):string`, `splitElementsIntoLines(array,array):array`, `renderMultilineParagraph(array,array):string`, `stripBulletGlyph(string):?string`, and the marker `makeListItem($inner,'glyph',0,'ul')` are used consistently across Tasks 1–5. `BULLET_GLYPHS` constant introduced in Task 4 and used only there.

**Known, accepted limitation:** `finalize()` keys list grouping on list *type*, not *listId* (pre-existing). A real `ul` immediately adjacent (no intervening block) to a glyph `ul` would merge into one `<ul>`. Pre-existing semantics, not introduced here.
