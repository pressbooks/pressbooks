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

		$parser = new Parser();
		$xml = $parser->parse( $upload['file'] );

		if ( is_wp_error( $xml ) ) {
			// echo $xml->get_error_message();
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


		$parser = new Parser();
		$xml = $parser->parse( $current_import['file'] );

		if ( is_wp_error( $xml ) ) {
			// echo $xml->get_error_message();
			return false;
		}

		$match_ids = array_flip( array_keys( $current_import['chapters'] ) );

		$q = new \WP_Query();

		$args = array(
			'post_type' => 'part',
			'posts_per_page' => 1,
			'orderby' => 'menu_order',
			'order' => 'ASC',
			'no_found_rows' => true,
		);

		$results = $q->query( $args );

		$chapter_parent = $results[0]->ID;

		foreach ( $xml['posts'] as $p ) {

			// Skip
			if ( ! $this->flaggedForImport( $p['post_id'] ) ) continue;
			if ( ! isset( $match_ids[$p['post_id']] ) ) continue;

			// Insert

			$post_type = $this->determinePostType( $p['post_id'] );

			$new_post = array(
				'post_title' => wp_strip_all_tags( $p['post_title'] ),
				'post_content' => $this->tidy( $p['post_content'] ),
				'post_type' => $post_type,
				'post_status' => 'draft',
			);

			if ( 'chapter' == $post_type ) {
				$new_post['post_parent'] = $chapter_parent;
			}

			$pid = wp_insert_post( $new_post );
			Book::consolidatePost( $pid, get_post( $pid ) ); // Reorder
		}

		// Done
		return $this->revokeCurrentImport();
	}



}
