# Google Docs Positioned (Floating) Image Import — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Import positioned (floating) Google Docs images that are currently dropped, rendering them as `<img>` queued for download via the existing placeholder mechanism.

**Architecture:** Mapper-only change in `inc/modules/import/googledocs/class-docsmapper.php`. `toChapters()` reads `positionedObjects` and, for each body paragraph carrying `positionedObjectIds`, emits an `<img>` (and queues download metadata) before the paragraph's normal render. The importer (`GoogleDocs::processImages()`) already turns `#gdoc-image-{id}` + `images[]` entries into downloaded images, so no importer change is needed.

**Tech Stack:** PHP 8.3, WordPress test framework (`WP_UnitTestCase`), PHPUnit.

## Global Constraints

- **Test runner:** `lando composer test -- --filter <name>` (host `vendor/bin/phpunit` cannot reach the DB).
- **Touched file:** `inc/modules/import/googledocs/class-docsmapper.php` only (plus `tests/`).
- **Image markup:** bare `<img src="#gdoc-image-{object_id}" alt="{escaped}" />` emitted BEFORE the anchor paragraph's HTML. No `<figure>/<figcaption>`.
- **`images[]` entry shape (must match `collectImageMeta`):** `['object_id'=>id, 'content_uri'=>uri, 'alt'=>alt, 'title'=>title]`.
- **Alt text:** `description ?: title` (empty description falls back to title), then `''`.
- **Drawings:** positioned drawings (`embeddedDrawingProperties`) are skipped with a warning; images without `contentUri` are skipped silently.
- **Escaping:** `htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' )` for both `src` and `alt`, matching `renderInlineObject`.
- **Out of scope:** positioned objects anchored inside table cells; `<figure>` semantics; float/wrap layout.
- snake_case variables; canonical standards gate is `lando composer standards` (lints inc/ bin/).

---

### Task 1: Render positioned images at their anchor paragraph

**Files:**
- Modify: `inc/modules/import/googledocs/class-docsmapper.php` — `toChapters()` (read `positionedObjects` near `:28-30`; emit between the `collectImageMeta` call `:69` and the `renderParagraph` call `:71`); add new method `renderPositionedImages()`.
- Test: `tests/test-modules-import-google-docs-mapper.php`

**Interfaces:**
- Consumes: `$doc['positionedObjects']`; existing `$current_images` accumulator; `htmlspecialchars`.
- Produces: `renderPositionedImages( array $ids, array $positioned_objects, array &$images ): string` — returns concatenated `<img>` HTML for positioned images among `$ids`, appends each image's metadata to `$images`, skips drawings (with a warning) and images without `contentUri`.

- [ ] **Step 1: Write the failing tests**

Add to `tests/test-modules-import-google-docs-mapper.php` (before the closing `}`):

