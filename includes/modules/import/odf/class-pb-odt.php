<?php

/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */

namespace Pressbooks\Modules\Import\Odf;


use Pressbooks\Modules\Import\Import;
use Pressbooks\Book;

class Odt extends Import {

	/**
	 * @var \ZipArchive
	 */
	protected $zip;

	/**
	 * @var string
	 */
	protected $tag = 'h1';

	/**
	 * @var string
	 */
	protected $authors;


	/**
	 *
	 */
	function __construct() {

		$this->zip = new \ZipArchive();
	}


	/**
	 * Imports content, only after options (chapters, parts) have been selected
	 *
	 * @param array $current_import
	 *
	 * @return boolean - returns false if import fails
	 */
	function import( array $current_import ) {

		try {
			$this->isValidZip( $current_import['file'] );
		} catch ( \Exception $e ) {
			return false;
		}

		$xml = $this->getZipContent( 'content.xml' );
		$meta = $this->getZipContent( 'meta.xml' );

		// introduce a stylesheet 
		$proc = new \XSLTProcessor();
		$xsl = new \DOMDocument();
		$xsl->load( __DIR__ . '/xsl/odt2html.xsl' );
		$proc->importStylesheet( $xsl );

		// throw it back into the DOM
		$dom_doc = $proc->transformToDoc( $xml );

		$this->parseMetaData( $meta );
		$chapter_parent = $this->getChapterParent();

		foreach ( $current_import['chapters'] as $id => $chapter_title ) {
			// do nothing it has been omitted
			if ( ! $this->flaggedForImport( $id ) ) continue;

			$html = $this->parseContent( $dom_doc, $chapter_title );
			$this->kneadAndInsert( $html, $chapter_title, $this->determinePostType( $id ), $chapter_parent );
		}

		// Done
		return $this->revokeCurrentImport();
	}


	/**
	 * Sets an instance variable with value(s) from metadata
	 *
	 * @param \DOMDocument $meta
	 */
	protected function parseMetaData( \DOMDocument $meta ) {

		$nodeList = $meta->getElementsByTagName( 'creator' );
		if ( $nodeList->item( 0 ) )
			$this->authors = $nodeList->item( 0 )->nodeValue;
	}


	/**
	 * Pummel then insert HTML into our database
	 *
	 * @param string $html
	 * @param string $title
	 * @param string $post_type (front-matter', 'chapter', 'back-matter')
	 * @param int $chapter_parent
	 */
	protected function kneadAndInsert( $html, $title, $post_type, $chapter_parent ) {

		$body = $this->tidy( $html );
		$body = $this->kneadHTML( $body );

		$title = wp_strip_all_tags( $title );

		$new_post = array(
			'post_title' => $title,
			'post_content' => $body,
			'post_type' => $post_type,
			'post_status' => 'draft',
		);

		if ( 'chapter' == $post_type ) {
			$new_post['post_parent'] = $chapter_parent;
		}

		$pid = wp_insert_post( add_magic_quotes( $new_post ) );

		update_post_meta( $pid, 'pb_show_title', 'on' );
		update_post_meta( $pid, 'pb_export', 'on' );

		Book::consolidatePost( $pid, get_post( $pid ) ); // Reorder		
	}


	/**
	 * Pummel the HTML into WordPress compatible dough.
	 *
	 * @param string $body
	 *
	 * @return string - modified with correct image paths
	 */
	protected function kneadHTML( $body ) {

		libxml_use_internal_errors( true );

		$old_value = libxml_disable_entity_loader( true );
		$doc = new \DOMDocument( '1.0', 'UTF-8' );
		$doc->loadXML( $body );
		libxml_disable_entity_loader( $old_value );

		// Download images, change to relative paths
		$doc = $this->scrapeAndKneadImages( $doc );

		$html = $doc->saveXML( $doc->documentElement );

		$errors = libxml_get_errors(); // TODO: Handle errors gracefully
		libxml_clear_errors();

		return $html;
	}


	/**
	 * Parse HTML snippet, save all found <img> tags using media_handle_sideload(), return the HTML with changed <img> paths.
	 *
	 * @param \DOMDocument $doc
	 *
	 * @return \DOMDocument
	 */
	protected function scrapeAndKneadImages( \DOMDocument $doc ) {

		$images = $doc->getElementsByTagName( 'img' );
		foreach ( $images as $image ) {
			// Fetch image, change src
			$old_src = $image->getAttribute( 'src' );
			$new_src = $this->fetchAndSaveUniqueImage( $old_src );
			if ( $new_src ) {
				// Replace with new image
				$image->setAttribute( 'src', $new_src );
			} else {
				// Tag broken image
				$image->setAttribute( 'src', "{$old_src}#fixme" );
			}
		}

		return $doc;
	}


