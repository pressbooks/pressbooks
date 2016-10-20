<?php
/**
 * @author  Book Oven Inc. <code@pressbooks.com>
 * @license GPLv2+
 */

namespace Pressbooks\Modules\SearchAndReplace\Types;

class Content extends \Pressbooks\Modules\SearchAndReplace\Search {
	function find( $pattern, $limit, $offset, $orderby ) {
		global $wpdb;
		$results = array();
		if ( $limit > 0 ) {
			if ( 'asc' == $orderby ) {
				$posts = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT ID, post_content, post_title
						FROM $wpdb->posts
						WHERE post_status != 'inherit'
						AND post_type IN ('chapter','front-matter','back-matter')
						ORDER BY ID ASC
						LIMIT %d,%d",
						$offset,
						$limit
					)
				);
			} else {
				$posts = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT ID, post_content, post_title
						FROM $wpdb->posts
						WHERE post_status != 'inherit'
						AND post_type IN ('chapter','front-matter','back-matter')
						ORDER BY ID DESC
						LIMIT %d,%d",
						$offset,
						$limit
					)
				);
			}
		} else {
			if ( 'asc' == $orderby ) {
				$posts = $wpdb->get_results(
					"SELECT ID, post_content, post_title
					FROM $wpdb->posts
					WHERE post_status != 'inherit'
					AND post_type IN ('chapter','front-matter','back-matter')
					ORDER BY ID ASC"
				);
			} else {
				$posts = $wpdb->get_results(
					"SELECT ID, post_content, post_title
					FROM $wpdb->posts
					WHERE post_status != 'inherit'
					AND post_type IN ('chapter','front-matter','back-matter')
					ORDER BY ID DESC"
				);
			}
		}
		if ( count( $posts ) > 0 ) {
			foreach ( $posts as $post ) {
				if ( ( $matches = $this->matches( $pattern, $post->post_content, $post->ID ) ) ) {
					foreach ( $matches as $match ) {
						$match->title = $post->post_title;
					}
					$results = array_merge( $results, $matches );
				}
			}
		}
		return $results;
	}

	function get_options( $result ) {
		$options[] = '<a href="' . get_permalink( $result->id ) . '">' . __( 'view', 'pressbooks' ) . '</a>';
		if ( current_user_can( 'edit_post', $result->id ) ) {
			$options[] = '<a href="' . get_bloginfo( 'wpurl' ) . '/wp-admin/post.php?action=edit&amp;post=' . $result->id . '">' . __( 'edit','pressbooks' ) . '</a>';
		}
		return $options;
	}
	function show( $result ) {
		switch ( get_post_type( $result->id ) ) :
			case 'chapter':
				$type = __( 'Chapter', 'pressbooks' );
				break;
			case 'front-matter':
				$type = __( 'Front Matter', 'pressbooks' );
				break;
			case 'back-matter':
				$type = __( 'Back Matter', 'pressbooks' );
				break;
		endswitch;
		printf( __( '%1$s ID #%2$d: %1$s', 'pressbooks' ), $type, $result->id, $result->title );
	}
	function get_content( $id ) {
		global $wpdb;
		$post = $wpdb->get_row( $wpdb->prepare( "SELECT post_content FROM {$wpdb->prefix}posts WHERE id=%d", $id ) );
		return $post->post_content;
	}
	function replace_content( $id, $content ) {
		global $wpdb;
		$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->posts} SET post_content=%s WHERE ID=%d", $content, $id ) );
		wp_cache_flush();
	}

	function name() {
		return __( 'Content Text', 'pressbooks' );
	}



}
