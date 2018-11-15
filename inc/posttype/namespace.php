<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\PostType;

use Pressbooks\Modules\ThemeOptions\GlobalOptions;

/**
 * List our post_types
 *
 * @return array
 */
function list_post_types() {

	/**
	 * Allow users to add additional custom post types to the list of permitted post types.
	 *
	 * @since 4.0.0
	 *
	 * @param array $value
	 */
	return apply_filters(
		'pb_supported_post_types', [
			'metadata',
			'part',
			'chapter',
			'front-matter',
			'back-matter',
			'custom-css',
			'glossary',
		]
	);
}

/**
 * Loads section and metadata custom post types
 */
function register_post_types() {
	register_extended_post_type(
		'chapter', [
			'admin_cols' => [
				'chapter_type' => [
					'taxonomy' => 'chapter-type',
				],
			],
			'quick_edit' => false,
			'capability_type' => 'post',
			'has_archive' => true,
			'hierarchical' => false,
			'menu_icon' => 'dashicons-book',
			'supports' => [ 'title', 'editor', 'author', 'comments', 'page-attributes', 'revisions' ],
			'show_in_menu' => false,
			'show_in_admin_bar' => true,
			'show_in_rest' => true,
			'rest_base' => 'chapters',
			'rest_controller_class' => '\Pressbooks\Api\Endpoints\Controller\Posts',
			'rewrite' => [
				'slug' => 'chapter',
				'with_front' => false,
			],
		],
		[
			'singular' => __( 'Chapter', 'pressbooks' ),
			'plural' => __( 'Chapters', 'pressbooks' ),
		]
	);
	register_extended_post_type(
		'part',
		[
			'quick_edit' => false,
			'capability_type' => 'page',
			'has_archive' => true,
			'hierarchical' => true,
			'supports' => [ 'title', 'editor', 'page-attributes', 'revisions' ],
			'show_in_menu' => false,
			'show_in_admin_bar' => true,
			'show_in_rest' => true,
			'rest_base' => 'parts',
			'rest_controller_class' => '\Pressbooks\Api\Endpoints\Controller\Posts',
			'rewrite' => [
				'slug' => 'part',
				'with_front' => false,
			],
		],
		[
			'singular' => __( 'Part', 'pressbooks' ),
			'plural' => __( 'Parts', 'pressbooks' ),
		]
	);
	register_extended_post_type(
		'front-matter',
		[
			'admin_cols' => [
				'front_matter_type' => [
					'taxonomy' => 'front-matter-type',
				],
			],
			'quick_edit' => false,
			'capability_type' => 'post',
			'has_archive' => true,
			'hierarchical' => false,
			'supports' => [ 'title', 'editor', 'author', 'comments', 'revisions', 'page-attributes' ],
			'show_in_menu' => false,
			'show_in_admin_bar' => true,
			'show_in_rest' => true,
			'rest_base' => 'front-matter',
			'rest_controller_class' => '\Pressbooks\Api\Endpoints\Controller\Posts',
			'rewrite' => [
				'slug' => 'front-matter',
				'with_front' => false,
			],
		],
		[
			'singular' => __( 'Front Matter', 'pressbooks' ),
			'plural' => __( 'Front Matter', 'pressbooks' ),
		]
	);
	register_extended_post_type(
		'back-matter',
		[
			'admin_cols' => [
				'back_matter_type' => [
					'taxonomy' => 'back-matter-type',
				],
			],
			'quick_edit' => false,
			'capability_type' => 'post',
			'has_archive' => true,
			'hierarchical' => false,
			'supports' => [ 'title', 'editor', 'author', 'comments', 'revisions', 'page-attributes' ],
			'show_in_menu' => false,
			'show_in_admin_bar' => true,
			'show_in_rest' => true,
			'rest_base' => 'back-matter',
			'rest_controller_class' => '\Pressbooks\Api\Endpoints\Controller\Posts',
			'rewrite' => [
				'slug' => 'back-matter',
				'with_front' => false,
			],
		],
		[
			'singular' => __( 'Back Matter', 'pressbooks' ),
			'plural' => __( 'Back Matter', 'pressbooks' ),
		]
	);
	register_extended_post_type(
		'metadata',
		[
			'labels' => [
				'menu_name' => __( 'Book Info', 'pressbooks' ),
			],
			'quick_edit' => false,
			'capability_type' => 'post',
			'exclude_from_search' => true,
			'hierarchical' => false,
			'publicly_queryable' => false,
			'rewrite' => false,
			'show_in_menu' => false,
			'show_in_nav_menus' => false,
			'supports' => false,
			'menu_icon' => 'dashicons-info',
		],
		[
			'singular' => __( 'Book Information', 'pressbooks' ),
			'plural' => __( 'Book Information', 'pressbooks' ),
		]
	);
	register_extended_post_type(
		'glossary',
		[
			'admin_cols' => [
				'glossary_type' => [
					'taxonomy' => 'glossary-type',
				],
			],
			'quick_edit' => false,
			'capability_type' => 'post',
			'has_archive' => false,
			'hierarchical' => false,
			'publicly_queryable' => false,
			'supports' => [ 'title', 'editor', 'author', 'revisions' ],
			'show_in_menu' => false,
			'show_in_admin_bar' => false,
			'show_in_rest' => true,
			'rest_base' => 'glossary',
			'rest_controller_class' => '\Pressbooks\Api\Endpoints\Controller\Posts',
			'rewrite' => [
				'slug' => 'glossary',
				'with_front' => false,
			],
		],
		[
			'singular' => __( 'Glossary Term', 'pressbooks' ),
			'plural' => __( 'Glossary Terms', 'pressbooks' ),
		]
	);
}

