<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */

if ( ! defined( 'ABSPATH' ) )
	exit;

// -------------------------------------------------------------------------------------------------------------------
// Includes
// -------------------------------------------------------------------------------------------------------------------

require( PB_PLUGIN_DIR . 'includes/admin/pb-dashboard.php' );
require( PB_PLUGIN_DIR . 'includes/admin/pb-laf.php' );
require( PB_PLUGIN_DIR . 'includes/admin/pb-metaboxes.php' );
require( PB_PLUGIN_DIR . 'includes/admin/pb-customcss.php' );
require( PB_PLUGIN_DIR . 'includes/admin/pb-network-managers.php' );
require( PB_PLUGIN_DIR . 'includes/admin/pb-fonts.php' );

// -------------------------------------------------------------------------------------------------------------------
// Look & feel of admin interface and Dashboard
// -------------------------------------------------------------------------------------------------------------------

// PressBook-ify the admin bar
add_action( 'admin_bar_menu', '\PressBooks\Admin\Laf\replace_menu_bar_branding', 11 );
add_action( 'admin_bar_menu', '\PressBooks\Admin\Laf\replace_menu_bar_my_sites', 21 );
add_action( 'admin_bar_menu', '\PressBooks\Admin\Laf\remove_menu_bar_update', 41 );
add_action( 'admin_bar_menu', '\PressBooks\Admin\Laf\remove_menu_bar_new_content', 71 );

// Add contact Info
add_filter( 'admin_footer_text', '\PressBooks\Admin\Laf\add_footer_link' );

if ( \PressBooks\Book::isBook() ) {
	// Aggressively replace default interface
	add_action( 'admin_init', '\PressBooks\Admin\Laf\redirect_away_from_bad_urls' );
	add_action( 'admin_menu', '\PressBooks\Admin\Laf\replace_book_admin_menu', 1 );
	add_action( 'wp_dashboard_setup', '\PressBooks\Admin\Dashboard\replace_dashboard_widgets' );
	remove_action( 'welcome_panel', 'wp_welcome_panel' );
	add_action( 'customize_register', '\PressBooks\Admin\Laf\customize_register', 1000 );
} else {
	// Fix extraneous menus
	add_action( 'admin_menu', '\PressBooks\Admin\Laf\fix_root_admin_menu', 1 );
}

if ( is_network_admin() ) {
	add_action( 'wp_network_dashboard_setup', '\PressBooks\Admin\Dashboard\replace_network_dashboard_widgets' );
}

if ( true == is_main_site() ) {
	add_action( 'wp_dashboard_setup', '\PressBooks\Admin\Dashboard\replace_root_dashboard_widgets' );
}

// Javascript, Css
add_action( 'admin_init', '\PressBooks\Admin\Laf\init_css_js' );

// Disable theme customizer
add_action( 'admin_body_class', '\PressBooks\Admin\Laf\disable_customizer');

// Hacks
add_action( 'edit_form_advanced', '\PressBooks\Admin\Laf\edit_form_hacks' );

// Privacy, Ecommerce, and Export settings
add_action( 'admin_init', '\PressBooks\Admin\Laf\privacy_settings_init' );
add_action( 'admin_init', '\PressBooks\Admin\Laf\ecomm_settings_init' );
add_action( 'admin_init', '\PressBooks\Admin\Laf\export_settings_init' );

//  Replaces 'WordPress' with 'Pressbooks' in titles of admin pages.
add_filter( 'admin_title', '\PressBooks\Admin\Laf\admin_title' );

// Echo our notices, if any
add_action( 'admin_notices', '\PressBooks\Admin\Laf\admin_notices' );

// Network Manager routines

add_filter( 'admin_body_class', '\PressBooks\Admin\NetworkManagers\admin_body_class' );
add_action( 'network_admin_menu', '\PressBooks\Admin\NetworkManagers\add_menu' );
add_action( 'wp_ajax_pb_update_admin_status', '\PressBooks\Admin\NetworkManagers\update_admin_status' );
add_action( 'admin_init', '\PressBooks\Admin\NetworkManagers\restrict_access' );
add_action( 'admin_menu', '\PressBooks\Admin\NetworkManagers\hide_menus' );
add_action( 'admin_bar_menu', '\PressBooks\Admin\NetworkManagers\hide_admin_bar_menus', 999 );
if ( ! \PressBooks\Book::isBook() ) {
	add_action( 'network_admin_menu', '\PressBooks\Admin\NetworkManagers\hide_network_menus' );
}

// -------------------------------------------------------------------------------------------------------------------
// Posts, Meta Boxes
// -------------------------------------------------------------------------------------------------------------------

add_action('init', function() { // replace default title filtering with our custom one that allows certain tags
	remove_filter('title_save_pre', 'wp_filter_kses');
	add_filter( 'title_save_pre', 'PressBooks\Sanitize\filter_title');	
});

add_action( 'admin_menu', function () {
	remove_meta_box( 'pageparentdiv', 'chapter', 'normal' );
	remove_meta_box( 'submitdiv', 'metadata', 'normal' );
	remove_meta_box( 'submitdiv', 'author', 'normal' );
	remove_meta_box( 'submitdiv', 'part', 'normal' );
} );

add_action( 'custom_metadata_manager_init_metadata', '\PressBooks\Admin\Metaboxes\add_meta_boxes' );

