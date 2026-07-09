# Google Docs Importer — Test Coverage Expansion Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add 29 new tests across DocsMapper edge cases, DocsFetcher (zero coverage), OAuthClient direct mode, and SettingsPage.

**Architecture:** Two independent tracks. Track 1 appends inline-data test methods to the existing mapper test file — no new infrastructure. Track 2 stubs network-touching code using PHPUnit mocks of `\Google\Client`, Guzzle `MockHandler` for HTTP responses, and a `wp_redirect` filter hook to intercept SettingsPage redirects before `exit`.

**Tech Stack:** PHPUnit via `WP_UnitTestCase`, `GuzzleHttp\Handler\MockHandler` (transitive dep of `google/apiclient`, no new require needed), PHPUnit mock objects.

## Global Constraints

- All new test methods tagged `@group import`
- Run tests with: `lando composer test -- --filter <ClassName>` (must use Lando; host phpunit cannot reach the DB)
- PHP 8.3, WordPress Multisite
- Do NOT add new composer dependencies
- New test files follow the naming pattern: `tests/test-modules-import-google-docs-<name>.php`

---

## File Structure

| File | Status | Responsibility |
|---|---|---|
| `tests/test-modules-import-google-docs-mapper.php` | **Modify** | Add 10 new test methods (Tasks 1–3) |
| `tests/test-modules-import-google-docs-fetcher.php` | **Create** | 6 tests for `DocsFetcher` (Task 4) |
| `tests/test-modules-import-google-docs-oauth.php` | **Modify** | Add 8 test methods for OAuthClient direct mode (Task 5) |
| `tests/test-modules-import-google-docs-settings-page.php` | **Create** | 5 tests for `SettingsPage` (Task 6) |

---

### Task 1: DocsMapper — applyTextStyle and content edge cases

**Files:**
- Modify: `tests/test-modules-import-google-docs-mapper.php`

**Tests added:** `test_bold_and_italic_combined`, `test_link_suppresses_underline`, `test_empty_paragraph_produces_no_output`, `test_inline_drawing_produces_warning`, `test_equation_element_produces_warning`, `test_subtitle_style_renders_as_h2`

- [ ] **Step 1: Append the first three tests (bold+italic, link suppresses underline, empty paragraph)**

Add before the final `}` of `Modules_ImportGoogleDocsMapperTest`:

```php
	/**
	 * @group import
	 */
	public function test_bold_and_italic_combined(): void {
		$doc = [
			'title' => 'Style Test',
			'body'  => [ 'content' => [
				[ 'sectionBreak' => [ 'sectionStyle' => [] ] ],
				[ 'paragraph' => [
					'elements'       => [ [ 'textRun' => [ 'content' => "Chapter\n", 'textStyle' => [] ] ] ],
					'paragraphStyle' => [ 'namedStyleType' => 'HEADING_1' ],
				] ],
				[ 'paragraph' => [
					'elements'       => [
						[ 'textRun' => [ 'content' => 'hello', 'textStyle' => [ 'bold' => true, 'italic' => true ] ] ],
						[ 'textRun' => [ 'content' => "\n", 'textStyle' => [] ] ],
					],
					'paragraphStyle' => [ 'namedStyleType' => 'NORMAL_TEXT' ],
				] ],
			] ],
			'inlineObjects' => [],
		];

		$mapper   = new DocsMapper();
		$chapters = $mapper->toChapters( $doc );

		$this->assertStringContainsString( '<strong><em>hello</em></strong>', $chapters[0]['body'] );
	}

	/**
	 * @group import
	 */
	public function test_link_suppresses_underline(): void {
		$doc = [
			'title' => 'Link Test',
			'body'  => [ 'content' => [
				[ 'sectionBreak' => [ 'sectionStyle' => [] ] ],
				[ 'paragraph' => [
					'elements'       => [ [ 'textRun' => [ 'content' => "Chapter\n", 'textStyle' => [] ] ] ],
					'paragraphStyle' => [ 'namedStyleType' => 'HEADING_1' ],
				] ],
				[ 'paragraph' => [
					'elements'       => [
						[ 'textRun' => [ 'content' => 'click here', 'textStyle' => [
							'link'      => [ 'url' => 'https://example.com' ],
							'underline' => true,
						] ] ],
						[ 'textRun' => [ 'content' => "\n", 'textStyle' => [] ] ],
					],
					'paragraphStyle' => [ 'namedStyleType' => 'NORMAL_TEXT' ],
				] ],
			] ],
			'inlineObjects' => [],
		];

		$mapper   = new DocsMapper();
		$chapters = $mapper->toChapters( $doc );
		$body     = $chapters[0]['body'];

		$this->assertStringContainsString( '<a href="https://example.com">click here</a>', $body );
		$this->assertStringNotContainsString( '<u>', $body );
	}

	/**
	 * @group import
	 */
	public function test_empty_paragraph_produces_no_output(): void {
		$doc = [
			'title' => 'Empty Para',
			'body'  => [ 'content' => [
				[ 'sectionBreak' => [ 'sectionStyle' => [] ] ],
				[ 'paragraph' => [
					'elements'       => [ [ 'textRun' => [ 'content' => "Chapter\n", 'textStyle' => [] ] ] ],
					'paragraphStyle' => [ 'namedStyleType' => 'HEADING_1' ],
				] ],
				[ 'paragraph' => [
					'elements'       => [ [ 'textRun' => [ 'content' => "   \n", 'textStyle' => [] ] ] ],
					'paragraphStyle' => [ 'namedStyleType' => 'NORMAL_TEXT' ],
				] ],
				[ 'paragraph' => [
					'elements'       => [ [ 'textRun' => [ 'content' => "Real content.\n", 'textStyle' => [] ] ] ],
					'paragraphStyle' => [ 'namedStyleType' => 'NORMAL_TEXT' ],
				] ],
			] ],
			'inlineObjects' => [],
		];

		$mapper   = new DocsMapper();
		$chapters = $mapper->toChapters( $doc );
		$body     = $chapters[0]['body'];

		$this->assertStringContainsString( '<p>Real content.</p>', $body );
		$this->assertSame( 1, substr_count( $body, '<p>' ) );
	}
```