/**
 * @param array $actions
 * @param \WP_Post $post
 *
 * @return array
 */
function row_actions( $actions, $post ) {
	if ( $post->post_type === 'glossary' ) {
		unset( $actions['view'] );
		unset( $actions['inline hide-if-no-js'] );
	}
	return $actions;
}

/**
 * @param bool $disable
 * @param string $post_type
 *
 * @return bool
 */
function disable_months_dropdown( $disable, $post_type ) {
	if ( $post_type === 'glossary' ) {
		return true;
	}
	return $disable;
}

/**
 * @param \WP_Post $post
 */
function after_title( $post ) {
	if ( $post->post_type === 'glossary' ) {
		echo '<p>';
		_e( 'HTML and shortcodes are not supported in glossary terms.', 'pressbooks' );
		echo '</p>';
	}
	if ( $post->post_type === 'back-matter' ) {
		$taxonomy = \Pressbooks\Taxonomy::init();
		if ( $taxonomy->getBackMatterType( $post->ID ) === 'glossary' ) {
			echo '<div id="pb-post-type-notice" class="notice notice-info" aria-live="assertive"><p>';
			_e( "To display a list of glossary terms, leave this back matter's content blank.", 'pressbooks' );
			echo '</p></div>';
		} else {
			echo '<div id="pb-post-type-notice" class="notice notice-info" style="display:none;" aria-live="assertive"></div>'; // Placeholder
		}
	}
}

/**
 * @param array $settings
 *
 * @return array
 */
function wp_editor_settings( $settings ) {
	if ( get_post_type() === 'glossary' ) {
		$settings['wpautop'] = false;
		$settings['media_buttons'] = false;
		$settings['tinymce'] = false;
		$settings['quicktags'] = false;
		$settings['editor_css'] = '<style>.wp-editor-area { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif; font-size: 14px; }</style>';
	}
	return $settings;
}

/**
 * @param array $post_states An array of post display states.
 * @param \WP_Post $post The current post object.
 *
 * @return array
 */
function display_post_states( $post_states, $post ) {
	if ( $post->post_type === 'glossary' && isset( $post_states['private'] ) ) {
		$post_states['private'] = __( 'Unlisted', 'pressbooks' );
	}
	return $post_states;
}

