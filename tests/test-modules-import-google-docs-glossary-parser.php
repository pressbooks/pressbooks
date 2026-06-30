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