- [ ] **Step 2: Run these three tests**

```bash
lando composer test -- --filter 'test_bold_and_italic_combined|test_link_suppresses_underline|test_empty_paragraph_produces_no_output'
```

Expected: 3 pass. If `test_bold_and_italic_combined` fails, check `applyTextStyle()` ordering — bold is applied outermost, so the expected string is `<strong><em>hello</em></strong>`.

- [ ] **Step 3: Append the remaining three tests (drawing warning, equation warning, subtitle)**

```php
	/**
	 * @group import
	 */
	public function test_inline_drawing_produces_warning(): void {
		$obj_id = 'kix.drawing1';
		$doc    = [
			'title' => 'Drawing Test',
			'body'  => [ 'content' => [
				[ 'sectionBreak' => [ 'sectionStyle' => [] ] ],
				[ 'paragraph' => [
					'elements'       => [ [ 'textRun' => [ 'content' => "Chapter\n", 'textStyle' => [] ] ] ],
					'paragraphStyle' => [ 'namedStyleType' => 'HEADING_1' ],
				] ],
				[ 'paragraph' => [
					'elements'       => [
						[ 'inlineObjectElement' => [ 'inlineObjectId' => $obj_id ] ],
						[ 'textRun' => [ 'content' => "\n", 'textStyle' => [] ] ],
					],
					'paragraphStyle' => [ 'namedStyleType' => 'NORMAL_TEXT' ],
				] ],
			] ],
			'inlineObjects' => [
				$obj_id => [ 'inlineObjectProperties' => [ 'embeddedObject' => [
					'embeddedDrawingProperties' => [],
				] ] ],
			],
		];

		$mapper   = new DocsMapper();
		$chapters = $mapper->toChapters( $doc );

		$this->assertStringNotContainsString( '<img', $chapters[0]['body'] );
		$this->assertContains( "Drawing element skipped (unsupported): {$obj_id}", $mapper->getWarnings() );
	}

	/**
	 * @group import
	 */
	public function test_equation_element_produces_warning(): void {
		$doc = [
			'title' => 'Equation Test',
			'body'  => [ 'content' => [
				[ 'sectionBreak' => [ 'sectionStyle' => [] ] ],
				[ 'paragraph' => [
					'elements'       => [ [ 'textRun' => [ 'content' => "Chapter\n", 'textStyle' => [] ] ] ],
					'paragraphStyle' => [ 'namedStyleType' => 'HEADING_1' ],
				] ],
				[ 'paragraph' => [
					'elements'       => [
						[ 'textRun' => [ 'content' => 'Before ', 'textStyle' => [] ] ],
						[ 'equation' => [] ],
						[ 'textRun' => [ 'content' => " after.\n", 'textStyle' => [] ] ],
					],
					'paragraphStyle' => [ 'namedStyleType' => 'NORMAL_TEXT' ],
				] ],
			] ],
			'inlineObjects' => [],
		];

		$mapper   = new DocsMapper();
		$chapters = $mapper->toChapters( $doc );

		$this->assertContains( 'Equation element skipped (unsupported).', $mapper->getWarnings() );
		$body = $chapters[0]['body'];
		$this->assertStringContainsString( 'Before', $body );
		$this->assertStringContainsString( 'after.', $body );
	}

	/**
	 * @group import
	 */
	public function test_subtitle_style_renders_as_h2(): void {
		$doc = [
			'title' => 'Subtitle Test',
			'body'  => [ 'content' => [
				[ 'sectionBreak' => [ 'sectionStyle' => [] ] ],
				[ 'paragraph' => [
					'elements'       => [ [ 'textRun' => [ 'content' => "Chapter\n", 'textStyle' => [] ] ] ],
					'paragraphStyle' => [ 'namedStyleType' => 'HEADING_1' ],
				] ],
				[ 'paragraph' => [
					'elements'       => [ [ 'textRun' => [ 'content' => "My Subtitle\n", 'textStyle' => [] ] ] ],
					'paragraphStyle' => [ 'namedStyleType' => 'SUBTITLE' ],
				] ],
			] ],
			'inlineObjects' => [],
		];

		$mapper   = new DocsMapper();
		$chapters = $mapper->toChapters( $doc );

		$this->assertStringContainsString( '<h2>My Subtitle</h2>', $chapters[0]['body'] );
	}
```

- [ ] **Step 4: Run these three tests**

```bash
lando composer test -- --filter 'test_inline_drawing_produces_warning|test_equation_element_produces_warning|test_subtitle_style_renders_as_h2'
```

Expected: 3 pass.

- [ ] **Step 5: Commit**

```bash
git add tests/test-modules-import-google-docs-mapper.php
git commit -m "test(google-docs): mapper applyTextStyle and content edge cases"
```

---

### Task 2: DocsMapper — finalize() list edge cases

**Files:**
- Modify: `tests/test-modules-import-google-docs-mapper.php`

**Tests added:** `test_list_type_switch_at_same_level`, `test_three_level_nested_list`

These cover the two untested branches of `finalize()`: type switching at the same nesting level (L628–635) and 3-level deep nesting with placeholder `<li>` wrappers (L640–653).

- [ ] **Step 1: Append list-type-switch test**