/**
 * Disable comments for Metadata
 *
 * @param bool $open Whether the current post is open for comments.
 * @param int $post_id The post ID.
 *
 * @return bool
 */
function comments_open( $open, $post_id ) {
	if ( $open ) {
		if ( ( new \Pressbooks\Metadata() )->getMetaPost()->ID === $post_id ) {
			return false;
		}
	}
	return $open;
}

/**
 * Register meta keys for our custom post types (used by REST API)
 */
function register_meta() {
	$defaults = [
		'show_in_rest' => true,
		'single' => true,
		'type' => 'string',
	];

	foreach ( [ 'front-matter', 'chapter', 'back-matter' ] as $post_type ) {
		\register_meta(
			'post', 'pb_show_title', array_merge(
				$defaults, [
					'object_subtype' => $post_type,
					'description' => __( 'Show title in exports', 'pressbooks' ),
					'sanitize_callback' => function( $v ) {
						return ( $v ? 'on' : null );
					},
				]
			)
		);

		\register_meta(
			'post', 'pb_short_title', array_merge(
				$defaults, [
					'object_subtype' => $post_type,
					'description' => __( 'Chapter Short Title (appears in the PDF running header)', 'pressbooks' ),
				]
			)
		);

		\register_meta(
			'post', 'pb_subtitle', array_merge(
				$defaults, [
					'object_subtype' => $post_type,
					'description' => __( 'Chapter Subtitle (appears in the Web/ebook/PDF output)', 'pressbooks' ),
				]
			)
		);

		\register_meta(
			'post', 'pb_authors', array_merge(
				$defaults, [
					'object_subtype' => $post_type,
					'single' => false,
					'description' => __( 'Chapter Author (appears in Web/ebook/PDF output)', 'pressbooks' ),
				]
			)
		);

		\register_meta(
			'post', 'pb_section_license', array_merge(
				$defaults, [
					'object_subtype' => $post_type,
					'description' => __( 'Chapter Copyright License (overrides book license on this page)', 'pressbooks' ),
				]
			)
		);
	}

	\register_meta(
		'post', 'pb_part_invisible', array_merge(
			$defaults, [
				'object_subtype' => 'part',
				'description' => __( 'Whether or not the part is shown in the table of contents', 'pressbooks' ),
				'type' => 'boolean',
			]
		)
	);

	\register_meta(
		'post', 'pb_media_attribution_title_url', array_merge(
			$defaults, [
				'object_subtype' => 'attachment',
				'description' => __( 'Media attribution source url', 'pressbooks' ),
			]
		)
	);

	\register_meta(
		'post', 'pb_media_attribution_author', array_merge(
			$defaults, [
				'object_subtype' => 'attachment',
				'description' => __( 'Media attribution author', 'pressbooks' ),
			]
		)
	);

	\register_meta(
		'post', 'pb_media_attribution_author_url', array_merge(
			$defaults, [
				'object_subtype' => 'attachment',
				'description' => __( 'Media attribution author url', 'pressbooks' ),
			]
		)
	);

	\register_meta(
		'post', 'pb_media_attribution_adapted', array_merge(
			$defaults, [
				'object_subtype' => 'attachment',
				'description' => __( 'Media attribution adapted by', 'pressbooks' ),
			]
		)
	);

	\register_meta(
		'post', 'pb_media_attribution_adapted_url', array_merge(
			$defaults, [
				'object_subtype' => 'attachment',
				'description' => __( 'Media attribution adapted by url', 'pressbooks' ),
			]
		)
	);

	\register_meta(
		'post', 'pb_media_attribution_license', array_merge(
			$defaults, [
				'object_subtype' => 'attachment',
				'description' => __( 'Media attribution license', 'pressbooks' ),
			]
		)
	);
}

/**
 * @since 5.0.0
 */
function register_post_statii() {
	\register_post_status(
		'web-only', [
			'label'       => _x( 'Web Only', 'post status', 'pressbooks' ),
			'public'      => true,
			'label_count' => _n_noop( 'Web Only <span class="count">(%s)</span>', 'Web Only <span class="count">(%s)</span>' ),
		]
	);
}