```php
	/**
	 * @group import
	 */
	public function test_positioned_image_rendered_before_caption(): void {
		$doc = [
			'title' => 'Figures',
			'body' => [ 'content' => [
				[ 'sectionBreak' => [ 'sectionStyle' => [] ] ],
				[ 'paragraph' => [ 'elements' => [ [ 'textRun' => [ 'content' => "Chapter\n", 'textStyle' => [] ] ] ], 'paragraphStyle' => [ 'namedStyleType' => 'HEADING_1' ] ] ],
				[ 'paragraph' => [
					'elements' => [ [ 'textRun' => [ 'content' => "Figure 1.1: The United States Supreme Court.\n", 'textStyle' => [] ] ] ],
					'paragraphStyle' => [ 'namedStyleType' => 'NORMAL_TEXT' ],
					'positionedObjectIds' => [ 'kix.img99' ],
				] ],
			] ],
			'inlineObjects' => [],
			'positionedObjects' => [ 'kix.img99' => [ 'positionedObjectProperties' => [ 'embeddedObject' => [
				'title' => 'Figure 1.1: The United States Supreme Court.',
				'description' => 'Figure 1.1 The United States Supreme Court building.',
				'imageProperties' => [ 'contentUri' => 'https://example.com/supreme.png' ],
			] ] ] ],
		];

		$mapper = new DocsMapper();
		$chapters = $mapper->toChapters( $doc );
		$body = $chapters[0]['body'];

		$this->assertStringContainsString( '<img src="#gdoc-image-kix.img99" alt="Figure 1.1 The United States Supreme Court building." />', $body );
		$this->assertLessThan( strpos( $body, '<p>Figure 1.1' ), strpos( $body, '<img' ) );

		$this->assertCount( 1, $chapters[0]['images'] );
		$this->assertSame( 'kix.img99', $chapters[0]['images'][0]['object_id'] );
		$this->assertSame( 'https://example.com/supreme.png', $chapters[0]['images'][0]['content_uri'] );
		$this->assertSame( 'Figure 1.1 The United States Supreme Court building.', $chapters[0]['images'][0]['alt'] );
	}

	/**
	 * @group import
	 */
	public function test_positioned_drawing_skipped_with_warning(): void {
		$doc = [
			'title' => 'Drawings',
			'body' => [ 'content' => [
				[ 'sectionBreak' => [ 'sectionStyle' => [] ] ],
				[ 'paragraph' => [ 'elements' => [ [ 'textRun' => [ 'content' => "Chapter\n", 'textStyle' => [] ] ] ], 'paragraphStyle' => [ 'namedStyleType' => 'HEADING_1' ] ] ],
				[ 'paragraph' => [
					'elements' => [ [ 'textRun' => [ 'content' => "Body text.\n", 'textStyle' => [] ] ] ],
					'paragraphStyle' => [ 'namedStyleType' => 'NORMAL_TEXT' ],
					'positionedObjectIds' => [ 'draw1' ],
				] ],
			] ],
			'inlineObjects' => [],
			'positionedObjects' => [ 'draw1' => [ 'positionedObjectProperties' => [ 'embeddedObject' => [
				'embeddedDrawingProperties' => [],
			] ] ] ],
		];

		$mapper = new DocsMapper();
		$chapters = $mapper->toChapters( $doc );

		$this->assertStringNotContainsString( '<img', $chapters[0]['body'] );
		$this->assertSame( [], $chapters[0]['images'] );
		$this->assertContains( 'Positioned drawing skipped (unsupported): draw1', $mapper->getWarnings() );
	}

	/**
	 * @group import
	 */
	public function test_positioned_image_alt_falls_back_to_title(): void {
		$doc = [
			'title' => 'Figures',
			'body' => [ 'content' => [
				[ 'sectionBreak' => [ 'sectionStyle' => [] ] ],
				[ 'paragraph' => [ 'elements' => [ [ 'textRun' => [ 'content' => "Chapter\n", 'textStyle' => [] ] ] ], 'paragraphStyle' => [ 'namedStyleType' => 'HEADING_1' ] ] ],
				[ 'paragraph' => [
					'elements' => [ [ 'textRun' => [ 'content' => "Caption.\n", 'textStyle' => [] ] ] ],
					'paragraphStyle' => [ 'namedStyleType' => 'NORMAL_TEXT' ],
					'positionedObjectIds' => [ 'kix.img7' ],
				] ],
			] ],
			'inlineObjects' => [],
			'positionedObjects' => [ 'kix.img7' => [ 'positionedObjectProperties' => [ 'embeddedObject' => [
				'title' => 'My Title',
				'description' => '',
				'imageProperties' => [ 'contentUri' => 'https://example.com/x.png' ],
			] ] ] ],
		];

		$mapper = new DocsMapper();
		$chapters = $mapper->toChapters( $doc );

		$this->assertStringContainsString( 'alt="My Title"', $chapters[0]['body'] );
	}

	/**
	 * @group import
	 */
	public function test_paragraph_without_positioned_ids_unchanged(): void {
		$doc = [
			'title' => 'Plain',
			'body' => [ 'content' => [
				[ 'sectionBreak' => [ 'sectionStyle' => [] ] ],
				[ 'paragraph' => [ 'elements' => [ [ 'textRun' => [ 'content' => "Chapter\n", 'textStyle' => [] ] ] ], 'paragraphStyle' => [ 'namedStyleType' => 'HEADING_1' ] ] ],
				[ 'paragraph' => [ 'elements' => [ [ 'textRun' => [ 'content' => "Just text.\n", 'textStyle' => [] ] ] ], 'paragraphStyle' => [ 'namedStyleType' => 'NORMAL_TEXT' ] ] ],
			] ],
			'inlineObjects' => [],
		];

		$mapper = new DocsMapper();
		$chapters = $mapper->toChapters( $doc );

		$this->assertStringNotContainsString( '<img', $chapters[0]['body'] );
		$this->assertStringContainsString( '<p>Just text.</p>', $chapters[0]['body'] );
		$this->assertSame( [], $chapters[0]['images'] );
	}
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `lando composer test -- --filter test_positioned_image_rendered_before_caption`
Expected: FAIL — no `<img>` in body, `images` empty (positioned objects are not read yet).

- [ ] **Step 3: Read `positionedObjects` in `toChapters()`**

In `toChapters()`, after the existing `$lists = $doc['lists'] ?? [];` line (around `:30`), add:

```php
		$positioned_objects = $doc['positionedObjects'] ?? [];
