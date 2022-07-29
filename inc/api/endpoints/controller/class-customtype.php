<?php

namespace Pressbooks\Api\Endpoints\Controller;

class CustomType extends \WP_REST_Posts_Controller {

	public function __construct( string $post_type ) {
		$this->post_type = $post_type;
		$this->namespace = 'pressbooks/v2';
		$this->rest_base = $post_type;
		$this->meta = new \WP_REST_Post_Meta_Fields( $post_type );
	}

	/**
	 * Retrieves the post's schema, conforming to JSON Schema.
	 *
	 * @since 4.7.0
	 *
	 * @return array Item schema data.
	 */
	public function get_item_schema(): array {
		$schema = parent::get_item_schema();
		$schema['properties']['content'] = [
			'description' => __( 'The content for the post.', 'pressbooks' ),
			'type'        => 'object',
			'context'     => [ 'view', 'edit', 'embed' ],
			'arg_options' => [
				'sanitize_callback' => null, // Note: sanitization implemented in self::prepare_item_for_database().
				'validate_callback' => null, // Note: validation implemented in self::prepare_item_for_database().
			],
			'properties'  => [
				'raw'           => [
					'description' => __( 'Content for the post, as it exists in the database.', 'pressbooks' ),
					'type'        => 'string',
				],
				'rendered'      => [
					'description' => __( 'HTML content for the post, transformed for display.', 'pressbooks' ),
					'type'        => 'string',
					'readonly'    => true,
				],
				'protected'     => [
					'description' => __( 'Whether the content is protected with a password.', 'pressbooks' ),
					'type'        => 'boolean',
					'readonly'    => true,
				],
			],
		];
		$schema['properties']['title'] = [
			'description' => __( 'The title for the post.', 'pressbooks' ),
			'type'        => 'object',
			'context'     => [ 'view', 'edit', 'embed' ],
			'arg_options' => [
				'sanitize_callback' => null, // Note: sanitization implemented in self::prepare_item_for_database().
				'validate_callback' => null, // Note: validation implemented in self::prepare_item_for_database().
			],
			'properties'  => [
				'raw'      => [
					'description' => __( 'Title for the post, as it exists in the database.', 'pressbooks' ),
					'type'        => 'string',
				],
				'rendered' => [
					'description' => __( 'HTML title for the post, transformed for display.', 'pressbooks' ),
					'type'        => 'string',
					'readonly'    => true,
				],
			],
		];
		$schema['properties']['status'] = [
			'description' => __( 'The status for the post.', 'pressbooks' ),
			'type'        => 'string',
			'context' => [ 'view', 'edit', 'embed' ],
		];
		$schema['properties']['meta'] = $this->meta->get_field_schema();

		$type = $this->post_type === 'chapters' ? 'chapter' : $this->post_type;
		$schema['properties'][ "{$type}-type" ] = [
			'description' => sprintf( __( 'The type of %s.', 'pressbooks' ), $type ),
			'type'        => 'string',
			'context' => [ 'view', 'edit', 'embed' ],
		];
		return $schema;
	}

	/**
	 * @param  \WP_REST_Request $request Full details about the request.
	 *
	 * @return bool True if the request has read access, WP_Error object otherwise.
	 */
	public function get_items_permissions_check( $request ): bool {
		if ( has_filter( 'pb_set_api_items_permission' ) && apply_filters( 'pb_set_api_items_permission', false ) ) {
			return true;
		}
		return current_user_can( 'edit_posts' ) || get_option( 'blog_public' );
	}

	/**
	 * Checks if a post can be read.
	 *
	 * Correctly handles posts with the inherit status.
	 *
	 * @since 4.7.0
	 *
	 * @param \WP_Post $post Post object.
	 * @return bool Whether the post can be read.
	 */
	public function check_read_permission( $post ): bool {
		if (
			$this->post_type === 'glossary' ||
			(
				has_filter( 'pb_set_api_items_permission' ) &&
				apply_filters( 'pb_set_api_items_permission', false )
			)
		) {
			// display glossary with any status
			return true;
		}
		return parent::check_read_permission( $post );
	}

}
