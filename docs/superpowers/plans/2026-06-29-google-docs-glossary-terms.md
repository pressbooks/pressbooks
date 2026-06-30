# Google Docs `[GT]` Glossary Terms Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** During Google Docs import, turn inline `[GT]term[/GT]` markers into real Pressbooks glossary terms (definitions pulled from a dedicated H3 "Glossary" section) and replace each marker with the `[pb_glossary]` shortcode.

**Architecture:** A new pure-PHP `GlossaryParser` class detects markers, parses the Glossary section, strips it, and rewrites markers. `GoogleDocs::import()` runs it as a book-wide pre-pass (create/look-up `glossary` posts → build a term→id map) before its existing per-chapter save loop, then replaces markers in each saved chapter after `tidy()`. This mirrors the WXR importer's two-phase glossary handling.

**Tech Stack:** PHP 8.3, WordPress 6.8 multisite, PHPUnit (WP_UnitTestCase), `DOMDocument` for HTML parsing.

## Global Constraints

- PHP 8.3; follow existing Pressbooks code style (tabs, `vendor/bin/phpcs --standard=phpcs.ruleset.xml inc/`).
- `GlossaryParser` must NOT call WordPress functions — use plain PHP (`strip_tags`, `mb_*`, `DOMDocument`) so it is unit-testable in isolation.
- Glossary CPT post type is `glossary`; created terms use `post_status => 'publish'` so they surface in back matter.
- Inline shortcode format is exactly `[pb_glossary id="N"]term[/pb_glossary]`.
- Glossary definition markup is limited to `a, br, em, p, strong, sub, sup` (enforced on save by `sanitizeGlossaryTerm()` — `inc/shortcodes/glossary/class-glossary.php:323`); definitions are joined with `<br>`.
- Term matching/de-dupe is case-insensitive and whitespace-trimmed.
- Marker regex: `/\[GT\](.+?)\[\/GT\]/s`.
- Entry boundary heuristic ("colon + plausible key"): a line starts a new entry only if it contains a colon AND the text before the first colon is non-empty, ≤ 60 chars, ≤ 6 words, and does not end in `.`, `?`, or `!`.
- Run tests with: `vendor/bin/phpunit --configuration phpunit.xml --filter <Name>`.

## File Structure

- **Create** `inc/modules/import/googledocs/class-glossaryparser.php` — pure-PHP parser. Responsibilities: marker extraction, Glossary-section detection + entry parsing (boundary heuristic, multiline, de-dupe), section stripping, marker→shortcode replacement, key normalization.
- **Modify** `inc/modules/import/googledocs/class-googledocs.php` — wire the pre-pass and per-chapter replacement into `import()`; add `resolveGlossaryTerms()` and `isEffectivelyEmpty()` helpers.
- **Create** `tests/test-modules-import-google-docs-glossary-parser.php` — unit tests for `GlossaryParser` (no fixture needed; bodies built inline).
- **Modify** `tests/test-modules-import-google-docs-importer.php` — integration tests for end-to-end term creation + shortcode wiring.
- **Create** `tests/fixtures/google-docs/with-glossary-terms.json` — Google Docs API JSON fixture driving the integration test.

---

### Task 1: `GlossaryParser` skeleton, `normalizeKey()`, `extractMarkerTerms()`

**Files:**
- Create: `inc/modules/import/googledocs/class-glossaryparser.php`
- Test: `tests/test-modules-import-google-docs-glossary-parser.php`

