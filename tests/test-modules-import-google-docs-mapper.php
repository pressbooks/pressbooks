<?php

use Pressbooks\Modules\Import\GoogleDocs\DocsMapper;

class Modules_ImportGoogleDocsMapperTest extends \WP_UnitTestCase {

	private function loadFixture( string $name ): array {
		$path = __DIR__ . '/fixtures/google-docs/' . $name . '.json';
		return json_decode( file_get_contents( $path ), true );
	}

	/**
	 * @group import
	 */
	public function test_headings_split_into_chapters(): void {
		$mapper = new DocsMapper();
		$doc = $this->loadFixture( 'headings-only' );
		$chapters = $mapper->toChapters( $doc );

		$this->assertCount( 2, $chapters );
		$this->assertSame( 'Chapter One', $chapters[0]['title'] );
		$this->assertSame( 'Chapter Two', $chapters[1]['title'] );
		$this->assertStringContainsString( '<p>This is the first paragraph.</p>', $chapters[0]['body'] );
		$this->assertStringContainsString( '<h2>A Subheading</h2>', $chapters[0]['body'] );
		$this->assertStringContainsString( '<p>More content under the subheading.</p>', $chapters[0]['body'] );
		$this->assertStringContainsString( '<p>Content in chapter two.</p>', $chapters[1]['body'] );
	}

	/**
	 * @group import
	 */
	public function test_unordered_and_ordered_lists(): void {
		$mapper = new DocsMapper();
		$doc = $this->loadFixture( 'mixed-lists' );
		$chapters = $mapper->toChapters( $doc );

		$this->assertCount( 1, $chapters );
		$body = $chapters[0]['body'];
		$this->assertStringContainsString( '<ul>', $body );
		$this->assertStringContainsString( '<li>Bullet one</li>', $body );
		$this->assertStringContainsString( '<li>Bullet two</li>', $body );
		$this->assertStringContainsString( '<ol>', $body );
		$this->assertStringContainsString( '<li>Number one</li>', $body );
		$this->assertStringContainsString( '<li>Number two</li>', $body );
		$this->assertStringContainsString( '<p>Normal paragraph between lists.</p>', $body );
	}

	/**
	 * @group import
	 */
	public function test_nested_lists(): void {
		$mapper = new DocsMapper();
		$doc = $this->loadFixture( 'nested-lists' );
		$chapters = $mapper->toChapters( $doc );

		$this->assertCount( 1, $chapters );
		$body = $chapters[0]['body'];
		$this->assertStringContainsString( '<li>Top level', $body );
		$this->assertStringContainsString( '<li>Nested item</li>', $body );
		$this->assertStringContainsString( '<li>Back to top</li>', $body );
		// Verify nesting structure: ul > li > ul > li
		$this->assertSame( substr_count( $body, '<ul>' ), substr_count( $body, '</ul>' ) );
	}

	/**
	 * @group import
	 */
	public function test_tables(): void {
		$mapper = new DocsMapper();
		$doc = $this->loadFixture( 'simple-table' );
		$chapters = $mapper->toChapters( $doc );

		$this->assertCount( 1, $chapters );
		$body = $chapters[0]['body'];
		$this->assertStringContainsString( '<table>', $body );
		$this->assertStringContainsString( '<td>Cell A1</td>', $body );
		$this->assertStringContainsString( '<td>Cell B1</td>', $body );
		$this->assertStringContainsString( '<td>Cell A2</td>', $body );
		$this->assertStringContainsString( '<td>Cell B2</td>', $body );
		$this->assertSame( 2, substr_count( $body, '<tr>' ) );
	}

	/**
	 * @group import
	 */
	public function test_inline_images(): void {
		$mapper = new DocsMapper();
		$doc = $this->loadFixture( 'with-images' );
		$chapters = $mapper->toChapters( $doc );

		$this->assertCount( 1, $chapters );
		$body = $chapters[0]['body'];
		$this->assertStringContainsString( '<img ', $body );
		$this->assertStringContainsString( 'src="#gdoc-image-kix.img1"', $body );
		$this->assertStringContainsString( 'alt="A beautiful landscape"', $body );
		$this->assertStringContainsString( '<p>Text after the image.</p>', $body );
	}

