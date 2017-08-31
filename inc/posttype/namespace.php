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
	 * @since 4.0.0
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
 * Loads Chapter, Part, Front Matter, Back Matter, and Metadata custom post types
 */
function register_post_types() {

	/* Chapters */

	$labels = [
		'name' => _x( 'Chapters', 'post type general name', 'pressbooks' ),
		'singular_name' => _x( 'Chapter', 'post type singular name', 'pressbooks' ),
		'add_new' => _x( 'Add New Chapter', 'book', 'pressbooks' ),
		'add_new_item' => __( 'Add New Chapter', 'pressbooks' ),
		'edit_item' => __( 'Edit Chapter', 'pressbooks' ),
		'new_item' => __( 'New Chapter', 'pressbooks' ),
		'view_item' => __( 'View Chapter', 'pressbooks' ),
		'search_items' => __( 'Search Chapters', 'pressbooks' ),
		'not_found' => __( 'No chapters found', 'pressbooks' ),
		'not_found_in_trash' => __( 'No chapters found in Trash', 'pressbooks' ),
		'parent_item_colon' => '',
		'menu_name' => __( 'Text', 'pressbooks' ),
	];
	$args = [
		'labels' => $labels,
		'public' => true,
		'publicly_queryable' => true,
		'show_ui' => true,
		'show_in_menu' => true,
		'menu_position' => 5,
		'query_var' => true,
		'rewrite' => [ 'slug' => 'chapter', 'with_front' => false ],
		'capability_type' => 'post',
		'has_archive' => true,
		'hierarchical' => false, // do not set to true unless you want to break permalinks. Do you really want to do that? >:(
		'supports' => [ 'title', 'editor', 'author', 'comments', 'page-attributes', 'revisions' ],
		'taxonomies' => [ 'chapter-type' ],
		'menu_icon' => 'dashicons-book',
		'show_in_rest' => true,
		'rest_base' => 'chapters',
		'rest_controller_class' => '\Pressbooks\Api\Endpoints\Controller\Posts',
	];
	register_post_type( 'chapter', $args );

	/* Parts */

	$labels = [
		'name' => _x( 'Parts', 'post type general name', 'pressbooks' ),
		'singular_name' => _x( 'Part', 'post type singular name', 'pressbooks' ),
		'add_new' => _x( 'Add Part', 'book', 'pressbooks' ),
		'add_new_item' => __( 'Add New Part', 'pressbooks' ),
		'edit_item' => __( 'Edit Part', 'pressbooks' ),
		'new_item' => __( 'New Part', 'pressbooks' ),
		'view_item' => __( 'View Part', 'pressbooks' ),
		'search_items' => __( 'Search Parts', 'pressbooks' ),
		'not_found' => __( 'No parts found', 'pressbooks' ),
		'not_found_in_trash' => __( 'No parts found in Trash', 'pressbooks' ),
		'parent_item_colon' => '',
		'menu_name' => __( 'Parts', 'pressbooks' ),
	];
	$args = [
		'labels' => $labels,
		'public' => true,
		'publicly_queryable' => true,
		'show_ui' => true,
		'show_in_menu' => true,
		'menu_position' => 5,
		'query_var' => true,
		'rewrite' => [ 'slug' => 'part', 'with_front' => false ],
		'capability_type' => 'post',
		'has_archive' => true,
		'hierarchical' => true,
		'supports' => [ 'title', 'editor', 'page-attributes' ],
		'show_in_rest' => true,
		'rest_base' => 'parts',
		'rest_controller_class' => '\Pressbooks\Api\Endpoints\Controller\Posts',
	];
	register_post_type( 'part', $args );

	/* Front Matter */

	$labels = [
		'name' => _x( 'Front Matter', 'post type general name', 'pressbooks' ),
		'singular_name' => _x( 'Front Matter', 'post type singular name', 'pressbooks' ),
		'add_new' => _x( 'Add New Front Matter', 'book', 'pressbooks' ),
		'add_new_item' => __( 'Add New Front Matter', 'pressbooks' ),
		'edit_item' => __( 'Edit Front Matter', 'pressbooks' ),
		'new_item' => __( 'New Front Matter', 'pressbooks' ),
		'view_item' => __( 'View Front Matter', 'pressbooks' ),
		'search_items' => __( 'Search Front Matter', 'pressbooks' ),
		'not_found' => __( 'No front matter found', 'pressbooks' ),
		'not_found_in_trash' => __( 'No front matter found in Trash', 'pressbooks' ),
		'parent_item_colon' => '',
		'menu_name' => __( 'Front Matter', 'pressbooks' ),
	];
	$args = [
		'labels' => $labels,
		'public' => true,
		'publicly_queryable' => true,
		'show_ui' => true,
		'show_in_menu' => true,
		'menu_position' => 5,
		'query_var' => true,
		'rewrite' => [ 'slug' => 'front-matter', 'with_front' => false ],
		'capability_type' => 'post',
		'has_archive' => true,
		'hierarchical' => true,
		'supports' => [ 'title', 'editor', 'author', 'comments', 'revisions' ],
		'taxonomies' => [ 'front-matter-type' ],
		'show_in_rest' => true,
		'rest_controller_class' => '\Pressbooks\Api\Endpoints\Controller\Posts',
	];
	register_post_type( 'front-matter', $args );

	/* Back Matter */

	$labels = [
		'name' => _x( 'Back Matter', 'post type general name', 'pressbooks' ),
		'singular_name' => _x( 'Back Matter', 'post type singular name', 'pressbooks' ),
		'add_new' => _x( 'Add New Back Matter', 'book', 'pressbooks' ),
		'add_new_item' => __( 'Add New Back Matter', 'pressbooks' ),
		'edit_item' => __( 'Edit Back Matter', 'pressbooks' ),
		'new_item' => __( 'New Back Matter', 'pressbooks' ),
		'view_item' => __( 'View Back Matter', 'pressbooks' ),
		'search_items' => __( 'Search Back Matter', 'pressbooks' ),
		'not_found' => __( 'No back matter found', 'pressbooks' ),
		'not_found_in_trash' => __( 'No back matter found in Trash', 'pressbooks' ),
		'parent_item_colon' => '',
		'menu_name' => __( 'Back Matter', 'pressbooks' ),
	];
	$args = [
		'labels' => $labels,
		'public' => true,
		'publicly_queryable' => true,
		'show_ui' => true,
		'show_in_menu' => true,
		'menu_position' => 5,
		'query_var' => true,
		'rewrite' => [ 'slug' => 'back-matter', 'with_front' => false ],
		'capability_type' => 'post',
		'has_archive' => true,
		'hierarchical' => true,
		'supports' => [ 'title', 'editor', 'author', 'comments', 'revisions' ],
		'taxonomies' => [ 'back-matter-type' ],
		'show_in_rest' => true,
		'rest_controller_class' => '\Pressbooks\Api\Endpoints\Controller\Posts',
	];
	register_post_type( 'back-matter', $args );

	/* Book Information (Ie. Metadata) */

	$labels = [
		'name' => _x( 'Book Information', 'post type general name', 'pressbooks' ),
		'singular_name' => _x( 'Book Information', 'post type singular name', 'pressbooks' ),
		'add_new' => _x( 'Add New Book Information', 'book', 'pressbooks' ),
		'add_new_item' => __( 'Edit Book Information', 'pressbooks' ),
		'edit_item' => __( 'Edit Book Information', 'pressbooks' ),
		'new_item' => __( 'New Book Information', 'pressbooks' ),
		'view_item' => __( 'View Book Information', 'pressbooks' ),
		'search_items' => __( 'Search Book Information', 'pressbooks' ),
		'not_found' => __( 'No book information found', 'pressbooks' ),
		'not_found_in_trash' => __( 'No book information found in Trash', 'pressbooks' ),
		'parent_item_colon' => '',
		'menu_name' => __( 'Book Information', 'pressbooks' ),
	];
	$args = [
		'labels' => $labels,
		'public' => true,
		'publicly_queryable' => true,
		'show_ui' => true,
		'show_in_menu' => true,
		'menu_position' => 5,
		'query_var' => true,
		'rewrite' => false,
		'capability_type' => 'post',
		'has_archive' => true,
		'hierarchical' => false,
		'supports' => [ '' ],
	];
	register_post_type( 'metadata', $args );
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

	\register_meta( 'post', 'pb_export', array_merge( $defaults, [
		'description' => __( 'Include in exports', 'pressbooks' ),
		'sanitize_callback' => function( $v ) { return ( $v ? 'on' : null ) ; },
	] ) );

	\register_meta( 'post', 'pb_show_title', array_merge( $defaults, [
		'description' => __( 'Show title in exports', 'pressbooks' ),
		'sanitize_callback' => function( $v ) { return ( $v ? 'on' : null ) ; },
	] ) );

	\register_meta( 'post', 'pb_ebook_start', array_merge( $defaults, [
		'description' => __( 'Set as ebook start-point', 'pressbooks' ),
		'sanitize_callback' => function( $v ) { return ( $v ? 'on' : null ) ; },
	] ) );

	\register_meta( 'post', 'pb_short_title', array_merge( $defaults, [
		'description' => __( 'Chapter Short Title (appears in the PDF running header)', 'pressbooks' ),
	] ) );

	\register_meta( 'post', 'pb_subtitle', array_merge( $defaults, [
		'description' => __( 'Chapter Subtitle (appears in the Web/ebook/PDF output)', 'pressbooks' ),
	] ) );

	\register_meta( 'post', 'pb_section_author', array_merge( $defaults, [
		'description' => __( 'Chapter Author (appears in Web/ebook/PDF output)', 'pressbooks' ),
	] ) );

	\register_meta( 'post', 'pb_section_license', array_merge( $defaults, [
		'description' => __( 'Chapter Copyright License (overrides book license on this page)', 'pressbooks' ),
	] ) );
}

