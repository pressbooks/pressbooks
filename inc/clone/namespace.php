<?php
/**
 * Cloning functions.
 *
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */

namespace Pressbooks\Clone;

function clone_section( $id, $book_id, $post_type = 'chapter' ) {
	$endpoint = ( in_array( $post_type, [ 'chapter', 'part' ], true ) ) ? $post_type . 's' : $post_type;
	$request = new \WP_REST_Request( 'GET', "/pressbooks/v2/$endpoint/$id" );
	$section = rest_do_request( $request )->get_data();
	foreach ( [ 'guid', 'link', 'id' ] as $bad_key ) {
		unset( $section[ $bad_key ] );
	}
	$title = $section['title']['rendered'];
	$content = $section['content']['rendered'];
	$section['title'] = $title;
	$section['content'] = $content;
	switch_to_blog( $book_id );
	$request = new \WP_REST_Request( 'POST', "/pressbooks/v2/$endpoint" );
	$request->set_body_params( $section );
	$new_section = rest_do_request( $request )->get_data();
	restore_current_blog();
}