```php
	/**
	 * @group import
	 */
	public function test_list_type_switch_at_same_level(): void {
		$doc = [
			'title' => 'Switching Lists',
			'body'  => [ 'content' => [
				[ 'sectionBreak' => [ 'sectionStyle' => [] ] ],
				[ 'paragraph' => [
					'elements'       => [ [ 'textRun' => [ 'content' => "Chapter\n", 'textStyle' => [] ] ] ],
					'paragraphStyle' => [ 'namedStyleType' => 'HEADING_1' ],
				] ],
				[ 'paragraph' => [
					'elements'       => [ [ 'textRun' => [ 'content' => "Apple\n", 'textStyle' => [] ] ] ],
					'paragraphStyle' => [ 'namedStyleType' => 'NORMAL_TEXT' ],
					'bullet'         => [ 'listId' => 'kix.ul1', 'nestingLevel' => 0 ],
				] ],
				[ 'paragraph' => [
					'elements'       => [ [ 'textRun' => [ 'content' => "Banana\n", 'textStyle' => [] ] ] ],
					'paragraphStyle' => [ 'namedStyleType' => 'NORMAL_TEXT' ],
					'bullet'         => [ 'listId' => 'kix.ul1', 'nestingLevel' => 0 ],
				] ],
				[ 'paragraph' => [
					'elements'       => [ [ 'textRun' => [ 'content' => "Step one\n", 'textStyle' => [] ] ] ],
					'paragraphStyle' => [ 'namedStyleType' => 'NORMAL_TEXT' ],
					'bullet'         => [ 'listId' => 'kix.ol1', 'nestingLevel' => 0 ],
				] ],
				[ 'paragraph' => [
					'elements'       => [ [ 'textRun' => [ 'content' => "Step two\n", 'textStyle' => [] ] ] ],
					'paragraphStyle' => [ 'namedStyleType' => 'NORMAL_TEXT' ],
					'bullet'         => [ 'listId' => 'kix.ol1', 'nestingLevel' => 0 ],
				] ],
			] ],
			'inlineObjects' => [],
			'lists'         => [
				'kix.ul1' => [ 'listProperties' => [ 'nestingLevels' => [ [ 'glyphSymbol' => '●' ] ] ] ],
				'kix.ol1' => [ 'listProperties' => [ 'nestingLevels' => [ [ 'glyphType' => 'DECIMAL' ] ] ] ],
			],
		];

		$mapper   = new DocsMapper();
		$chapters = $mapper->toChapters( $doc );
		$body     = $chapters[0]['body'];

		$this->assertStringContainsString( '<li>Apple</li>', $body );
		$this->assertStringContainsString( '<li>Banana</li>', $body );
		$this->assertStringContainsString( '<li>Step one</li>', $body );
		$this->assertStringContainsString( '<li>Step two</li>', $body );
		$this->assertStringContainsString( '<ul>', $body );
		$this->assertStringContainsString( '<ol>', $body );
		// ul must close before ol opens
		$this->assertLessThan( strpos( $body, '<ol>' ), strpos( $body, '</ul>' ) );
		$this->assertSame( substr_count( $body, '<ul>' ), substr_count( $body, '</ul>' ) );
		$this->assertSame( substr_count( $body, '<ol>' ), substr_count( $body, '</ol>' ) );
	}
```

- [ ] **Step 2: Run it**

```bash
lando composer test -- --filter test_list_type_switch_at_same_level
```

Expected: 1 pass.

- [ ] **Step 3: Append three-level nested list test**

```php
	/**
	 * @group import
	 */
	public function test_three_level_nested_list(): void {
		$doc = [
			'title' => 'Deep Nesting',
			'body'  => [ 'content' => [
				[ 'sectionBreak' => [ 'sectionStyle' => [] ] ],
				[ 'paragraph' => [
					'elements'       => [ [ 'textRun' => [ 'content' => "Chapter\n", 'textStyle' => [] ] ] ],
					'paragraphStyle' => [ 'namedStyleType' => 'HEADING_1' ],
				] ],
				[ 'paragraph' => [
					'elements'       => [ [ 'textRun' => [ 'content' => "Level one\n", 'textStyle' => [] ] ] ],
					'paragraphStyle' => [ 'namedStyleType' => 'NORMAL_TEXT' ],
					'bullet'         => [ 'listId' => 'kix.deep', 'nestingLevel' => 0 ],
				] ],
				[ 'paragraph' => [
					'elements'       => [ [ 'textRun' => [ 'content' => "Level two\n", 'textStyle' => [] ] ] ],
					'paragraphStyle' => [ 'namedStyleType' => 'NORMAL_TEXT' ],
					'bullet'         => [ 'listId' => 'kix.deep', 'nestingLevel' => 1 ],
				] ],
				[ 'paragraph' => [
					'elements'       => [ [ 'textRun' => [ 'content' => "Level three\n", 'textStyle' => [] ] ] ],
					'paragraphStyle' => [ 'namedStyleType' => 'NORMAL_TEXT' ],
					'bullet'         => [ 'listId' => 'kix.deep', 'nestingLevel' => 2 ],
				] ],
			] ],
			'inlineObjects' => [],
			'lists'         => [
				'kix.deep' => [ 'listProperties' => [ 'nestingLevels' => [
					[ 'glyphSymbol' => '●' ],
					[ 'glyphSymbol' => '○' ],
					[ 'glyphSymbol' => '▪' ],
				] ] ],
			],
		];

		$mapper   = new DocsMapper();
		$chapters = $mapper->toChapters( $doc );
		$body     = $chapters[0]['body'];

		$this->assertSame( 3, substr_count( $body, '<ul>' ) );
		$this->assertSame( 3, substr_count( $body, '</ul>' ) );
		$this->assertStringContainsString( '<li>Level one', $body );
		$this->assertStringContainsString( '<li>Level two</li>', $body );
		$this->assertStringContainsString( '<li>Level three</li>', $body );
	}
```

- [ ] **Step 4: Run it**

```bash
lando composer test -- --filter test_three_level_nested_list
```

Expected: 1 pass.

- [ ] **Step 5: Commit**

```bash
git add tests/test-modules-import-google-docs-mapper.php
git commit -m "test(google-docs): mapper finalize() list type switch and 3-level nesting"
```

---

### Task 3: DocsMapper — table image collection and multi-footnote

**Files:**
- Modify: `tests/test-modules-import-google-docs-mapper.php`

**Tests added:** `test_images_in_table_cells_collected`, `test_multiple_footnotes_in_same_paragraph`

- [ ] **Step 1: Append both tests**