/**
 * Filters the post updated messages.
 *
 * @param array $messages
 *
 * @return array
 */
function post_type_messages( $messages ) {
	global $post;

	$permalink = get_permalink( $post );

	$messages['part'] = [
		0 => '', // Unused. Messages start at index 1.
		1 => ( ! empty( trim( $post->post_content ) ) ? sprintf( __( 'Part updated. <a target="_blank" href="%s">View Part</a>', 'pressbooks' ), esc_url( $permalink ) ) : __( 'Part updated.', 'pressbooks' ) ),
		2 => __( 'Custom field updated.', 'pressbooks' ),
		3 => __( 'Custom field deleted.', 'pressbooks' ),
		4 => __( 'Part updated.', 'pressbooks' ),
		/* translators: %s: date and time of the revision */
		5 => isset( $_GET['revision'] ) ? sprintf( __( 'Part restored to revision from %s', 'pressbooks' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
		6 => ( ! empty( trim( $post->post_content ) ) ? sprintf( __( 'Part published. <a target="_blank" href="%s">View Part</a>', 'pressbooks' ), esc_url( $permalink ) ) : __( 'Part published.', 'pressbooks' ) ),
		7 => __( 'Part saved.', 'pressbooks' ),
		8 => sprintf( __( 'Part submitted. <a target="_blank" href="%s">Preview Part</a>', 'pressbooks' ), esc_url( add_query_arg( 'preview', 'true', $permalink ) ) ),
		9 => sprintf(
			__( 'Part scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview Part</a>', 'pressbooks' ),
			// translators: Publish box date format, see http://php.net/date
			date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( $permalink )
		),
		10 => sprintf( __( 'Part draft updated. <a target="_blank" href="%s">Preview Part</a>', 'pressbooks' ), esc_url( add_query_arg( 'preview', 'true', $permalink ) ) ),
	];

	$messages['metadata'] = [
		0 => '', // Unused. Messages start at index 1.
		1 => __( 'Book Information updated.', 'pressbooks' ),
		2 => __( 'Custom field updated.', 'pressbooks' ),
		3 => __( 'Custom field deleted.', 'pressbooks' ),
		4 => __( 'Book Information updated.', 'pressbooks' ),
		/* translators: %s: date and time of the revision */
		5 => isset( $_GET['revision'] ) ? sprintf( __( 'Book Information restored to revision from %s', 'pressbooks' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
		6 => __( 'Book Information updated.', 'pressbooks' ),
		7 => __( 'Book Information saved.', 'pressbooks' ),
		8 => __( 'Book Information submitted.', 'pressbooks' ),
	];

	$messages['chapter'] = [
		0 => '', // Unused. Messages start at index 1.
		1 => sprintf( __( 'Chapter updated. <a target="_blank" href="%s">View Chapter</a>', 'pressbooks' ), esc_url( $permalink ) ),
		2 => __( 'Custom field updated.', 'pressbooks' ),
		3 => __( 'Custom field deleted.', 'pressbooks' ),
		4 => __( 'Chapter updated.', 'pressbooks' ),
		/* translators: %s: date and time of the revision */
		5 => isset( $_GET['revision'] ) ? sprintf( __( 'Chapter restored to revision from %s', 'pressbooks' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
		6 => sprintf( __( 'Chapter published. <a href="%s">View Chapter</a>', 'pressbooks' ), esc_url( $permalink ) ),
		7 => __( 'Chapter saved.', 'pressbooks' ),
		8 => sprintf( __( 'Chapter submitted. <a target="_blank" href="%s">Preview Chapter</a>', 'pressbooks' ), esc_url( add_query_arg( 'preview', 'true', $permalink ) ) ),
		9 => sprintf(
			__( 'Chapter scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview Chapter</a>', 'pressbooks' ),
			// translators: Publish box date format, see http://php.net/date
			date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( $permalink )
		),
		10 => sprintf( __( 'Chapter draft updated. <a target="_blank" href="%s">Preview Chapter</a>', 'pressbooks' ), esc_url( add_query_arg( 'preview', 'true', $permalink ) ) ),
	];

	$messages['front-matter'] = [
		0 => '', // Unused. Messages start at index 1.
		1 => sprintf( __( 'Front Matter updated. <a target="_blank" href="%s">View Front Matter</a>', 'pressbooks' ), esc_url( $permalink ) ),
		2 => __( 'Custom field updated.', 'pressbooks' ),
		3 => __( 'Custom field deleted.', 'pressbooks' ),
		4 => __( 'Front Matter updated.', 'pressbooks' ),
		/* translators: %s: date and time of the revision */
		5 => isset( $_GET['revision'] ) ? sprintf( __( 'Front Matter restored to revision from %s', 'pressbooks' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
		6 => sprintf( __( 'Front Matter published. <a href="%s">View Front Matter</a>', 'pressbooks' ), esc_url( $permalink ) ),
		7 => __( 'Front Matter saved.', 'pressbooks' ),
		8 => sprintf( __( 'Front Matter submitted. <a target="_blank" href="%s">Preview Front Matter</a>', 'pressbooks' ), esc_url( add_query_arg( 'preview', 'true', $permalink ) ) ),
		9 => sprintf(
			__( 'Front Matter scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview Front Matter</a>', 'pressbooks' ),
			// translators: Publish box date format, see http://php.net/date
			date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( $permalink )
		),
		10 => sprintf( __( 'Front Matter draft updated. <a target="_blank" href="%s">Preview Front Matter</a>', 'pressbooks' ), esc_url( add_query_arg( 'preview', 'true', $permalink ) ) ),
	];

	$messages['back-matter'] = [
		0 => '', // Unused. Messages start at index 1.
		1 => sprintf( __( 'Back Matter updated. <a target="_blank" href="%s">View Back Matter</a>', 'pressbooks' ), esc_url( $permalink ) ),
		2 => __( 'Custom field updated.', 'pressbooks' ),
		3 => __( 'Custom field deleted.', 'pressbooks' ),
		4 => __( 'Back Matter updated.', 'pressbooks' ),
		/* translators: %s: date and time of the revision */
		5 => isset( $_GET['revision'] ) ? sprintf( __( 'Back Matter restored to revision from %s', 'pressbooks' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
		6 => sprintf( __( 'Back Matter published. <a href="%s">View Back Matter</a>', 'pressbooks' ), esc_url( $permalink ) ),
		7 => __( 'Back Matter saved.', 'pressbooks' ),
		8 => sprintf( __( 'Back Matter submitted. <a target="_blank" href="%s">Preview Back Matter</a>', 'pressbooks' ), esc_url( add_query_arg( 'preview', 'true', $permalink ) ) ),
		9 => sprintf(
			__( 'Back Matter scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview Back Matter</a>', 'pressbooks' ),
			// translators: Publish box date format, see http://php.net/date
			date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( $permalink )
		),
		10 => sprintf( __( 'Back Matter draft updated. <a target="_blank" href="%s">Preview Back Matter</a>', 'pressbooks' ), esc_url( add_query_arg( 'preview', 'true', $permalink ) ) ),
	];

	return $messages;
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
