<?php

/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Modules\Import\Odf;

use Pressbooks\Book;
use Pressbooks\Modules\Import\Import;

class Odt extends Import {
	public const TYPE_OF = 'odt';

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

	public function __construct() {
		if ( ! function_exists( 'media_handle_sideload' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/image.php' );
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
			require_once( ABSPATH . 'wp-admin/includes/media.php' );
		}

		$this->zip = new \ZipArchive();
	}

	/**
	 * Imports content, only after options (chapters, parts) have been selected
	 *
	 * @param array $current_import
	 * @return bool - returns false if import fails
	 */
	public function import( array $current_import ): bool {
		try {
			$this->isValidZip( $current_import['file'] );
		} catch ( \Exception ) {
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
	 * Sets an instance variable with value(s) from metadata
	 *
	 * @param \DOMDocument $meta
	 */
	protected function parseMetaData( \DOMDocument $meta ) {
		$node_list = $meta->getElementsByTagName( 'creator' );
		if ( $node_list->item( 0 ) ) {
			$this->authors = $node_list->item( 0 )->nodeValue;
		}
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
		$body = $this->kneadHTML( $body );

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
	 * @return \DOMDocument
	 */
	protected function scrapeAndKneadImages( \DOMDocument $doc ): \DOMDocument {
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
	 * @param string $href original filename, with (relative) path
	 *
	 * @see media_handle_sideload
	 *
	 * @return string filename
	 */
	protected function fetchAndSaveUniqueImage( $href ) {

		$img_location = $href;

		// Cheap cache
		static $already_done = [];
		if ( isset( $already_done[ $img_location ] ) ) {
			return $already_done[ $img_location ];
		}

		/* Process */

		// Basename without query string
		$filename = explode( '?', basename( $href ) );
		$filename = array_shift( $filename );

		$filename = sanitize_file_name( urldecode( $filename ) );

		if ( ! preg_match( '/\.(jpe?g|gif|png)$/i', $filename ) ) {
			// Unsupported image type
			$already_done[ $img_location ] = '';

			return '';
		}

		$image_content = $this->getZipContent( $img_location, false );
		if ( ! $image_content ) {
			$already_done[ $img_location ] = '';

			return '';
		}

		$tmp_name = $this->createTmpFile();
		\Pressbooks\Utility\put_contents( $tmp_name, $image_content );

		if ( ! \Pressbooks\Image\is_valid_image( $tmp_name, $filename ) ) {

			try { // changing the file name so that extension matches the mime type
				$filename = $this->properImageExtension( $tmp_name, $filename );

				if ( ! \Pressbooks\Image\is_valid_image( $tmp_name, $filename ) ) {
					throw new \Exception( 'Image is corrupt, and file extension matches the mime type' );
				}
			} catch ( \Exception ) {
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
			'no_deprecated_attr' => 2,
			'elements' => '* -span',
			'deny_attribute' => 'id, style',
			'hook' => '\Pressbooks\Sanitize\html5_to_xhtml11',
		];

		return \Pressbooks\HtmLawed::filter( $html, $config );
	}

	/**
	 * Chapter detection
	 *
	 * @param array $upload
	 * @return bool
	 */
	public function setCurrentImportOption( array $upload ): bool {
		try {
			$this->isValidZip( $upload['file'] );
		} catch ( \Exception ) {
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
	 * Checks for standard ODT file structure
	 *
	 * @param string $fullpath
	 *
	 * @throws \Exception
	 */
	protected function isValidZip( $fullpath ) {

		$result = $this->zip->open( $fullpath );

		if ( true !== $result ) {
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
	 * @return \DOMNode|mixed
	 */
	protected function findTheNode( \DOMNode $node, string $chapter_name ) {
		if ( XML_ELEMENT_NODE !== $node->nodeType ) {
			return '';
		}

		$current_tag = $node->tagName;
		$current_value = $node->nodeValue;

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
	 * Find where to start, iterate through a list, add elements to a
	 * new DomDocument, return resulting xhtml
	 *
	 * @param \DOMNodeList $dom_list
	 * @param int $index
	 * @param string $chapter_title
	 * @return string XHTML
	 * @throws \DOMException
	 */
	protected function getChapter( \DOMNodeList $dom_list, int $index, string $chapter_title ): string {
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

		} while ( @$dom_list->item( $i )->tagName != $this->tag && $i < $dom_list->length ); // @codingStandardsIgnoreLine

		// h1 tag will not be needed in the body of the html
		$h1 = $chapter->getElementsByTagName( $this->tag )->item( 0 );

		// removeChild is quick to throw a fatal error
		if ( $h1 && $this->tag === $h1->nodeName && 'div' === $h1->parentNode->nodeName ) {
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
	 * @param \DOMDocument
	 * @param string $chapter_title
	 * @return string XML
	 * @throws \DOMException
	 */
	protected function parseContent( \DOMDocument $xml, $chapter_title ): string {
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
	 * Returns an array of available chapters, or 'unknown' if none
	 *
	 * @return array Chapter titles
	 */
	protected function getFuzzyChapterTitles() {

		$chapters = [];

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
	 * Locates an entry using its name, returns the entry contents
	 *
	 * @param string $file - path to a file
	 * @param bool $as_xml
	 * @return bool|\DOMDocument
	 */
	protected function getZipContent( string $file, bool $as_xml = true ): bool | \DOMDocument {
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
		$old_value = libxml_disable_entity_loader( true );
		$xml = new \DOMDocument();
		$xml->loadXML( $content, LIBXML_NOBLANKS | LIBXML_NOENT | LIBXML_NONET | LIBXML_XINCLUDE | LIBXML_NOERROR | LIBXML_NOWARNING );
		libxml_disable_entity_loader( $old_value );

		return $xml;
	}

}
