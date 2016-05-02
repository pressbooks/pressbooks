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

	return array(
		'metadata',
		'part',
		'chapter',
		'front-matter',
		'back-matter',
		'custom-css',
	);
}


/**
 * Loads Chapter, Part, Front Matter, Back Matter, and Metadata custom post types
 */
function register_post_types() {

	/* Chapters */

	$labels = array(
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
		'menu_name' => __( 'Text', 'pressbooks' )

	);
	$args = array(
		'labels' => $labels,
		'public' => true,
		'publicly_queryable' => true,
		'show_ui' => true,
		'show_in_menu' => true,
		'menu_position' => 5,
		'query_var' => true,
		'rewrite' => array( 'slug' => 'chapter', 'with_front' => false ),
		'capability_type' => 'post',
		'has_archive' => true,
		'hierarchical' => false, // do not set to true unless you want to break permalinks. Do you really want to do that? >:(
		'supports' => array( 'title', 'editor', 'author', 'comments', 'page-attributes', 'revisions' ),
		'taxonomies' => array( 'chapter-type' ),
		'menu_icon' => 'dashicons-book',
	);
	register_post_type( 'chapter', $args );


	/* Parts */

	$labels = array(
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
		'menu_name' => __( 'Parts', 'pressbooks' )

	);
	$args = array(
		'labels' => $labels,
		'public' => true,
		'publicly_queryable' => true,
		'show_ui' => true,
		'show_in_menu' => true,
		'menu_position' => 5,
		'query_var' => true,
		'rewrite' => array( 'slug' => 'part', 'with_front' => false ),
		'capability_type' => 'post',
		'has_archive' => true,
		'hierarchical' => true,
		'supports' => array( 'title', 'page-attributes' )
	);
	register_post_type( 'part', $args );

	/* Front Matter */

	$labels = array(
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
		'menu_name' => __( 'Front Matter', 'pressbooks' )

	);
	$args = array(
		'labels' => $labels,
		'public' => true,
		'publicly_queryable' => true,
		'show_ui' => true,
		'show_in_menu' => true,
		'menu_position' => 5,
		'query_var' => true,
		'rewrite' => array( 'slug' => 'front-matter', 'with_front' => false ),
		'capability_type' => 'post',
		'has_archive' => true,
		'hierarchical' => true,
		'supports' => array( 'title', 'editor', 'author', 'comments', 'revisions' ),
		'taxonomies' => array( 'front-matter-type' )
	);
	register_post_type( 'front-matter', $args );


	/* Back Matter */

	$labels = array(
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
		'menu_name' => __( 'Back Matter', 'pressbooks' )

	);
	$args = array(
		'labels' => $labels,
		'public' => true,
		'publicly_queryable' => true,
		'show_ui' => true,
		'show_in_menu' => true,
		'menu_position' => 5,
		'query_var' => true,
		'rewrite' => array( 'slug' => 'back-matter', 'with_front' => false ),
		'capability_type' => 'post',
		'has_archive' => true,
		'hierarchical' => true,
		'supports' => array( 'title', 'editor', 'author', 'comments', 'revisions' ),
		'taxonomies' => array( 'back-matter-type' )
	);
	register_post_type( 'back-matter', $args );


	/* Book Information (Ie. Metadata) */

	$labels = array(
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
		'menu_name' => __( 'Book Information', 'pressbooks' )

	);
	$args = array(
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
		'supports' => array( '' ),
	);
	register_post_type( 'metadata', $args );


	/* Custom CSS */

	$args = array(
		'exclude_from_search' => true,
		'public' => false,
		'publicly_queryable' => false,
		'show_ui' => false,
		'supports' => array( 'revisions' ),
		'label' => 'Custom CSS',
		'can_export' => false,
		'rewrite' => false,
		'capabilities' => array(
			'edit_post' => 'edit_theme_options',
			'read_post' => 'read',
			'delete_post' => 'edit_theme_options',
			'edit_posts' => 'edit_theme_options',
			'edit_others_posts' => 'edit_theme_options',
			'publish_posts' => 'edit_theme_options',
			'read_private_posts' => 'read'
		)
	);
	register_post_type( 'custom-css', $args );
}

