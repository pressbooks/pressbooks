<?php
/**
 * @author  Book Oven Inc. <code@pressbooks.com>
 * @license GPLv2+
 */

namespace Pressbooks\Modules\SearchAndReplace\Types;

class Content extends \Pressbooks\Modules\SearchAndReplace\Search {

	/**
	 * @param $pattern
	 * @param $limit
	 * @param $offset
	 * @param $orderby
	 *
	 * @return \Pressbooks\Modules\SearchAndReplace\Result[]
	 */
	function find( $pattern, $limit, $offset, $orderby ) {
		global $wpdb;
		$results = [];

		$sql = "SELECT ID, post_content, post_title FROM {$wpdb->posts}				
				WHERE post_type IN ('part','chapter','front-matter','back-matter')
				AND post_status NOT IN ('trash','inherit')
				ORDER BY ID ";
		$sql .= ( 'asc' === $orderby ) ? 'ASC' : 'DESC';
		if ( $limit > 0 ) {
			$sql .= sprintf( ' LIMIT %d,%d ', $offset, $limit );
		}

		$posts = $wpdb->get_results( $sql ); // @codingStandardsIgnoreLine

		if ( count( $posts ) > 0 ) {
			foreach ( $posts as $key => $post ) {
				if ( ( $matches = $this->matches( $pattern, $post->post_content, $post->ID ) ) ) {
					foreach ( $matches as $match ) {
						$match->title = $post->post_title;
					}
					$results = array_merge( $results, $matches );
				}
				unset( $posts[ $key ] ); // Reduce memory usage
			}
		}
		return $results;
	}

	/**
	 * @param object $result
	 *
	 * @return array
	 */
	function getOptions( $result ) {
		$options[] = '<a href="' . get_permalink( $result->id ) . '">' . __( 'view', 'pressbooks' ) . '</a>';
		if ( current_user_can( 'edit_post', $result->id ) ) {
			$options[] = '<a href="' . get_bloginfo( 'wpurl' ) . '/wp-admin/post.php?action=edit&amp;post=' . $result->id . '">' . __( 'edit','pressbooks' ) . '</a>';
		}
		return $options;
	}

	/**
	 * @param object $result
	 */
	function show( $result ) {
		$type = '';
		switch ( get_post_type( $result->id ) ) :
			case 'part':
				$type = __( 'Part', 'pressbooks' );
				break;
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
		printf( __( '%1$s ID #%2$d: %3$s', 'pressbooks' ), $type, $result->id, $result->title );
	}

	/**
	 * @param int $id
	 *
	 * @return string
	 */
	function getContent( $id ) {
		global $wpdb;
		$post = $wpdb->get_row( $wpdb->prepare( "SELECT post_content FROM {$wpdb->posts} WHERE id=%d", $id ) );
		return $post->post_content;
	}

	/**
	 * @param int $id
	 * @param string $content
	 */
	function replaceContent( $id, $content ) {
		global $wpdb;
		$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->posts} SET post_content=%s WHERE ID=%d", $content, $id ) );
		wp_cache_flush();
	}

	/**
	 * @return string
	 */
	function name() {
		return __( 'Content Text', 'pressbooks' );
	}

}