	/**
	 * @group import
	 */
	public function test_multi_chapter_split(): void {
		$mapper = new DocsMapper();
		$doc = $this->loadFixture( 'multi-chapter' );
		$chapters = $mapper->toChapters( $doc );

		$this->assertCount( 3, $chapters );
		$this->assertSame( 'First Chapter', $chapters[0]['title'] );
		$this->assertSame( 'Second Chapter', $chapters[1]['title'] );
		$this->assertSame( 'Third Chapter', $chapters[2]['title'] );
		$this->assertStringContainsString( '<p>Content one.</p>', $chapters[0]['body'] );
		$this->assertStringContainsString( '<p>Content two.</p>', $chapters[1]['body'] );
		$this->assertStringContainsString( '<p>Content three.</p>', $chapters[2]['body'] );
	}

	/**
	 * @group import
	 */
	public function test_no_h1_fallback(): void {
		$mapper = new DocsMapper();
		$doc = $this->loadFixture( 'no-h1' );
		$chapters = $mapper->toChapters( $doc );

		$this->assertCount( 1, $chapters );
		$this->assertSame( 'Document Without Chapters', $chapters[0]['title'] );
		$this->assertStringContainsString( '<p>Just a paragraph.</p>', $chapters[0]['body'] );
		$this->assertStringContainsString( '<h2>A sub heading</h2>', $chapters[0]['body'] );
	}

	/**
	 * @group import
	 */
	public function test_unsupported_content_skipped(): void {
		$mapper = new DocsMapper();
		$doc = $this->loadFixture( 'unsupported-content' );
		$chapters = $mapper->toChapters( $doc );

		$this->assertCount( 1, $chapters );
		$body = $chapters[0]['body'];
		$this->assertStringContainsString( '<p>Normal text.</p>', $body );
		// Drawings and equations should be skipped
		$this->assertStringNotContainsString( 'drawing', strtolower( $body ) );
		$this->assertStringNotContainsString( 'equation', strtolower( $body ) );
	}

	/**
	 * @group import
	 */
	public function test_image_metadata_collected(): void {
		$mapper = new DocsMapper();
		$doc = $this->loadFixture( 'with-images' );
		$chapters = $mapper->toChapters( $doc );

		$this->assertCount( 1, $chapters );
		$this->assertArrayHasKey( 'images', $chapters[0] );
		$this->assertCount( 1, $chapters[0]['images'] );

		$img = $chapters[0]['images'][0];
		$this->assertSame( 'kix.img1', $img['object_id'] );
		$this->assertSame( 'A beautiful landscape', $img['alt'] );
		$this->assertSame( 'My Photo', $img['title'] );
		$this->assertSame( 'https://lh3.googleusercontent.com/fake-image-uri', $img['content_uri'] );
	}

	/**
	 * @group import
	 */
	public function test_chapter_without_images_has_empty_images_array(): void {
		$mapper = new DocsMapper();
		$doc = $this->loadFixture( 'headings-only' );
		$chapters = $mapper->toChapters( $doc );

		$this->assertCount( 2, $chapters );
		$this->assertArrayHasKey( 'images', $chapters[0] );
		$this->assertSame( [], $chapters[0]['images'] );
	}

