<?php

/**
 * @author  PressBooks <code@pressbooks.org>
 * @license GPLv2 (or any later version)
 */

namespace PressBooks\Import\Epub;


use PressBooks\Import\Import;
use PressBooks\Book;

class Epub201 extends Import {


	/**
	 * Reference to the object that represents the Epub zip folder
	 *
	 * @var \ZipArchive
	 */
	protected $zip;


	/**
	 * @var string
	 */
	protected $basedir = '/';


	/**
	 * Constructor requires a file to import, validates it, unzips contents of the epub
	 * and puts it in a temporary directory.
	 *
	 * @param string $filename
	 * @param string $selective_import- user wants to choose which chapters to bring in
	 *
	 * @throws \Exception
	 */
	function __construct() {

		$this->zip = new \ZipArchive;
	}


	/**
	 * @param array $upload
	 *
	 * @return bool
	 */
	function setCurrentImportOption( array $upload ) {

		try {
			$this->setCurrentZip( $upload['file'] );
		} catch ( \Exception $e ) {
			// TODO: Do something with exception
		}

		$option = array(
			'file' => $upload['file'],
			'file_type' => $upload['type'],
			'type_of' => 'epub',
			'chapters' => array(),
		);

		$xml = $this->getOpf();
		foreach ( $xml->manifest->children() as $item ) {

			// Get attributes
			$id = $title = $type = '';
			foreach ( $item->attributes() as $key => $val ) {
				if ( 'id' == $key ) $id = (string) $val;
				else if ( 'media-type' == $key ) $type = (string) $val;
			}

			// Skip
			if ( 'application/xhtml+xml' != $type ) continue;

			// Set
			$title = $id; // TODO: Get real title
			$option['chapters'][$id] = $title;
		}

		return update_option( 'pressbooks_current_import', $option );
	}


	/**
	 * @param array $current_import
	 *
	 * @return bool
	 */
	function import( array $current_import ) {

		try {
			$this->setCurrentZip( $current_import['file'] );
		} catch ( \Exception $e ) {
			// TODO: Do something with exception
		}

		$match_ids = array_flip( array_keys( $current_import['chapters'] ) );
		$chapter_parent = $this->getChapterParent();

		$xml = $this->getOpf();

		// TODO: $xml->metadata->children()

		foreach ( $xml->manifest->children() as $item ) {

			// Get attributes
			$id = $href = '';
			foreach ( $item->attributes() as $key => $val ) {
				if ( 'id' == $key ) $id = (string) $val;
				elseif ( 'href' == $key ) $href = (string) $val;
			}

			// Skip
			if ( ! $this->flaggedForImport( $id ) ) continue;
			if ( ! isset( $match_ids[$id] ) ) continue;

			// Insert
			$this->kneadAndInsert( $href, $this->determinePostType( $id ), $chapter_parent );
		}

		// Done
		return $this->revokeCurrentImport();
	}


	/**
	 * Get the OPF, set basedir
	 *
	 * @return \SimpleXMLElement
	 */
	protected function getOpf() {

		$containerXml = $this->getZipContent( 'META-INF/container.xml' );
		$contentPath = $containerXml->rootfiles->rootfile['full-path'];
		$this->basedir = dirname( $contentPath ) . '/';

		return $this->getZipContent( $contentPath );
	}


	/**
	 * @param string $fullpath
	 *
	 * @throws \Exception
	 */
	protected function setCurrentZip( $fullpath ) {

		$result = $this->zip->open( $fullpath );
		if ( $result !== true ) {
			throw new \Exception ( 'Opening epub file failed' );
		}

		// Safety dance
		$mimetype = $this->getZipContent( 'mimetype', false );
		if ( $mimetype != 'application/epub+zip' ) {
			throw new \Exception ( 'Wrong mimetype!' );
		}

	}


	/**
	 * @param $file
	 * @param bool $as_xml
	 *
	 * @return mixed|\SimpleXMLElement
	 * @throws \Exception
	 */
	protected function getZipContent( $file, $as_xml = true ) {

		// Locates an entry using its name
		$index = $this->zip->locateName( $file );

		if ( $index === false ) {
			throw new \Exception ( 'file [' . $file . '] not found' );
		}

		// returns the contents using its index
		$content = $this->zip->getFromIndex( $index );

		// if it's not xml, return
		if ( ! $as_xml ) {
			return $content;
		}

		// if it is xml, then instantiate and return a simplexml object
		return new \SimpleXMLElement ( $content );
	}


	/**
	 * @param string $href
	 * @param string $post_type
	 * @param int $chapter_parent
	 */
	protected function kneadAndInsert( $href, $post_type, $chapter_parent ) {

		$html = $this->getZipContent( $href, false );

		$matches = array();

		preg_match( '/<title>(.+)<\/title>/', $html, $matches );
		$title = ( ! empty( $matches[1] ) ? wp_strip_all_tags( $matches[1] ) : '__UNKNOWN__' );

		preg_match( '/(?:<body[^>]*>)(.*)<\/body>/isU', $html, $matches );
		$body = $this->tidy( @$matches[1] );

		// TODO: Pummel images found in $body

		$new_post = array(
			'post_title' => $title,
			'post_content' => $body,
			'post_type' => $post_type,
			'post_status' => 'draft',
		);

		if ( 'chapter' == $post_type ) {
			$new_post['post_parent'] = $chapter_parent;
		}

		$pid = wp_insert_post( $new_post );

		// update_post_meta( $pid, 'pb_section_author', $foo ); // TODO
		update_post_meta( $pid, 'pb_show_title', 'on' );

		Book::consolidatePost( $pid, get_post( $pid ) ); // Reorder
	}


	/**
	 * @param string $html
	 *
	 * @return string
	 */
	protected function tidy( $html ) {

		// Make XHTML 1.1 strict using htmlLawed

		$config = array(
			'safe' => 1,
			'valid_xhtml' => 1,
			'no_deprecated_attr' => 2,
			'hook' => '\PressBooks\Sanitize\html5_to_xhtml11',
		);

		return htmLawed( $html, $config );
	}


}