```php
	/**
	 * @group import
	 */
	public function test_images_in_table_cells_collected(): void {
		$obj_id = 'kix.cellimg1';
		$doc    = [
			'title' => 'Table Image',
			'body'  => [ 'content' => [
				[ 'sectionBreak' => [ 'sectionStyle' => [] ] ],
				[ 'paragraph' => [
					'elements'       => [ [ 'textRun' => [ 'content' => "Chapter\n", 'textStyle' => [] ] ] ],
					'paragraphStyle' => [ 'namedStyleType' => 'HEADING_1' ],
				] ],
				[ 'table' => [ 'tableRows' => [
					[ 'tableCells' => [ [ 'content' => [
						[ 'paragraph' => [
							'elements'       => [
								[ 'inlineObjectElement' => [ 'inlineObjectId' => $obj_id ] ],
								[ 'textRun' => [ 'content' => "\n", 'textStyle' => [] ] ],
							],
							'paragraphStyle' => [ 'namedStyleType' => 'NORMAL_TEXT' ],
						] ],
					] ] ] ],
				] ] ],
			] ],
			'inlineObjects' => [
				$obj_id => [ 'inlineObjectProperties' => [ 'embeddedObject' => [
					'description'     => 'A chart',
					'title'           => 'Chart Title',
					'imageProperties' => [ 'contentUri' => 'https://example.com/chart.png' ],
				] ] ],
			],
			'lists' => [],
		];

		$mapper   = new DocsMapper();
		$chapters = $mapper->toChapters( $doc );

		$this->assertCount( 1, $chapters[0]['images'] );
		$img = $chapters[0]['images'][0];
		$this->assertSame( $obj_id, $img['object_id'] );
		$this->assertSame( 'A chart', $img['alt'] );
		$this->assertSame( 'https://example.com/chart.png', $img['content_uri'] );
	}

	/**
	 * @group import
	 */
	public function test_multiple_footnotes_in_same_paragraph(): void {
		$doc = [
			'title'     => 'Footnotes',
			'body'      => [ 'content' => [
				[ 'sectionBreak' => [ 'sectionStyle' => [] ] ],
				[ 'paragraph' => [
					'elements'       => [ [ 'textRun' => [ 'content' => "Chapter\n", 'textStyle' => [] ] ] ],
					'paragraphStyle' => [ 'namedStyleType' => 'HEADING_1' ],
				] ],
				[ 'paragraph' => [
					'elements'       => [
						[ 'textRun'           => [ 'content' => 'First claim', 'textStyle' => [] ] ],
						[ 'footnoteReference' => [ 'footnoteId' => 'fn1' ] ],
						[ 'textRun'           => [ 'content' => ' and second claim', 'textStyle' => [] ] ],
						[ 'footnoteReference' => [ 'footnoteId' => 'fn2' ] ],
						[ 'textRun'           => [ 'content' => ".\n", 'textStyle' => [] ] ],
					],
					'paragraphStyle' => [ 'namedStyleType' => 'NORMAL_TEXT' ],
				] ],
			] ],
			'inlineObjects' => [],
			'footnotes'  => [
				'fn1' => [ 'content' => [ [ 'paragraph' => [
					'elements' => [ [ 'textRun' => [ 'content' => "Note one.\n", 'textStyle' => [] ] ] ],
				] ] ] ],
				'fn2' => [ 'content' => [ [ 'paragraph' => [
					'elements' => [ [ 'textRun' => [ 'content' => "Note two.\n", 'textStyle' => [] ] ] ],
				] ] ] ],
			],
		];

		$mapper   = new DocsMapper();
		$chapters = $mapper->toChapters( $doc );
		$body     = $chapters[0]['body'];

		$this->assertStringContainsString( '[footnote]Note one.[/footnote]', $body );
		$this->assertStringContainsString( '[footnote]Note two.[/footnote]', $body );
		$this->assertStringContainsString(
			'<p>First claim[footnote]Note one.[/footnote] and second claim[footnote]Note two.[/footnote].</p>',
			$body
		);
	}
```

- [ ] **Step 2: Run both tests**

```bash
lando composer test -- --filter 'test_images_in_table_cells_collected|test_multiple_footnotes_in_same_paragraph'
```

Expected: 2 pass.

- [ ] **Step 3: Run the full mapper test class to confirm no regressions**

```bash
lando composer test -- --filter Modules_ImportGoogleDocsMapperTest
```

Expected: All pass (existing 33 + 10 new = 43 total).

- [ ] **Step 4: Commit**

```bash
git add tests/test-modules-import-google-docs-mapper.php
git commit -m "test(google-docs): mapper table image collection and multi-footnote in paragraph"
```

---

### Task 4: DocsFetcher tests

**Files:**
- Create: `tests/test-modules-import-google-docs-fetcher.php`

**Stub strategy:**
- `fetchDocument` / `getFileMetadata`: Construct `\Google\Client` with a `GuzzleHttp\Handler\MockHandler` injected via `$client->setHttpClient()`. The Google PHP client uses Guzzle internally, so all API calls are intercepted.
- `downloadImage`: PHPUnit-mock `\Google\Client` to control `authorize()`, which returns a mock Guzzle HTTP client.
- `fetchAndCache`: Guzzle mock + real temp directory.

`GuzzleHttp\Handler\MockHandler` and `GuzzleHttp\Psr7\Response` live in the main `guzzlehttp/guzzle` package (not test-only), which is a transitive dependency of `google/apiclient`. No new requires.

- [ ] **Step 1: Create the test file with the helper and fetchDocument / getFileMetadata tests**

Create `tests/test-modules-import-google-docs-fetcher.php`:

```php
<?php

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Pressbooks\Modules\Import\GoogleDocs\DocsFetcher;

class Modules_ImportGoogleDocsFetcherTest extends \WP_UnitTestCase {

	private function makeClientWithMock( array $responses ): \Google\Client {
		$mock    = new MockHandler( $responses );
		$stack   = HandlerStack::create( $mock );
		$guzzle  = new \GuzzleHttp\Client( [ 'handler' => $stack ] );

		$client = new \Google\Client();
		$client->setHttpClient( $guzzle );
		$client->setAccessToken( [
			'access_token' => 'fake_token',
			'expires_in'   => 3600,
			'created'      => time(),
			'token_type'   => 'Bearer',
		] );
		return $client;
	}

	/**
	 * @group import
	 */
	public function test_fetch_document_returns_php_array(): void {
		$json = json_encode( [
			'documentId' => 'doc_abc',
			'title'      => 'My Test Doc',
			'body'       => [ 'content' => [] ],
		] );

		$client  = $this->makeClientWithMock( [ new Response( 200, [ 'Content-Type' => 'application/json' ], $json ) ] );
		$fetcher = new DocsFetcher( $client );
		$result  = $fetcher->fetchDocument( 'doc_abc' );

		$this->assertIsArray( $result );
		$this->assertSame( 'doc_abc', $result['documentId'] );
		$this->assertSame( 'My Test Doc', $result['title'] );
		$this->assertArrayHasKey( 'body', $result );
	}

	/**
	 * @group import
	 */
	public function test_get_file_metadata_returns_name_and_mime_type(): void {
		$json = json_encode( [
			'name'     => 'My Document',
			'mimeType' => 'application/vnd.google-apps.document',
		] );

		$client  = $this->makeClientWithMock( [ new Response( 200, [ 'Content-Type' => 'application/json' ], $json ) ] );
		$fetcher = new DocsFetcher( $client );
		$result  = $fetcher->getFileMetadata( 'doc_abc' );

		$this->assertSame( 'My Document', $result['title'] );
		$this->assertSame( 'application/vnd.google-apps.document', $result['mimeType'] );
	}
}
```

- [ ] **Step 2: Run these two tests**

```bash
lando composer test -- --filter 'test_fetch_document_returns_php_array|test_get_file_metadata_returns_name_and_mime_type'
```

Expected: 2 pass. If one fails with a Google auth error, verify that `setAccessToken()` receives an array (not a JSON string).

- [ ] **Step 3: Add the three downloadImage tests**

Append inside `Modules_ImportGoogleDocsFetcherTest` (before the closing `}`):

```php
	/**
	 * @group import
	 */
	public function test_download_image_returns_body_on_200(): void {
		$image_bytes = 'fake_image_data';

		$mock_http = $this->createMock( \GuzzleHttp\Client::class );
		$mock_http->method( 'get' )->willReturn( new Response( 200, [], $image_bytes ) );

		$mock_client = $this->createMock( \Google\Client::class );
		$mock_client->method( 'authorize' )->willReturn( $mock_http );

		$fetcher = new DocsFetcher( $mock_client );
		$result  = $fetcher->downloadImage( 'https://example.com/img.png' );

		$this->assertSame( $image_bytes, $result );
	}

	/**
	 * @group import
	 */
	public function test_download_image_returns_false_on_non_200(): void {
		$mock_http = $this->createMock( \GuzzleHttp\Client::class );
		$mock_http->method( 'get' )->willReturn( new Response( 404, [], '' ) );

		$mock_client = $this->createMock( \Google\Client::class );
		$mock_client->method( 'authorize' )->willReturn( $mock_http );

		$fetcher = new DocsFetcher( $mock_client );
		$result  = $fetcher->downloadImage( 'https://example.com/missing.png' );

		$this->assertFalse( $result );
	}

	/**
	 * @group import
	 */
	public function test_download_image_returns_false_on_exception(): void {
		$mock_http = $this->createMock( \GuzzleHttp\Client::class );
		$mock_http->method( 'get' )->willThrowException( new \Exception( 'Network error' ) );

		$mock_client = $this->createMock( \Google\Client::class );
		$mock_client->method( 'authorize' )->willReturn( $mock_http );

		$fetcher = new DocsFetcher( $mock_client );
		$result  = $fetcher->downloadImage( 'https://example.com/img.png' );

		$this->assertFalse( $result );
	}
```

- [ ] **Step 4: Run all three downloadImage tests**

```bash
lando composer test -- --filter 'test_download_image'
```

Expected: 3 pass.

- [ ] **Step 5: Add the fetchAndCache test**

Append inside the class:

```php
	/**
	 * @group import
	 */
	public function test_fetch_and_cache_writes_json_file_and_returns_path(): void {
		$doc_id = 'doc_cache_test';
		$json   = json_encode( [
			'documentId' => $doc_id,
			'title'      => 'Cached Doc',
			'body'       => [ 'content' => [] ],
		] );

		$client    = $this->makeClientWithMock( [ new Response( 200, [ 'Content-Type' => 'application/json' ], $json ) ] );
		$fetcher   = new DocsFetcher( $client );
		$cache_dir = sys_get_temp_dir() . '/pb-gdoc-test-' . uniqid();
		mkdir( $cache_dir, 0777, true );

		$path = $fetcher->fetchAndCache( $doc_id, $cache_dir );

		$this->assertFileExists( $path );
		$this->assertStringContainsString( $doc_id, basename( $path ) );
		$this->assertStringEndsWith( '.json', $path );
		$contents = json_decode( file_get_contents( $path ), true );
		$this->assertSame( 'Cached Doc', $contents['title'] );

		unlink( $path );
		rmdir( $cache_dir );
	}
```

- [ ] **Step 6: Run all DocsFetcher tests**

```bash
lando composer test -- --filter Modules_ImportGoogleDocsFetcherTest
```

Expected: 6 pass.

- [ ] **Step 7: Commit**

```bash
git add tests/test-modules-import-google-docs-fetcher.php
git commit -m "test(google-docs): DocsFetcher coverage with Guzzle MockHandler"
```

---

### Task 5: OAuthClient direct mode tests

**Files:**
- Modify: `tests/test-modules-import-google-docs-oauth.php`

**Tests added:** `test_disconnect_is_noop_when_no_token`, `test_disconnect_deletes_token_in_direct_mode`, `test_disconnect_throws_when_google_revoke_fails`, `test_handle_callback_direct_mode_stores_token_and_returns_url`, `test_handle_callback_direct_mode_throws_on_token_error`, `test_get_authed_client_refreshes_expired_direct_token`, `test_get_authed_client_throws_when_no_refresh_token`, `test_get_authed_client_throws_when_direct_refresh_returns_error`

**Stub strategy:** `getMockBuilder(OAuthClient::class)->onlyMethods(['buildClient'])` injects a PHPUnit-mocked `\Google\Client` without any network calls. `TokenStorage` (interface) and `CredentialsStore` (class) are PHPUnit-mocked so no encryption key constants are needed.

