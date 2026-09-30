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

	/**
	 * @group import
	 */
	public function test_positioned_image_attributes_are_escaped(): void {
		$doc = [
			'title' => 'XSS',
			'body' => [ 'content' => [
				[ 'sectionBreak' => [ 'sectionStyle' => [] ] ],
				[ 'paragraph' => [ 'elements' => [ [ 'textRun' => [ 'content' => "Chapter\n", 'textStyle' => [] ] ] ], 'paragraphStyle' => [ 'namedStyleType' => 'HEADING_1' ] ] ],
				[ 'paragraph' => [
					'elements' => [ [ 'textRun' => [ 'content' => "Caption.\n", 'textStyle' => [] ] ] ],
					'paragraphStyle' => [ 'namedStyleType' => 'NORMAL_TEXT' ],
					'positionedObjectIds' => [ 'kix.x' ],
				] ],
			] ],
			'inlineObjects' => [],
			'positionedObjects' => [ 'kix.x' => [ 'positionedObjectProperties' => [ 'embeddedObject' => [
				'description' => 'a" onerror="alert(1)',
				'imageProperties' => [ 'contentUri' => 'https://example.com/x.png' ],
			] ] ] ],
		];

		$mapper = new DocsMapper();
		$chapters = $mapper->toChapters( $doc );
		$body = $chapters[0]['body'];

		$this->assertStringNotContainsString( 'onerror="alert(1)"', $body );
		$this->assertStringContainsString( 'alt="a&quot; onerror=&quot;alert(1)"', $body );
	}

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
}