	/**
	 * Extract url from zip and load into WP using media_handle_sideload()
	 * Will return an empty string if something went wrong.
	 *
	 * @param string $href original filename, with (relative) path
	 *
	 * @see media_handle_sideload
	 *
	 * @return string filename
	 * @throws \Exception
	 */
	protected function fetchAndSaveUniqueImage( $href ) {

		$img_location = $href;

		// Cheap cache
		static $already_done = array();
		if ( isset( $already_done[$img_location] ) ) {
			return $already_done[$img_location];
		}

		/* Process */

		// Basename without query string
		$filename = explode( '?', basename( $href ) );
		$filename = array_shift( $filename );

		$filename = sanitize_file_name( urldecode( $filename ) );

		if ( ! preg_match( '/\.(jpe?g|gif|png)$/i', $filename ) ) {
			// Unsupported image type
			$already_done[$img_location] = '';

			return '';
		}

		$image_content = $this->getZipContent( $img_location, false );
		if ( ! $image_content ) {
			$already_done[$img_location] = '';

			return '';
		}

		$tmp_name = $this->createTmpFile();
		file_put_contents( $tmp_name, $image_content );

		if ( ! \Pressbooks\Image\is_valid_image( $tmp_name, $filename ) ) {

			try { // changing the file name so that extension matches the mime type
				$filename = $this->properImageExtension( $tmp_name, $filename );

				if ( ! \Pressbooks\Image\is_valid_image( $tmp_name, $filename ) ) {
					throw new \Exception( 'Image is corrupt, and file extension matches the mime type' );
				}
			} catch ( \Exception $exc ) {
				// Garbage, Don't import
				$already_done[$img_location] = '';

				return '';
			}
		}

		$pid = media_handle_sideload( array( 'name' => $filename, 'tmp_name' => $tmp_name ), 0 );
		$src = wp_get_attachment_url( $pid );
		if ( ! $src ) $src = ''; // Change false to empty string
		$already_done[$img_location] = $src;

		return $src;
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

		$config = array(
			'safe' => 1,
			'valid_xhtml' => 1,
			'no_deprecated_attr' => 2,
			'elements' => '* -span',
			'deny_attribute' => 'id, style',
			'hook' => '\Pressbooks\Sanitize\html5_to_xhtml11',
		);

		return \Htmlawed::filter( $html, $config );
	}


	/**
	 * Chapter detection
	 *
	 * @param array $upload
	 *
	 * @return boolean
	 */
	function setCurrentImportOption( array $upload ) {

		try {
			$this->isValidZip( $upload['file'] );
		} catch ( \Exception $e ) {
			return false;
		}

		$option = array(
			'file' => $upload['file'],
			'file_type' => $upload['type'],
			'type_of' => 'odt',
			'chapters' => array(),
		);

		$option['chapters'] = $this->getFuzzyChapterTitles();

		return update_option( 'pressbooks_current_import', $option );
	}


	/**
	 * Checks for standard ODT file structure
	 *
	 * @param string $fullpath
	 *
	 * @throws \Exception
	 */
	protected function isValidZip( $fullpath ) {

		$result = $this->zip->open( $fullpath );

		if ( $result !== true ) {
			throw new \Exception( 'Opening odt file failed' );
		}

		$ok = $this->getZipContent( 'META-INF/manifest.xml' );

		if ( ! $ok ) {
			throw new \Exception( 'Bad or corrupted META-INF/manifest.xml' );
		}
	}


	/**
	 * Recursive iterator to locate and return a specific node, targeting child nodes
	 *
	 * @param \DOMNode $node
	 * @param string $chapter_name
	 *
	 * @return \DOMNode
	 */
	protected function findTheNode( \DOMNode $node, $chapter_name ) {

		if ( $node->nodeType !== XML_ELEMENT_NODE ) {
			return '';
		}

		$currentTag = $node->tagName;
		$currentValue = $node->nodeValue;

		if ( $chapter_name == $currentValue && $this->tag == $currentTag ) {
			return $node;
		}
		// test
		if ( $node->hasChildNodes() ) {
			$nodeList = $node->childNodes;

			for ( $i = 0; $i < $nodeList->length; $i ++ ) {

				if ( $nodeList->item( $i )->nodeType !== XML_ELEMENT_NODE ) {
					continue;
				}

				if ( $chapter_name != $nodeList->item( $i )->nodeValue && $this->tag != $nodeList->item( $i )->tagName ) {
					// recursive
					return $this->findTheNode( $nodeList->item( $i ), $chapter_name );
				}
			}
		}

		return '';
	}