- [ ] **Step 1: Append the private helper and the three disconnect tests**

Add before the final `}` of `Modules_ImportGoogleDocsOAuthTest`:

```php
	// ─── Direct mode helpers ──────────────────────────────────────────────

	private function makeDirectOAuth( \Pressbooks\Modules\Import\GoogleDocs\Storage\TokenStorage $storage ): array {
		$creds = $this->createMock( CredentialsStore::class );
		$creds->method( 'isBrokerMode' )->willReturn( false );
		$creds->method( 'getClientCredentials' )->willReturn( [
			'client_id'     => 'test_client_id',
			'client_secret' => 'test_client_secret',
		] );

		$mock_google = $this->createMock( \Google\Client::class );

		$oauth = $this->getMockBuilder( OAuthClient::class )
			->setConstructorArgs( [ $storage, $creds ] )
			->onlyMethods( [ 'buildClient' ] )
			->getMock();
		$oauth->method( 'buildClient' )->willReturn( $mock_google );

		return [ $oauth, $mock_google ];
	}

	/**
	 * @group import
	 */
	public function test_disconnect_is_noop_when_no_token(): void {
		$storage = $this->createMock( \Pressbooks\Modules\Import\GoogleDocs\Storage\TokenStorage::class );
		$storage->method( 'load' )->willReturn( null );
		$storage->expects( $this->never() )->method( 'delete' );

		[ $oauth ] = $this->makeDirectOAuth( $storage );
		$oauth->disconnect( 1 );
		$this->assertTrue( true ); // must not throw
	}

	/**
	 * @group import
	 */
	public function test_disconnect_deletes_token_in_direct_mode(): void {
		$token = new StoredToken(
			[ 'access_token' => 'tok', 'expires_at' => time() + 3600 ],
			TokenMode::Direct
		);

		$storage = $this->createMock( \Pressbooks\Modules\Import\GoogleDocs\Storage\TokenStorage::class );
		$storage->method( 'load' )->willReturn( $token );
		$storage->expects( $this->once() )->method( 'delete' )->with( 42 );

		[ $oauth, $mock_google ] = $this->makeDirectOAuth( $storage );
		$mock_google->method( 'revokeToken' )->willReturn( null );

		$oauth->disconnect( 42 );
	}

	/**
	 * @group import
	 */
	public function test_disconnect_throws_when_google_revoke_fails(): void {
		$token = new StoredToken(
			[ 'access_token' => 'tok', 'expires_at' => time() + 3600 ],
			TokenMode::Direct
		);

		$storage = $this->createMock( \Pressbooks\Modules\Import\GoogleDocs\Storage\TokenStorage::class );
		$storage->method( 'load' )->willReturn( $token );

		[ $oauth, $mock_google ] = $this->makeDirectOAuth( $storage );
		$mock_google->method( 'revokeToken' )
			->willThrowException( new \Exception( 'revoke failed' ) );

		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessageMatches( '/Failed to revoke token at Google/' );
		$oauth->disconnect( 1 );
	}
```

- [ ] **Step 2: Run the three disconnect tests**

```bash
lando composer test -- --filter 'test_disconnect_is_noop_when_no_token|test_disconnect_deletes_token_in_direct_mode|test_disconnect_throws_when_google_revoke_fails'
```

Expected: 3 pass.

- [ ] **Step 3: Append the two handleCallback direct mode tests**

```php
	/**
	 * @group import
	 */
	public function test_handle_callback_direct_mode_stores_token_and_returns_url(): void {
		$user_id    = $this->factory->user->create();
		$state      = 'state_direct_123';
		$return_url = 'https://example.com/return';
		set_site_transient( 'pb_gdocs_state_' . $state, $return_url, 600 );

		$saved_token = null;
		$storage     = $this->createMock( \Pressbooks\Modules\Import\GoogleDocs\Storage\TokenStorage::class );
		$storage->method( 'save' )->willReturnCallback( function ( $uid, $tok ) use ( &$saved_token ) {
			$saved_token = $tok;
		} );

		[ $oauth, $mock_google ] = $this->makeDirectOAuth( $storage );
		$mock_google->method( 'fetchAccessTokenWithAuthCode' )->willReturn( [
			'access_token'  => 'access_123',
			'refresh_token' => 'refresh_456',
			'expires_in'    => 3600,
			'token_type'    => 'Bearer',
		] );

		$result = $oauth->handleCallback( 'auth_code', $state, $user_id );

		$this->assertSame( $return_url, $result );
		$this->assertNotNull( $saved_token );
		$this->assertSame( 'access_123', $saved_token->accessToken() );
		$this->assertSame( 'refresh_456', $saved_token->refreshToken() );
	}

	/**
	 * @group import
	 */
	public function test_handle_callback_direct_mode_throws_on_token_error(): void {
		$state = 'state_error_456';
		set_site_transient( 'pb_gdocs_state_' . $state, 'https://example.com', 600 );

		$storage = $this->createMock( \Pressbooks\Modules\Import\GoogleDocs\Storage\TokenStorage::class );
		[ $oauth, $mock_google ] = $this->makeDirectOAuth( $storage );
		$mock_google->method( 'fetchAccessTokenWithAuthCode' )->willReturn( [
			'error'             => 'invalid_grant',
			'error_description' => 'Token has been expired or revoked.',
		] );

		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessageMatches( '/Token exchange failed/' );
		$oauth->handleCallback( 'bad_code', $state, 1 );
	}
```

- [ ] **Step 4: Run both handleCallback tests**

```bash
lando composer test -- --filter 'test_handle_callback_direct_mode'
```

Expected: 2 pass.

- [ ] **Step 5: Append the three getAuthedClient direct mode tests**