```

- [ ] **Step 4: Emit positioned images before the paragraph render**

In the `paragraph` branch of `toChapters()`, between the existing
`$this->collectImageMeta( $para['elements'] ?? [], $inline_objects, $current_images );` line (`:69`)
and the `$current_body .= $this->renderParagraph( ... );` line (`:71`), insert:

```php
				$current_body .= $this->renderPositionedImages( $para['positionedObjectIds'] ?? [], $positioned_objects, $current_images );
```

- [ ] **Step 5: Add the `renderPositionedImages()` method**

Add this method (e.g. directly after `renderInlineObject()`):

```php
	/**
	 * Render positioned (floating) images anchored to a paragraph, and queue
	 * their download metadata. Positioned drawings are skipped with a warning.
	 */
	protected function renderPositionedImages( array $ids, array $positioned_objects, array &$images ): string {
		$html = '';
		foreach ( $ids as $id ) {
			$obj = $positioned_objects[ $id ]['positionedObjectProperties']['embeddedObject'] ?? [];

			if ( isset( $obj['embeddedDrawingProperties'] ) ) {
				$this->warnings[] = "Positioned drawing skipped (unsupported): {$id}";
				continue;
			}

			if ( ! isset( $obj['imageProperties']['contentUri'] ) ) {
				continue;
			}

			$alt = ( $obj['description'] ?? '' ) ?: ( $obj['title'] ?? '' );

			$images[] = [
				'object_id'   => $id,
				'content_uri' => $obj['imageProperties']['contentUri'],
				'alt'         => $alt,
				'title'       => $obj['title'] ?? '',
			];

			$src = '#gdoc-image-' . $id;
			$html .= '<img src="' . htmlspecialchars( $src, ENT_QUOTES, 'UTF-8' ) . '" alt="' . htmlspecialchars( $alt, ENT_QUOTES, 'UTF-8' ) . '" />';
		}
		return $html;
	}
