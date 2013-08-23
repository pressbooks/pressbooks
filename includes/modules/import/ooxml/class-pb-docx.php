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

	/**
	 *
	 * @var type 
	 */
	protected $authors;

	/**
	 * 
	 */

	const DOCUMENT_SCHEMA = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument';
	const METADATA_SCHEMA = 'http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties';

	/**
	 * 
	 */
	function __construct() {

		$this->zip = new \ZipArchive();
	}

	/**
	 * 
	 * @param array $current_import
	 * @return boolean
	 */
	function import( array $current_import ) {
		try {
			$this->isValidZip( $current_import['file'] );
		} catch ( \Exception $e ) {
			return false;
		}
		// get the paths to content
		$doc_path = $this->getTargetPath( self::DOCUMENT_SCHEMA );
		$meta_path = $this->getTargetPath( self::METADATA_SCHEMA );

		// get the content
		$xml = $this->getZipContent( $doc_path );
		$meta = $this->getZipContent( $meta_path );

		// introduce a stylesheet 
		$proc = new \XSLTProcessor();
		$xsl = new \DOMDocument();
		$xsl->load( __DIR__ . '/xsl/docx2html.xsl' );
		$proc->importStylesheet( $xsl );

		// throw it back into the DOM
		$dom_doc = $proc->transformToDoc( $xml );

		$this->parseMetaData( $meta );
		$chapter_parent = $this->getChapterParent();

		foreach ( $current_import['chapters'] as $id => $chapter_title ) {
			// do nothing it has been omitted
			if ( ! $this->flaggedForImport( $id ) ) continue;

			$html = $this->parseContent( $dom_doc, $chapter_title );
			//$this->kneadAndInsert( $html, $chapter_title, $this->determinePostType( $id ), $chapter_parent );
		}

		// Done
		return $this->revokeCurrentImport();
	}
	
	protected function parseContent( \DomDocument $xml, $chapter_title ){
		echo "<pre>";
		print_r( get_defined_vars() );
		echo "</pre>";
		die();
	}
	
	/**
	 * 
	 * @param \DomDocument $meta
	 */
	protected function parseMetaData( \DomDocument $meta ) {
		$nodeList = $meta->getElementsByTagName( 'creator' );
		if ( $nodeList->item( 0 ) ) $this->authors = $nodeList->item( 0 )->nodeValue;
	}

	/**
	 * 
	 * @param array $upload
	 * @return boolean
	 */
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

		// get the path to the content and the content
		$doc_path = $this->getTargetPath( self::DOCUMENT_SCHEMA );
		$xml = $this->getZipContent( $doc_path );

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
		// check if a document.xml exists
		$path = $this->getTargetPath( self::DOCUMENT_SCHEMA );

		$ok = $this->getZipContent( $path );

		if ( ! $ok ) {
			throw new \Exception( 'Bad or corrupted _rels/.rels' );
		}
	}

	/**
	 * Give a schema, get back a path that points to the file
	 * 
	 * @param type $schema
	 * @return string
	 */
	protected function getTargetPath( $schema ) {
		$path = '';
		
		// The subfolder name "_rels", the file extension ".rels" are 
		// reserved names in an OPC package 
		$relations = simplexml_load_string(
			$this->zip->getFromName( '_rels/.rels' )
		);

		foreach ( $relations->Relationship as $rel ) {
			if ( $rel["Type"] == $schema ) {
				$path = $rel['Target'];
			}
		}
		return $path;
	}

	/**
	 * 
	 * @param type $file
	 * @param type $as_xml
	 * @return boolean|\DOMDocument
	 */
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

	/**
	 * Compliance with XTHML standards, rid cruft generated by word processors
	 *
	 * @param string $html
	 *
	 * @return string
	 */
	protected function tidy( $html ) {

		// Reduce the vulnerability for scripting attacks
		// Make XHTML 1.1 strict using htmlLawed

		$config = array (
		    'safe' => 1,
		    'valid_xhtml' => 1,
		    'no_deprecated_attr' => 2,
		    'elements' => '* -span',
		    'deny_attribute' => 'id, style',
		    'hook' => '\PressBooks\Sanitize\html5_to_xhtml11',
		);

		return htmLawed( $html, $config );
	}

}

?>