	/**
	 * @group import
	 */
	public function test_text_styling(): void {
		$doc = [
			'title' => 'Style Test',
			'body' => [
				'content' => [
					['sectionBreak' => ['sectionStyle' => []]],
					['paragraph' => [
						'elements' => [
							['textRun' => ['content' => "Styled Chapter\n", 'textStyle' => []]],
						],
						'paragraphStyle' => ['namedStyleType' => 'HEADING_1'],
					]],
					['paragraph' => [
						'elements' => [
							['textRun' => ['content' => 'Bold text', 'textStyle' => ['bold' => true]]],
							['textRun' => ['content' => ' and ', 'textStyle' => []]],
							['textRun' => ['content' => 'italic text', 'textStyle' => ['italic' => true]]],
							['textRun' => ['content' => ' and ', 'textStyle' => []]],
							['textRun' => ['content' => 'underlined', 'textStyle' => ['underline' => true]]],
							['textRun' => ['content' => ' and ', 'textStyle' => []]],
							['textRun' => ['content' => 'a link', 'textStyle' => ['link' => ['url' => 'https://example.com']]]],
							['textRun' => ['content' => "\n", 'textStyle' => []]],
						],
						'paragraphStyle' => ['namedStyleType' => 'NORMAL_TEXT'],
					]],
				],
			],
			'inlineObjects' => [],
		];

		$mapper = new DocsMapper();
		$chapters = $mapper->toChapters( $doc );

		$this->assertCount( 1, $chapters );
		$body = $chapters[0]['body'];
		$this->assertStringContainsString( '<strong>Bold text</strong>', $body );
		$this->assertStringContainsString( '<em>italic text</em>', $body );
		$this->assertStringContainsString( '<u>underlined</u>', $body );
		$this->assertStringContainsString( '<a href="https://example.com">a link</a>', $body );
	}

	/**
	 * @group import
	 */
	public function test_footnotes_rendered_as_shortcodes(): void {
		$mapper = new DocsMapper();
		$doc = $this->loadFixture( 'with-footnotes' );
		$chapters = $mapper->toChapters( $doc );

		$this->assertCount( 1, $chapters );
		$body = $chapters[0]['body'];
		$this->assertStringContainsString( '[footnote]First footnote text.[/footnote]', $body );
		$this->assertStringContainsString( '[footnote]Second footnote with <strong>bold</strong> text.[/footnote]', $body );
		$this->assertStringContainsString( 'This has a footnote', $body );
		$this->assertStringContainsString( 'and continues', $body );
	}

	/**
	 * @group import
	 */
	public function test_footnote_inline_placement(): void {
		$mapper = new DocsMapper();
		$doc = $this->loadFixture( 'with-footnotes' );
		$chapters = $mapper->toChapters( $doc );

		$body = $chapters[0]['body'];
		// Footnote shortcode should appear inline within the paragraph
		$this->assertStringContainsString( '<p>This has a footnote[footnote]', $body );
	}

	/**
	 * @group import
	 */
	public function test_merged_cells_colspan(): void {
		$mapper = new DocsMapper();
		$doc = $this->loadFixture( 'merged-cells-table' );
		$chapters = $mapper->toChapters( $doc );

		$this->assertCount( 1, $chapters );
		$body = $chapters[0]['body'];
		$this->assertStringContainsString( 'colspan="3"', $body );
		$this->assertStringContainsString( 'Merged Header', $body );
	}

	/**
	 * @group import
	 */
	public function test_merged_cells_rowspan(): void {
		$mapper = new DocsMapper();
		$doc = $this->loadFixture( 'merged-cells-table' );
		$chapters = $mapper->toChapters( $doc );

		$body = $chapters[0]['body'];
		$this->assertStringContainsString( 'rowspan="2"', $body );
		$this->assertStringContainsString( 'Tall Cell', $body );
		// Non-merged cells should render normally without span attributes
		$this->assertStringContainsString( '<td>B2</td>', $body );
		$this->assertStringContainsString( '<td>C3</td>', $body );
	}

	/**
	 * @group import
	 */
	public function test_merged_cells_warning(): void {
		$mapper = new DocsMapper();
		$doc = $this->loadFixture( 'merged-cells-table' );
		$mapper->toChapters( $doc );

		$warnings = $mapper->getWarnings();
		$this->assertContains( 'Table contains merged cells; verify layout after import.', $warnings );
	}

	/**
	 * @group import
	 */
	public function test_simple_table_no_span_attributes(): void {
		$mapper = new DocsMapper();
		$doc = $this->loadFixture( 'simple-table' );
		$chapters = $mapper->toChapters( $doc );

		$body = $chapters[0]['body'];
		$this->assertStringNotContainsString( 'colspan', $body );
		$this->assertStringNotContainsString( 'rowspan', $body );

		$warnings = $mapper->getWarnings();
		$this->assertNotContains( 'Table contains merged cells; verify layout after import.', $warnings );
	}
}