```

- [ ] **Step 6: Run the new tests to verify they pass**

Run each:
`lando composer test -- --filter test_positioned_image_rendered_before_caption`
`lando composer test -- --filter test_positioned_drawing_skipped_with_warning`
`lando composer test -- --filter test_positioned_image_alt_falls_back_to_title`
`lando composer test -- --filter test_paragraph_without_positioned_ids_unchanged`
Expected: PASS.

- [ ] **Step 7: Run the full mapper suite (no regression)**

Run: `lando composer test -- --filter Modules_ImportGoogleDocsMapperTest`
Expected: PASS (all existing tests + the 4 new ones).

- [ ] **Step 8: Commit**

```bash
git add inc/modules/import/googledocs/class-docsmapper.php tests/test-modules-import-google-docs-mapper.php
git commit -m "feat(google-docs): import positioned (floating) images (#positioned-images)"
```

---

### Task 2: Realistic fixture + end-to-end test

**Files:**
- Create: `tests/fixtures/google-docs/positioned-image.json`
- Test: `tests/test-modules-import-google-docs-mapper.php`

**Interfaces:**
- Consumes: `loadFixture()` (existing), `toChapters()`.

- [ ] **Step 1: Create the fixture**

Create `tests/fixtures/google-docs/positioned-image.json` (models the real shape: a caption paragraph anchoring one positioned image):

```json
{
  "title": "Positioned Image",
  "body": {
    "content": [
      {"sectionBreak": {"sectionStyle": {}}},
      {"paragraph": {"elements": [{"textRun": {"content": "Chapter One\n", "textStyle": {}}}], "paragraphStyle": {"namedStyleType": "HEADING_1"}}},
      {"paragraph": {"elements": [{"textRun": {"content": "Body paragraph before the figure.\n", "textStyle": {}}}], "paragraphStyle": {"namedStyleType": "NORMAL_TEXT"}}},
      {"paragraph": {
        "elements": [{"textRun": {"content": "Figure 1.1: The United States Supreme Court.\n", "textStyle": {}}}],
        "paragraphStyle": {"namedStyleType": "NORMAL_TEXT"},
        "positionedObjectIds": ["kix.he6ar3rdivr8"]
      }}
    ]
  },
  "inlineObjects": {},
  "positionedObjects": {
    "kix.he6ar3rdivr8": {
      "positionedObjectProperties": {
        "embeddedObject": {
          "title": "Figure 1.1: The United States Supreme Court.",
          "description": "Figure 1.1 The United States Supreme Court building.",
          "imageProperties": {"contentUri": "https://lh7-rt.googleusercontent.com/example-supreme-court.png"}
        }
      }
    }
  }
}
```

- [ ] **Step 2: Write the fixture-driven test**

Add to `tests/test-modules-import-google-docs-mapper.php`:

```php
	/**
	 * @group import
	 */
	public function test_positioned_image_fixture_end_to_end(): void {
		$mapper = new DocsMapper();
		$chapters = $mapper->toChapters( $this->loadFixture( 'positioned-image' ) );
		$body = $chapters[0]['body'];

		$this->assertStringContainsString( '<img src="#gdoc-image-kix.he6ar3rdivr8"', $body );
		$this->assertStringContainsString( 'alt="Figure 1.1 The United States Supreme Court building."', $body );
		// Image emitted before its caption paragraph.
		$this->assertLessThan( strpos( $body, '<p>Figure 1.1' ), strpos( $body, '<img' ) );
		// Download metadata queued for the importer.
		$this->assertCount( 1, $chapters[0]['images'] );
		$this->assertSame( 'https://lh7-rt.googleusercontent.com/example-supreme-court.png', $chapters[0]['images'][0]['content_uri'] );
	}
```

- [ ] **Step 3: Run the fixture test**

Run: `lando composer test -- --filter test_positioned_image_fixture_end_to_end`
Expected: PASS.

- [ ] **Step 4: Run the full Google Docs suite + standards**

Run: `lando composer test -- --filter Modules_ImportGoogleDocs`
Then: `lando composer standards`
Expected: suite PASS; standards exit 0.

- [ ] **Step 5: Commit**

```bash
git add tests/fixtures/google-docs/positioned-image.json tests/test-modules-import-google-docs-mapper.php
git commit -m "test(google-docs): fixture for positioned image import (#positioned-images)"
```

---

## Self-Review

**Spec coverage:**
- Read `positionedObjects` + thread into paragraph loop → Task 1 Steps 3-4. ✔
- `renderPositionedImages` (image render, drawing skip+warning, no-contentUri skip, alt→title, metadata shape) → Task 1 Step 5 + tests. ✔
- Emit `<img>` before anchor paragraph → Task 1 Step 4 + ordering assertions. ✔
- Reuse `#gdoc-image-*` + `images[]` shape (no importer change) → metadata entry in Step 5; integration asserts `content_uri`. ✔
- No regression (paragraph without ids) → `test_paragraph_without_positioned_ids_unchanged` + full-suite runs. ✔
- Realistic fixture end-to-end → Task 2. ✔
- Out of scope (table-cell anchors, figure semantics, layout) → not implemented, by design. ✔

**Placeholder scan:** No TBD/TODO; every code step shows full code; every run step has an expected result.

**Type consistency:** `renderPositionedImages(array $ids, array $positioned_objects, array &$images): string` used consistently; `images[]` entries use `object_id`/`content_uri`/`alt`/`title` (matching `collectImageMeta` and what `GoogleDocs::processImages()` reads).
