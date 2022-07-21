<?php

namespace Pressbooks\Covergenerator;

class Spine {

	/**
	 * Constructor
	 */
	public function __construct() {

	}

	/**
	 * Spine Width Calculator
	 *
	 * Take the number of pages in your book and divide that figure by your text paper's PPI (Pages Per Inch).
	 *
	 * Where do you get the PPI? It depends on what kind of paper you're using and it usually appears on the printer's
	 * estimate or quote. If for some reason it doesn't appear there, ask the printer for it.
	 *
	 * Let's say your book has 200 pages and you are printing it on a web press using a paper which has a PPI of 400.
	 * Then the width of your book's spine will be 200 ÷ 400 or half an inch. That's for a paperback. For a hard cover
	 * book, you have to add the thickness of the boards.
	 *
	 * Heavily influenced by:
	 * http://www.selfpublishing.com/design/production-center/spine-width-calculator/
	 *
	 * @param int $pages
	 * @param int $ppi
	 *
	 * @return float Inches
	 */
	public function spineWidthCalculator( $pages, $ppi ) {

		return round( $pages / $ppi, 4 );
	}

	/**
	 * Spine Width Calculator (using Caliper)
	 *
	 * Caliper refers to the thickness of a sheet of paper expressed in thousandth of an inch.
	 *
	 * @param int $pages
	 * @param float $caliper
	 *
	 * @return float Inches
	 */
	public function spineWidthCalculatorCaliper( $pages, $caliper ) {

		return $this->spineWidthCalculator( $pages, $this->caliperToPpi( $caliper ) );
	}

	/**
	 * Caliper to PPI
	 *
	 * To determine the pages per inch (PPI), divide 2 by the caliper of the given sheet.
	 * (Round to the nearest whole number)
	 *
	 * Heavily influenced by:
	 * http://www.casepaper.com/resources/calculators/pages-per-inch/
	 *
	 * @param float $caliper
	 *
	 * @return int PPI
	 */
	public function caliperToPpi( $caliper ) {

		return round( 2 / $caliper, 0 );
	}

	/**
	 * Count the pages in the most recent PDF export
	 *
	 * @return int
	 */
	public function countPagesInMostRecentPdf() {
		$files = \Pressbooks\Utility\group_exports();

		if ( empty( $files ) ) {
			return 0;
		}

		foreach ( $files as $date => $exports ) {
			foreach ( $exports as $file ) {
				$file_extension = substr( strrchr( $file, '.' ), 1 );
				if ( 'pdf' === $file_extension ) {
					$path_to_pdf = \Pressbooks\Modules\Export\Export::getExportFolder() . $file;
					break 2;
				}
			}
		}

		if ( empty( $path_to_pdf ) ) {
			return 0;
		}

		try {
			return $this->countPagesInPdf( $path_to_pdf );
		} catch ( \Exception ) {
			return 0;
		}
	}

	/**
	 * Count the pages in a PDF file
	 *
	 * @param $path_to_pdf
	 *
	 * @return int
	 */
	public function countPagesInPdf( $path_to_pdf ) {
		if ( ! file_exists( $path_to_pdf ) ) {
			throw new \InvalidArgumentException( "File not found: $path_to_pdf" );
		}

		$output = [];
		$return_var = 0;
		$command = PB_PDFINFO_COMMAND . ' ' . escapeshellarg( $path_to_pdf ) . ' | awk \'/Pages/ {print $2}\'';

		exec( $command, $output, $return_var ); // @codingStandardsIgnoreLine

		return (int) $output[0];
	}
}
