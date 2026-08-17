<?php

use Pressbooks\Modules\Export\Epub\Epub;

class Epub_ValidationTest extends \WP_UnitTestCase {

	protected Epub $epub;

	private const VALIDATION_LINES = [
		'Validating using EPUB version 3.3 rules.',
		'ERROR(RSC-005): ./Transforming-Healthcare-Through-Data-1785798544.epub/EPUB/copyright.xhtml(15,1045): Error while parsing file: value of attribute "target" is invalid; must be a string matching the regular expression _"()|([^_].*)|(_[bB][lL][aA][nN][kK])|(_[sS][eE][lL][fF])|(_[pP][aA][rR][eE][nN][tT])|(_[tT][oO][pP])"_',
		'ERROR(RSC-005): ./Transforming-Healthcare-Through-Data-1785798544.epub/EPUB/chapter-008-chapter-08-mapping-the-terrain-domains-and-maturity-in-healthcare-analytics.xhtml(19,314773): Error while parsing file: value of attribute "target" is invalid; must be a string matching the regular expression _"()|([^_].*)|(_[bB][lL][aA][nN][kK])|(_[sS][eE][lL][fF])|(_[pP][aA][rR][eE][nN][tT])|(_[tT][oO][pP])"_',
		'ERROR(OPF-014): ./Transforming-Healthcare-Through-Data-1785798544.epub/EPUB/content.opf(2,92): element from namespace "http://www.idpf.org/2007/opf" must reference an internal resource',
		'WARNING(PKG-022): ./Transforming-Healthcare-Through-Data-1785798544.epub/EPUB/assets/cover.jpeg(1,1): filename "cover.jpeg" does not map to its media type "image/png"',
		'Check finished with errors',
		'Messages: 0 fatals / 2 errors / 0 warnings / 0 infos',
	];

	public function set_up() {
		parent::set_up();
		$this->epub = new Epub( [] );
	}

	private function logWithLiteralSeparators(): string {
		return 'EPUB Validation Warnings/Errors:' . '\\n' . implode( '\\n', self::VALIDATION_LINES );
	}

	private function logWithRealNewlines(): string {
		return 'EPUB Validation Warnings/Errors:' . "\n" . implode( "\n", self::VALIDATION_LINES );
	}

	private function getValidationSummary( string $validation_log ): string {
		$method = new ReflectionMethod( Epub::class, 'getValidationSummary' );
		$method->setAccessible( true );
		return $method->invoke( $this->epub, $validation_log );
	}

	private function reportTotals( string $report ): array {
		preg_match( '/Total Errors: (\d+)/', $report, $errors );
		preg_match( '/Total Warnings: (\d+)/', $report, $warnings );
		preg_match( '/Files Affected: (\d+)/', $report, $files );
		return [ (int) $errors[1], (int) $warnings[1], (int) $files[1] ];
	}

	private function summaryTotals( string $summary ): array {
		preg_match( '/Errors: (\d+), Warnings: (\d+), Files affected: (\d+)/', $summary, $m );
		return [ (int) $m[1], (int) $m[2], (int) $m[3] ];
	}

	/**
	 * @group export
	 * @test
	 */
	public function report_totals_match_summary_counts(): void {
		$log = $this->logWithLiteralSeparators();

		$report_totals = $this->reportTotals( $this->epub->formatValidationLog( $log ) );
		$summary_totals = $this->summaryTotals( $this->getValidationSummary( $log ) );

		$this->assertSame( $summary_totals, $report_totals );
		$this->assertSame( [ 3, 1, 4 ], $report_totals );
	}

	/**
	 * @group export
	 * @test
	 */
	public function verbatim_message_and_location_are_preserved(): void {
		$report = $this->epub->formatValidationLog( $this->logWithLiteralSeparators() );

		$this->assertStringContainsString( '[ERROR RSC-005] Line 15, Col 1045: Error while parsing file: value of attribute "target" is invalid', $report );
		$this->assertStringContainsString( '[ERROR RSC-005] Line 19, Col 314773:', $report );
		$this->assertStringContainsString( 'copyright.xhtml', $report );
		$this->assertStringContainsString( 'chapter-008-chapter-08-mapping-the-terrain-domains-and-maturity-in-healthcare-analytics.xhtml', $report );
	}

	/**
	 * @group export
	 * @test
	 */
	public function errors_in_non_xhtml_files_are_reported(): void {
		$report = $this->epub->formatValidationLog( $this->logWithLiteralSeparators() );

		$this->assertStringContainsString( 'content.opf', $report );
		$this->assertStringContainsString( '[ERROR OPF-014] Line 2, Col 92:', $report );
		$this->assertStringContainsString( '[WARNING PKG-022] Line 1, Col 1:', $report );
	}

	/**
	 * @group export
	 * @test
	 */
	public function literal_and_real_newline_logs_format_identically(): void {
		$literal = $this->epub->formatValidationLog( $this->logWithLiteralSeparators() );
		$real = $this->epub->formatValidationLog( $this->logWithRealNewlines() );

		$this->assertSame( $literal, $real );
		$this->assertSame( $this->getValidationSummary( $this->logWithLiteralSeparators() ), $this->getValidationSummary( $this->logWithRealNewlines() ) );
	}

	/**
	 * @group export
	 * @test
	 */
	public function unknown_codes_and_messages_are_not_dropped(): void {
		$log = 'EPUB Validation Warnings/Errors:\nERROR(MED-001): ./book.epub/EPUB/chapter-001.xhtml(7,42): some future epubcheck message\nWARNING(ABC-999): package-level warning without a file';

		$report = $this->epub->formatValidationLog( $log );

		$this->assertSame( [ 1, 1, 2 ], $this->reportTotals( $report ) );
		$this->assertStringContainsString( '[ERROR MED-001] Line 7, Col 42: some future epubcheck message', $report );
		$this->assertStringContainsString( '(package)', $report );
		$this->assertStringContainsString( '[WARNING ABC-999] package-level warning without a file', $report );
	}

	/**
	 * @group export
	 * @test
	 */
	public function noise_lines_do_not_inflate_counts(): void {
		$log = "Validating using EPUB version 3.3 rules.\nCheck finished with errors\nMessages: 0 fatals / 2 errors / 0 warnings / 0 infos";

		$this->assertSame( [ 0, 0, 0 ], $this->reportTotals( $this->epub->formatValidationLog( $log ) ) );
		$this->assertSame( 'Errors: 0, Warnings: 0, Files affected: 0', $this->getValidationSummary( $log ) );
	}

	/**
	 * @group export
	 * @test
	 */
	public function empty_log_reports_zero_totals(): void {
		$this->assertSame( [ 0, 0, 0 ], $this->reportTotals( $this->epub->formatValidationLog( '' ) ) );
		$this->assertSame( 'Errors: 0, Warnings: 0, Files affected: 0', $this->getValidationSummary( '' ) );
	}

	/**
	 * @group export
	 * @test
	 */
	public function info_only_entries_are_not_counted_as_issues(): void {
		$log = 'INFO(INF-001): ./book.epub/EPUB/chapter-001.xhtml(1,1): informational message';

		$report = $this->epub->formatValidationLog( $log );

		$this->assertSame( [ 0, 0, 0 ], $this->reportTotals( $report ) );
		$this->assertStringNotContainsString( 'INFO', $report );
	}
}
