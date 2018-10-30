<?php

namespace Pressbooks\Api\Endpoints\Controller;

class Posts extends \WP_REST_Posts_Controller {

	/**
	 * @param string $post_type Post type.
	 */
	public function __construct( $post_type ) {

		parent::__construct( $post_type );
		$this->namespace = 'pressbooks/v2';
		$this->overrideUsingFilterAndActions();
	}

	/**
	 * Use object inheritance as little as possible to future-proof against WP API changes
	 *
	 * With the exception of the abstract \WP_REST_Controller class, the WordPress API documentation
	 * strongly suggests not overriding controllers. Instead we are encouraged to create an entirely separate
	 * controller class for each end point.
	 *
	 * Hooks, actions, and `register_rest_field` are fair game.
	 *
	 * @see https://developer.wordpress.org/rest-api/extending-the-rest-api/controller-classes/#overview-the-future
	 */
	protected function overrideUsingFilterAndActions() {

		// The post type must have custom-fields support otherwise the meta fields will not appear in the REST API.
		add_post_type_support( $this->post_type, 'custom-fields' );

		add_filter( "rest_{$this->post_type}_query", [ $this, 'overrideQueryArgs' ] );
		add_filter( "rest_prepare_{$this->post_type}", [ $this, 'overrideResponse' ], 10, 3 );
		add_filter( "rest_{$this->post_type}_trashable", [ $this, 'overrideTrashable' ], 10, 2 );
	}


	/**
	 * Override the order the posts are displayed
	 *
	 * @param array $args
	 *
	 * @return array
	 */
	public function overrideQueryArgs( $args ) {

		// TODO: $args come from \Pressbooks\Book::getBookStructure, we should consolidate this somewhere?

		$args['post_status'] = 'any';
		$args['orderby'] = 'menu_order';
		$args['order'] = 'ASC';

		return $args;
	}

	/**
	 * Override the response object
	 *
	 * @param \WP_REST_Response $response
	 * @param \WP_Post $post
	 * @param \WP_REST_Request $request
	 *
	 * @return mixed
	 */
	public function overrideResponse( $response, $post, $request ) {

		if ( $post->post_type === 'chapter' ) {
			// Add rest link to associated part
			$response->add_link( 'part', trailingslashit( rest_url( sprintf( '%s/%s', $this->namespace, 'parts' ) ) ) . $post->post_parent );
		}

		if ( in_array( $post->post_type, [ 'front-matter', 'chapter', 'back-matter', 'glossary' ], true ) ) {
			// Add rest link to metadata
			$response->add_link( 'metadata', trailingslashit( rest_url( sprintf( '%s/%s/%d/metadata', $this->namespace, $this->rest_base, $post->ID ) ) ) );
		}

		// Check that we are in view/embed context (and)
		// Check that content is password protected (and)
		// Check that content is empty (if not then API has already verified that the user can_access_password_content)
		if ( in_array( $request['context'], [ 'view', 'embed' ], true ) ) {
			if ( ! empty( $response->data['content'] ) ) {
				if ( $response->data['content']['protected'] && empty( $response->data['content']['rendered'] ) ) {
					// Hide raw data
					$response->data['content']['raw'] = '';
				}
			}
			if ( ! empty( $response->data['excerpt'] ) ) {
				if ( $response->data['excerpt']['protected'] && empty( $response->data['excerpt']['rendered'] ) ) {
					// Hide raw data
					$response->data['excerpt']['raw'] = '';
				}
			}
		}

		return $response;
	}

	/**
	 * @param bool $supports_trash
	 * @param \WP_Post $post
	 *
	 * @return bool
	 */
	public function overrideTrashable( $supports_trash, $post ) {

		global $wpdb;

		if ( $post->post_type === 'part' && $supports_trash ) {
			// Don't delete a part if it has chapters
			$pids = $wpdb->get_col(
				$wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_parent = %d AND (post_status != 'trash' AND post_status != 'inherit') LIMIT 1 ", $post->ID )
			);
			if ( ! empty( $pids ) ) {
				$supports_trash = false;
			}
		}

		return $supports_trash;
	}

	// -------------------------------------------------------------------------------------------------------------------
	// Overrides
	// -------------------------------------------------------------------------------------------------------------------

	/**
	 * @param  \WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|\WP_Error True if the request has read access, WP_Error object otherwise.
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

	/**
	 * @return array
	 */
	public function get_item_schema() {

		$schema = parent::get_item_schema();

		// To reduce the number of HTTP requests required, clients may wish to fetch a resource as well as the linked resources.
		// The _embed parameter indicates to the server that the response should include these embedded resources.
		// @see https://developer.wordpress.org/rest-api/using-the-rest-api/global-parameters/#_embed

		if ( isset( $schema['properties']['content'] ) ) {
			$schema['properties']['content']['context'][] = 'embed';
			$schema['properties']['content']['properties']['rendered']['context'][] = 'embed';
			// Add raw content to view/embed contexts so that we can use it when cloning over REST API
			$schema['properties']['content']['properties']['raw']['context'][] = 'view';
			$schema['properties']['content']['properties']['raw']['context'][] = 'embed';
		}
		if ( isset( $schema['properties']['meta'] ) ) {
			$schema['properties']['meta']['context'][] = 'embed';
		}
		if ( isset( $schema['properties']['menu_order'] ) ) {
			$schema['properties']['menu_order']['context'][] = 'embed';
		}
		foreach ( [ 'front-matter-type', 'chapter-type', 'back-matter-type', 'glossary-type' ] as $taxonomy ) {
			if ( isset( $schema['properties'][ $taxonomy ] ) ) {
				$schema['properties'][ $taxonomy ]['context'][] = 'embed';
			}
		}

		return $schema;
	}

}
