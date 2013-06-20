<?php
/**
 * @author  PressBooks <code@pressbooks.org>
 * @license GPLv2 (or any later version)
 */
namespace PressBooks\Import\WordPress;


use PressBooks\Import\Import;
use PressBooks\Book;

class Wxr extends Import {

	/**
	 * @param array $upload
	 *
	 * @return bool
	 */
	function setCurrentImportOption( array $upload ) {

		try {
			$parser = new Parser();
			$xml = $parser->parse( $upload['file'] );
		} catch ( \Exception $e ) {
			return false;
		}

		$option = array(
			'file' => $upload['file'],
			'file_type' => $upload['type'],
			'type_of' => 'wxr',
			'chapters' => array(),
		);

		$supported_post_types = array( 'post', 'page', 'front-matter', 'chapter', 'back-matter' );

		foreach ( $xml['posts'] as $p ) {

			// Skip
			if ( ! in_array( $p['post_type'], $supported_post_types ) ) continue;
			if ( empty( $p['post_content'] ) ) continue;
			if ( '<!-- Here be dragons.-->' == $p['post_content'] ) continue;

			// Set
			$option['chapters'][$p['post_id']] = $p['post_title'];
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
			$parser = new Parser();
			$xml = $parser->parse( $current_import['file'] );
		} catch ( \Exception $e ) {
			return false;
		}

		$match_ids = array_flip( array_keys( $current_import['chapters'] ) );
		$chapter_parent = $this->getChapterParent();
		$total = 0;

		libxml_use_internal_errors( true );
		
		foreach ( $xml['posts'] as $p ) {

			// Skip
			if ( ! $this->flaggedForImport( $p['post_id'] ) ) continue;
			if ( ! isset( $match_ids[$p['post_id']] ) ) continue;

			// Insert
			$post_type = $this->determinePostType( $p['post_id'] );

			// Load HTMl snippet into DOMDocument using UTF-8 hack
			$utf8_hack = '<?xml version="1.0" encoding="UTF-8"?>';
			$doc = new \DOMDocument();
			$doc->loadHTML( $utf8_hack . $p['post_content'] );

			// Download images, change image paths
			$doc = $this->scrapeAndKneadImages( $doc );

			$html = $doc->saveXML( $doc->documentElement );

			$new_post = array (
			    'post_title' => wp_strip_all_tags( $p['post_title'] ),
			    'post_content' => $this->tidy( $html ),
			    'post_type' => $post_type,
			    'post_status' => 'draft',
			);

			if ( 'chapter' == $post_type ) {
				$new_post['post_parent'] = $chapter_parent;
			}

			$pid = wp_insert_post( $new_post );

			$section_author = $this->searchForSectionAuthor( $p['postmeta'] );

			if ( $section_author ) {
				update_post_meta( $pid, 'pb_section_author', $section_author );
			} else { // if above returns no results, take value from 'dc:creator' 
				update_post_meta( $pid, 'pb_section_author', $p['post_author'] );
			}

			update_post_meta( $pid, 'pb_show_title', 'on' );
			update_post_meta( $pid, 'pb_export', 'on' );

			Book::consolidatePost( $pid, get_post( $pid ) ); // Reorder
			++$total;
		}

		// Done
		$_SESSION['pb_notices'][] = sprintf( __( 'Imported %s chapters.', 'pressbooks' ), $total );
		return $this->revokeCurrentImport();
	}

	/**
	 * Check for PB specific metadata, returns empty string if not found.
	 * 
	 * @param array $postmeta
	 * @return string Author's name 
	 */
	protected function searchForSectionAuthor( array $postmeta ) {
		if ( ! is_array( $postmeta ) || empty( $postmeta ) ) {
			return '';
		}
		foreach ( $postmeta as $meta ) {
			// prefer this value, if it's set
			if ( 'pb_section_author' == $meta['key'] ) {
				return $meta['value'];
			}
		}
		return '';
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
	 * Load remote url of image into WP using media_handle_sideload()
	 * Will return an empty string if something went wrong.
	 *
	 * @param string $url 
	 *
	 * @see media_handle_sideload
	 *
	 * @return string filename
	 */
	protected function fetchAndSaveUniqueImage( $url ) {

		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return '';
		}
		$remote_img_location = $url;

		// Cheap cache
		static $already_done = array ( );
		if ( isset( $already_done[$remote_img_location] ) ) {
			return $already_done[$remote_img_location];
		}

		/* Process */

		$filename = array_shift( explode( '?', basename( $url ) ) ); // Basename without query string
		$filename = sanitize_file_name( urldecode( $filename ) );

		if ( ! preg_match( '/\.(jpe?g|gif|png)$/i', $filename ) ) {
			// Unsupported image type
			$already_done[$remote_img_location] = '';
			return '';
		}


		$tmp_name = $this->createTmpFile();

		file_put_contents( $tmp_name, file_get_contents( $remote_img_location ) );

		$pid = media_handle_sideload( array ( 'name' => $filename, 'tmp_name' => $tmp_name ), 0 );
		$src = wp_get_attachment_url( $pid );
		if ( ! $src ) $src = ''; // Change false to empty string
		$already_done[$remote_img_location] = $src;

		return $src;
	}

}
