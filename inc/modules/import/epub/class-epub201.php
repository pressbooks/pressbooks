<?php

/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Modules\Import\Epub;

use Pressbooks\Book;
use Pressbooks\HtmlParser;
use Pressbooks\Modules\Import\Import;

class Epub201 extends Import {

	const TYPE_OF = 'epub';

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
	 * Array of manifest with type application/xhtml+xml
	 *
	 * @var array()
	 */
	protected $manifest = [];


	/**
	 *
	 */
	function __construct() {
		if ( ! function_exists( 'media_handle_sideload' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/image.php' );
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
			require_once( ABSPATH . 'wp-admin/includes/media.php' );
		}

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

		$option = [
			'file' => $upload['file'],
			'url' => $upload['url'] ?? null,
			'file_type' => $upload['type'],
			'type_of' => self::TYPE_OF,
			'chapters' => [],
		];

		$xml = $this->getOpf();

		//Format manifest to array
		$this->parseManifestToArray( $xml );

		//Iterate each spine and get each manifest item in the order of spine
		foreach ( $xml->spine->children() as $item ) {
			/** @var \SimpleXMLElement $item */
			// Get attributes
			$id = '';

			foreach ( $item->attributes() as $key => $val ) {
				if ( 'idref' === $key ) {
					$id = (string) $val;
				}
			}

			//Check this manifest item exists or not
			if ( isset( $this->manifest[ $id ] ) ) {

				$href = $this->manifest[ $id ]['herf'];

				//Check manifest item is copyright or not
				if ( 'OEBPS/copyright.html' === $href ) {
					$this->pbCheck( $href );
				}

				// Set
				// Extract title from file
				$html = $this->getZipContent( $this->basedir . $href, false );
				$matches = [];
				preg_match( '/(?:<title[^>]*>)(.+)<\/title\s*>/isU', $html, $matches );
				$title = ( ! empty( $matches[1] ) ? wp_strip_all_tags( $matches[1] ) : $id );

				$option['chapters'][ $id ] = $title;
			}
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
		$this->parseManifest( $xml, $match_ids, $chapter_parent, $current_import );

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
			if ( 'creator' === $key && ! empty( $val ) ) {
				$this->authors .= trim( $val ) . ', ';
			} elseif ( 'contributor' === $key && ! empty( $val ) ) {
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
	 * @param array $current_import
	 */
	protected function parseManifest( \SimpleXMLElement $xml, array $match_ids, $chapter_parent, $current_import ) {

		//Format manifest to array
		$this->parseManifestToArray( $xml );
		$total = 0;

		//Iterate each spine and get each manifest item in the order of spine
		foreach ( $xml->spine->children() as $item ) {
			/** @var \SimpleXMLElement $item */
			// Get attributes
			$id = '';

			foreach ( $item->attributes() as $key => $val ) {
				if ( 'idref' === $key ) {
					$id = (string) $val;
				}
			}

			//Check this manifest item exists or not
			if ( isset( $this->manifest[ $id ] ) ) {
				$href = $this->manifest[ $id ]['herf'];

				if ( 'OEBPS/copyright.html' === $href ) {
					$this->pbCheck( $href );
				}

				$href = $this->basedir . $href;

				// Skip
				if ( ! $this->flaggedForImport( $id ) ) {
					continue;
				}
				if ( ! isset( $match_ids[ $id ] ) ) {
					continue;
				}

				// Insert
				$this->kneadAndInsert( $href, $this->determinePostType( $id ), $chapter_parent, $current_import['default_post_status'] );
				++$total;
			}
		}

		$_SESSION['pb_notices'][] = sprintf( __( 'Imported %s chapters.', 'pressbooks' ), $total );
	}


	/**
	 * Return book.opf as a SimpleXML object
	 *
	 * @return \SimpleXMLElement
	 */
	protected function getOpf() {

		$container_xml = $this->getZipContent( 'META-INF/container.xml' );
		$content_path = $container_xml->rootfiles->rootfile['full-path'];

		$base = dirname( $content_path );
		if ( '.' !== $base ) {
			$this->basedir = "$base/";
		}

		return $this->getZipContent( $content_path );
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
		if ( true !== $result ) {
			throw new \Exception( 'Opening epub file failed' );
		}

		/* Safety dance */

		$ok = $this->getZipContent( 'META-INF/container.xml' );
		if ( ! $ok ) {
			throw new \Exception( 'Bad or corrupted META-INF/container.xml' );
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

		if ( false === $index ) {
			return '';
		}

		// returns the contents using its index
		$content = $this->zip->getFromIndex( $index );

		// if it's not xml, return
		if ( ! $as_xml ) {
			return $content;
		}

		// if it is xml, then instantiate and return a simplexml object
		return new \SimpleXMLElement( $content );
	}


	/**
	 * Pummel then insert HTML into our database
	 *
	 * @param string $href
	 * @param string $post_type
	 * @param int $chapter_parent
	 * @param string $post_status
	 */
	protected function kneadAndInsert( $href, $post_type, $chapter_parent, $post_status ) {

		$html = $this->getZipContent( $href, false );

		$matches = [];

		preg_match( '/(?:<title[^>]*>)(.+)<\/title\s*>/isU', $html, $matches );
		$title = ( ! empty( $matches[1] ) ? wp_strip_all_tags( $matches[1] ) : '__UNKNOWN__' );

		preg_match( '/(?:<body[^>]*>)(.*)<\/body\s*>/isU', $html, $matches );
		$body = ( isset( $matches[1] ) ) ? $this->tidy( $matches[1] ) : '';
		$body = $this->kneadHtml( $body, $post_type, $href );

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
			'hook' => '\Pressbooks\Sanitize\html5_to_xhtml11',
		];

		return \Pressbooks\HtmLawed::filter( $html, $config );
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

		$html5 = new HtmlParser();
		$dom = $html5->loadHTML( $html );

		// Download images, change to relative paths
		$dom = $this->scrapeAndKneadImages( $dom, $href );

		// Deal with <a href="">, <a href=''>, and other mutations
		$dom = $this->kneadHref( $dom, $type, $href );

		$html = $html5->saveHTML( $dom );

		// Clean up html
		$html = $this->regexSearchReplace( $html );

		return $html;
	}

	/**
	 * Cleans imported html of unwanted tags
	 *
	 * @param string $html
	 *
	 * @return string
	 */
	protected function regexSearchReplace( $html ) {
		$result = $html;
		if ( true === $this->isPbEpub ) {
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
	 * @param string $copyright_file
	 *
	 * @return boolean
	 * @see createCopyright() in /export/epub/class-pb-epub201.php
	 */
	protected function pbCheck( $copyright_file ) {
		$result = $this->getZipContent( $copyright_file );

		foreach ( $result->body->div->div->p as $node ) {

			if ( strpos( $node->a['href'][0], 'pressbooks.com', 0 ) ) {
				$this->isPbEpub = true;
			}
		}
		// applies to PB generated EPUBs with PB_SECRET_SAUCE
		// @see createCopyright() in export/epub/class-pb-epub201.php
		if ( 'copyright-page' === $result->body->div[0]->attributes()->id[0] && 'ugc' === $result->body->div->div->attributes()->class[0] ) {
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
			/** @var \DOMElement $image */
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
		$dir = ( isset( $path_parts['dirname'] ) ) ? $path_parts['dirname'] : '';
		$img_location = ( $dir ? "$dir/$url" : $url );

		// Cheap cache
		static $already_done = [];
		if ( isset( $already_done[ $img_location ] ) ) {
			return $already_done[ $img_location ];
		}

		/* Process */

		// Basename without query string
		$filename = explode( '?', basename( $url ) );
		$filename = array_shift( $filename );

		$filename = sanitize_file_name( urldecode( $filename ) );

		if ( ! preg_match( '/\.(jpe?g|gif|png)$/i', $filename ) ) {
			// Unsupported image type
			$already_done[ $img_location ] = '';
			return '';
		}

		$image_content = $this->getZipContent( "$dir/$url", false );
		if ( ! $image_content ) {
			// Could not find image?
			try {  // case where $url is '../Images/someimage.jpg'
				$trim_url = ltrim( $url, './' );
				$image_content = $this->getZipContent( $this->basedir . $trim_url, false );

				if ( ! $image_content ) {
					throw new \Exception( 'Could not import images from EPUB' );
				}
			} catch ( \Exception $e ) {
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


	/**
	* Parse manifest with type 'application/xhtml+xml' to array
	*
	* @param \SimpleXMLElement $xml
	*/
	protected function parseManifestToArray( \SimpleXMLElement $xml ) {

		foreach ( $xml->manifest->children() as $item ) {
			/** @var \SimpleXMLElement $item */
			// Get attributes
			$id = '';
			$type = '';
			$href = '';
			foreach ( $item->attributes() as $key => $val ) {
				if ( 'id' === $key ) {
					$id = (string) $val;
				} elseif ( 'media-type' === $key ) {
						$type = (string) $val;
				} elseif ( 'href' === $key ) {
						$href = $val;
				}
			}

						// Skip
			if ( 'application/xhtml+xml' !== $type ) {
				continue;
			}

			$this->manifest[ $id ] = [
				'type' => $type,
				'herf' => $href,
			];
		}

	}
}
