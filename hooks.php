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

require_once( PB_PLUGIN_DIR . 'includes/pb-utility.php' );
require_once( PB_PLUGIN_DIR . 'includes/pb-l10n.php' );
require_once( PB_PLUGIN_DIR . 'includes/pb-postype.php' );
require_once( PB_PLUGIN_DIR . 'includes/pb-redirect.php' );
require_once( PB_PLUGIN_DIR . 'includes/pb-sanitize.php' );
require_once( PB_PLUGIN_DIR . 'includes/pb-taxonomy.php' );

// -------------------------------------------------------------------------------------------------------------------
// Custom Metadata plugin
// -------------------------------------------------------------------------------------------------------------------

add_filter( 'custom_metadata_manager_default_editor_args', '\PressBooks\Metadata::metadataManagerDefaultEditorArgs' );
require_once( PB_PLUGIN_DIR . 'symbionts/custom-metadata/custom_metadata.php' );

// -------------------------------------------------------------------------------------------------------------------
// Languages
// -------------------------------------------------------------------------------------------------------------------

add_action( 'init', '\PressBooks\L10n\load_plugin_textdomain' );
add_filter( 'gettext', '\PressBooks\L10n\override_core_strings', 10, 3 );
add_filter( 'locale', '\PressBooks\L10n\set_locale' );

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
add_filter( 'login_redirect', '\PressBooks\Redirect\login', 10, 3 );

// -------------------------------------------------------------------------------------------------------------------
// Shortcodes
// -------------------------------------------------------------------------------------------------------------------

$_ = new \PressBooks\Shortcodes\Footnotes\Footnotes();
$_ = new \PressBooks\Shortcodes\WikiPublisher\Glyphs();

// -------------------------------------------------------------------------------------------------------------
// Upgrade Book Metadata
// -------------------------------------------------------------------------------------------------------------------

if ( \PressBooks\Book::isBook() ) {
	add_action( 'init', function () {
		$meta_version = get_option( 'pressbooks_metadata_version' );
		if ( $meta_version < \PressBooks\Metadata::$currentVersion ) {
			$metadata = new \PressBooks\Metadata();
			$metadata->upgrade( $meta_version );
			update_option( 'pressbooks_metadata_version', \PressBooks\Metadata::$currentVersion );
		}
	} );
}