<?php
/**
 * @author  PressBooks <code@pressbooks.org>
 * @license GPLv2 (or any later version)
 */

if ( ! defined( 'ABSPATH' ) )
	exit;

// -------------------------------------------------------------------------------------------------------------------
// Includes
// -------------------------------------------------------------------------------------------------------------------

require_once( PB_PLUGIN_DIR . 'admin/pb-admin-dashboard.php' );
require_once( PB_PLUGIN_DIR . 'admin/pb-admin-laf.php' );
require_once( PB_PLUGIN_DIR . 'admin/pb-admin-metaboxes.php' );
require_once( PB_PLUGIN_DIR . 'admin/pb-admin-users.php' );

// -------------------------------------------------------------------------------------------------------------------
// Look & feel of admin interface and Dashboard
// -------------------------------------------------------------------------------------------------------------------

// PressBook-ify the admin bar
add_action( 'admin_bar_menu', '\PressBooks\Admin\Laf\replace_menu_bar_branding', 11 );
add_action( 'admin_bar_menu', '\PressBooks\Admin\Laf\replace_menu_bar_my_sites', 21 );
add_action( 'admin_bar_menu', '\PressBooks\Admin\Laf\remove_menu_bar_update', 41 );
add_action( 'admin_bar_menu', '\PressBooks\Admin\Laf\remove_menu_bar_new_content', 71 );

// Add contact Info
add_action( 'admin_head', '\PressBooks\Admin\Laf\add_feedback_dialogue' );
add_filter( 'admin_footer_text', '\PressBooks\Admin\Laf\add_footer_link' );

if ( \PressBooks\Book::isBook() ) {
	// Aggressively replace default interface
	add_action( 'admin_menu', '\PressBooks\Admin\Laf\replace_book_admin_menu', 1 );
	add_action( 'wp_dashboard_setup', '\PressBooks\Admin\Dashboard\replace_dashboard_widgets' );
} else {
	// Fix extraneous menus
	add_action( 'admin_menu', '\PressBooks\Admin\Laf\fix_root_admin_menu', 1 );
}

if ( true == is_main_site() ) {
	add_action( 'wp_dashboard_setup', '\PressBooks\Admin\Dashboard\replace_root_dashboard_widgets' );
}

// Javascript, Css
add_action( 'admin_init', '\PressBooks\Admin\Laf\init_css_js' );

// Hacks
add_action( 'edit_form_advanced', '\PressBooks\Admin\Laf\edit_form_hacks' );

// Privacy, Ecommerce, and Advanced settings
add_action( 'admin_init', '\PressBooks\Admin\Laf\privacy_settings_init' );
add_action( 'admin_init', '\PressBooks\Admin\Laf\ecomm_settings_init' );
add_action( 'admin_init', '\PressBooks\Admin\Laf\advanced_settings_init' );

//  Replaces 'WordPress' with 'PressBooks' in titles of admin pages.
add_filter( 'admin_title', '\PressBooks\Admin\Laf\admin_title' );

// -------------------------------------------------------------------------------------------------------------------
// Posts, Meta Boxes
// -------------------------------------------------------------------------------------------------------------------

add_action( 'admin_menu', function () {
	remove_meta_box( 'pageparentdiv', 'chapter', 'normal' );
	remove_meta_box( 'submitdiv', 'metadata', 'normal' );
	remove_meta_box( 'submitdiv', 'author', 'normal' );
	remove_meta_box( 'submitdiv', 'part', 'normal' );
} );

add_action( 'admin_init', '\PressBooks\Admin\Metaboxes\add_meta_boxes' );

if ( \PressBooks\Book::isBook() ) {
	add_action( 'admin_enqueue_scripts', '\PressBooks\Admin\Metaboxes\add_metadata_styles' );
	add_action( 'save_post', '\PressBooks\Book::consolidatePost', 10, 2 );
	add_action( 'save_post', '\PressBooks\Admin\Metaboxes\upload_cover_image', 10, 2 );
	add_action( 'save_post', '\PressBooks\Admin\Metaboxes\title_update', 20, 2 );
	add_action( 'save_post', '\PressBooks\Admin\Metaboxes\add_required_data', 30, 2 );
	add_action( 'save_post', '\PressBooks\Book::deleteBookObjectCache', 1000 );
	add_action( 'wp_trash_post', '\PressBooks\Book::deletePost' );
	add_action( 'wp_trash_post', '\PressBooks\Book::deleteBookObjectCache', 1000 );
}

// -------------------------------------------------------------------------------------------------------------------
// Custom user profile
// -------------------------------------------------------------------------------------------------------------------

add_action( 'admin_init', '\PressBooks\Admin\Users\add_user_meta' );

// -------------------------------------------------------------------------------------------------------------------
// Ajax
// -------------------------------------------------------------------------------------------------------------------

// Book Organize Page
add_action( 'wp_ajax_pb_update_chapter', '\PressBooks\Book::updateChapter' );
add_action( 'wp_ajax_pb_update_front_matter', '\PressBooks\Book::updateFrontMatter' );
add_action( 'wp_ajax_pb_update_back_matter', '\PressBooks\Book::updateBackMatter' );
add_action( 'wp_ajax_pb_update_export_options', '\PressBooks\Book::updateExportOptions' );
// Book Information Page
add_action( 'wp_ajax_pb_delete_cover_image', '\PressBooks\Admin\Metaboxes\delete_cover_image' );
// Convert MS Word Footnotes
add_action( 'wp_ajax_pb_ftnref_convert', '\PressBooks\Shortcodes\Footnotes\Footnotes::convertWordFootnotes' );

// -------------------------------------------------------------------------------------------------------------------
// Export page "catch-all" routine, must come after taxonomies and friends
// -------------------------------------------------------------------------------------------------------------------

add_action( 'init', '\PressBooks\Export\Export::formSubmit', 50 );

// -------------------------------------------------------------------------------------------------------------------
// Leftovers
// -------------------------------------------------------------------------------------------------------------------

if ( \PressBooks\Book::isBook() ) {

	add_action( 'post_edit_form_tag', function () {
		echo ' enctype="multipart/form-data"';
	} );

	// Disable all pointers (i.e. tooltips) all the time, see \WP_Internal_Pointers()
	add_action( 'admin_init', function () {
		remove_action( 'admin_enqueue_scripts', array( 'WP_Internal_Pointers', 'enqueue_scripts' ) );
	} );

	// Fix for "are you sure you want to leave page" message when editing a part
	add_action( 'admin_enqueue_scripts', function () {
		if ( 'part' == get_post_type() )
			wp_dequeue_script( 'autosave' );
	} );

	// Hide welcome screen
	add_action( 'load-index.php', function () {
		$user_id = get_current_user_id();
		if ( get_user_meta( $user_id, 'show_welcome_panel', true ) ) {
			update_user_meta( $user_id, 'show_welcome_panel', 0 );
		}
	} );
}
