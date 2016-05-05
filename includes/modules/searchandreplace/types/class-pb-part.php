<?php
/**
 * @author  Book Oven Inc. <code@pressbooks.com>
 * @license GPLv2+
 */

namespace Pressbooks\Modules\SearchAndReplace\Types;

class Part extends \Pressbooks\Modules\SearchAndReplace\Search {
    function find( $pattern, $limit, $offset, $orderby ) {
		global $wpdb;
        $sql = "SELECT {$wpdb->postmeta}.meta_id AS meta_id,
            {$wpdb->postmeta}.meta_value AS meta_value,
            {$wpdb->postmeta}.post_id AS post_id,
            {$wpdb->posts}.post_title AS title
            FROM {$wpdb->postmeta}
            LEFT JOIN {$wpdb->posts}
            ON {$wpdb->postmeta}.post_id = {$wpdb->posts}.ID
            WHERE {$wpdb->postmeta}.meta_key = 'pb_part_content'
            ORDER BY meta_value $orderby";
		if ( $limit > 0 )
			$sql .= $wpdb->prepare( " LIMIT %d,%d", $offset, $limit );
		$results = array();
        $metas = $wpdb->get_results( $sql );
        if ( count( $metas ) > 0 ) {
            foreach ( $metas as $meta ) {
				if ( ( $matches = $this->matches ($pattern, $meta->meta_value, $meta->meta_id)) !== false ) {
                    foreach ($matches as $match) {
                        $match->sub_id = $meta->post_id;
                        $match->title  = $meta->title;
					}
                    $results = array_merge($results, $matches);
				}
			}
		}
		return $results;
	}

    function get_options ($result)
	{
		$options[] = '<a href="'.get_permalink ($result->sub_id).'">'.__ ('view', 'pressbooks').'</a>';
		if (current_user_can ('edit_post', $result->sub_id))
			$options[] = '<a href="'.get_bloginfo ('wpurl').'/wp-admin/post.php?action=edit&amp;post='.$result->sub_id.'">'.__ ('edit','pressbooks').'</a>';
		return $options;
	}
	function show ($result)
	{
		printf (__ ('Part ID #%d: %s', 'pressbooks'), $result->sub_id, $result->title);
	}
	function get_content ($id)
	{
		global $wpdb;
        $post = $wpdb->get_row ($wpdb->prepare( "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_id=%d", $id ) );
		return $post->meta_value;
	}

    function replace( $results ) {
        // Update database, if appropriate
		if ( count( $results ) > 0 ) {
			// We only do the first replace of any set, as that will cover everything
			$lastid = '';
			foreach( $results as $result) {
				if ( $result->id !== $lastid ) {
					$this->replace_content( $result->sub_id, $result->content );
					$lastid = $result->id;
				}
			}
		}
    }

	function replace_content ($id, $content)
	{
		update_post_meta($id, 'pb_part_content', $content);
	}

    function name () {
        return __('Part Text', 'pressbooks');
    }
}
