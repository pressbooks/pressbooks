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

	/**
	 * @group import
	 */
	public function test_strip_removes_glossary_heading_and_entries(): void {
		$html = '<p>Intro.</p><h3>Glossary</h3><p>Kernel: The core of an OS.</p>';
		$this->assertSame( '<p>Intro.</p>', $this->parser()->stripGlossarySection( $html ) );
	}

	/**
	 * @group import
	 */
	public function test_strip_keeps_content_after_next_heading(): void {
		$html = '<h3>Glossary</h3><p>Kernel: The core of an OS.</p><h2>Next</h2><p>After.</p>';
		$this->assertSame( '<h2>Next</h2><p>After.</p>', $this->parser()->stripGlossarySection( $html ) );
	}

	/**
	 * @group import
	 */
	public function test_strip_returns_unchanged_when_no_glossary(): void {
		$html = '<h2>Intro</h2><p>Some text here.</p>';
		$this->assertSame( $html, $this->parser()->stripGlossarySection( $html ) );
	}

	/**
	 * @group import
	 */
	public function test_strip_returns_unchanged_when_glossary_word_but_no_h3(): void {
		$html = '<h2>Intro</h2><p>A glossary is useful here.</p>';
		$this->assertSame( $html, $this->parser()->stripGlossarySection( $html ) );
	}

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

	/**
	 * @group import
	 */
	public function test_definition_preserves_allowed_inline_formatting(): void {
		$bodies = [ '<h3>Glossary</h3><p>Water: H<sub>2</sub>O is <strong>essential</strong>.</p>' ];
		$entries = $this->parser()->parseGlossaryEntries( $bodies );

		$this->assertSame( 'H<sub>2</sub>O is <strong>essential</strong>.', $entries['water']['definition'] );
	}

	/**
	 * @group import
	 */
	public function test_definition_strips_disallowed_tags_keeps_text(): void {
		$bodies = [ '<h3>Glossary</h3><p>Term: a <span class="x">styled</span> word.</p>' ];
		$entries = $this->parser()->parseGlossaryEntries( $bodies );

		$this->assertSame( 'a styled word.', $entries['term']['definition'] );
	}

	/**
	 * @group import
	 */
	public function test_glossary_heading_matches_despite_nbsp(): void {
		$bodies = [ "<h3>Glossary\xc2\xa0</h3><p>Term: Def.</p>" ];
		$entries = $this->parser()->parseGlossaryEntries( $bodies );

		$this->assertArrayHasKey( 'term', $entries );
	}
}
