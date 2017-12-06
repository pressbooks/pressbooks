<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */

namespace Pressbooks\PostType;

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
				'chapter_type' => [ 'taxonomy' => 'chapter-type' ],
			],
			'quick_edit' => false,
			'capability_type' => 'post',
			'has_archive' => true,
			'hierarchical' => false,
			'menu_icon' => 'dashicons-book',
			'supports' => [ 'title', 'editor', 'author', 'comments', 'page-attributes', 'revisions' ],
			'show_in_menu' => false,
			'show_in_rest' => true,
			'rest_base' => 'chapters',
			'rest_controller_class' => '\Pressbooks\Api\Endpoints\Controller\Posts',
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
			'supports' => [ 'title', 'editor', 'page-attributes' ],
			'show_in_menu' => false,
			'show_in_rest' => true,
			'rest_base' => 'parts',
			'rest_controller_class' => '\Pressbooks\Api\Endpoints\Controller\Posts',
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
				'front_matter_type' => [ 'taxonomy' => 'front-matter-type' ],
			],
			'quick_edit' => false,
			'capability_type' => 'post',
			'has_archive' => true,
			'hierarchical' => false,
			'supports' => [ 'title', 'editor', 'author', 'comments', 'revisions', 'page-attributes' ],
			'show_in_menu' => false,
			'show_in_rest' => true,
			'rest_base' => 'front-matter',
			'rest_controller_class' => '\Pressbooks\Api\Endpoints\Controller\Posts',
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
				'back_matter_type' => [ 'taxonomy' => 'back-matter-type' ],
			],
			'quick_edit' => false,
			'capability_type' => 'post',
			'has_archive' => true,
			'hierarchical' => false,
			'supports' => [ 'title', 'editor', 'author', 'comments', 'revisions', 'page-attributes' ],
			'show_in_menu' => false,
			'show_in_rest' => true,
			'rest_base' => 'back-matter',
			'rest_controller_class' => '\Pressbooks\Api\Endpoints\Controller\Posts',
		],
		[
			'singular' => __( 'Back Matter', 'pressbooks' ),
			'plural' => __( 'Back Matter', 'pressbooks' ),
		]
	);
	register_extended_post_type(
		'metadata',
		[
			'labels' => [ 'menu_name' => __( 'Book Info', 'pressbooks' ) ],
			'quick_edit' => false,
			'capability_type' => 'post',
			'hierarchical' => false,
			'rewrite' => false,
			'show_in_menu' => false,
			'supports' => false,
			'menu_icon' => 'dashicons-info',
		],
		[
			'singular' => __( 'Book Information', 'pressbooks' ),
			'plural' => __( 'Book Information', 'pressbooks' ),
		]
	);
}

/**
 * Register meta keys for our custom post types (used by REST API)
 */
function register_meta() {

	// TODO Change from 'post' to 'chapter,etc' when this bug is fixed:
	// @see https://core.trac.wordpress.org/ticket/38323

	$defaults = [
		'show_in_rest' => true,
		'single' => true,
		'type' => 'string',
	];

	\register_meta(
		'post', 'pb_export', array_merge(
			$defaults, [
				'description' => __( 'Include in exports', 'pressbooks' ),
				'sanitize_callback' => function( $v ) {
					return ( $v ? 'on' : null ) ; },
			]
		)
	);

	\register_meta(
		'post', 'pb_show_title', array_merge(
			$defaults, [
				'description' => __( 'Show title in exports', 'pressbooks' ),
				'sanitize_callback' => function( $v ) {
					return ( $v ? 'on' : null ) ; },
			]
		)
	);

	\register_meta(
		'post', 'pb_ebook_start', array_merge(
			$defaults, [
				'description' => __( 'Set as ebook start-point', 'pressbooks' ),
				'sanitize_callback' => function( $v ) {
					return ( $v ? 'on' : null ) ; },
			]
		)
	);

	\register_meta(
		'post', 'pb_short_title', array_merge(
			$defaults, [
				'description' => __( 'Chapter Short Title (appears in the PDF running header)', 'pressbooks' ),
			]
		)
	);

	\register_meta(
		'post', 'pb_subtitle', array_merge(
			$defaults, [
				'description' => __( 'Chapter Subtitle (appears in the Web/ebook/PDF output)', 'pressbooks' ),
			]
		)
	);

	\register_meta(
		'post', 'pb_section_author', array_merge(
			$defaults, [
				'description' => __( 'Chapter Author (appears in Web/ebook/PDF output)', 'pressbooks' ),
			]
		)
	);

	\register_meta(
		'post', 'pb_section_license', array_merge(
			$defaults, [
				'description' => __( 'Chapter Copyright License (overrides book license on this page)', 'pressbooks' ),
			]
		)
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
	];

	return $posttypes;
}
