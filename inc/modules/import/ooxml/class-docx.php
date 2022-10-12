<?php

/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Modules\Import\Ooxml;

use Pressbooks\Book;
use Pressbooks\Modules\Import\Import;
use Pressbooks\Utility;

class Docx extends Import {

	const TYPE_OF = 'docx';

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
	protected $fn = [];

	/**
	 * @var array
	 */
	protected $en = [];

	/**
	 * @var array
	 */
	protected $ln = [];

	/**
	 * Must not rely on all OOXML to be consistent with naming document.xml
	 *
	 * @var string
	 */
	protected $document_target = '';

	/**
	 * @var array
	 */
	protected $fn_styles = [];

	const DOCUMENT_SCHEMA = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument';
	const METADATA_SCHEMA = 'http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties';
	const IMAGE_SCHEMA = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/image';
	const FOOTNOTES_SCHEMA = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/footnotes';
	const ENDNOTES_SCHEMA = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/endnotes';
	const HYPERLINK_SCHEMA = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/hyperlink';
	const STYLESHEET_SCHEMA = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles';

	const FOOTNOTE_HREF_PATTERN = '/^#sdfootnote(\d+)sym$/';

	/**
	 *
	 */
	function __construct() {
		if ( ! function_exists( 'media_handle_sideload' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/image.php' );
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
			require_once( ABSPATH . 'wp-admin/includes/media.php' );
		}

		$this->zip = new \ZipArchive();
	}

	/**
	 *
	 * @param array $current_import
	 *
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
		$styles_path = $this->getTargetPath( self::STYLESHEET_SCHEMA, '_styles' );

		// get the content
		$xml = $this->getZipContent( $doc_path );
		$meta = $this->getZipContent( $meta_path );
		$styles = $this->getZipContent( $styles_path );

		// get all Footnote IDs from document
		$fn_ids = $this->getIDs( $xml );
		// get all Endnote IDs from document
		$en_ids = $this->getIDs( $xml, 'endnoteReference' );
		// get all Hyperlink IDs from the document
		$ln_ids = $this->getIDs( $xml, 'hyperlink', 'r:id' );

		// process the footnote ids
		if ( $fn_ids ) {
			try {
				// pass the IDs and get the content
				$this->fn = $this->getRelationshipPart( $fn_ids, 'footnotes', true );
			} catch ( \Exception $e ) {
				$this->fn_styles = [];
				$_SESSION['pb_notices'][] = $e->getMessage();
			}
		}

		// process the endnote ids
		if ( $en_ids ) {
			try {
				$this->en = $this->getRelationshipPart( $en_ids, 'endnotes' );
			} catch ( \Exception $e ) {
				$_SESSION['pb_notices'][] = $e->getMessage();
			}
		}

		// process the hyperlink ids
		if ( $ln_ids ) {
			try {
				$this->ln = $this->getRelationshipPart( $ln_ids, 'hyperlink' );
			} catch ( \Exception $e ) {
				$_SESSION['pb_notices'][] = $e->getMessage();
			}
		}

		// introduce a stylesheet
		$proc = new \XSLTProcessor();
		$xsl = new \DOMDocument();
		$xsl->load( __DIR__ . '/xsl/docx2html.xsl' );
		$proc->importStylesheet( $xsl );

		// cram the styles into the main document
		$xml->documentElement->appendChild( $xml->importNode( $styles->documentElement, true ) );

		// throw it back into the DOM
		$dom_doc = $proc->transformToDoc( $xml );

		if ( $meta ) {
			$this->parseMetaData( $meta );
		}

		$chapter_parent = $this->getChapterParent();

		foreach ( $current_import['chapters'] as $id => $chapter_title ) {
			// do nothing it has been omitted
			if ( ! $this->flaggedForImport( $id ) ) {
				continue;
			}

			$html = $this->parseContent( $dom_doc, $chapter_title );
			$this->kneadAndInsert( $html, $chapter_title, $this->determinePostType( $id ), $chapter_parent, $current_import['default_post_status'] );
		}
		// Done
		return $this->revokeCurrentImport();
	}

	/**
	 * Given a documentElement, it will return an array of ids
	 *
	 * @param \DOMDocument $dom_doc
	 * @param string $tag
	 * @param string $attr
	 *
	 * @return array
	 */
	protected function getIDs( \DOMDocument $dom_doc, $tag = 'footnoteReference', $attr = 'w:id' ) {
		$fn_ids = [];

		$doc_elem = $dom_doc->documentElement;

		$tags_fn_ref = $doc_elem->getElementsByTagName( $tag );

		// if footnotes are in the document, get the ids
		if ( $tags_fn_ref->length > 0 ) {
			foreach ( $tags_fn_ref as $id ) {
				/** @var \DOMElement $id */
				$id_attribute = $id->getAttribute( $attr );
				if ( '' !== $id_attribute ) { // don't add if its empty
					$fn_ids[] = $id_attribute;
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
	 * @param boolean $fn_styles
	 *
	 * @return array|bool
	 * @throws \Exception if there is discrepancy between the number of footnotes in document.xml and footnotes.xml
	 */
	protected function getRelationshipPart( array $ids, $tag = 'footnotes', $fn_styles = false ) {
		$footnotes = [];
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
		if ( empty( $target_path ) ) {
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
		if ( $text_tags->length !== $limit + 2 ) {
			throw new \Exception( 'mismatch between length of FootnoteReference array number of footnotes available' );
		}

		// get all the footnote ids
		// +2 to the domlist skips over two default nodes that don't contain end/footnotes
		for ( $i = 0; $i < $limit; $i++ ) {
			$footnotes[ $ids[ $i ] ] = $text_tags->item( $i + 2 )->nodeValue;
		}

		if ( $fn_styles ) {
			$this->getFootnotesStyles( $text_tags, $ids );
		}

		return $footnotes;
	}

	/**
	 * Get styles for footnotes (Italic, Bold and Underline) and save it in fn_styles property.
	 *
	 * @param array $text_tags
	 * @param array $ids
	 *
	 * @return array
	 */
	private function getFootnotesStyles( $text_tags, $ids ) {
		// for now only italic, bold and underlined: https://github.com/pressbooks/pressbooks/issues/1852#issuecomment-617268552
		$available_styles = [ 'i', 'b', 'u' ];

		$this->fn_styles = [];
		$limit = count( $ids );
		for ( $i = 0; $i < $limit; $i++ ) {
			$id_style_main = $text_tags->item( $i + 2 );
			foreach ( $available_styles as $available_style ) {
				$s = $id_style_main->getElementsByTagName( $available_style );
				$styles = [];
				if ( $s->length > 0 ) {
					$texts = [];
					for ( $j = 0; $j < $s->length; $j++ ) {
						$texts[] = $s->item( $j )->parentNode->parentNode->lastChild->nodeValue;
					}
					$styles[] = [
						'style' => $available_style,
						'texts' => $texts,
					];
				}
				if ( count( $styles ) > 0 ) {
					$this->fn_styles[ $ids[ $i ] ] = $styles;
				}
			}
		}
		return $this->fn_styles;
	}

	/**
	 * Pummel then insert HTML into our database
	 *
	 * @param string $html
	 * @param string $title
	 * @param string $post_type (front-matter', 'chapter', 'back-matter')
	 * @param int $chapter_parent
	 * @param string $post_status
	 */
	protected function kneadAndInsert( $html, $title, $post_type, $chapter_parent, $post_status ) {

		$body = $this->tidy( $html );
		$body = $this->kneadXML( $body );

		$title = wp_strip_all_tags( $title );

		$new_post = [
			'post_title' => $title,
			'post_content' => $body,
			'post_type' => $post_type,
			'post_status' => $post_status,
		];

		if ( 'chapter' === $post_type ) {
			$new_post['post_parent'] = $chapter_parent;
		}

		$pid = wp_insert_post( add_magic_quotes( $new_post ) );

		update_post_meta( $pid, 'pb_show_title', 'on' );

		Book::consolidatePost( $pid, get_post( $pid ) ); // Reorder
	}

	/**
	 * Pummel the HTML into WordPress compatible dough.
	 *
	 * @param string $body
	 *
	 * @return string - modified with correct image paths
	 */
	protected function kneadXML( $body ) {

		libxml_use_internal_errors( true );

		$doc = new \DOMDocument( '1.0', 'UTF-8' );
		$doc->loadXML( $body );

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
			/** @var \DOMElement $image */
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
		static $already_done = [];
		if ( isset( $already_done[ $img_id ] ) ) {
			return $already_done[ $img_id ];
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
			$already_done[ $img_id ] = '';

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
				$already_done[ $img_location ] = '';

				return '';
			}
		}

		$tmp_name = $this->createTmpFile();
		\Pressbooks\Utility\put_contents( $tmp_name, $image_content );

		if ( ! \Pressbooks\Image\is_valid_image( $tmp_name, $filename ) ) {

			try { // changing the file name so that extension matches the mime type
				$filename = $this->properImageExtension( $tmp_name, $filename );

				if ( ! \Pressbooks\Image\is_valid_image( $tmp_name, $filename ) ) {
					throw new \Exception( 'Image is corrupt, and file extension matches the mime type' );
				}
			} catch ( \Exception $exc ) {
				// Garbage, Don't import
				$already_done[ $img_location ] = '';

				return '';
			}
		}

		$pid = media_handle_sideload(
			[
				'name' => $filename,
				'tmp_name' => $tmp_name,
			], 0
		);
		$src = wp_get_attachment_url( $pid );
		if ( ! $src ) {
			$src = ''; // Change false to empty string
		}
		$already_done[ $img_location ] = $src;

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
		for ( $i = 0; $i < $node_list->length; $i++ ) {

			$chapter_node = $this->findTheNode( $node_list->item( $i ), $chapter_title );
			if ( ! empty( $chapter_node ) ) {
				// assumes h1 is going to be first child of parent 'html'
				$index = $i;
				break;
			}
		}

		if ( $chapter_node ) {
			$chapter_title = strtolower( preg_replace( '/\s+/', '-', $chapter_node->nodeValue ) );
		}

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

		if ( empty( $chapter_title ) ) {
			$chapter_title = 'unknown';
		}
		$chapter = new \DOMDocument( '1.0', 'UTF-8' );

		// create a new node element
		$root = $chapter->createElement( 'div' );
		$root->setAttribute( 'class', $chapter_title );

		$chapter->appendChild( $root );

		// Start at the beginning if no h1 tags are found.
		// In other words...bring in the whole document.
		( '__UNKNOWN__' === $chapter_title ) ? $i = 0 : $i = $index;

		do {
			$node = $chapter->importNode( $dom_list->item( $i ), true );
			$chapter->documentElement->appendChild( $node );
			$i++;

			// TODO
			// This is problematic
			// DOMNodeList can be made up of DOMElement(s)
			// *and* DOMText(s) which do not have the property ->tagName
		} while ( @$dom_list->item( $i )->tagName !== $this->tag && $i < $dom_list->length ); // @codingStandardsIgnoreLine

		// h1 tag will not be needed in the body of the html
		$h1 = $chapter->getElementsByTagName( $this->tag )->item( 0 );

		// removeChild is quick to throw a fatal error
		if ( $h1 && $this->tag === $h1->nodeName && 'div' === $h1->parentNode->nodeName ) {
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
	 *
	 * @return \DOMDocument
	 */
	protected function addHyperlinks( \DOMDocument $chapter ) {
		$ln = $chapter->getElementsByTagName( 'a' );

		for ( $i = $ln->length; --$i >= 0; ) {  // If you're deleting elements from within a loop, you need to loop backwards
			$link = $ln->item( $i );
			if (
				$link->hasAttribute( 'name' ) &&
				in_array( $link->getAttribute( 'name' ), [ '_GoBack' ], true )
			) {
				// Delete hidden Shift+F5 editing bookmark
				$link->parentNode->removeChild( $link );
				continue;
			}
			if ( $link->hasAttribute( 'class' ) ) {
				$ln_id = $link->getAttribute( 'class' );
				if ( array_key_exists( $ln_id, $this->ln ) ) {
					// Add external hyperlink
					$link->setAttribute( 'href', $this->ln[ $ln_id ] );
				}
			}
		}

		return $chapter;
	}

	/**
	 * adds footnotes, if they are present in the chapter
	 *
	 * @param \DOMDocument $chapter
	 *
	 * @return \DOMDocument
	 */
	protected function addFootnotes( \DOMDocument $chapter ) {
		$fn_candidates = $chapter->getelementsByTagName( 'a' );
		$fn_ids = [];
		foreach ( $fn_candidates as $fn_candidate ) {
			/** @var \DOMElement $fn_candidate */
			$href = $fn_candidate->getAttribute( 'href' );
			if ( ! empty( $href ) ) {
				$fn_matches = null;
				if ( preg_match( self::FOOTNOTE_HREF_PATTERN, $href, $fn_matches ) ) {
					$fn_ids[] = $fn_matches[1];
				}
			}
		}

		return $this->addFootnotesToDOM( $chapter, $fn_ids );

	}

	/**
	 * adds footnotes to DOM applying styles
	 *
	 * @param \DOMDocument $chapter
	 * @param array $fn_ids
	 *
	 * @return \DOMDocument
	 */
	private function addFootnotesToDOM( \DOMDocument $chapter, $fn_ids ) {
		// TODO either/or is not sufficient, needs to be built to
		// cover a use case where both are present.
		$notes = [];
		if ( ! empty( $this->fn ) ) {
			$notes = $this->fn;
		}
		if ( ! empty( $this->en ) ) {
			$notes = $this->en;
		}

		// We need to improve our way to get $fn_candidates
		if ( empty( $fn_ids ) && ! empty( $notes ) ) {
			$fn_ids = array_keys( $notes );
		}

		foreach ( $fn_ids as $id ) {
			if ( array_key_exists( $id, $notes ) ) {
				$grandparent = $chapter->createElement( 'div' );
				$grandparent->setAttribute( 'id', "sdfootnote{$id}sym" );
				$parent = $chapter->createElement( 'span' );
				$child = $chapter->createElement( 'a', $id );
				$child->setAttribute( 'href', "#sdfootnote{$id}anc" );
				$child->setAttribute( 'name', "sdfootnote{$id}sym" );
				$text = $chapter->createTextNode( $notes[ $id ] );

				// attach
				$grandparent->appendChild( $parent );
				$parent->appendChild( $child );

				if ( isset( $this->fn_styles ) && array_key_exists( $id, $this->fn_styles ) ) {
					$footnote_text = $notes[ $id ];
					foreach ( $this->fn_styles[ $id ] as $style ) {
						foreach ( $style['texts'] as $text_style ) {
							// Create style element
							$style_element = $chapter->createElement( $style['style'] );
							$text_element = $chapter->createTextNode( $text_style );
							$style_element->appendChild( $text_element );

							$e = explode( $text_style, $footnote_text );
							$position = strpos( $footnote_text, $text_style );

							if ( $position === 0 ) {
								$footnote_text = str_replace( $text_style, '', $footnote_text );
								$parent->appendChild( $style_element );
							} else {
								$text_replaced = $chapter->createTextNode( $e[0] );
								$parent->appendChild( $text_replaced );
								$parent->appendChild( $style_element );
								$footnote_text = substr( $footnote_text, $position + strlen( $text_style ) );
							}
						}
					}
					if ( strlen( $footnote_text ) > 0 ) {
						$text_replaced = $chapter->createTextNode( $footnote_text );
						$parent->appendChild( $text_replaced );
					}
				} else {
					$parent->appendChild( $text );
				}

				$chapter->documentElement->appendChild( $grandparent );
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
	 * @return \DOMNode|mixed
	 */
	protected function findTheNode( \DOMNode $node, $chapter_name ) {

		if ( XML_ELEMENT_NODE !== $node->nodeType ) {
			return '';
		}

		$current_tag = $node->tagName;
		$current_value = trim( $node->nodeValue );

		if ( $chapter_name === $current_value && $this->tag === $current_tag ) {
			return $node;
		}
		// test
		if ( $node->hasChildNodes() ) {
			$node_list = $node->childNodes;

			for ( $i = 0; $i < $node_list->length; $i++ ) {

				if ( $node_list->item( $i )->nodeType !== XML_ELEMENT_NODE ) {
					continue;
				}

				if ( $chapter_name !== $node_list->item( $i )->nodeValue && $this->tag !== $node_list->item( $i )->tagName ) {
					// recursive
					return $this->findTheNode( $node_list->item( $i ), $chapter_name );
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
		$node_list = $meta->getElementsByTagName( 'creator' );
		if ( $node_list->item( 0 ) ) {
			$this->authors = $node_list->item( 0 )->nodeValue;
		}
	}

	/**
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

		$option = [
			'file' => $upload['file'],
			'url' => $upload['url'] ?? null,
			'file_type' => $upload['type'],
			'type_of' => self::TYPE_OF,
			'chapters' => [],
		];

		$option['chapters'] = $this->getFuzzyChapterTitles();

		return update_option( 'pressbooks_current_import', $option );
	}

	/**
	 * Returns an array of available chapters, or 'unknown' if none
	 *
	 * @return array Chapter titles
	 */
	protected function getFuzzyChapterTitles() {
		$chapters = [];

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
		for ( $i = 0; $i < $headings->length; $i++ ) {
			$chapters[] = trim( $headings->item( $i )->nodeValue );
		}

		// get rid of h1 tags with empty values
		$chapters = array_values( array_filter( $chapters ) );

		// default chapter title if there are no h1 headings in the document
		if ( 0 === count( $chapters ) ) {
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

		if ( true !== $result ) {
			throw new \Exception( 'Opening docx file failed' );
		}

		// check if a document file exists
		$path = $this->getTargetPath( self::DOCUMENT_SCHEMA );

		// save document file name
		if ( $path ) {
			$this->document_target = basename( $path );
		}

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
	 *
	 * @return string|array
	 */
	protected function getTargetPath( $schema, $id = '' ) {
		// The subfolder name "_rels", the file extension ".rels" are
		// reserved names in an OPC package
		if ( empty( $id ) ) {
			$path_to_rel_doc = '_rels/.rels';
		} else {
			$path_to_rel_doc = "word/_rels/{$this->document_target}.rels";
		}

		$relations = simplexml_load_string(
			$this->zip->getFromName( $path_to_rel_doc )
		);

		$path = ( $id === 'hyperlink' ) ? [] : '';
		foreach ( $relations->Relationship as $rel ) {
			// must be cast as a string to avoid returning SimpleXml Object.
			$rel_type = (string) $rel['Type'];
			$rel_id = (string) $rel['Id'];
			$rel_target = (string) $rel['Target'];
			if ( $rel_type === $schema ) {
				switch ( (string) $id ) {
					// Array
					case 'hyperlink':
						$path[ $rel_id ] = $rel_target;
						break;
					// String
					case 'footnotes':
					case 'endnotes':
					case '_styles':
						$path = 'word/' . basename( $rel_target );
						break;
					default:
						if ( $rel_type === self::IMAGE_SCHEMA ) {
							if ( $id === $rel_id ) {
								$path = $rel_target;
								break 2; // Unique id was found, break out of foreach
							}
						} else {
							$path = $rel_target;
						}
						break;
				}
			}
		}

		if ( is_array( $path ) ) {
			foreach ( $path as $index => $p ) {
				$path[ $index ] = Utility\str_remove_prefix( $p, '/' );
			}

			return $path;
		}

		return Utility\str_remove_prefix( $path, '/' );
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

		if ( false === $index ) {
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

		$config = [
			'safe' => 1,
			'valid_xhtml' => 1,
			'xml:lang' => 1, // keep xml:lang *and* lang
			'no_deprecated_attr' => 2,
			'deny_attribute' => 'div -id',
			'hook' => '\Pressbooks\Sanitize\html5_to_xhtml11',
		];

		return \Pressbooks\HtmLawed::filter( $html, $config );
	}

}
