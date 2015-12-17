<?php

class SearchPostContent extends Search
{
	function find ($pattern, $limit, $offset, $orderby)
	{
		global $wpdb;

		$results = array ();
		$posts   = $wpdb->get_results ( "SELECT ID, post_content, post_title FROM {$wpdb->posts} WHERE post_status != 'inherit' AND post_type IN ('chapter','front-matter','back-matter') ORDER BY ID $orderby" );

		if ( $limit > 0 )
			$sql .= $wpdb->prepare( " LIMIT %d,%d", $offset, $limit );

		if (count ($posts) > 0)
		{
			foreach ($posts AS $post)
			{
				if (($matches = $this->matches ($pattern, $post->post_content, $post->ID)))
				{
					foreach ($matches AS $match)
						$match->title = $post->post_title;

					$results = array_merge ($results, $matches);
				}
			}
		}

		return $results;
	}

	function get_options ($result)
	{
		$options[] = '<a href="'.get_permalink ($result->id).'">'.__ ('view', 'pressbooks' ).'</a>';

		if (current_user_can ('edit_post', $result->id))
			$options[] = '<a href="'.get_bloginfo ('wpurl').'/wp-admin/post.php?action=edit&amp;post='.$result->id.'">'.__('edit','pressbooks').'</a>';
		return $options;
	}

	function show ($result)
	{
		$post = get_post($result->id);
		switch ($post->post_type) {
			case "chapter":
				$post_type='Chapter';
				break;
			case "front-matter":
				$post_type='Front Matter';
				break;
			case "back-matter":
				$post_type='Back Matter';
				break;
		}
		printf (__ ($post_type.' ID #%d: %s', 'pressbooks' ), $result->id, $result->title);
	}

	function name () { return __ ('Content', 'pressbooks' ); }

	function get_content ($id)
	{
		global $wpdb;

		$post = $wpdb->get_row ( $wpdb->prepare( "SELECT post_content FROM {$wpdb->prefix}posts WHERE id=%d", $id ) );
		return $post->post_content;
	}

	function replace_content ($id, $content)
	{
		wp_update_post(
			array(
				'ID'           => $id,
				'post_content' => $content,
			)
		);
	}
}
