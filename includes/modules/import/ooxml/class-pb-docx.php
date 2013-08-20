<?php

/**
 * @author  PressBooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */

namespace PressBooks\Import\Ooxml;

use PressBooks\Import\Import;
use PressBooks\Book;

class Docx extends Import {

	/**
	 * @var \ZipArchive
	 */
	protected $zip;

	/**
	 * @var string
	 */
	protected $tag = 'h1';

	function __construct() {

		$this->zip = new \ZipArchive();
	}

	function import( array $current_import ) {

		echo "<pre>";
		print_r( get_defined_vars() );
		echo "</pre>";
		die();
	}

	function setCurrentImportOption( array $upload ) {
		try {
			$this->isValidZip( $upload['file'] );
		} catch ( \Exception $e ) {
			return false;
		}

		$option = array (
		    'file' => $upload['file'],
		    'file_type' => $upload['type'],
		    'type_of' => 'docx',
		    'chapters' => array ( ),
		);

		$option['chapters'] = $this->getFuzzyChapterTitles();

		return update_option( 'pressbooks_current_import', $option );
	}

	/**
	 * Returns an array of available chapters, or 'unknown' if none
	 *
	 * @return array Chapter titles
	 */
	protected function getFuzzyChapterTitles() {

		$chapters = array ( );

		$xml = $this->getZipContent( 'word/document.xml' );

		// introduce a stylesheet 
		$proc = new \XSLTProcessor();
		$xsl = new \DOMDocument();
		$xsl->load( __DIR__ . '/xsl/docx2html.xsl' );
		$proc->importStylesheet( $xsl );

		// throw it back into the DOM
		$dom_doc = $proc->transformToDoc( $xml );

		// get all headings
		$headings = $dom_doc->getElementsByTagName( $this->tag );

		// populate chapters with title names
		for ( $i = 0; $i < $headings->length; $i ++  ) {
			$chapters[] = trim( $headings->item( $i )->nodeValue );
		}

		// get rid of h1 tags with empty values
		$chapters = array_values( array_filter( $chapters ) );

		// default chapter title if there are no h1 headings in the document
		if ( 0 == count( $chapters ) ) {
			$chapters[] = '__UNKNOWN__';
		}

		return $chapters;
	}

	/**
	 * Checks for standard DOCX file structure
	 *
	 * @param string $fullpath
	 *
	 * @throws \Exception
	 */
	protected function isValidZip( $fullpath ) {

		$result = $this->zip->open( $fullpath );

		if ( $result !== true ) {
			throw new \Exception( 'Opening docx file failed' );
		}

		$ok = $this->getZipContent( 'word/document.xml' );

		if ( ! $ok ) {
			throw new \Exception( 'Bad or corrupted word/document.xml' );
		}
	}

	protected function getZipContent( $file, $as_xml = true ) {
		// Locates an entry using its name
		$index = $this->zip->locateName( $file );

		if ( $index === false ) {
			return false;
		}

		// returns the contents using its index
		$content = $this->zip->getFromIndex( $index );

		// if it's not xml, return
		if ( ! $as_xml ) {
			return $content;
		}

		$collapsed = preg_replace( '/\s+/', '', $content );
		if ( preg_match( '/<!DOCTYPE/i', $collapsed ) ) {
			// Invalid XML: Detected use of illegal DOCTYPE
			return false;
		}

		// trouble with simplexmlelement and elements with dashes
		// (ODT's are ripe with dashes), so giving it to the DOM

		$xml = new \DOMDocument();
		$xml->loadXML( $content, LIBXML_NOBLANKS | LIBXML_NOENT | LIBXML_NONET | LIBXML_XINCLUDE | LIBXML_NOERROR | LIBXML_NOWARNING );

		return $xml;
	}

}

?>
