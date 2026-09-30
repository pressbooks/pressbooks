<?php
/**
 * Parses raw epubcheck output and renders it as a human-readable report.
 *
 * @package Pressbooks
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Modules\Export\Epub;

use Illuminate\Support\Collection;

class EpubcheckLog {

	/**
	 * Matches any epubcheck message line, e.g. `ERROR(RSC-005): <detail>`.
	 */
	private const LINE_PATTERN = '/^(FATAL|ERROR|WARNING|INFO|USAGE)\(([A-Z]{3}-\d{3}\w?)\):\s*(.*)$/';

	/**
	 * Extracts `filename(line,column): message` from a message detail, any file extension.
	 */
	private const LOCATION_PATTERN = '/\/([^\/()]+)\((\d+),(\d+)\):\s*(.*)$/';

	private const ERROR_SEVERITIES = [ 'FATAL', 'ERROR' ];

	/**
	 * Group for messages that carry no file reference, so nothing is ever dropped.
	 */
	private const PACKAGE_GROUP = '(package)';

	private Collection $issues;

	/**
	 * @param string $raw_log Raw epubcheck output, with literal '\n' separators (as produced by Epub::validate()) or real newlines
	 */
	public function __construct( string $raw_log ) {
		$this->issues = collect( explode( "\n", str_replace( '\n', "\n", $raw_log ) ) )
			->map( fn( string $line ) => $this->parseLine( trim( $line ) ) )
			->filter()
			->values();
	}

	/**
	 * Full validation report: issues grouped per file, verbatim messages with locations, totals.
	 */
	public function report(): string {
		/* translators: "EPUB" is a file format name and should not be translated */
		$report = __( 'EPUB VALIDATION REPORT', 'pressbooks' ) . "\n";
		$report .= str_repeat( '=', 50 ) . "\n\n";

		$report .= $this->issues
			->groupBy( 'file' )
			->filter( fn( Collection $issues ) => $issues->some( fn( array $issue ) => $this->isError( $issue ) || $this->isWarning( $issue ) ) )
			->map( fn( Collection $issues, string $file ) => $this->fileSection( $file, $issues ) )
			->implode( '' );

		return $report . $this->totals();
	}

	/**
	 * One-line summary, guaranteed to match the report totals.
	 */
	public function summary(): string {
		/* translators: 1: number of errors, 2: number of warnings, 3: number of files affected */
		return sprintf(
			__( 'Errors: %1$s, Warnings: %2$s, Files affected: %3$s', 'pressbooks' ),
			$this->errorCount(),
			$this->warningCount(),
			$this->affectedFileCount()
		);
	}

	/**
	 * @return array{severity: string, code: string, file: string, line: int, column: int, message: string}|null
	 */
	private function parseLine( string $line ): ?array {
		if ( ! preg_match( self::LINE_PATTERN, $line, $matches ) ) {
			return null;
		}

		$issue = [
			'severity' => $matches[1],
			'code' => $matches[2],
			'file' => self::PACKAGE_GROUP,
			'line' => 0,
			'column' => 0,
			'message' => $matches[3],
		];

		if ( preg_match( self::LOCATION_PATTERN, $issue['message'], $location ) ) {
			$issue['file'] = $location[1];
			$issue['line'] = (int) $location[2];
			$issue['column'] = (int) $location[3];
			$issue['message'] = $location[4];
		}

		return $issue;
	}

	private function isError( array $issue ): bool {
		return in_array( $issue['severity'], self::ERROR_SEVERITIES, true );
	}

	private function isWarning( array $issue ): bool {
		return $issue['severity'] === 'WARNING';
	}

	private function errorCount(): int {
		return $this->issues->filter( fn( array $issue ) => $this->isError( $issue ) )->count();
	}

	private function warningCount(): int {
		return $this->issues->filter( fn( array $issue ) => $this->isWarning( $issue ) )->count();
	}

	private function affectedFileCount(): int {
		return $this->issues
			->filter( fn( array $issue ) => $this->isError( $issue ) || $this->isWarning( $issue ) )
			->unique( 'file' )
			->count();
	}

	private function fileSection( string $file, Collection $issues ): string {
		$section = sprintf( __( 'FILE: %s', 'pressbooks' ), $file ) . "\n";
		/* translators: 1: number of errors, 2: number of warnings */
		$section .= sprintf(
			__( 'Errors: %1$s | Warnings: %2$s', 'pressbooks' ),
			$issues->filter( fn( array $issue ) => $this->isError( $issue ) )->count(),
			$issues->filter( fn( array $issue ) => $this->isWarning( $issue ) )->count()
		) . "\n";
		$section .= str_repeat( '-', 40 ) . "\n";
		$section .= $issues->map( fn( array $issue ) => $this->issueLine( $issue ) )->implode( '' );

		return $section . "\n";
	}

	private function issueLine( array $issue ): string {
		$location = '';
		if ( $issue['line'] > 0 ) {
			/* translators: 1: line number, 2: column number */
			$location = ' ' . sprintf( __( 'Line %1$s, Col %2$s', 'pressbooks' ), $issue['line'], $issue['column'] ) . ':';
		}

		return "  [{$issue['severity']} {$issue['code']}]{$location} {$issue['message']}\n";
	}

	private function totals(): string {
		$totals = str_repeat( '=', 50 ) . "\n";
		$totals .= __( 'SUMMARY', 'pressbooks' ) . "\n";
		/* translators: %s: number of errors */
		$totals .= sprintf( __( 'Total Errors: %s', 'pressbooks' ), $this->errorCount() ) . "\n";
		/* translators: %s: number of warnings */
		$totals .= sprintf( __( 'Total Warnings: %s', 'pressbooks' ), $this->warningCount() ) . "\n";
		/* translators: %s: number of files affected */
		$totals .= sprintf( __( 'Files Affected: %s', 'pressbooks' ), $this->affectedFileCount() ) . "\n";

		return $totals;
	}
}