function post_type_messages( $messages ) {
	global $post;

	$permalink = get_permalink( $post );

	

	$messages['part'] = array(
		0 => '', // Unused. Messages start at index 1.
		1 => ( get_post_meta( $post->ID, 'pb_part_content' ) ? sprintf( __('Part updated. <a target="_blank" href="%s">View Part</a>', 'pressbooks'), esc_url( $permalink ) ) : __('Part updated.', 'pressbooks') ),
		2 => __('Custom field updated.', 'pressbooks'),
		3 => __('Custom field deleted.', 'pressbooks'),
		4 => __('Part updated.', 'pressbooks'),
		/* translators: %s: date and time of the revision */
		5 => isset($_GET['revision']) ? sprintf( __('Part restored to revision from %s', 'pressbooks'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
		6 => ( get_post_meta( $post->ID, 'pb_part_content' ) ? sprintf( __('Part published. <a target="_blank" href="%s">View Part</a>', 'pressbooks'), esc_url( $permalink ) ) : __('Part published.', 'pressbooks') ),
		7 => __('Part saved.', 'pressbooks'),
		8 => sprintf( __('Part submitted. <a target="_blank" href="%s">Preview Part</a>', 'pressbooks'), esc_url( add_query_arg( 'preview', 'true', $permalink ) ) ),
		9 => sprintf( __('Part scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview Part</a>', 'pressbooks'),
		// translators: Publish box date format, see http://php.net/date
		date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( $permalink ) ),
		10 => sprintf( __('Part draft updated. <a target="_blank" href="%s">Preview Part</a>', 'pressbooks'), esc_url( add_query_arg( 'preview', 'true', $permalink ) ) ),
	);

	$messages['metadata'] = array(
		0 => '', // Unused. Messages start at index 1.
		1 => __('Book Information updated.', 'pressbooks'),
		2 => __('Custom field updated.', 'pressbooks'),
		3 => __('Custom field deleted.', 'pressbooks'),
		4 => __('Book Information updated.', 'pressbooks'),
		/* translators: %s: date and time of the revision */
		5 => isset($_GET['revision']) ? sprintf( __('Book Information restored to revision from %s', 'pressbooks'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
		6 => __('Book Information updated.', 'pressbooks'),
		7 => __('Book Information saved.', 'pressbooks'),
		8 => __('Book Information submitted.', 'pressbooks'),
	);

	$messages['chapter'] = array(
		0 => '', // Unused. Messages start at index 1.
		1 => sprintf( __('Chapter updated. <a target="_blank" href="%s">View Chapter</a>', 'pressbooks'), esc_url( $permalink ) ),
		2 => __('Custom field updated.', 'pressbooks'),
		3 => __('Custom field deleted.', 'pressbooks'),
		4 => __('Chapter updated.', 'pressbooks'),
		/* translators: %s: date and time of the revision */
		5 => isset($_GET['revision']) ? sprintf( __('Chapter restored to revision from %s', 'pressbooks'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
		6 => sprintf( __('Chapter published. <a href="%s">View Chapter</a>', 'pressbooks'), esc_url( $permalink ) ),
		7 => __('Chapter saved.', 'pressbooks'),
		8 => sprintf( __('Chapter submitted. <a target="_blank" href="%s">Preview Chapter</a>', 'pressbooks'), esc_url( add_query_arg( 'preview', 'true', $permalink ) ) ),
		9 => sprintf( __('Chapter scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview Chapter</a>', 'pressbooks'),
		// translators: Publish box date format, see http://php.net/date
		date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( $permalink ) ),
		10 => sprintf( __('Chapter draft updated. <a target="_blank" href="%s">Preview Chapter</a>', 'pressbooks'), esc_url( add_query_arg( 'preview', 'true', $permalink ) ) ),
	);

	$messages['front-matter'] = array(
		0 => '', // Unused. Messages start at index 1.
		1 => sprintf( __('Front Matter updated. <a target="_blank" href="%s">View Front Matter</a>', 'pressbooks'), esc_url( $permalink ) ),
		2 => __('Custom field updated.', 'pressbooks'),
		3 => __('Custom field deleted.', 'pressbooks'),
		4 => __('Front Matter updated.', 'pressbooks'),
		/* translators: %s: date and time of the revision */
		5 => isset($_GET['revision']) ? sprintf( __('Front Matter restored to revision from %s', 'pressbooks'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
		6 => sprintf( __('Front Matter published. <a href="%s">View Front Matter</a>', 'pressbooks'), esc_url( $permalink ) ),
		7 => __('Front Matter saved.', 'pressbooks'),
		8 => sprintf( __('Front Matter submitted. <a target="_blank" href="%s">Preview Front Matter</a>', 'pressbooks'), esc_url( add_query_arg( 'preview', 'true', $permalink ) ) ),
		9 => sprintf( __('Front Matter scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview Front Matter</a>', 'pressbooks'),
		// translators: Publish box date format, see http://php.net/date
		date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( $permalink ) ),
		10 => sprintf( __('Front Matter draft updated. <a target="_blank" href="%s">Preview Front Matter</a>', 'pressbooks'), esc_url( add_query_arg( 'preview', 'true', $permalink ) ) ),
	);

	$messages['back-matter'] = array(
		0 => '', // Unused. Messages start at index 1.
		1 => sprintf( __('Back Matter updated. <a target="_blank" href="%s">View Back Matter</a>', 'pressbooks'), esc_url( $permalink ) ),
		2 => __('Custom field updated.', 'pressbooks'),
		3 => __('Custom field deleted.', 'pressbooks'),
		4 => __('Back Matter updated.', 'pressbooks'),
		/* translators: %s: date and time of the revision */
		5 => isset($_GET['revision']) ? sprintf( __('Back Matter restored to revision from %s', 'pressbooks'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
		6 => sprintf( __('Back Matter published. <a href="%s">View Back Matter</a>', 'pressbooks'), esc_url( $permalink ) ),
		7 => __('Back Matter saved.', 'pressbooks'),
		8 => sprintf( __('Back Matter submitted. <a target="_blank" href="%s">Preview Back Matter</a>', 'pressbooks'), esc_url( add_query_arg( 'preview', 'true', $permalink ) ) ),
		9 => sprintf( __('Back Matter scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview Back Matter</a>', 'pressbooks'),
		// translators: Publish box date format, see http://php.net/date
		date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( $permalink ) ),
		10 => sprintf( __('Back Matter draft updated. <a target="_blank" href="%s">Preview Back Matter</a>', 'pressbooks'), esc_url( add_query_arg( 'preview', 'true', $permalink ) ) ),
	);

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
	if ( 1 == $blog_public ) {
		if ( isset( $args['feed'] ) && ! isset( $args['post_type'] ) ) {
			$args['post_type'] = array( 'front-matter', 'back-matter', 'chapter' );
		}
		// increase default posts per rss
		if ( 10 == $num_posts ) {
			update_option( 'posts_per_rss', 999 );
		}
	} elseif ( 0 == $blog_public ) {
		if ( isset( $args['feed'] ) && ! isset( $args['post_type'] ) ) {
			$args['post_type'] = array( 'post' );
		}
	}
	return $args;
}
