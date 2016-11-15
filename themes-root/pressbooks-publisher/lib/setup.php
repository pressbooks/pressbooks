<?php

namespace Roots\Sage\Setup;

use Roots\Sage\Assets;

/**
 * Theme setup
 */
function setup() {
	// Make theme available for translation
	load_theme_textdomain( 'pressbooks', PB_PLUGIN_DIR . 'languages' );

	// Enable plugins to manage the document title
	// http://codex.wordpress.org/Function_Reference/add_theme_support#Title_Tag
	add_theme_support( 'title-tag' );

	// Content width
	$GLOBALS['content_width'] = apply_filters( 'pressbooks_publisher_content_width', 640 );

	// Add image sizes for custom logo and book covers
	add_image_size( 'pressbooks-publisher-custom-logo', 99999, 55, false );
	add_image_size( 'pressbooks-publisher-book-cover', 500, 650, true );

	// Enable custom logo support
	add_theme_support( 'custom-logo', [ 'size' => 'pressbooks-publisher-site-logo' ] );

	// Enable HTML5 markup support
	// http://codex.wordpress.org/Function_Reference/add_theme_support#HTML5
	add_theme_support( 'html5', [ 'caption', 'comment-form', 'comment-list', 'gallery', 'search-form' ] );

	// Use main stylesheet for visual editor
	// To add custom styles edit /assets/styles/layouts/_tinymce.scss
	add_editor_style( Assets\asset_path( 'styles/main.css' ) );
}
add_action( 'after_setup_theme', __NAMESPACE__ . '\\setup' );

/**
 * Theme assets
 */
function assets() {
	wp_enqueue_style( 'pressbooks-publisher/css', Assets\asset_path( 'styles/main.css' ), false, null );
	wp_enqueue_style( 'pressbooks-publisher/fonts', 'https://fonts.googleapis.com/css?family=Droid+Sans|Droid+Serif:400,400italic,700|Oswald', false, null );
	wp_enqueue_script( 'pressbooks-publisher/skip-link-focus-fix', Assets\asset_path( 'scripts/skip-link-focus-fix.js' ), [], null, true );
	wp_enqueue_script( 'pressbooks-publisher/match-height', Assets\asset_path( 'scripts/matchheight.js' ), [ 'jquery' ], null, true );
	wp_enqueue_script( 'pressbooks-publisher/js', Assets\asset_path( 'scripts/main.js' ), [ 'jquery' ], null, true );
}
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\assets', 100 );

// Clean up the admin menu
function admin_menu() {
	global $menu, $submenu;

	remove_menu_page( 'index.php' );
	remove_menu_page( 'edit.php' );
	remove_menu_page( 'upload.php' );
	remove_menu_page( 'link-manager.php' );
	remove_menu_page( 'edit.php?post_type=page' );
	remove_menu_page( 'edit-comments.php' );
	remove_submenu_page( 'themes.php', 'nav-menus.php' );
	remove_menu_page( 'plugins.php' );
	remove_menu_page( 'users.php' );
	remove_menu_page( 'tools.php' );
	remove_menu_page( 'options-general.php' );

	$submenu['themes.php'][6][4] = 'customize-support'; // Fix empty submenu by overriding css. See line ~152 in: ./wp-admin/menu.php
}
add_action( 'admin_menu', __NAMESPACE__ . '\\admin_menu', 1 );

// Hide the admin bar
add_filter( 'show_admin_bar', function () {
	return false;
} );