if ( \PressBooks\Book::isBook() ) {
	add_action( 'admin_enqueue_scripts', '\PressBooks\Admin\Metaboxes\add_metadata_styles' );
	add_action( 'save_post', '\PressBooks\Book::consolidatePost', 10, 2 );
	add_action( 'save_post_metadata', '\PressBooks\Admin\Metaboxes\upload_cover_image', 10, 2 );
	add_action( 'save_post_metadata', '\PressBooks\Admin\Metaboxes\title_update', 20, 2 );
	add_action( 'save_post_metadata', '\PressBooks\Admin\Metaboxes\add_required_data', 30, 2 );
	add_action( 'save_post', '\PressBooks\Book::deleteBookObjectCache', 1000 );
	add_action( 'wp_trash_post', '\PressBooks\Book::deletePost' );
	add_action( 'wp_trash_post', '\PressBooks\Book::deleteBookObjectCache', 1000 );
	add_filter( 'mce_external_languages', '\PressBooks\Editor\add_languages' );
	add_filter( 'tiny_mce_before_init', '\PressBooks\Editor\mce_before_init_insert_formats' );
	add_filter( 'tiny_mce_before_init', '\PressBooks\Editor\mce_valid_word_elements' );
	add_filter( 'tiny_mce_before_init', '\PressBooks\Editor\mce_table_editor_options' );
	add_filter( 'mce_external_plugins', '\PressBooks\Editor\mce_button_scripts' );
	add_filter( 'mce_buttons_2', '\PressBooks\Editor\mce_buttons');
}

// -------------------------------------------------------------------------------------------------------------------
// Custom user profile
// -------------------------------------------------------------------------------------------------------------------

add_action( 'custom_metadata_manager_init_metadata', '\PressBooks\Admin\Metaboxes\add_user_meta' );

// -------------------------------------------------------------------------------------------------------------------
// Ajax
// -------------------------------------------------------------------------------------------------------------------

// Book Organize Page
add_action( 'wp_ajax_pb_update_chapter', '\PressBooks\Book::updateChapter' );
add_action( 'wp_ajax_pb_update_front_matter', '\PressBooks\Book::updateFrontMatter' );
add_action( 'wp_ajax_pb_update_back_matter', '\PressBooks\Book::updateBackMatter' );
add_action( 'wp_ajax_pb_update_export_options', '\PressBooks\Book::updateExportOptions' );
add_action( 'wp_ajax_pb_update_privacy_options', '\PressBooks\Book::updatePrivacyOptions' );
add_action( 'wp_ajax_pb_update_show_title_options', '\PressBooks\Book::updateShowTitleOptions' );
add_action( 'wp_ajax_pb_update_global_privacy_options', '\PressBooks\Book::updateGlobalPrivacyOptions' );
// Book Information Page
add_action( 'wp_ajax_pb_delete_cover_image', '\PressBooks\Admin\Metaboxes\delete_cover_image' );
// Convert MS Word Footnotes
add_action( 'wp_ajax_pb_ftnref_convert', '\PressBooks\Shortcodes\Footnotes\Footnotes::convertWordFootnotes' );
// Load CSS into Custom CSS textarea
add_action( 'wp_ajax_pb_load_css_from', '\PressBooks\Admin\CustomCss\load_css_from' );
// User Catalog Page
add_action( 'wp_ajax_pb_delete_catalog_logo', '\PressBooks\Catalog::deleteLogo' );

// -------------------------------------------------------------------------------------------------------------------
// Custom Css
// -------------------------------------------------------------------------------------------------------------------

add_action( 'admin_menu', '\PressBooks\Admin\CustomCss\add_menu' );
add_action( 'load-post.php', '\PressBooks\Admin\CustomCss\redirect_css_editor' );

// -------------------------------------------------------------------------------------------------------------------
// SASS
// -------------------------------------------------------------------------------------------------------------------

add_action( 'update_option_pressbooks_global_typography', '\PressBooks\Admin\Fonts\update_font_stacks' );

if ( \PressBooks\Book::isBook() ) {

	// Look & Feel
	add_action( 'after_switch_theme', '\PressBooks\Admin\Fonts\update_font_stacks' );

	// Posts, Meta Boxes
	add_action( 'updated_postmeta', function ( $meta_id, $object_id, $meta_key, $meta_value ) {
		if ( 'pb_language' == $meta_key ) {
			\PressBooks\Book::deleteBookObjectCache();
			\PressBooks\Admin\Fonts\update_font_stacks();
		}
	}, 10, 4 );

	// Init
	add_action( 'admin_init', '\PressBooks\Admin\Fonts\fix_missing_font_stacks' );
	add_action( 'admin_init', '\PressBooks\Editor\add_editor_style' );
}

// -------------------------------------------------------------------------------------------------------------------
// "Catch-all" routines, must come after taxonomies and friends
// -------------------------------------------------------------------------------------------------------------------

add_action( 'init', '\PressBooks\Modules\Export\Export::formSubmit', 50 );
add_action( 'init', '\PressBooks\Modules\Import\Import::formSubmit', 50 );
add_action( 'init', '\PressBooks\CustomCss::formSubmit', 50 );
add_action( 'init', '\PressBooks\Catalog::formSubmit', 50 );

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

	// Disable live preview
	add_filter( 'theme_action_links', function ( $actions ) {
		unset ( $actions['preview'] );
		return $actions;
	} );

}

// Hide WP update nag
add_action( 'admin_menu', function () {
	remove_action( 'admin_notices', 'update_nag', 3 );
	remove_filter( 'update_footer', 'core_update_footer' );
} );