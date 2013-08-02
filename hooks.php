<?php
/**
 * @author  PressBooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */

if ( ! defined( 'ABSPATH' ) )
	exit;

// -------------------------------------------------------------------------------------------------------------------
// Includes
// -------------------------------------------------------------------------------------------------------------------

require( PB_PLUGIN_DIR . 'includes/pb-utility.php' );
require( PB_PLUGIN_DIR . 'includes/pb-image.php' );
require( PB_PLUGIN_DIR . 'includes/pb-l10n.php' );
require( PB_PLUGIN_DIR . 'includes/pb-postype.php' );
require( PB_PLUGIN_DIR . 'includes/pb-redirect.php' );
require( PB_PLUGIN_DIR . 'includes/pb-sanitize.php' );
require( PB_PLUGIN_DIR . 'includes/pb-taxonomy.php' );

// -------------------------------------------------------------------------------------------------------------------
// Custom Metadata plugin
// -------------------------------------------------------------------------------------------------------------------

add_filter( 'custom_metadata_manager_default_editor_args', '\PressBooks\Editor::metadataManagerDefaultEditorArgs' );
require( PB_PLUGIN_DIR . 'symbionts/custom-metadata/custom_metadata.php' );

// -------------------------------------------------------------------------------------------------------------------
// Languages
// -------------------------------------------------------------------------------------------------------------------

add_action( 'init', '\PressBooks\L10n\load_plugin_textdomain' );
add_filter( 'gettext', '\PressBooks\L10n\override_core_strings', 10, 3 );

if ( \PressBooks\Book::isBook() && \PressBooks\l10n\use_book_locale() ) {
	add_filter( 'locale', '\PressBooks\Export\Export::setLocale' );
} else {
	add_filter( 'locale', '\PressBooks\L10n\set_locale' );
}

// -------------------------------------------------------------------------------------------------------------------
// Images
// -------------------------------------------------------------------------------------------------------------------

add_action( 'init', '\PressBooks\Image\fix_intermediate_image_size_options' );
add_filter( 'intermediate_image_sizes', '\PressBooks\Image\intermediate_image_sizes' );
add_filter( 'intermediate_image_sizes_advanced', '\PressBooks\Image\intermediate_image_sizes_advanced' );
add_action( 'delete_attachment', '\PressBooks\Image\delete_attachment' );
add_filter( 'wp_update_attachment_metadata', '\PressBooks\Image\save_attachment', 10, 2 );

// -------------------------------------------------------------------------------------------------------------------
// Custom Post Types and Taxonomies
// -------------------------------------------------------------------------------------------------------------------

add_action( 'init', '\PressBooks\PostType\register_post_types' );
add_action( 'init', '\PressBooks\Taxonomy\register_taxonomies' );

// -------------------------------------------------------------------------------------------------------------------
// Remove the "admin bar" from any public facing theme
// -------------------------------------------------------------------------------------------------------------------

if ( is_admin() === false ) {
	add_action( 'init', function () {
		wp_deregister_script( 'admin-bar' );
		wp_deregister_style( 'admin-bar' );
		remove_action( 'init', '_wp_admin_bar_init' );
		remove_action( 'wp_footer', 'wp_admin_bar_render', 1000 );
		remove_action( 'admin_footer', 'wp_admin_bar_render', 1000 );
	}, 0 );
}

// -------------------------------------------------------------------------------------------------------------------
// The following is used when a REGISTERED USER creates a NEW BLOG
// -------------------------------------------------------------------------------------------------------------------

add_action( 'wpmu_new_blog', function ( $b, $u ) {
	$activate = new \PressBooks\Activation();
	$activate->wpmuNewBlog( $b, $u );
}, 9, 2 );

// -------------------------------------------------------------------------------------------------------------------
// Redirects
// -------------------------------------------------------------------------------------------------------------------

add_filter( 'init', '\PressBooks\Redirect\rewrite_rules_for_format', 1 );
add_filter( 'init', '\PressBooks\Redirect\rewrite_rules_for_catalog', 1 );
add_filter( 'login_redirect', '\PressBooks\Redirect\login', 10, 3 );

// -------------------------------------------------------------------------------------------------------------------
// Sitemap
// -------------------------------------------------------------------------------------------------------------------

add_filter( 'init', '\PressBooks\Redirect\rewrite_rules_for_sitemap', 1 );
add_action( 'do_robotstxt', '\PressBooks\Utility\add_sitemap_to_robots_txt' );

// -------------------------------------------------------------------------------------------------------------------
// Shortcodes
// -------------------------------------------------------------------------------------------------------------------

$_ = new \PressBooks\Shortcodes\Footnotes\Footnotes();
$_ = new \PressBooks\Shortcodes\WikiPublisher\Glyphs();

// -------------------------------------------------------------------------------------------------------------------
// Upgrade Book Metadata
// -------------------------------------------------------------------------------------------------------------------

if ( \PressBooks\Book::isBook() ) {
	add_action( 'init', function () {
		$meta_version = get_option( 'pressbooks_metadata_version', 0 );
		if ( $meta_version < \PressBooks\Metadata::$currentVersion ) {
			$metadata = new \PressBooks\Metadata();
			$metadata->upgrade( $meta_version );
			update_option( 'pressbooks_metadata_version', \PressBooks\Metadata::$currentVersion );
		}
	}, 1000 );
}

// -------------------------------------------------------------------------------------------------------------------
// Upgrade Catalog
// -------------------------------------------------------------------------------------------------------------------

add_action( 'init', function () {
	$catalog_version = get_site_option( 'pressbooks_catalog_version', 0 );
	if ( $catalog_version < \PressBooks\Catalog::$currentVersion ) {
		$metadata = new \PressBooks\Catalog();
		$metadata->upgrade( $catalog_version );
		update_site_option( 'pressbooks_catalog_version', \PressBooks\Catalog::$currentVersion );
	}
}, 1000 );

// -------------------------------------------------------------------------------------------------------------------
// Force Flush
// -------------------------------------------------------------------------------------------------------------------

if ( ! empty( $GLOBALS['PB_SECRET_SAUCE']['FORCE_FLUSH'] ) ) {
	add_action( 'init', function () { flush_rewrite_rules( false ); }, 9999 );
} else {
	add_action( 'init', '\PressBooks\Redirect\flusher', 9999 );
}

// -------------------------------------------------------------------------------------------------------------------
// Turn off XML-RPC
// -------------------------------------------------------------------------------------------------------------------

add_filter( 'xmlrpc_enabled', '__return_false' );
remove_action( 'wp_head', 'rsd_link' );
remove_action( 'wp_head', 'wlwmanifest_link' );
