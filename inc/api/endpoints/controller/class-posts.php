<?php

namespace Pressbooks\Api\Endpoints\Controller;

class Posts extends \WP_REST_Posts_Controller {

	/**
	 * @param \WP_Post $post Post object.
	 *
	 * @return bool Whether the post can be read.
	 */
	public function check_read_permission( $post ) {

		if ( 1 !== absint( get_option( 'blog_public' ) ) ) {
			return false;
		}

		return parent::check_read_permission( $post );
	}

}
