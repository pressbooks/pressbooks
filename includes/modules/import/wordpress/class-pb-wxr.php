<?php
/**
 * @author  PressBooks <code@pressbooks.org>
 * @license GPLv2 (or any later version)
 */
namespace PressBooks\Import\WordPress;


use PressBooks\Import\Import;

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


	function import() {

		var_dump("UP TO HERE!");
		die();

	}



}
