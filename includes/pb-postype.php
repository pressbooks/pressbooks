<?php
/**
 * @author  PressBooks <code@pressbooks.org>
 * @license GPLv2 (or any later version)
 */
namespace PressBooks\PostType;


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
		'supports' => array( 'title', 'editor', 'author', 'comments', 'page-attributes', 'revisions' )
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
		'rewrite' => true,
		'capability_type' => 'post',
		'has_archive' => true,
		'hierarchical' => true,
		'supports' => array( 'title' )
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
		'rewrite' => true,
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
		'rewrite' => true,
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


	// Flush rewrite rules
	$set = get_option( 'pressbooks_flushed_post_type' );
	if ( $set !== true ) {
		flush_rewrite_rules( false );
		update_option( 'pressbooks_flushed_post_type', true );
	}

}