```php
	/**
	 * @group import
	 */
	public function test_get_authed_client_refreshes_expired_direct_token(): void {
		$user_id       = $this->factory->user->create();
		$original_token = new StoredToken(
			[
				'access_token'  => 'old_access',
				'refresh_token' => 'my_refresh',
				'expires_at'    => time() - 100,
			],
			TokenMode::Direct
		);

		$saved_token = null;
		$storage     = $this->createMock( \Pressbooks\Modules\Import\GoogleDocs\Storage\TokenStorage::class );
		$storage->method( 'load' )->willReturn( $original_token );
		$storage->method( 'save' )->willReturnCallback( function ( $uid, $tok ) use ( &$saved_token ) {
			$saved_token = $tok;
		} );

		[ $oauth, $mock_google ] = $this->makeDirectOAuth( $storage );
		$mock_google->method( 'isAccessTokenExpired' )->willReturn( true );
		$mock_google->method( 'fetchAccessTokenWithRefreshToken' )
			->with( 'my_refresh' )
			->willReturn( [
				'access_token' => 'new_access',
				'expires_in'   => 3600,
				'token_type'   => 'Bearer',
			] );

		$client = $oauth->getAuthedClient( $user_id );

		$this->assertInstanceOf( \Google\Client::class, $client );
		$this->assertNotNull( $saved_token );
		$this->assertSame( 'new_access', $saved_token->accessToken() );
		$this->assertSame( 'my_refresh', $saved_token->refreshToken() );
	}

	/**
	 * @group import
	 */
	public function test_get_authed_client_throws_when_no_refresh_token(): void {
		$user_id = $this->factory->user->create();
		$token   = new StoredToken(
			[ 'access_token' => 'old', 'expires_at' => time() - 100 ],
			TokenMode::Direct
		);

		$storage = $this->createMock( \Pressbooks\Modules\Import\GoogleDocs\Storage\TokenStorage::class );
		$storage->method( 'load' )->willReturn( $token );
		$storage->expects( $this->once() )->method( 'delete' )->with( $user_id );

		[ $oauth, $mock_google ] = $this->makeDirectOAuth( $storage );
		$mock_google->method( 'isAccessTokenExpired' )->willReturn( true );

		$this->expectException( \Pressbooks\Modules\Import\GoogleDocs\ReauthorizationRequiredException::class );
		$oauth->getAuthedClient( $user_id );
	}

	/**
	 * @group import
	 */
	public function test_get_authed_client_throws_when_direct_refresh_returns_error(): void {
		$user_id = $this->factory->user->create();
		$token   = new StoredToken(
			[
				'access_token'  => 'old',
				'refresh_token' => 'stale_refresh',
				'expires_at'    => time() - 100,
			],
			TokenMode::Direct
		);

		$storage = $this->createMock( \Pressbooks\Modules\Import\GoogleDocs\Storage\TokenStorage::class );
		$storage->method( 'load' )->willReturn( $token );
		$storage->expects( $this->once() )->method( 'delete' )->with( $user_id );

		[ $oauth, $mock_google ] = $this->makeDirectOAuth( $storage );
		$mock_google->method( 'isAccessTokenExpired' )->willReturn( true );
		$mock_google->method( 'fetchAccessTokenWithRefreshToken' )->willReturn( [
			'error'             => 'invalid_grant',
			'error_description' => 'Token has been expired or revoked.',
		] );

		$this->expectException( \Pressbooks\Modules\Import\GoogleDocs\ReauthorizationRequiredException::class );
		$oauth->getAuthedClient( $user_id );
	}
```

- [ ] **Step 6: Run all new OAuthClient tests**

```bash
lando composer test -- --filter 'test_disconnect|test_handle_callback_direct_mode|test_get_authed_client_refreshes_expired|test_get_authed_client_throws_when_no_refresh|test_get_authed_client_throws_when_direct_refresh'
```

Expected: 8 pass.

- [ ] **Step 7: Run the full OAuthClient class to confirm no regressions**

```bash
lando composer test -- --filter Modules_ImportGoogleDocsOAuthTest
```

Expected: All pass.

- [ ] **Step 8: Commit**

```bash
git add tests/test-modules-import-google-docs-oauth.php
git commit -m "test(google-docs): OAuthClient direct mode disconnect, callback, and token refresh"
```

---

### Task 6: SettingsPage tests

**Files:**
- Create: `tests/test-modules-import-google-docs-settings-page.php`

**Notes on `wp_redirect` intercept:** `handleOAuthCallback()` calls `wp_redirect()` then `exit`. In the WP test environment `wp_redirect` passes through a filter before calling `header()`. We hook that filter to throw a `\RuntimeException` capturing the redirect URL — this stops execution before `exit`. The `catchRedirect()` helper below wires and unwires this automatically.

- [ ] **Step 1: Create the test file with setUp, tearDown, helper, and the two error-redirect tests**

Create `tests/test-modules-import-google-docs-settings-page.php`:

```php
<?php

use Pressbooks\Modules\Import\GoogleDocs\CredentialsStore;
use Pressbooks\Modules\Import\GoogleDocs\OAuthClient;
use Pressbooks\Modules\Import\GoogleDocs\SettingsPage;
use Pressbooks\Modules\Import\GoogleDocs\Storage\TokenStorage;

class Modules_ImportGoogleDocsSettingsPageTest extends \WP_UnitTestCase {

	private int $super_admin_id;
	private SettingsPage $settings;
	private CredentialsStore $creds_store;
	private OAuthClient $mock_oauth;
	private TokenStorage $mock_storage;

	public function setUp(): void {
		parent::setUp();

		$this->super_admin_id = $this->factory->user->create();
		grant_super_admin( $this->super_admin_id );
		wp_set_current_user( $this->super_admin_id );

		$this->creds_store  = $this->createMock( CredentialsStore::class );
		$this->mock_oauth   = $this->createMock( OAuthClient::class );
		$this->mock_storage = $this->createMock( TokenStorage::class );
		$this->mock_storage->method( 'isAvailable' )->willReturn( true );

		$this->settings = new SettingsPage( $this->creds_store, $this->mock_oauth, $this->mock_storage );
	}

	public function tearDown(): void {
		$_GET     = [];
		$_POST    = [];
		$_REQUEST = [];
		parent::tearDown();
	}

	private function catchRedirect( callable $fn ): string {
		$captured = '';
		$filter   = static function ( string $location ) use ( &$captured ): string {
			$captured = $location;
			throw new \RuntimeException( 'redirect:' . $location );
		};
		add_filter( 'wp_redirect', $filter );
		try {
			$fn();
		} catch ( \RuntimeException $e ) {
			// expected — execution stopped before exit
		} finally {
			remove_filter( 'wp_redirect', $filter );
		}
		return $captured;
	}

	/**
	 * @group import
	 */
	public function test_handle_callback_redirects_with_denied_when_google_error_and_valid_state(): void {
		$state      = 'test_state_abc';
		$return_url = 'https://example.com/import';
		set_site_transient( 'pb_gdocs_state_' . $state, $return_url, 600 );

		$_GET['error'] = 'access_denied';
		$_GET['state'] = $state;

		$location = $this->catchRedirect( function () {
			$this->settings->handleOAuthCallback();
		} );

		$this->assertStringContainsString( 'pb_gdocs=denied', $location );
		$this->assertStringContainsString( $return_url, $location );
		$this->assertFalse( (bool) get_site_transient( 'pb_gdocs_state_' . $state ) );
	}

	/**
	 * @group import
	 */
	public function test_handle_callback_redirects_to_admin_when_error_and_no_state(): void {
		$_GET['error'] = 'access_denied';
		// No state transient set — falls back to admin import page

		$location = $this->catchRedirect( function () {
			$this->settings->handleOAuthCallback();
		} );

		$this->assertStringContainsString( 'pb_gdocs=denied', $location );
		$this->assertStringContainsString( 'pb_import', $location );
	}
}
```