/**
 * Add custom post types to RSS feed
 *
 * @param array $args
 *
 * @return array $args
 */
function add_post_types_rss( $args ) {
	$blog_public = get_option( 'blog_public' );
	$num_posts = get_option( 'posts_per_rss' );

	// only if book is public
	if ( 1 === absint( $blog_public ) ) {
		if ( isset( $args['feed'] ) && ! isset( $args['post_type'] ) ) {
			$args['post_type'] = [ 'front-matter', 'back-matter', 'chapter' ];
		}
		// increase default posts per rss
		if ( 10 === absint( $num_posts ) ) {
			update_option( 'posts_per_rss', 999 );
		}
	} elseif ( 0 === absint( $blog_public ) ) {
		if ( isset( $args['feed'] ) && ! isset( $args['post_type'] ) ) {
			$args['post_type'] = [ 'post' ];
		}
	}
	return $args;
}

/**
 * Add Hypothesis support for Pressbooks custom post types
 * @see https://github.com/hypothesis/wp-hypothesis/blob/master/hypothesis.php#L63-L68
 *
 * @param array $posttypes Default Hypothesis post types.
 *
 * @return array
 */
function add_posttypes_to_hypothesis( $posttypes ) {
	$posttypes = [
		'part' => __( 'parts', 'pressbooks' ),
		'chapter' => __( 'chapters', 'pressbooks' ),
		'front-matter' => __( 'front matter', 'pressbooks' ),
		'back-matter' => __( 'back matter', 'pressbooks' ),
		'glossary' => __( 'glossary', 'pressbooks' ),
	];

	return $posttypes;
}

/**
 * @since 5.0.0
 *
 * @param int $post_id
 *
 * @return bool
 */
function can_export( $post_id = 0 ) {

	if ( ! $post_id ) {
		$post_id = get_the_ID();
		if ( ! $post_id ) {
			// Try to find using deprecated means
			global $id;
			$post = get_post( $id );
			if ( ! $post ) {
				return false;
			} else {
				$post_id = $post->ID;
			}
		}
	}

	// Look if info exist in post status (new data model)
	if ( in_array( get_post_status( $post_id ), [ 'private', 'publish' ], true ) ) {
		return true;
	} else {
		// Look if info exist in post meta (old data model)
		return ( get_post_meta( $post_id, 'pb_export', true ) === 'on' );
	}
}

/**
 * @since 5.2.0
 *
 * @param string $posttype The slug of a post type
 *
 * @return string The localized label for the post type, or false if an invalid post type was supplied.
 */
function get_post_type_label( $posttype ) {
	switch ( $posttype ) :
		case 'metadata':
			$label = __( 'Book Information', 'pressbooks' );
			break;
		case 'part':
			$label = __( 'Part', 'pressbooks' );
			break;
		case 'chapter':
			$label = __( 'Chapter', 'pressbooks' );
			break;
		case 'front-matter':
			$label = __( 'Front Matter', 'pressbooks' );
			break;
		case 'back-matter':
			$label = __( 'Back Matter', 'pressbooks' );
			break;
		case 'glossary':
			$label = __( 'Glossary', 'pressbooks' );
			break;
		default:
			$label = false;
	endswitch;
	return $label;
}

/**
 * @since 5.6.0
 *
 * @param string $label The post type label
 * @param array $args
 *
 * @return string
 */

function filter_post_type_label( $label, $args ) {
	if ( isset( $args['post_type'] ) && in_array( $args['post_type'], [ 'part', 'chapter' ], true ) ) {
		$defaults = GlobalOptions::getDefaults();
		$options = get_option( 'pressbooks_theme_options_global', $defaults );
		$post_type = str_replace( '-', '_', $args['post_type'] );
		return $options[ "{$post_type}_label" ] ?? $defaults[ "{$post_type}_label" ];
	}
	return $label;
}
