<?php

namespace Pressbooks\Api\Endpoints\Controller;

class Posts extends \WP_REST_Posts_Controller {

	/**
	 * @param string $post_type Post type.
	 */
	public function __construct( $post_type ) {

		parent::__construct( $post_type );

		$this->namespace = 'pressbooks/v2';
		add_filter( "rest_{$this->post_type}_query", [ $this, 'overrideDefaultQueryArguments' ] );
	}

	/**
	 * @param array $args
	 *
	 * @return array
	 */
	public function overrideDefaultQueryArguments( $args ) {

		// TODO: $args come from \Pressbooks\Book::getBookStructure, we should consolidate this somewhere?

		$args['post_status'] = 'any';
		$args['orderby'] = 'menu_order';
		$args['order'] = 'ASC';

		return $args;
	}

	/**
	 * @param  \WP_REST_Request $request Full details about the request.
	 *
	 * @return true|\WP_Error True if the request has read access, WP_Error object otherwise.
	 */
	public function get_items_permissions_check( $request ) {

		if ( current_user_can( 'edit_posts' ) ) {
			return true;
		}

		if ( 1 !== absint( get_option( 'blog_public' ) ) ) {
			return false;
		}

		return parent::get_items_permissions_check( $request );
	}

	/**
	 * @param \WP_Post $post Post object.
	 *
	 * @return bool Whether the post can be read.
	 */
	public function check_read_permission( $post ) {

		if ( parent::check_read_permission( $post ) ) {
			return true;
		}

		if ( current_user_can( 'edit_posts' ) ) {
			return true;
		}

		return false;
	}

}