- [ ] **Step 2: Run these two tests**

```bash
lando composer test -- --filter 'test_handle_callback_redirects_with_denied|test_handle_callback_redirects_to_admin'
```

Expected: 2 pass.

- [ ] **Step 3: Append the broker token, code, and renderPage tests**

Append inside the class (before the closing `}`):

```php
	/**
	 * @group import
	 */
	public function test_handle_callback_processes_broker_token_and_redirects_connected(): void {
		$state      = 'broker_state_xyz';
		$return_url = 'https://example.com/return';
		set_site_transient( 'pb_gdocs_state_' . $state, $return_url, 600 );

		$_GET['token'] = 'jwt.token.here';
		$_GET['state'] = $state;

		$this->mock_oauth
			->expects( $this->once() )
			->method( 'handleCallback' )
			->with( 'jwt.token.here', $state, $this->super_admin_id )
			->willReturn( $return_url );

		$location = $this->catchRedirect( function () {
			$this->settings->handleOAuthCallback();
		} );

		$this->assertStringContainsString( 'pb_gdocs=connected', $location );
		$this->assertStringContainsString( $return_url, $location );
	}

	/**
	 * @group import
	 */
	public function test_handle_callback_processes_code_and_redirects_connected(): void {
		$state      = 'code_state_xyz';
		$return_url = 'https://example.com/return';
		set_site_transient( 'pb_gdocs_state_' . $state, $return_url, 600 );

		$_GET['code']  = 'auth_code_123';
		$_GET['state'] = $state;

		$this->mock_oauth
			->expects( $this->once() )
			->method( 'handleCallback' )
			->with( 'auth_code_123', $state, $this->super_admin_id )
			->willReturn( $return_url );

		$location = $this->catchRedirect( function () {
			$this->settings->handleOAuthCallback();
		} );

		$this->assertStringContainsString( 'pb_gdocs=connected', $location );
		$this->assertStringContainsString( $return_url, $location );
	}

	/**
	 * @group import
	 */
	public function test_render_page_saves_credentials_on_valid_post(): void {
		$this->mock_oauth->method( 'isBrokerMode' )->willReturn( false );
		$this->mock_oauth->method( 'getRedirectUri' )->willReturn( 'https://example.com/callback' );
		$this->creds_store->method( 'getClientCredentials' )->willReturn( [
			'client_id'     => '',
			'client_secret' => '',
		] );
		$this->creds_store
			->expects( $this->once() )
			->method( 'saveClientCredentials' )
			->with( 'my-client-id', 'my-secret' );

		$_POST['client_id']     = 'my-client-id';
		$_POST['client_secret'] = 'my-secret';
		$_POST['_wpnonce']      = wp_create_nonce( 'pb_save_google_docs_settings' );
		$_REQUEST               = $_POST;

		ob_start();
		$this->settings->renderPage();
		ob_end_clean();
	}
```

- [ ] **Step 4: Run all SettingsPage tests**

```bash
lando composer test -- --filter Modules_ImportGoogleDocsSettingsPageTest
```

Expected: 5 pass.

- [ ] **Step 5: Run the full import group to confirm no regressions**

```bash
lando composer test -- --group import
```

Expected: All tests pass.

- [ ] **Step 6: Commit**

```bash
git add tests/test-modules-import-google-docs-settings-page.php
git commit -m "test(google-docs): SettingsPage OAuth callback routing and credential save"
```

---

## Self-Review

**Spec coverage check:**
- Track 1 (10 DocsMapper tests): Tests 1–10 all implemented across Tasks 1–3. ✓
- DocsFetcher (6 tests): `fetchDocument`, `getFileMetadata`, `downloadImage` ×3, `fetchAndCache` — all in Task 4. ✓
- OAuthClient direct mode (8 tests): disconnect ×3, handleCallback ×2, getAuthedClient ×3 — all in Task 5. ✓
- SettingsPage (5 tests): error redirect ×2, broker token, code, renderPage — all in Task 6. ✓

**Placeholder scan:** No TBDs, no "add appropriate error handling", no incomplete steps. All test code is complete. ✓

**Type consistency:**
- `StoredToken::accessToken()` / `refreshToken()` used consistently across Task 5. ✓
- `TokenMode::Direct` used in all direct-mode token constructions. ✓
- `CredentialsStore::saveClientCredentials()` signature `(string $client_id, string $client_secret)` matches Task 6 assertion. ✓
- `TokenStorage` interface methods `load`, `save`, `delete`, `isAvailable` — all mocked correctly. ✓
- `makeDirectOAuth()` returns `[$oauth, $mock_google]` and is destructured consistently in all Task 5 tests. ✓