	/**
	 * Find where to start, iterate through a list, add elements to a
	 * new DomDocument, return resulting xhtml
	 *
	 * @param \DOMNodeList $dom_list
	 * @param int $index
	 * @param string $chapter_title
	 *
	 * @return string XHTML
	 */
	protected function getChapter( \DOMNodeList $dom_list, $index, $chapter_title ) {

		if ( '' == $chapter_title ) $chapter_title = 'unknown';
		$chapter = new \DOMDocument( '1.0', 'UTF-8' );

		// create a new node element
		$root = $chapter->createElement( 'div' );
		$root->setAttribute( 'class', $chapter_title );

		$chapter->appendChild( $root );

		// Start at the beginning if no h1 tags are found.
		// In other words...bring in the whole document. 
		( '__UNKNOWN__' == $chapter_title ) ? $i = 0 : $i = $index;

		do {
			$node = $chapter->importNode( $dom_list->item( $i ), true );
			$chapter->documentElement->appendChild( $node );
			$i ++;

			// TODO
			// This is problematic
			// DOMNodeList can be made up of DOMElement(s)
			// *and* DOMText(s) which do not have the property ->tagName

		} while ( $this->tag != @$dom_list->item( $i )->tagName && $i < $dom_list->length );

		// h1 tag will not be needed in the body of the html
		$h1 = $chapter->getElementsByTagName( $this->tag )->item( 0 );

		// removeChild is quick to throw a fatal error
		if ( $h1 && $this->tag == $h1->nodeName && 'div' == $h1->parentNode->nodeName ) {
			$chapter->documentElement->removeChild( $h1 );
		}

		$result = $chapter->saveHTML( $chapter->documentElement );

		// appendChild brings over the namespace which is superfluous on every html element
		// the string below is from the xslt file 
		// @see includes/modules/import/odf/xsl/odt2html.xsl
		$result = preg_replace( '/xmlns="http:\/\/www.w3.org\/1999\/xhtml"/', '', $result );

		return $result;
	}


	/**
	 * Find and return the identified chapter
	 *
	 * @param \DomDocument $xml
	 * @param string $chapter_title
	 *
	 * @return string XML
	 */
	protected function parseContent( \DomDocument $xml, $chapter_title ) {

		$element = $xml->documentElement;
		$node_list = $element->childNodes;
		$chapter_node = '';
		$index = 0;

		// loop through child siblings
		for ( $i = 0; $i < $node_list->length; $i ++ ) {

			$chapter_node = $this->findTheNode( $node_list->item( $i ), $chapter_title );
			if ( $chapter_node != '' ) {
				// assumes h1 is going to be first child of parent 'html'
				$index = $i;
				break;
			}
		}

		if ( $chapter_node )
			$chapter_title = strtolower( preg_replace( '/\s+/', '-', $chapter_node->nodeValue ) );

		// iterate through
		return $this->getChapter( $node_list, $index, $chapter_title );
	}


	/**
	 * Returns an array of available chapters, or 'unknown' if none
	 *
	 * @return array Chapter titles
	 */
	protected function getFuzzyChapterTitles() {

		$chapters = array();

		$xml = $this->getZipContent( 'content.xml' );

		// introduce a stylesheet 
		$proc = new \XSLTProcessor();
		$xsl = new \DOMDocument();
		$xsl->load( __DIR__ . '/xsl/odt2html.xsl' );
		$proc->importStylesheet( $xsl );

		// throw it back into the DOM
		$dom_doc = $proc->transformToDoc( $xml );

		// get all headings
		$headings = $dom_doc->getElementsByTagName( $this->tag );

		// populate chapters with title names
		for ( $i = 0; $i < $headings->length; $i ++ ) {
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
	 * Locates an entry using its name, returns the entry contents
	 *
	 * @param string $file - path to a file
	 * @param boolean $as_xml
	 *
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
		$old_value = libxml_disable_entity_loader( true );
		$xml = new \DOMDocument();
		$xml->loadXML( $content, LIBXML_NOBLANKS | LIBXML_NOENT | LIBXML_NONET | LIBXML_XINCLUDE | LIBXML_NOERROR | LIBXML_NOWARNING );
		libxml_disable_entity_loader( $old_value );
		
		return $xml;
	}

}
