<?php

/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */

namespace Pressbooks\Modules\Import\Ooxml;

use Pressbooks\Modules\Import\Import;
use Pressbooks\Book;

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
	 * @var string
	 */
	protected $authors;
	
	/**
	 * @var array
	 */
	protected $fn = array();

	/**
	 *  @var array
	 */
	protected $en = array();
	
	/**
	 * @var array 
	 */
	protected $ln = array();

	const DOCUMENT_SCHEMA = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument';
	const METADATA_SCHEMA = 'http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties';
	const IMAGE_SCHEMA = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/image';
	const FOOTNOTES_SCHEMA = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/footnotes';
	const ENDNOTES_SCHEMA = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/endnotes';
	const HYPERLINK_SCHEMA = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/hyperlink';
	
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
		
		// get all Footnote IDs from document
		$fn_ids = $this->getIDs( $xml );
		// get all Endnote IDs from document
		$en_ids = $this->getIDs($xml, 'endnoteReference');
		// get all Hyperlink IDs from the document
		$ln_ids = $this->getIDs($xml, 'hyperlink', 'r:id');
		
		// process the footnote ids 
		if ( $fn_ids ) {
			// pass the IDs and get the content
			$this->fn = $this->getRelationshipPart( $fn_ids );
		}
	
		// process the endnote ids
		if ( $en_ids ) {
			$this->en = $this->getRelationshipPart( $en_ids, 'endnotes' );
		}
		
		// process the hyperlink ids
		if ( $ln_ids ){
			$this->ln = $this->getRelationshipPart ( $ln_ids, 'hyperlink' );
		}
		
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
			$this->kneadAndInsert( $html, $chapter_title, $this->determinePostType( $id ), $chapter_parent );
		}
		// Done
		return $this->revokeCurrentImport();
	}

	/**
	 * Given a documentElement, it will return an array of ids
	 *
	 * @param \DOMDocument $dom_doc
	 * @param string $tag
	 *
	 * @return array
	 */
	protected function getIDs( \DOMDocument $dom_doc, $tag = 'footnoteReference', $attr = 'w:id' ) {
		$fn_ids = array ();
		$doc_elem = $dom_doc->documentElement;

		$tags_fn_ref = $doc_elem->getElementsByTagName( $tag );

		// if footnotes are in the document, get the ids
		if ( $tags_fn_ref->length > 0 ) {
			foreach ( $tags_fn_ref as $id ) {
				if ( '' !== $id->getAttribute( $attr ) ) { // don't add if its empty
					$fn_ids[] = $id->getAttribute( $attr );
				}
			}
		}

		return $fn_ids;
	}

	/**
	 * Give this some ids and it returns an associative array of footnotes
	 *
	 * @param array $ids
	 * @param string $tag
	 *
	 * @return array|bool
	 * @throws \Exception if there is discrepancy between the number of footnotes in document.xml and footnotes.xml
	 */
	protected function getRelationshipPart( array $ids, $tag = 'footnotes' ) {
		$footnotes = array ();
		$tag_name = rtrim( $tag, 's' );

		// get the path for the footnotes
		switch ( $tag ) {

			case 'endnotes':
				$target_path = $this->getTargetPath( self::ENDNOTES_SCHEMA, $tag );
				break;
			case 'hyperlink':
				$target_path = $this->getTargetPath( self::HYPERLINK_SCHEMA, $tag );
				break;
			default:
				$target_path = $this->getTargetPath( self::FOOTNOTES_SCHEMA, $tag );
				break;
		}

		// safety — if there are no footnotes, return
		if ( '' == $target_path ) {
			return false;
		}

		// if they are hyperlinks
		if ( is_array( $target_path ) ) {
			return $target_path;
		}

		$limit = count( $ids );

		// set it up
		$dom_doc = $this->getZipContent( $target_path );
		$doc_elem = $dom_doc->documentElement;

		// grab all the footnotes
		$text_tags = $doc_elem->getElementsByTagName( $tag_name );


		// TODO
		// could be more sophisticated
		if ( $text_tags->length != $limit + 2 ) {
			throw new \Exception( 'mismatch between length of FootnoteReference array number of footnotes available' );
		}

		// get all the footnote ids
		// +2 to the domlist skips over two default nodes that don't contain end/footnotes 
		for ( $i = 0; $i < $limit; $i ++  ) {
			$footnotes[$ids[$i]] = $text_tags->item( $i + 2 )->nodeValue;
		}

		return $footnotes;
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

		$new_post = array (
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
	 * @param string $img_id
	 *
	 * @return string
	 */
	protected function fetchAndSaveUniqueImage( $img_id ) {

		// Cheap cache
		static $already_done = array ( );
		if ( isset( $already_done[$img_id] ) ) {
			return $already_done[$img_id];
		}

		/* Process */
		// Get target path
		$img_location = $this->getTargetPath( self::IMAGE_SCHEMA, $img_id );

		// Basename without query string
		$filename = explode( '?', basename( $img_location ) );
		$filename = array_shift( $filename );

		$filename = sanitize_file_name( urldecode( $filename ) );

		if ( ! preg_match( '/\.(jpe?g|gif|png)$/i', $filename ) ) {
			// Unsupported image type
			$already_done[$img_id] = '';

			return '';
		}

		$image_content = $this->getZipContent( $img_location, false );

		if ( ! $image_content ) {
			// try a different directory in the zip container
			try {
				$alt_img_location = 'word/' . $img_location;
				$image_content = $this->getZipContent( $alt_img_location, false );

				if ( ! $image_content ) {
					throw new \Exception( 'Image could not be retrieved in the DOCX file with Pressbooks\Import\Ooxml\fetchAndSaveUniqueImage()' );
				}
			} catch ( \Exception $exc ) {
				$this->log( $exc->getMessage() );
				$already_done[$img_location] = '';

				return '';
			}
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

		$pid = media_handle_sideload( array ( 'name' => $filename, 'tmp_name' => $tmp_name ), 0 );
		$src = wp_get_attachment_url( $pid );
		if ( ! $src ) $src = ''; // Change false to empty string
		$already_done[$img_location] = $src;

		return $src;
	}

	/**
	 * @param \DomDocument $xml
	 * @param string $chapter_title
	 *
	 * @return string
	 */
	protected function parseContent( \DomDocument $xml, $chapter_title ) {
		$element = $xml->documentElement;
		$node_list = $element->childNodes;
		$chapter_node = '';
		$index = 0;

		// loop through child siblings
		for ( $i = 0; $i < $node_list->length; $i ++  ) {

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
		
		// hyperlinks
		$chapter = $this->addHyperlinks( $chapter );

		// footnotes
		$chapter = $this->addFootnotes( $chapter );

		$result = $chapter->saveHTML( $chapter->documentElement );

		// appendChild brings over the namespace which is superfluous on every html element
		// the string below is from the xslt file 
		// @see includes/modules/import/ooxml/xsl/docx2html.xsl
		$result = preg_replace( '/xmlns="http:\/\/www.w3.org\/1999\/xhtml"/', '', $result );

		return $result;
	}

	/**
	 * adds external hyperlinks, if they are present in a chapter
	 * 
	 * @param \DOMDocument $chapter
	 * @return \DOMDocument
	 */
	protected function addHyperlinks( \DOMDocument $chapter ) {
		$ln = $chapter->getElementsByTagName( 'a' );

		if ( $ln->length > 0 ) {
			foreach ( $ln as $link ) {
				if ( $link->hasAttribute( 'class' ) ) {
					$ln_id = $link->getAttribute( 'class' );

					if ( array_key_exists( $ln_id, $this->ln ) ) {
						$link->setAttribute( 'href', $this->ln[$ln_id] );
					}
				}
			}
		}
		return $chapter;
	}

	/**
	 * adds footnotes, if they are present in the chapter
	 *
	 * @param \DOMDocument $chapter
	 * @return \DOMDocument
	 */
	protected function addFootnotes( \DOMDocument $chapter ) {

		$fn = $chapter->getElementsByTagName( 'sup' );

		if ( $fn->length > 0 ) {
			$fn_ids = array ();
			foreach ( $fn as $int ) {
				if ( is_numeric( $int->nodeValue ) ) { // TODO should be a stronger test for footnotes
					$fn_ids[] = $int->nodeValue;
				}
			}
			// append
			// TODO either/or is not sufficient, needs to be built to cover
			// a use case where both are present.
			if ( ! empty( $this->fn ) ) $notes = $this->fn;
			if ( ! empty( $this->en ) ) $notes = $this->en;

			foreach ( $fn_ids as $id ) {
				if ( array_key_exists( $id, $notes ) ) {
					$grandparent = $chapter->createElement( 'div' );
					$grandparent->setAttribute( "id", "sdfootnote{$id}sym" );
					$parent = $chapter->createElement( 'span' );
					$child = $chapter->createElement( "a", $id );
					$child->setAttribute( "href", "#sdfootnote{$id}anc" );
					$child->setAttribute( "name", "sdfootnote{$id}sym" );
					$text = $chapter->createTextNode( $notes[$id] );

					// attach 
					$grandparent->appendChild( $parent );
					$parent->appendChild( $child );
					$parent->appendChild( $text );

					$chapter->documentElement->appendChild( $grandparent );
				}
			}
		}
		return $chapter;
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
		$currentValue = trim( $node->nodeValue );

		if ( $chapter_name == $currentValue && $this->tag == $currentTag ) {
			return $node;
		}
		// test
		if ( $node->hasChildNodes() ) {
			$nodeList = $node->childNodes;

			for ( $i = 0; $i < $nodeList->length; $i ++  ) {

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
	 * Give it a schema, get back a path(s) that points to a resource
	 * 
	 * @param string $schema
	 * @param string $id
	 * @return string
	 */
	protected function getTargetPath( $schema, $id = '' ) {
		$path = '';

		// The subfolder name "_rels", the file extension ".rels" are 
		// reserved names in an OPC package 
		if ( empty( $id ) ) {
			$path_to_rel_doc = '_rels/.rels';
		} else {
			$path_to_rel_doc = 'word/_rels/document.xml.rels';
		}

		$relations = simplexml_load_string(
			$this->zip->getFromName( $path_to_rel_doc )
		);

		foreach ( $relations->Relationship as $rel ) {
			if ( $rel["Type"] == $schema ) {
				switch ( $id ) {
					// must be cast as a string to avoid returning SimpleXml Object.
					case 'footnotes':
						$path = 'word/' . ( string ) $rel['Target'];
						break;
					case 'endnotes':
						$path = 'word/' . ( string ) $rel['Target'];
						break;
					case 'hyperlink':
						$path["{$rel['Id']}"] = ( string ) $rel['Target'];
						break;
					default:
						$path = ( string ) $rel['Target'];
						break;
				}
			}
		}

		return $path;
	}

	/**
	 * Give it a path to a file and it will return
	 * the contents of that file, either as xml or html
	 *
	 * @param string $file
	 * @param bool $as_xml (optional)
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
		    'deny_attribute' => 'div -id',
		    'hook' => '\Pressbooks\Sanitize\html5_to_xhtml11',
		);

		return \Htmlawed::filter( $html, $config );
	}

}

?>
