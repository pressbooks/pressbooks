<?php
/**
 * Cloning functions.
 *
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */

namespace Pressbooks\Cloner;

function clone_section( $section_id, $source_book_id, $target_book_id, $target_parent_id = null, $post_type = 'chapter' ) {
	global $blog_id;
	$endpoint = ( in_array( $post_type, [ 'chapter', 'part' ], true ) ) ? $post_type . 's' : $post_type;
	if ( $blog_id !== $source_book_id ) {
		switch_to_blog( $source_book_id );
	}
	$request = new \WP_REST_Request( 'GET', "/pressbooks/v2/$endpoint/$section_id" );
	$section = rest_do_request( $request )->get_data();
	if ( $blog_id !== $source_book_id ) {
		restore_current_blog();
	}
	foreach ( [ 'guid', 'link', 'id' ] as $bad_key ) {
		unset( $section[ $bad_key ] );
	}
	$title = $section['title']['rendered'];
	$content = $section['content']['rendered'];
	$section['title'] = $title;
	$section['content'] = $content;
	if ( $post_type === 'chapter' ) {
		$section['part'] = $target_parent_id;
	}
	if ( $blog_id !== $target_book_id ) {
		switch_to_blog( $target_book_id );
	}
	$request = new \WP_REST_Request( 'POST', "/pressbooks/v2/$endpoint" );
	$request->set_body_params( $section );
	$response = rest_do_request( $request )->get_data();
	if ( $blog_id !== $target_book_id ) {
		restore_current_blog();
	}
	if ( @$response['data']['status'] > 400 ) { // @codingStandardsIgnoreLine
		// $_SESSION['pb_errors'][] = $response['message'];
		return false;
	} else {
		return $response['id'];
	}
}
