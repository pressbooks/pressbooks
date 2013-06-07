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
	 * OPF Basedir
	 *
	 * @var string
	 */
	protected $basedir = '';


	/**
	 * @var string
	 */
	protected $rights;


	/**
	 *
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
			return false;
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
			return false;
		}

		$xml = $this->getOpf();
		$match_ids = array_flip( array_keys( $current_import['chapters'] ) );
		$chapter_parent = $this->getChapterParent();

		$this->parseMetadata( $xml );
		$this->parseManifest( $xml, $match_ids, $chapter_parent );

		// Done
		return $this->revokeCurrentImport();
	}


	/**
	 * Parse OPF metadata nodes
	 *
	 * @param \SimpleXMLElement $xml
	 */
	protected function parseMetadata( \SimpleXMLElement $xml ) {

		foreach ( $xml->metadata->children( 'dc', true ) as $key => $val ) {

			$val = (string) $val;

			// Set rights
			if ( 'creator' == $key && ! empty( $val ) ) {
				$this->rights .= trim( $val ) . ', ';
			} elseif ( 'rights' == $key && ! empty( $val ) ) {
				$this->rights .= trim( $val ) . ', ';
			}

		}

		// Get rid of trailing comma
		$this->rights = rtrim( $this->rights, ', ' );
	}


	/**
	 * Parse OPF manifest nodes
	 *
	 * @param \SimpleXMLElement $xml
	 * @param array $match_ids
	 * @param $chapter_parent
	 */
	protected function parseManifest( \SimpleXMLElement $xml, array $match_ids, $chapter_parent ) {

		$total = 0;
		foreach ( $xml->manifest->children() as $item ) {

			// Get attributes
			$id = $href = '';
			foreach ( $item->attributes() as $key => $val ) {
				if ( 'id' == $key ) $id = (string) $val;
				elseif ( 'href' == $key ) $href = $this->basedir . $val;
			}

			// Skip
			if ( ! $this->flaggedForImport( $id ) ) continue;
			if ( ! isset( $match_ids[$id] ) ) continue;

			// Insert
			$this->kneadAndInsert( $href, $this->determinePostType( $id ), $chapter_parent );
			++$total;
		}

		$_SESSION['pb_notices'][] = sprintf( __( 'Imported %s chapters.', 'pressbooks' ), $total );
	}


	/**
	 * Return book.opf as a SimpleXML object
	 *
	 * @return \SimpleXMLElement
	 */
	protected function getOpf() {

		$containerXml = $this->getZipContent( 'META-INF/container.xml' );
		$contentPath = $containerXml->rootfiles->rootfile['full-path'];

		$base = dirname( $contentPath );
		if ( '.' != $base ) {
			$this->basedir = "$base/";
		}

		return $this->getZipContent( $contentPath );
	}


	/**
	 * Opens a new Epub for reading, writing or modifying
	 *
	 * @param string $fullpath
	 *
	 * @throws \Exception
	 */
	protected function setCurrentZip( $fullpath ) {

		$result = $this->zip->open( $fullpath );
		if ( $result !== true ) {
			throw new \Exception ( 'Opening epub file failed' );
		}

		/* Safety dance */

		/*
		// TODO: Do we need this? Some EPUBs are garbage...
		$mimetype = $this->getZipContent( 'mimetype', false );
		if ( $mimetype != 'application/epub+zip' ) {
			throw new \Exception ( 'Wrong mimetype!' );
		}
		*/

		$ok = $this->getZipContent( 'META-INF/container.xml' );
		if ( ! $ok ) {
			throw new \Exception ( 'Bad or corrupted META-INF/container.xml' );
		}

	}


	/**
	 * Locates an entry using its name, returns the entry contents
	 *
	 * @param $file
	 * @param bool $as_xml
	 *
	 * @return string|\SimpleXMLElement
	 */
	protected function getZipContent( $file, $as_xml = true ) {

		// Locates an entry using its name
		$index = $this->zip->locateName( $file );

		if ( $index === false ) {
			return '';
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
	 * Pummel then insert HTML into our database
	 *
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
		$body = $this->kneadHtml( $body, $post_type, $href );

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

		if ( $this->rights ) {
			// TODO
			// update_post_meta( $pid, 'pb_section_author', $this->rights );
		}

		update_post_meta( $pid, 'pb_show_title', 'on' );
		update_post_meta( $pid, 'pb_export', 'on' );

		Book::consolidatePost( $pid, get_post( $pid ) ); // Reorder
	}


	/**
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
			'hook' => '\PressBooks\Sanitize\html5_to_xhtml11',
		);

		return htmLawed( $html, $config );
	}


	/**
	 * Pummel the HTML into WordPress compatible dough.
	 *
	 * @param string $html
	 * @param string $type front-matter, part, chapter, back-matter, ...
	 * @param string $href original filename, with (relative) path
	 *
	 * @return string
	 */
	protected function kneadHtml( $html, $type, $href ) {

		libxml_use_internal_errors( true );

		// Load HTMl snippet into DOMDocument using UTF-8 hack
		$utf8_hack = '<?xml version="1.0" encoding="UTF-8"?>';
		$doc = new \DOMDocument();
		$doc->loadHTML( $utf8_hack . $html );

		// Download images, change to relative paths
		$doc = $this->scrapeAndKneadImages( $doc, $href );

		// Deal with <a href="">, <a href=''>, and other mutations
		$doc = $this->kneadHref( $doc, $type, $href );

		// If you are storing multi-byte characters in XML, then saving the XML using saveXML() will create problems.
		// Ie. It will spit out the characters converted in encoded format. Instead do the following:
		$html = $doc->saveXML( $doc->documentElement );

		// Remove auto-created <html> <body> and <!DOCTYPE> tags.
		$html = preg_replace( '/^<!DOCTYPE.+?>/', '', str_replace( array( '<html>', '</html>', '<body>', '</body>' ), array( '', '', '', '' ), $html ) );
        // Remove PB created div id (on EPUB201 Export) that will generate a princexml error on re-export 
        // @see createPartsAndChapters() in export/epub/class-pb-epub201.php
        $html = preg_replace('/(?:<div class="chapter" id="(.*)">)/isU', '<div class="chapter">', $html);
        // Remove PB generated content that is superfluous in a WP/PB environment 
        // @see createPartsAndChapters() in export/epub/class-pb-epub201.php
        $html = preg_replace('/(?:<div class="chapter-title-wrap"[^>]*>)(.*)<\/div>/isU', '', $html);

       	$errors = libxml_get_errors(); // TODO: Handle errors gracefully
		libxml_clear_errors();

		return $html;
	}


	/**
	 * Parse HTML snippet, save all found <img> tags using media_handle_sideload(), return the HTML with changed <img> paths.
	 *
	 * @param \DOMDocument $doc
	 * @param string $href original filename, with (relative) path
	 *
	 * @return \DOMDocument
	 */
	protected function scrapeAndKneadImages( \DOMDocument $doc, $href ) {

		$images = $doc->getElementsByTagName( 'img' );
		foreach ( $images as $image ) {
			// Fetch image, change src
			$old_src = $image->getAttribute( 'src' );
			$new_src = $this->fetchAndSaveUniqueImage( $old_src, $href );
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
	 * @param $url         string
	 * @param string $href original filename, with (relative) path
	 *
	 * @see media_handle_sideload
	 *
	 * @return string filename
	 */
	protected function fetchAndSaveUniqueImage( $url, $href ) {

		$path_parts = pathinfo( $href );
		$dir = @$path_parts['dirname'];
		$img_location = ( $dir ? "$dir/$url" : $url );

		// Cheap cache
		static $already_done = array();
		if ( isset( $already_done[$img_location] ) ) {
			return $already_done[$img_location];
		}

		/* Process */

		$filename = array_shift( explode( '?', basename( $url ) ) ); // Basename without query string
		$filename = sanitize_file_name( urldecode( $filename ) );

		if ( ! preg_match( '/\.(jpe?g|gif|png)$/i', $filename ) ) {
			// Unsupported image type
			$already_done[$img_location] = '';
			return '';
		}

		$image_content = $this->getZipContent( "$dir/$url", false );
		if ( ! $image_content ) {
			// Could not find image?
			$already_done[$img_location] = '';
			return '';
		}

		$tmp_name = $this->createTmpFile();
		file_put_contents( $tmp_name, $image_content );

		$pid = media_handle_sideload( array( 'name' => $filename, 'tmp_name' => $tmp_name ), 0 );
		$src = wp_get_attachment_url( $pid );
		if ( ! $src ) $src = ''; // Change false to empty string
		$already_done[$img_location] = $src;

		return $src;
	}


	/**
	 * Change hrefs
	 *
	 * @param \DOMDocument $doc
	 * @param string $type front-matter, part, chapter, back-matter, ...
	 * @param string $href original filename, with (relative) path
	 *
	 * @return \DOMDocument
	 */
	protected function kneadHref( \DOMDocument $doc, $type, $href ) {

		// TODO: Fix self-referencing URLs

		return $doc;
	}



}
