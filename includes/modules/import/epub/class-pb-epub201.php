<?php

/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */

namespace Pressbooks\Modules\Import\Epub;


use Pressbooks\Modules\Import\Import;
use Pressbooks\Book;

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
	 * String for authors, contributors
	 *
	 * @var string
	 */
	protected $authors;


	/**
	 * If Pressbooks generated the epub file
	 *
	 * @var boolean
	 */
	protected $isPbEpub = false;


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
			$id = $title = $type = $href = '';
			foreach ( $item->attributes() as $key => $val ) {
				if ( 'id' == $key ) $id = (string) $val;
				elseif ( 'media-type' == $key ) $type = (string) $val;
				elseif ( 'href' == $key && 'OEBPS/copyright.html' == $val ) $this->pbCheck( $val );
				if ( 'href' == $key ) $href = $val;
			}

			// Skip
			if ( 'application/xhtml+xml' != $type ) continue;

			// Set
			// Extract title from file
			$html = $this->getZipContent( $this->basedir . $href, false );
			$matches = array();
			preg_match( '/(?:<title[^>]*>)(.+)<\/title>/isU', $html, $matches );
			$title = ( ! empty( $matches[1] ) ? wp_strip_all_tags( $matches[1] ) : $id );
			
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

			// Set authors
			if ( 'creator' == $key && ! empty( $val ) ) {
				$this->authors .= trim( $val ) . ', ';
			} elseif ( 'contributor' == $key && ! empty( $val ) ) {
				$this->authors .= trim( $val ) . ', ';
			}

		}

		// Get rid of trailing comma
		$this->authors = rtrim( $this->authors, ', ' );
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
				elseif ( 'href' == $key ) {
					if ( 'OEBPS/copyright.html' == $val ) {
						$this->pbCheck( $val );
					}
					$href = $this->basedir . $val;
				}
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
		$index = $this->zip->locateName( urldecode( $file ) );

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

		preg_match( '/(?:<title[^>]*>)(.+)<\/title>/isU', $html, $matches );
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

		$pid = wp_insert_post( add_magic_quotes( $new_post ) );

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
			'hook' => '\Pressbooks\Sanitize\html5_to_xhtml11',
		);
		
		return \Htmlawed::filter( $html, $config );
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

		// Clean up html
		$html = $this->regexSearchReplace( $html );

		$errors = libxml_get_errors(); // TODO: Handle errors gracefully
		libxml_clear_errors();

		return $html;
	}

	/**
	 * Cleans imported html of unwanted tags
	 * 
	 * @param string $html
	 * @return string
	 */
	protected function regexSearchReplace( $html ) {

		// Remove auto-created <html> <body> and <!DOCTYPE> tags.
		$result = preg_replace( '/^<!DOCTYPE.+?>/', '', str_replace( array ( '<html>', '</html>', '<body>', '</body>' ), array ( '', '', '', '' ), $html ) );

		if ( true == $this->isPbEpub ) {
			// Remove PB created div id (on EPUB201 Export) that will generate a princexml error on re-export 
			// @see createPartsAndChapters() in export/epub/class-pb-epub201.php
			$result = preg_replace( '/(?:<div class="chapter+(.*)" id="(.*)">)/isU', '<div>', $result );
			// Remove PB generated content that is superfluous in a WP/PB environment 
			// @see createPartsAndChapters() in export/epub/class-pb-epub201.php
			$result = preg_replace( '/(?:<div class="chapter-title-wrap"[^>]*>)(.*)<\/div>/isU', '', $result );
			// Remove PB generated author content to avoid duplicate content, (it's already copied to metadata as pb_section_author )
			$result = preg_replace( '/(?:<h2 class="chapter-author"[^>]*>)(.*)<\/h2>/isU', '', $result );
			// Replace PB generated div class="ugc chapter">
			$result = preg_replace( '/(?:<div class="ugc+(.*)">)/isU', '<div>', $result );
			// Remove PB generated nonindent/indent class
			$result = preg_replace( '/(?:<p class="(.*)+indent">)/isU', '<p>', $result );
		}
		return $result;
	}


	/**
	 * Is it an EPUB generated by PB?
	 *
	 * @param string $copyrightFile
	 *
	 * @return boolean
	 * @see createCopyright() in /export/epub/class-pb-epub201.php
	 */
	protected function pbCheck( $copyrightFile ) {
		$result = $this->getZipContent( $copyrightFile );
		
		foreach ( $result->body->div->div->p as $node ) {

			if ( strpos( $node->a['href'][0], 'pressbooks.com', 0 ) ) {
				$this->isPbEpub = true;
			}
		}
		// applies to PB generated EPUBs with PB_SECRET_SAUCE 
		// @see createCopyright() in export/epub/class-pb-epub201.php
		if ( 'copyright-page' == $result->body->div[0]->attributes()->id[0] && 'ugc' == $result->body->div->div->attributes()->class[0] ) {
			$this->isPbEpub = true;
		}
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
	 * @throws \Exception
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

		// Basename without query string
		$filename = explode( '?', basename( $url ) );
		$filename = array_shift( $filename );

		$filename = sanitize_file_name( urldecode( $filename ) );

		if ( ! preg_match( '/\.(jpe?g|gif|png)$/i', $filename ) ) {
			// Unsupported image type
			$already_done[$img_location] = '';
			return '';
		}
	
		$image_content = $this->getZipContent( "$dir/$url", false );
		if ( ! $image_content ) {
			// Could not find image?
			try {  // case where $url is '../Images/someimage.jpg'
				$trimUrl = ltrim( $url, './' );
				$image_content = $this->getZipContent( $this->basedir . $trimUrl, false );

				if ( ! $image_content ) throw new \Exception( 'Could not import images from EPUB' );
			} catch ( \Exception $e ) {
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