**Interfaces:**
- Consumes: nothing (entry task).
- Produces:
  - `const MARKER_REGEX = '/\[GT\](.+?)\[\/GT\]/s';`
  - `public static function normalizeKey( string $term ): string` — `mb_strtolower( trim( strip_tags( $term ) ) )`.
  - `public function extractMarkerTerms( string $html ): array` — returns `[ normalizedKey => displayTerm ]` for each unique `[GT]` marker (first occurrence's display text wins).

- [ ] **Step 1: Write the failing test**

Create `tests/test-modules-import-google-docs-glossary-parser.php`:

```php
<?php

use Pressbooks\Modules\Import\GoogleDocs\GlossaryParser;

class Modules_ImportGoogleDocsGlossaryParserTest extends \WP_UnitTestCase {

	private function parser(): GlossaryParser {
		return new GlossaryParser();
	}

	/**
	 * @group import
	 */
	public function test_normalize_key_lowercases_trims_and_strips_tags(): void {
		$this->assertSame( 'operating system (os)', GlossaryParser::normalizeKey( '  Operating System (OS) ' ) );
		$this->assertSame( 'kernel', GlossaryParser::normalizeKey( '<strong>Kernel</strong>' ) );
	}

	/**
	 * @group import
	 */
	public function test_extract_marker_terms_dedupes_case_insensitively(): void {
		$html = 'An [GT]operating system (OS)[/GT] uses a [GT]Kernel[/GT]; the [GT]kernel[/GT] again.';
		$terms = $this->parser()->extractMarkerTerms( $html );

		$this->assertSame(
			[ 'operating system (os)', 'kernel' ],
			array_keys( $terms )
		);
		$this->assertSame( 'operating system (OS)', $terms['operating system (os)'] );
		$this->assertSame( 'Kernel', $terms['kernel'] );
	}

	/**
	 * @group import
	 */
	public function test_extract_marker_terms_empty_when_none(): void {
		$this->assertSame( [], $this->parser()->extractMarkerTerms( '<p>No markers here.</p>' ) );
	}
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit --configuration phpunit.xml --filter Modules_ImportGoogleDocsGlossaryParserTest`
Expected: FAIL — `Class "Pressbooks\Modules\Import\GoogleDocs\GlossaryParser" not found`.

- [ ] **Step 3: Write minimal implementation**

Create `inc/modules/import/googledocs/class-glossaryparser.php`:

```php
<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Modules\Import\GoogleDocs;

/**
 * Parses [GT]term[/GT] markers and a dedicated "Glossary" H3 section out of
 * converted Google Docs chapter HTML. Pure PHP: no WordPress calls.
 */
class GlossaryParser {

	const MARKER_REGEX = '/\[GT\](.+?)\[\/GT\]/s';

	/**
	 * Normalize a term for case-insensitive matching and de-dupe.
	 */
	public static function normalizeKey( string $term ): string {
		return mb_strtolower( trim( strip_tags( $term ) ) );
	}

	/**
	 * Extract unique [GT] marker terms from a chapter body.
	 *
	 * @return array<string,string> normalizedKey => display term (first wins)
	 */
	public function extractMarkerTerms( string $html ): array {
		$terms = [];
		if ( preg_match_all( self::MARKER_REGEX, $html, $matches ) ) {
			foreach ( $matches[1] as $inner ) {
				$key = self::normalizeKey( $inner );
				if ( '' !== $key && ! isset( $terms[ $key ] ) ) {
					$terms[ $key ] = trim( $inner );
				}
			}
		}
		return $terms;
	}
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit --configuration phpunit.xml --filter Modules_ImportGoogleDocsGlossaryParserTest`
Expected: PASS (3 tests).

- [ ] **Step 5: Commit**

```bash
git add inc/modules/import/googledocs/class-glossaryparser.php tests/test-modules-import-google-docs-glossary-parser.php
git commit -m "feat(google-docs): add GlossaryParser marker extraction (#4505)"
```

---

### Task 2: Parse the Glossary section into entries

**Files:**
- Modify: `inc/modules/import/googledocs/class-glossaryparser.php`
- Test: `tests/test-modules-import-google-docs-glossary-parser.php`

**Interfaces:**
- Consumes: `normalizeKey()` (Task 1).
- Produces:
  - `public function parseGlossaryEntries( array $bodies ): array` — input is a list of chapter HTML strings; returns `[ normalizedKey => [ 'title' => string, 'definition' => string ] ]` for every entry in the first "Glossary" H3 section found in each body. First occurrence of a key wins. Multiline definitions joined with `<br>`.
  - Private DOM helpers `loadDom()`, `findGlossarySection()`, `nodesToLines()`, `innerHtml()` and parsing helpers `entriesFromLines()`, `startsNewEntry()` (reused by Task 3).

- [ ] **Step 1: Write the failing tests**

Append to `tests/test-modules-import-google-docs-glossary-parser.php` (inside the class):

```php
	/**
	 * @group import
	 */
	public function test_parse_single_entry(): void {
		$bodies = [ '<h3>Glossary</h3><p>Kernel: The core of an operating system.</p>' ];
		$entries = $this->parser()->parseGlossaryEntries( $bodies );

		$this->assertArrayHasKey( 'kernel', $entries );
		$this->assertSame( 'Kernel', $entries['kernel']['title'] );
		$this->assertSame( 'The core of an operating system.', $entries['kernel']['definition'] );
	}

	/**
	 * @group import
	 */
	public function test_parse_multiline_definition_joined_with_br(): void {
		$bodies = [ '<h3>Glossary</h3><p>Kernel: The core of an OS.</p><p>It manages resources.</p>' ];
		$entries = $this->parser()->parseGlossaryEntries( $bodies );

		$this->assertSame( 'The core of an OS.<br>It manages resources.', $entries['kernel']['definition'] );
	}

	/**
	 * @group import
	 */
	public function test_continuation_line_with_colon_stays_continuation(): void {
		// "see also: x" is a long-ish continuation; treated as continuation, not a new entry,
		// because the previous entry is open and this line is not a plausible new key here.
		$bodies = [ '<h3>Glossary</h3><p>Kernel: The core of an OS, namely this: the scheduler.</p>' ];
		$entries = $this->parser()->parseGlossaryEntries( $bodies );

		$this->assertCount( 1, $entries );
		$this->assertSame( 'The core of an OS, namely this: the scheduler.', $entries['kernel']['definition'] );
	}

	/**
	 * @group import
	 */
	public function test_parse_multiple_entries_and_section_ends_at_next_heading(): void {
		$bodies = [
			'<h3>Glossary</h3>'
			. '<p>Operating system (OS): Manages hardware.</p>'
			. '<p>Daemon: A background process.</p>'
			. '<h2>Next Section</h2><p>Not a definition: really.</p>',
		];
		$entries = $this->parser()->parseGlossaryEntries( $bodies );

		$this->assertSame( [ 'operating system (os)', 'daemon' ], array_keys( $entries ) );
		$this->assertSame( 'Manages hardware.', $entries['operating system (os)']['definition'] );
		$this->assertArrayNotHasKey( 'not a definition', $entries );
	}

	/**
	 * @group import
	 */
	public function test_no_glossary_section_returns_empty(): void {
		$bodies = [ '<h2>Intro</h2><p>Some text with a colon: here.</p>' ];
		$this->assertSame( [], $this->parser()->parseGlossaryEntries( $bodies ) );
	}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `vendor/bin/phpunit --configuration phpunit.xml --filter Modules_ImportGoogleDocsGlossaryParserTest`
Expected: FAIL — `Call to undefined method ...::parseGlossaryEntries()`.

- [ ] **Step 3: Write the implementation**

Add these methods to `class GlossaryParser` (after `extractMarkerTerms()`):

```php
	/**
	 * Parse "Glossary" H3 sections out of a set of chapter bodies into entries.
	 *
	 * @param array<int,string> $bodies chapter HTML strings
	 * @return array<string, array{title:string, definition:string}>
	 */
	public function parseGlossaryEntries( array $bodies ): array {
		$entries = [];
		foreach ( $bodies as $html ) {
			if ( '' === $html || false === stripos( $html, 'glossary' ) ) {
				continue;
			}
			$dom = $this->loadDom( $html );
			if ( null === $dom ) {
				continue;
			}
			$wrap = $dom->getElementsByTagName( 'div' )->item( 0 );
			if ( null === $wrap ) {
				continue;
			}
			$section = $this->findGlossarySection( $wrap );
			if ( null === $section['heading'] ) {
				continue;
			}
			$lines = $this->nodesToLines( $section['nodes'] );
			foreach ( $this->entriesFromLines( $lines ) as $key => $entry ) {
				if ( ! isset( $entries[ $key ] ) ) {
					$entries[ $key ] = $entry;
				}
			}
		}
		return $entries;
	}

	/**
	 * Load an HTML fragment into a DOMDocument wrapped in a single <div>.
	 */
	protected function loadDom( string $html ): ?\DOMDocument {
		$dom = new \DOMDocument();
		libxml_use_internal_errors( true );
		$ok = $dom->loadHTML(
			'<?xml encoding="UTF-8"?><div>' . $html . '</div>',
			LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
		);
		libxml_clear_errors();
		return $ok ? $dom : null;
	}

	/**
	 * Find the first "Glossary" H3 and the nodes that follow it up to the next
	 * h1/h2/h3 (or end of the wrapper).
	 *
	 * @return array{heading: ?\DOMNode, nodes: array<int,\DOMNode>}
	 */
	protected function findGlossarySection( \DOMElement $wrap ): array {
		$heading = null;
		$nodes = [];
		$collecting = false;
		foreach ( iterator_to_array( $wrap->childNodes ) as $node ) {
			if ( XML_ELEMENT_NODE !== $node->nodeType ) {
				if ( $collecting ) {
					$nodes[] = $node;
				}
				continue;
			}
			$tag = strtolower( $node->nodeName );
			if ( ! $collecting ) {
				if ( 'h3' === $tag && 'glossary' === strtolower( trim( $node->textContent ) ) ) {
					$heading = $node;
					$collecting = true;
				}
				continue;
			}
			if ( in_array( $tag, [ 'h1', 'h2', 'h3' ], true ) ) {
				break;
			}
			$nodes[] = $node;
		}
		return [ 'heading' => $heading, 'nodes' => $nodes ];
	}

	/**
	 * Convert section nodes into plain-text lines (splitting <p> on <br>).
	 *
	 * @param array<int,\DOMNode> $nodes
	 * @return array<int,string>
	 */
	protected function nodesToLines( array $nodes ): array {
		$lines = [];
		foreach ( $nodes as $node ) {
			if ( XML_ELEMENT_NODE !== $node->nodeType ) {
				$text = trim( $node->textContent ?? '' );
				if ( '' !== $text ) {
					$lines[] = $text;
				}
				continue;
			}
			$inner = $this->innerHtml( $node );
			foreach ( preg_split( '/<br\s*\/?>/i', $inner ) as $part ) {
				$text = trim( html_entity_decode( strip_tags( $part ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
				if ( '' !== $text ) {
					$lines[] = $text;
				}
			}
		}
		return $lines;
	}

	/**
	 * Serialize a node's inner HTML.
	 */
	protected function innerHtml( \DOMNode $node ): string {
		$html = '';
		foreach ( $node->childNodes as $child ) {
			$html .= $node->ownerDocument->saveHTML( $child );
		}
		return $html;
	}

	/**
	 * Build entries from plain-text lines using the boundary heuristic.
	 *
	 * @param array<int,string> $lines
	 * @return array<string, array{title:string, definition:string}>
	 */
	protected function entriesFromLines( array $lines ): array {
		$entries = [];
		$current = null;
		foreach ( $lines as $line ) {
			if ( $this->startsNewEntry( $line ) ) {
				$pos = mb_strpos( $line, ':' );
				$title = trim( mb_substr( $line, 0, $pos ) );
				$definition = trim( mb_substr( $line, $pos + 1 ) );
				$key = self::normalizeKey( $title );
				if ( ! isset( $entries[ $key ] ) ) {
					$entries[ $key ] = [ 'title' => $title, 'definition' => $definition ];
				}
				$current = $key;
			} elseif ( null !== $current ) {
				$entries[ $current ]['definition'] = trim(
					$entries[ $current ]['definition'] . '<br>' . $line
				);
			}
		}
		return $entries;
	}

	/**
	 * Whether a line begins a new entry: colon + a plausible short key.
	 */
	protected function startsNewEntry( string $line ): bool {
		$pos = mb_strpos( $line, ':' );
		if ( false === $pos || 0 === $pos ) {
			return false;
		}
		$key = trim( mb_substr( $line, 0, $pos ) );
		if ( '' === $key || mb_strlen( $key ) > 60 ) {
			return false;
		}
		if ( str_word_count( $key ) > 6 ) {
			return false;
		}
		if ( preg_match( '/[.?!]$/', $key ) ) {
			return false;
		}
		return true;
	}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `vendor/bin/phpunit --configuration phpunit.xml --filter Modules_ImportGoogleDocsGlossaryParserTest`
Expected: PASS (8 tests total).

- [ ] **Step 5: Commit**

```bash
git add inc/modules/import/googledocs/class-glossaryparser.php tests/test-modules-import-google-docs-glossary-parser.php
git commit -m "feat(google-docs): parse Glossary section into entries (#4505)"
```

---

### Task 3: Strip the Glossary section from a body

**Files:**
- Modify: `inc/modules/import/googledocs/class-glossaryparser.php`
- Test: `tests/test-modules-import-google-docs-glossary-parser.php`

**Interfaces:**
- Consumes: `loadDom()`, `findGlossarySection()`, `innerHtml()` (Task 2).
- Produces: `public function stripGlossarySection( string $html ): string` — returns the body with the "Glossary" H3 heading and its following entry nodes removed (up to the next h1/h2/h3). Returns the input unchanged when no Glossary section is present.

- [ ] **Step 1: Write the failing tests**

Append to the test class:

```php
	/**
	 * @group import
	 */
	public function test_strip_removes_glossary_section_keeps_rest(): void {
		$html = '<p>Intro.</p><h3>Glossary</h3><p>Kernel: Core.</p><p>Daemon: Background.</p>';
		$out = $this->parser()->stripGlossarySection( $html );

		$this->assertStringContainsString( '<p>Intro.</p>', $out );
		$this->assertStringNotContainsString( 'Glossary', $out );
		$this->assertStringNotContainsString( 'Kernel: Core.', $out );
		$this->assertStringNotContainsString( 'Daemon: Background.', $out );
	}

	/**
	 * @group import
	 */
	public function test_strip_stops_at_next_heading(): void {
		$html = '<h3>Glossary</h3><p>Kernel: Core.</p><h2>After</h2><p>Kept.</p>';
		$out = $this->parser()->stripGlossarySection( $html );

		$this->assertStringNotContainsString( 'Kernel: Core.', $out );
		$this->assertStringContainsString( '<h2>After</h2>', $out );
		$this->assertStringContainsString( '<p>Kept.</p>', $out );
	}

	/**
	 * @group import
	 */
	public function test_strip_noop_without_section(): void {
		$html = '<p>No glossary here.</p>';
		$this->assertSame( $html, $this->parser()->stripGlossarySection( $html ) );
	}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `vendor/bin/phpunit --configuration phpunit.xml --filter Modules_ImportGoogleDocsGlossaryParserTest`
Expected: FAIL — `Call to undefined method ...::stripGlossarySection()`.

- [ ] **Step 3: Write the implementation**

Add to `class GlossaryParser`:

```php
	/**
	 * Remove the "Glossary" H3 section (heading + entries) from a chapter body.
	 */
	public function stripGlossarySection( string $html ): string {
		if ( '' === $html || false === stripos( $html, 'glossary' ) ) {
			return $html;
		}
		$dom = $this->loadDom( $html );
		if ( null === $dom ) {
			return $html;
		}
		$wrap = $dom->getElementsByTagName( 'div' )->item( 0 );
		if ( null === $wrap ) {
			return $html;
		}
		$section = $this->findGlossarySection( $wrap );
		if ( null === $section['heading'] ) {
			return $html;
		}
		foreach ( array_merge( [ $section['heading'] ], $section['nodes'] ) as $node ) {
			if ( $node->parentNode ) {
				$node->parentNode->removeChild( $node );
			}
		}
		return trim( $this->innerHtml( $wrap ) );
	}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `vendor/bin/phpunit --configuration phpunit.xml --filter Modules_ImportGoogleDocsGlossaryParserTest`
Expected: PASS (11 tests total).

- [ ] **Step 5: Commit**

```bash
git add inc/modules/import/googledocs/class-glossaryparser.php tests/test-modules-import-google-docs-glossary-parser.php
git commit -m "feat(google-docs): strip Glossary section from chapter body (#4505)"
```

---

### Task 4: Replace markers with the `[pb_glossary]` shortcode

**Files:**
- Modify: `inc/modules/import/googledocs/class-glossaryparser.php`
- Test: `tests/test-modules-import-google-docs-glossary-parser.php`

**Interfaces:**
- Consumes: `MARKER_REGEX`, `normalizeKey()` (Task 1).
- Produces: `public function replaceMarkers( string $html, array $idMap ): string` — replaces each `[GT]term[/GT]` with `[pb_glossary id="N"]term[/pb_glossary]` using `$idMap` (`normalizedKey => postId`). If a key is missing from the map, the marker is reduced to its plain inner text (never leaves a literal `[GT]`).

- [ ] **Step 1: Write the failing tests**

Append to the test class:

```php
	/**
	 * @group import
	 */
	public function test_replace_markers_wraps_with_shortcode_and_preserves_display(): void {
		$idMap = [ 'kernel' => 42, 'operating system (os)' => 7 ];
		$html = 'Use the [GT]Kernel[/GT] in the [GT]operating system (OS)[/GT].';
		$out = $this->parser()->replaceMarkers( $html, $idMap );

		$this->assertSame(
			'Use the [pb_glossary id="42"]Kernel[/pb_glossary] in the [pb_glossary id="7"]operating system (OS)[/pb_glossary].',
			$out
		);
		$this->assertStringNotContainsString( '[GT]', $out );
		$this->assertStringNotContainsString( '[/GT]', $out );
	}

	/**
	 * @group import
	 */
	public function test_replace_markers_unmapped_key_falls_back_to_plain_text(): void {
		$out = $this->parser()->replaceMarkers( 'A [GT]ghost[/GT] term.', [] );

		$this->assertSame( 'A ghost term.', $out );
		$this->assertStringNotContainsString( '[GT]', $out );
	}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `vendor/bin/phpunit --configuration phpunit.xml --filter Modules_ImportGoogleDocsGlossaryParserTest`
Expected: FAIL — `Call to undefined method ...::replaceMarkers()`.

- [ ] **Step 3: Write the implementation**

Add to `class GlossaryParser`:

```php
	/**
	 * Replace [GT]term[/GT] markers with [pb_glossary id="N"]term[/pb_glossary].
	 *
	 * @param array<string,int> $idMap normalizedKey => glossary post ID
	 */
	public function replaceMarkers( string $html, array $idMap ): string {
		return preg_replace_callback(
			self::MARKER_REGEX,
			function ( array $match ) use ( $idMap ): string {
				$inner = $match[1];
				$key = self::normalizeKey( $inner );
				if ( isset( $idMap[ $key ] ) ) {
					return '[pb_glossary id="' . (int) $idMap[ $key ] . '"]' . $inner . '[/pb_glossary]';
				}
				return $inner;
			},
			$html
		);
	}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `vendor/bin/phpunit --configuration phpunit.xml --filter Modules_ImportGoogleDocsGlossaryParserTest`
Expected: PASS (13 tests total).

- [ ] **Step 5: Run code standards on the new class**

Run: `vendor/bin/phpcs --standard=phpcs.ruleset.xml inc/modules/import/googledocs/class-glossaryparser.php`
Expected: no errors (fix with `vendor/bin/phpcbf` if any).

- [ ] **Step 6: Commit**

```bash
git add inc/modules/import/googledocs/class-glossaryparser.php tests/test-modules-import-google-docs-glossary-parser.php
git commit -m "feat(google-docs): replace [GT] markers with pb_glossary shortcode (#4505)"
```

---

### Task 5: Wire the pre-pass into `GoogleDocs::import()` + integration test

**Files:**
- Modify: `inc/modules/import/googledocs/class-googledocs.php`
- Create: `tests/fixtures/google-docs/with-glossary-terms.json`
- Modify: `tests/test-modules-import-google-docs-importer.php`

**Interfaces:**
- Consumes: `GlossaryParser::extractMarkerTerms()`, `parseGlossaryEntries()`, `stripGlossarySection()`, `replaceMarkers()`, `normalizeKey()` (Tasks 1-4); `\Pressbooks\Shortcodes\Glossary\Glossary::init()->getGlossaryTerms()` (returns array keyed by `post_title`, values include `id`).
- Produces: glossary `glossary` posts + `[pb_glossary]` shortcodes wired into saved chapter content. No new public API.

- [ ] **Step 1: Create the integration fixture**

Create `tests/fixtures/google-docs/with-glossary-terms.json`:

```json
{
  "title": "Glossary Terms Test",
  "body": {
    "content": [
      {"sectionBreak": {"sectionStyle": {}}},
      {"paragraph": {"elements": [{"textRun": {"content": "Chapter One\n", "textStyle": {}}}], "paragraphStyle": {"namedStyleType": "HEADING_1"}}},
      {"paragraph": {"elements": [{"textRun": {"content": "An [GT]operating system (OS)[/GT] manages hardware via its [GT]kernel[/GT]. Data lives in [GT]RAM[/GT].\n", "textStyle": {}}}], "paragraphStyle": {"namedStyleType": "NORMAL_TEXT"}}},
      {"paragraph": {"elements": [{"textRun": {"content": "Glossary\n", "textStyle": {}}}], "paragraphStyle": {"namedStyleType": "HEADING_3"}}},
      {"paragraph": {"elements": [{"textRun": {"content": "operating system (OS): Software that manages computer hardware and software resources.\n", "textStyle": {}}}], "paragraphStyle": {"namedStyleType": "NORMAL_TEXT"}}},
      {"paragraph": {"elements": [{"textRun": {"content": "kernel: The core of an operating system.\n", "textStyle": {}}}], "paragraphStyle": {"namedStyleType": "NORMAL_TEXT"}}},
      {"paragraph": {"elements": [{"textRun": {"content": "It manages system resources.\n", "textStyle": {}}}], "paragraphStyle": {"namedStyleType": "NORMAL_TEXT"}}},
      {"paragraph": {"elements": [{"textRun": {"content": "daemon: A background process.\n", "textStyle": {}}}], "paragraphStyle": {"namedStyleType": "NORMAL_TEXT"}}}
    ]
  },
  "footnotes": {},
  "inlineObjects": {}
}
```

Note: this fixture is one HEADING_1 chapter whose body holds the inline markers, then the Glossary H3 section. `operating system (OS)` and `kernel` are referenced and defined; `RAM` is referenced but has no entry (→ empty term); `daemon` is defined but never referenced (→ still created); `kernel` has a multiline definition.

- [ ] **Step 2: Write the failing integration test**

Append to `tests/test-modules-import-google-docs-importer.php` (inside the class). Add `use Pressbooks\Modules\Import\GoogleDocs\GlossaryParser;` near the top `use` statements first.

```php
	/**
	 * @group import
	 */
	public function test_import_creates_and_links_glossary_terms(): void {
		$this->_book();

		$tmp = tempnam( sys_get_temp_dir(), 'pb_test_' );
		copy( __DIR__ . '/fixtures/google-docs/with-glossary-terms.json', $tmp );

		$importer = new GoogleDocs();
		$importer->setCurrentImportOption( [ 'file' => $tmp ] );
		$current_import = get_option( 'pressbooks_current_import' );

		$_POST['chapters'] = [
			0 => [ 'import' => '1', 'type' => 'chapter' ],
		];

		$this->assertTrue( $importer->import( $current_import ) );

		// Four glossary terms created: operating system (OS), kernel, daemon, RAM.
		$terms = get_posts( [
			'post_type'   => 'glossary',
			'post_status' => 'any',
			'numberposts' => -1,
		] );
		$by_title = [];
		foreach ( $terms as $t ) {
			$by_title[ strtolower( $t->post_title ) ] = $t;
		}

		$this->assertArrayHasKey( 'operating system (os)', $by_title );
		$this->assertArrayHasKey( 'kernel', $by_title );
		$this->assertArrayHasKey( 'daemon', $by_title );
		$this->assertArrayHasKey( 'ram', $by_title );

		// Definitions.
		$this->assertStringContainsString(
			'manages computer hardware',
			$by_title['operating system (os)']->post_content
		);
		// Multiline definition joined.
		$this->assertStringContainsString( 'The core of an operating system.', $by_title['kernel']->post_content );
		$this->assertStringContainsString( 'It manages system resources.', $by_title['kernel']->post_content );
		// Referenced-but-undefined term has empty definition.
		$this->assertSame( '', trim( $by_title['ram']->post_content ) );
		// Terms are published so they surface in back matter.
		$this->assertSame( 'publish', $by_title['kernel']->post_status );

		// Chapter content: markers replaced, glossary section stripped.
		$chapter = get_posts( [
			'post_type'   => 'chapter',
			'post_status' => 'any',
			'numberposts' => -1,
		] )[0];

		$this->assertStringNotContainsString( '[GT]', $chapter->post_content );
		$this->assertStringNotContainsString( '[/GT]', $chapter->post_content );
		$this->assertStringNotContainsString( 'daemon: A background process', $chapter->post_content );
		$this->assertStringNotContainsString( '>Glossary<', $chapter->post_content );

		// Shortcodes reference the created term IDs.
		$os_id = $by_title['operating system (os)']->ID;
		$this->assertStringContainsString(
			'[pb_glossary id="' . $os_id . '"]operating system (OS)[/pb_glossary]',
			$chapter->post_content
		);

		@unlink( $tmp );
	}
```

- [ ] **Step 3: Run test to verify it fails**

Run: `vendor/bin/phpunit --configuration phpunit.xml --filter test_import_creates_and_links_glossary_terms`
Expected: FAIL — markers not replaced / no glossary posts created (the wiring does not exist yet).

- [ ] **Step 4: Add the helper methods to `GoogleDocs`**

In `inc/modules/import/googledocs/class-googledocs.php`, add these two `protected` methods (e.g. after `processImages()`):

```php
	/**
	 * Create or reuse glossary posts for resolved terms; returns normalizedKey => post ID.
	 *
	 * @param array<string, array{title:string, definition:string}> $entries
	 * @param array<string, string> $marker_terms normalizedKey => display term
	 * @return array<string, int>
	 */
	protected function resolveGlossaryTerms( array $entries, array $marker_terms ): array {
		$glossary = \Pressbooks\Shortcodes\Glossary\Glossary::init();
		$existing_by_key = [];
		foreach ( $glossary->getGlossaryTerms() as $title => $data ) {
			$existing_by_key[ GlossaryParser::normalizeKey( $title ) ] = (int) $data['id'];
		}

		// Union: every glossary entry, plus marker terms with no entry (empty definition).
		$to_resolve = $entries;
		foreach ( $marker_terms as $key => $display ) {
			if ( ! isset( $to_resolve[ $key ] ) ) {
				$to_resolve[ $key ] = [ 'title' => $display, 'definition' => '' ];
			}
		}

		$id_map = [];
		foreach ( $to_resolve as $key => $term ) {
			if ( isset( $existing_by_key[ $key ] ) ) {
				$id_map[ $key ] = $existing_by_key[ $key ];
				continue;
			}
			$pid = wp_insert_post( add_magic_quotes( [
				'post_title'   => $term['title'],
				'post_content' => $term['definition'],
				'post_type'    => 'glossary',
				'post_status'  => 'publish',
			] ) );
			if ( $pid && ! is_wp_error( $pid ) ) {
				$id_map[ $key ] = (int) $pid;
			}
		}

		return $id_map;
	}

	/**
	 * Whether a chapter body has no visible content after stripping/replacement.
	 */
	protected function isEffectivelyEmpty( string $html ): bool {
		if ( '' !== trim( wp_strip_all_tags( $html ) ) ) {
			return false;
		}
		return ! preg_match( '/<(img|iframe|audio|video|embed|object|table|hr)\b/i', $html );
	}
```

- [ ] **Step 5: Wire the pre-pass and loop replacement**

In `inc/modules/import/googledocs/class-googledocs.php`, inside `import()`, replace the block from the `toChapters()` call through the start of the loop. Change:

```php
		$chapters_data = $this->mapper->toChapters( $json );
		$chapter_parent = $this->getChapterParent();

		foreach ( $current_import['chapters'] as $id => $chapter_title ) {
```

to:

```php
		$chapters_data = $this->mapper->toChapters( $json );
		$chapter_parent = $this->getChapterParent();

		// --- Glossary pre-pass: resolve [GT] terms book-wide before saving. ---
		$glossary_parser = new GlossaryParser();

		// Which chapters will actually be saved?
		$saved_ids = [];
		foreach ( $current_import['chapters'] as $id => $chapter_title ) {
			if ( $this->flaggedForImport( $id ) && isset( $chapters_data[ $id ] ) ) {
				$saved_ids[] = $id;
			}
		}

		// Glossary definitions can live in any chapter; scan them all.
		$all_bodies = [];
		foreach ( $chapters_data as $ch ) {
			$all_bodies[] = $ch['body'] ?? '';
		}
		$glossary_entries = $glossary_parser->parseGlossaryEntries( $all_bodies );

		// [GT] markers only matter in chapters being saved.
		$marker_terms = [];
		foreach ( $saved_ids as $id ) {
			$marker_terms += $glossary_parser->extractMarkerTerms( $chapters_data[ $id ]['body'] ?? '' );
		}

		$glossary_id_map = $this->resolveGlossaryTerms( $glossary_entries, $marker_terms );

		// Strip the Glossary section from every chapter body.
		foreach ( $chapters_data as $id => $ch ) {
			$chapters_data[ $id ]['body'] = $glossary_parser->stripGlossarySection( $ch['body'] ?? '' );
		}
		// --- end glossary pre-pass ---

		foreach ( $current_import['chapters'] as $id => $chapter_title ) {
```

Then, inside the loop, change:

```php
			$html = $this->processImages( $html, $ch['images'] ?? [] );
			$html = $this->tidy( $html );

			$post_type = $this->determinePostType( $id );
```

to:

```php
			$html = $this->processImages( $html, $ch['images'] ?? [] );
			$html = $this->tidy( $html );
			$html = $glossary_parser->replaceMarkers( $html, $glossary_id_map );

			// A chapter that held only the (now stripped) Glossary section is consumed, not imported.
			if ( $this->isEffectivelyEmpty( $html ) ) {
				continue;
			}

			$post_type = $this->determinePostType( $id );
```

Finally, add the import at the top of the file alongside the existing `use` statements:

```php
use Pressbooks\Book;
use Pressbooks\Modules\Import\Import;
use Pressbooks\Modules\Import\GoogleDocs\GlossaryParser;
```

(`GlossaryParser` is in the same namespace, so the `use` is optional but added for explicitness and consistency.)

- [ ] **Step 6: Run the integration test to verify it passes**

Run: `vendor/bin/phpunit --configuration phpunit.xml --filter test_import_creates_and_links_glossary_terms`
Expected: PASS.

- [ ] **Step 7: Run the full Google Docs import suite for regressions**

Run: `vendor/bin/phpunit --configuration phpunit.xml --filter Modules_ImportGoogleDocs`
Expected: PASS (parser unit tests + importer tests, including the existing `test_import_creates_chapters`).

- [ ] **Step 8: Run code standards**

Run: `vendor/bin/phpcs --standard=phpcs.ruleset.xml inc/modules/import/googledocs/class-googledocs.php inc/modules/import/googledocs/class-glossaryparser.php`
Expected: no errors (fix with `vendor/bin/phpcbf` if any).

- [ ] **Step 9: Commit**

```bash
git add inc/modules/import/googledocs/class-googledocs.php tests/fixtures/google-docs/with-glossary-terms.json tests/test-modules-import-google-docs-importer.php
git commit -m "feat(google-docs): create and link glossary terms on import (#4505)"
```

---

## Self-Review

**Spec coverage** (every acceptance criterion → task):
- `[GT]` recognized → Task 1 (`extractMarkerTerms`), Task 5 (wiring).
- Glossary H3 parsed incl. multiline → Task 2.
- One entry per unique term, case-insensitive de-dupe → Task 1/2 `normalizeKey` + first-wins; Task 5 `resolveGlossaryTerms` reuses existing.
- Every occurrence replaced with shortcode → Task 4 + Task 5 loop.
- Definition from matching entry → Task 2 + Task 5.
- `[GT]` with no entry → empty linked term → Task 5 (`marker_terms` union with empty definition); fixture covers `RAM`.
- Glossary section consumed, not imported → Task 3 strip + Task 5 skip-empty.
- Terms surface in back matter / management → `post_type=glossary` + `publish` (Task 5); asserted in integration test.
- No literal `[GT]`/`[/GT]` remain → Task 4 fallback; asserted in integration test.
- Multi-word & parenthesized terms → Task 1/2 tests + fixture `operating system (OS)`.
- Glossary entry never referenced still created → Task 5 union starts from all entries; fixture `daemon`; asserted.

**Placeholder scan:** none — every code step contains complete code.

**Type consistency:** `normalizeKey` (static), `extractMarkerTerms`/`parseGlossaryEntries`/`stripGlossarySection`/`replaceMarkers` signatures match between their defining task and Task 5's consumption. Entry shape `['title'=>, 'definition'=>]` and id map `normalizedKey=>int` are consistent across Tasks 2, 4, 5.
