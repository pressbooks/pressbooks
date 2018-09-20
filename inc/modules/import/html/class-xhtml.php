<?php

/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Modules\Import\Html;

use Pressbooks\Book;
use Pressbooks\Contributors;
use Pressbooks\HtmlParser;
use Pressbooks\Modules\Import\Import;

class Xhtml extends Import {

	const TYPE_OF = 'html';

	/**
	 *
	 */
	function __construct() {
		if ( ! function_exists( 'media_handle_sideload' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/image.php' );
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
			require_once( ABSPATH . 'wp-admin/includes/media.php' );
		}
	}

	/**
	 *
	 * @param array $current_import
	 *
	 * @return bool
	 */
	function import( array $current_import ) {

		$html = \Pressbooks\Utility\get_contents( $current_import['file'] );

		$html_len = strlen( $html );
		$pcre_limit = ini_get( 'pcre.backtrack_limit' );
		if ( $html_len > $pcre_limit ) {
			ini_set( 'pcre.backtrack_limit', min( $html_len, PHP_INT_MAX ) );
			if ( $html_len > ini_get( 'pcre.backtrack_limit' ) ) {
				$_SESSION['pb_errors'][] = __( 'The HTML is larger than pcre.backtrack_limit.', 'pressbooks' );
				return false;
			}
		}

		$url = wp_parse_url( $current_import['url'] );
		// get parent directory (with forward slash e.g. /parent)
		$path = isset( $url['path'] ) ? dirname( $url['path'] ) : '/';
		if ( isset( $url['scheme'], $url['host'] ) ) {
			$domain = $url['scheme'] . '://' . $url['host'] . $path;
		} else {
			$domain = $path;
		}

		// get id (there will be only one)
		$id = array_keys( $current_import['chapters'] );

		// front-matter, chapter, or back-matter
		$post_type = $this->determinePostType( $id[0] );
		$chapter_parent = $this->getChapterParent();

		$this->kneadAndInsert( $html, $post_type, $chapter_parent, $domain, $current_import['default_post_status'] );

		// Done
		return $this->revokeCurrentImport();
	}

	/**
	 * Pummel then insert HTML into our database
	 *
	 * @param string $html
	 * @param string $post_type
	 * @param int $chapter_parent
	 * @param string $domain domain name of the web page
	 * @param string $post_status
	 */
	function kneadAndInsert( $html, $post_type, $chapter_parent, $domain, $post_status ) {
		$matches = [];

		$meta = $this->getLicenseAttribution( $html );
		$author = ( isset( $meta['authors'] ) ) ? $meta['authors'] : $this->getAuthors( $html );
		$license = ( isset( $meta['license'] ) ) ? $this->extractCCLicense( $meta['license'] ) : '';

		// get the title, preference to title set by PB
		preg_match( '/<h2 class="entry-title">(.*)<\/h2>/', $html, $matches );
		if ( ! empty( $matches[1] ) ) {
			$title = wp_strip_all_tags( $matches[1] );
		} else {
			preg_match( '/<title>(.+)<\/title>/', $html, $matches );
			$title = ( ! empty( $matches[1] ) ? wp_strip_all_tags( $matches[1] ) : '__UNKNOWN__' );
		}

		// just get the body
		preg_match( '/(?:<body[^>]*>)(.*)<\/body>/isU', $html, $matches );

		// get rid of stuff we don't need
		$body = $this->regexSearchReplace( $matches[1] );

		// clean it up
		$xhtml = $this->tidy( $body );

		$body = $this->kneadHtml( $xhtml, $post_type, $domain );

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

		if ( ! empty( $author ) ) {
			( new Contributors() )->insert( $author, $pid, 'pb_authors' );
		}

		if ( ! empty( $license ) ) {
			update_post_meta( $pid, 'pb_section_license', $license );
		}

		update_post_meta( $pid, 'pb_show_title', 'on' );

		Book::consolidatePost( $pid, get_post( $pid ) ); // Reorder
	}

	/**
	 * Expects a URL string with Creative Commons domain similar in form to:
	 * http://creativecommons.org/licenses/by-sa/4.0/
	 *
	 * @param string $url
	 *
	 * @return string license meta value
	 */
	protected function extractCCLicense( $url ) {
		$license = '';

		// evaluate that it's a url
		if ( ! is_string( $url ) ) {
			return $license;
		}
		// look for creativecommons domain
		$parts = wp_parse_url( $url );

		if ( 'http' === $parts['scheme'] && 'creativecommons.org' === $parts['host'] ) {
			// extract the license information from it
			$split = explode( '/', $parts['path'] );
			if ( 'zero' === $split[2] ) {
				$license = 'cc0';
			} else {
				$license = 'cc-' . $split[2];
			}
		}

		return $license;
	}

	/**
	 * Looks for  div class created by the license module in PB, returns
	 * author and license information.
	 *
	 * @param string $html
	 *
	 * @return array $meta
	 */
	protected function getLicenseAttribution( $html ) {
		$meta = [];

		// get license attribution statement if it exists
		preg_match( '/(?:<div class="license-attribution[^>]*>)(.*)(<\/div>)/isU', $html, $matches );

		if ( ! empty( $matches[1] ) ) {
			$html5 = new HtmlParser();
			$dom = $html5->loadHTML( $matches[1] );
			$meta = $this->scrapeAndKneadMeta( $dom );
		}
		return $meta;
	}

	/**
	 * Looks for meta data in the <head> section of an HTML document.
	 * Priority is given to PB generated meta data.
	 *
	 * @param string $html
	 *
	 * @return string $authors
	 */
	protected function getAuthors( $html ) {

		// go for the book metadata set in PB <head>
		preg_match( '/(<meta itemprop="copyrightHolder" content=")(.+)(" id="copyrightHolder")>/is', $html, $matches );
		if ( empty( $matches[2] ) ) {
			// grab the authors, if copyrightHolders is empty
			preg_match( '/(<meta itemprop="author" content=")(.+)(" id="author")>/is', $html, $matches );
		}

		$authors = isset( $matches[2] ) ? $matches[2] : '';

		// final attempt, must not be a PB html page
		if ( empty( $authors ) ) {
			preg_match( '/(<meta name="author" content=")(.+)">/isU', $html, $matches );

			// get the copyright name, if author is empty
			if ( empty( $matches[1] ) ) {
				preg_match( '/(<meta name="copyright" content=")(.+)">/isU', $html, $matches );
			}
			$authors = ( ! empty( $matches[1] ) ? wp_strip_all_tags( $matches[1] ) : '' );
		}

		return $authors;
	}

	/**
	 * Cherry pick likely content areas, then cull known, unwanted content areas
	 *
	 * @param string $html
	 *
	 * @return string $html
	 */
	protected function regexSearchReplace( $html ) {

		/* cherry pick likely content areas */
		// HTML5, ungreedy
		preg_match( '/(?:<main[^>]*>)(.*)<\/main>/isU', $html, $matches );
		$html = ( ! empty( $matches[1] ) ) ? $matches[1] : $html;

		// WP content area, greedy
		preg_match( '/(?:<div id="main"[^>]*>)(.*)<\/div>/is', $html, $matches );
		$html = ( ! empty( $matches[1] ) ) ? $matches[1] : $html;

		// general content area, greedy
		preg_match( '/(?:<div id="content"[^>]*>)(.*)<\/div>/is', $html, $matches );
		$html = ( ! empty( $matches[1] ) ) ? $matches[1] : $html;

		// specific PB content area, greedy
		preg_match( '/(?:<div class="entry-content"[^>]*>)(.*)<\/div>/is', $html, $matches );
		$html = ( ! empty( $matches[1] ) ) ? $matches[1] : $html;

		/* cull */
		// get rid of script tags, ungreedy
		$result = preg_replace( '/(?:<script[^>]*>)(.*)<\/script>/isU', '', $html );
		// get rid of forms, ungreedy
		$result = preg_replace( '/(?:<form[^>]*>)(.*)<\/form>/isU', '', $result );
		// get rid of html5 nav content, ungreedy
		$result = preg_replace( '/(?:<nav[^>]*>)(.*)<\/nav>/isU', '', $result );
		// get rid of PB nav, next/previous
		$result = preg_replace( '/(?:<div class="nav"[^>]*>)(.*)<\/div>/isU', '', $result );
		// get rid of PB share buttons
		$result = preg_replace( '/(?:<div class="share-wrap-single"[^>]*>)(.*)<\/div>/isU', '', $result );
		// get rid of html5 footer content, ungreedy
		$result = preg_replace( '/(?:<footer[^>]*>)(.*)<\/footer>/isU', '', $result );
		// get rid of sidebar content, greedy
		$result = preg_replace( '/(?:<div id="sidebar\d{0,}"[^>]*>)(.*)<\/div>/is', '', $result );
		// get rid of comments, greedy
		$result = preg_replace( '/(?:<div id="comments"[^>]*>)(.*)<\/div>/is', '', $result );

		return $result;
	}

	/**
	 * Pummel the HTML into WordPress compatible dough.
	 *
	 * @param string $html
	 * @param string $type front-matter, part, chapter, back-matter, ...
	 * @param string $domain domain name of the web page
	 *
	 * @return string
	 */
	function kneadHtml( $html, $type, $domain ) {

		$html5 = new HtmlParser();
		$dom = $html5->loadHTML( $html );

		// Download images, change relative paths to absolute
		$dom = $this->scrapeAndKneadImages( $dom, $domain );

		$html = $html5->saveHTML( $dom );
		return $html;
	}

	/**
	 * Extracts section/book author and section/book license if they exist.
	 * Focus is given to CreativeCommons license information generated by PB
	 *
	 * @param \DOMDocument $doc
	 *
	 * @return array $meta
	 */
	protected function scrapeAndKneadMeta( \DOMDocument $doc ) {
		$meta = [];

		$urls = $doc->getElementsByTagName( 'a' );

		foreach ( $urls as $anchor ) {
			/** @var \DOMElement $anchor */
			$license = $anchor->getAttribute( 'rel' );
			$property = $anchor->getAttribute( 'property' );

			// expecting to find  <a href="http://creativecommons.org/licenses/by/4.0/" rel="license">
			if ( 'license' === $license ) {
				$meta['license'] = $anchor->getAttribute( 'href' );
			}

			// expecting to find  <a rel="cc:attributionURL" property="cc:attributionName" href="http://opentextbc.ca/geography/front-matter/about-the-book/" xmlns:cc="http://creativecommons.org/ns#">
			// Arthur Green, Britta Ricker, Siobhan McPhee, Aviv Ettya, Cristina Temenos</a>
			if ( 'cc:attributionName' === $property ) {
				$meta['authors'] = $anchor->nodeValue;
			}
		}

		return $meta;
	}

	/**
	 * Parse HTML snippet, save all found <img> tags using media_handle_sideload(), return the HTML with changed <img> paths.
	 *
	 * @param \DOMDocument $doc
	 * @param string $domain domain name of the web page
	 *
	 * @return \DOMDocument
	 */
	protected function scrapeAndKneadImages( \DOMDocument $doc, $domain ) {

		$images = $doc->getElementsByTagName( 'img' );
		foreach ( $images as $image ) {
			/** @var \DOMElement $image */
			// Fetch image, change src
			$old_src = $image->getAttribute( 'src' );

			// change to absolute links, if relative found
			if ( false === strpos( $old_src, 'http' ) ) {
				$old_src = $domain . '/' . $old_src;
			}

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
	 * Extract url and load into WP using media_handle_sideload()
	 * Will return an empty string if something went wrong.
	 *
	 * @param string $url
	 *
	 * @see media_handle_sideload
	 *
	 * @return string $src
	 */
	protected function fetchAndSaveUniqueImage( $url ) {

		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return '';
		}

		$remote_img_location = $url;

		// Cheap cache
		static $already_done = [];
		if ( isset( $already_done[ $remote_img_location ] ) ) {
			return $already_done[ $remote_img_location ];
		}

		/* Process */

		// Basename without query string
		$filename = explode( '?', basename( $url ) );
		$filename = array_shift( $filename );

		$filename = sanitize_file_name( urldecode( $filename ) );

		if ( ! preg_match( '/\.(jpe?g|gif|png)$/i', $filename ) ) {
			// Unsupported image type
			$already_done[ $remote_img_location ] = '';
			return '';
		}

		$tmp_name = download_url( $remote_img_location );
		if ( is_wp_error( $tmp_name ) ) {
			// Download failed
			$already_done[ $remote_img_location ] = '';
			return '';
		}

		if ( ! \Pressbooks\Image\is_valid_image( $tmp_name, $filename ) ) {

			try { // changing the file name so that extension matches the mime type
				$filename = $this->properImageExtension( $tmp_name, $filename );

				if ( ! \Pressbooks\Image\is_valid_image( $tmp_name, $filename ) ) {
					throw new \Exception( 'Image is corrupt, and file extension matches the mime type' );
				}
			} catch ( \Exception $exc ) {
				// Garbage, don't import
				$already_done[ $remote_img_location ] = '';
				unlink( $tmp_name );
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
		$already_done[ $remote_img_location ] = $src;
		@unlink( $tmp_name ); // @codingStandardsIgnoreLine

		return $src;
	}

	/**
	 *
	 * @param array $upload
	 *
	 * @return bool
	 */
	function setCurrentImportOption( array $upload ) {

		// ensure the media type is HTML (not JSON, or something we can't deal with)
		$valid_types = [
			'text/html',
			'application/xhtml+xml',
			'application/xml',
		];
		if ( str_replace( $valid_types, '', $upload['type'] ) === $upload['type'] ) {
			return false;
		}

		$body = \Pressbooks\Utility\get_contents( $upload['file'] );

		// get the title
		preg_match( '/<title>(.+)<\/title>/', $body, $matches );
		$title = ( ! empty( $matches[1] ) ? wp_strip_all_tags( $matches[1] ) : '__UNKNOWN__' );

		// set the args
		$option = [
			'file'      => $upload['file'],
			'url' => $upload['url'] ?? null,
			'file_type' => $upload['type'],
			'type_of' => self::TYPE_OF,
			'chapters'  => [],
		];

		// there will be only one chapter
		$option['chapters'][1] = $title;

		return update_option( 'pressbooks_current_import', $option );
	}

	/**
	 * Compliance with XHTML standards, rid cruft generated by word processors
	 *
	 * @param string $html
	 *
	 * @return string $html
	 */
	protected function tidy( $html ) {

		// Reduce the vulnerability for scripting attacks
		// Make XHTML 1.1 strict using htmlLawed

		$config = [
			'deny_attribute' => 'style',
			'comment' => 1,
			'safe' => 1,
			'valid_xhtml' => 1,
			'no_deprecated_attr' => 2,
		];

		return \Pressbooks\HtmLawed::filter( $html, $config );
	}

}
