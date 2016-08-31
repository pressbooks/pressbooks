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

require( PB_PLUGIN_DIR . 'includes/admin/pb-branding.php' );
require( PB_PLUGIN_DIR . 'includes/pb-analytics.php' );
require( PB_PLUGIN_DIR . 'includes/pb-utility.php' );
require( PB_PLUGIN_DIR . 'includes/pb-image.php' );
require( PB_PLUGIN_DIR . 'includes/pb-l10n.php' );
require( PB_PLUGIN_DIR . 'includes/pb-postype.php' );
require( PB_PLUGIN_DIR . 'includes/pb-redirect.php' );
require( PB_PLUGIN_DIR . 'includes/pb-registration.php' );
require( PB_PLUGIN_DIR . 'includes/pb-sanitize.php' );
require( PB_PLUGIN_DIR . 'includes/pb-media.php' );
require( PB_PLUGIN_DIR . 'includes/pb-editor.php' );
require( PB_PLUGIN_DIR . 'vendor/pressbooks/pressbooks-latex/pb-latex.php' );

Pressbooks\Utility\include_plugins();

// -------------------------------------------------------------------------------------------------------------------
// Initialize services
// -------------------------------------------------------------------------------------------------------------------

if ( ! empty( $GLOBALS['PB_PIMPLE_OVERRIDE'] ) ) {
	\Pressbooks\Container::init( $GLOBALS['PB_PIMPLE_OVERRIDE'] );
}
else \Pressbooks\Container::init();

// -------------------------------------------------------------------------------------------------------------------
// Login screen branding
// -------------------------------------------------------------------------------------------------------------------

add_action( 'login_head', '\Pressbooks\Admin\Branding\custom_color_scheme' );
add_action( 'login_head', '\Pressbooks\Admin\Branding\custom_login_logo' );
add_filter( 'login_headerurl', '\Pressbooks\Admin\Branding\login_url' );
add_filter( 'login_headertitle', '\Pressbooks\Admin\Branding\login_title' );


// -------------------------------------------------------------------------------------------------------------------
// Analytics
// -------------------------------------------------------------------------------------------------------------------
add_action( 'wp_head', '\Pressbooks\Analytics\print_analytics');

// -------------------------------------------------------------------------------------------------------------------
// Custom Metadata plugin
// -------------------------------------------------------------------------------------------------------------------

add_filter( 'custom_metadata_manager_wysiwyg_args_field_pb_custom_copyright', '\Pressbooks\Editor\metadata_manager_default_editor_args' );
add_filter( 'custom_metadata_manager_wysiwyg_args_field_pb_about_unlimited', '\Pressbooks\Editor\metadata_manager_default_editor_args' );

// -------------------------------------------------------------------------------------------------------------------
// Languages
// -------------------------------------------------------------------------------------------------------------------

add_action( 'init', '\Pressbooks\L10n\load_plugin_textdomain' );
add_filter( 'gettext', '\Pressbooks\L10n\override_core_strings', 10, 3 );

if ( \Pressbooks\Book::isBook() && \Pressbooks\l10n\use_book_locale() ) {
	add_filter( 'locale', '\Pressbooks\Modules\Export\Export::setLocale' );
} elseif ( \Pressbooks\Book::isBook() ) {
	add_filter( 'locale', '\Pressbooks\L10n\set_locale' );
} elseif ( ! \Pressbooks\Book::isBook() ) {
	add_filter( 'locale', '\Pressbooks\L10n\set_root_locale' );
}
add_action( 'user_register', '\Pressbooks\L10n\set_user_interface_lang', 10, 1 );

// -------------------------------------------------------------------------------------------------------------------
// Images
// -------------------------------------------------------------------------------------------------------------------

add_action( 'init', '\Pressbooks\Image\fix_intermediate_image_size_options' );
add_filter( 'intermediate_image_sizes', '\Pressbooks\Image\intermediate_image_sizes' );
add_filter( 'intermediate_image_sizes_advanced', '\Pressbooks\Image\intermediate_image_sizes_advanced' );
add_action( 'delete_attachment', '\Pressbooks\Image\delete_attachment' );
add_filter( 'wp_update_attachment_metadata', '\Pressbooks\Image\save_attachment', 10, 2 );
add_filter( 'the_content', '\Pressbooks\Media\force_wrap_images', 13 ); // execute image-hack after wpautop processing
add_filter( 'plupload_default_params', '\Pressbooks\Media\force_attach_media' );

// -------------------------------------------------------------------------------------------------------------------
// Audio/Video
// -------------------------------------------------------------------------------------------------------------------

add_filter('upload_mimes', '\Pressbooks\Media\add_mime_types');

// -------------------------------------------------------------------------------------------------------------------
// Custom Post Types and Taxonomies
// -------------------------------------------------------------------------------------------------------------------

add_action( 'init', '\Pressbooks\PostType\register_post_types' );
add_action( 'post_updated_messages', '\Pressbooks\PostType\post_type_messages' );
add_action( 'init', '\Pressbooks\Taxonomy::registerTaxonomies' );
if ( \Pressbooks\Book::isBook() ) {
	add_filter( 'request', '\Pressbooks\PostType\add_post_types_rss' );
}

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
	$activate = new \Pressbooks\Activation();
	$activate->wpmuNewBlog( $b, $u );
}, 9, 2 );

// Force PB colors
add_action( 'wp_login', '\Pressbooks\Activation::forcePbColors', 10, 2 );
add_action( 'profile_update', '\Pressbooks\Activation::forcePbColors' );
add_action( 'user_register', '\Pressbooks\Activation::forcePbColors' );

// -------------------------------------------------------------------------------------------------------------------
// Redirects
// -------------------------------------------------------------------------------------------------------------------

add_filter( 'init', '\Pressbooks\Redirect\rewrite_rules_for_format', 1 );
add_filter( 'init', '\Pressbooks\Redirect\rewrite_rules_for_catalog', 1 );
add_filter( 'init', '\Pressbooks\Redirect\rewrite_rules_for_api', 1 );
add_filter( 'init', '\Pressbooks\Redirect\rewrite_rules_for_open', 1 );
add_filter( 'login_redirect', '\Pressbooks\Redirect\login', 10, 3 );

// -------------------------------------------------------------------------------------------------------------------
// Sitemap
// -------------------------------------------------------------------------------------------------------------------

add_filter( 'init', '\Pressbooks\Redirect\rewrite_rules_for_sitemap', 1 );
add_action( 'do_robotstxt', '\Pressbooks\Utility\add_sitemap_to_robots_txt' );

// -------------------------------------------------------------------------------------------------------------------
// Shortcodes
// -------------------------------------------------------------------------------------------------------------------

remove_filter( 'the_content', 'wpautop' );
add_filter( 'the_content', 'wpautop' , 12); // execute wpautop after shortcode processing

$_ = \Pressbooks\Shortcodes\Footnotes\Footnotes::getInstance();
$_ = \Pressbooks\Shortcodes\Generics\Generics::getInstance();
$_ = \Pressbooks\Shortcodes\WikiPublisher\Glyphs::getInstance();

// -------------------------------------------------------------------------------------------------------------------
// Upgrade Book Metadata
// -------------------------------------------------------------------------------------------------------------------

if ( \Pressbooks\Book::isBook() ) {
	add_action( 'init', function () {
		$meta_version = get_option( 'pressbooks_metadata_version', 0 );
		if ( $meta_version < \Pressbooks\Metadata::$currentVersion ) {
			$metadata = new \Pressbooks\Metadata();
			$metadata->upgrade( $meta_version );
			update_option( 'pressbooks_metadata_version', \Pressbooks\Metadata::$currentVersion );
		}
	}, 1000 );
}

// -------------------------------------------------------------------------------------------------------------------
// Upgrade Taxonomies
// -------------------------------------------------------------------------------------------------------------------
if ( \Pressbooks\Book::isBook() ) {
	add_action( 'init', function () {
		$taxonomy_version = get_option( 'pressbooks_taxonomy_version', 0 );
		if ( $taxonomy_version < \Pressbooks\Taxonomy::$currentVersion ) {
			$taxonomy = new \Pressbooks\Taxonomy();
			$taxonomy->upgrade( $taxonomy_version );
			update_option( 'pressbooks_taxonomy_version', \Pressbooks\Metadata::$currentVersion );
		}
	}, 1000 );
}

// -------------------------------------------------------------------------------------------------------------------
// Upgrade Catalog
// -------------------------------------------------------------------------------------------------------------------

add_action( 'init', function () {
	$catalog_version = get_site_option( 'pressbooks_catalog_version', 0 );
	if ( $catalog_version < \Pressbooks\Catalog::$currentVersion ) {
		$metadata = new \Pressbooks\Catalog();
		$metadata->upgrade( $catalog_version );
		update_site_option( 'pressbooks_catalog_version', \Pressbooks\Catalog::$currentVersion );
	}
}, 1000 );

// -------------------------------------------------------------------------------------------------------------------
// Force Flush
// -------------------------------------------------------------------------------------------------------------------

if ( ! empty( $GLOBALS['PB_SECRET_SAUCE']['FORCE_FLUSH'] ) ) {
	add_action( 'init', function () { flush_rewrite_rules( false ); }, 9999 );
} else {
	add_action( 'init', '\Pressbooks\Redirect\flusher', 9999 );
}

// -------------------------------------------------------------------------------------------------------------------
// Turn off XML-RPC
// -------------------------------------------------------------------------------------------------------------------

add_filter( 'xmlrpc_enabled', '__return_false' );
remove_action( 'wp_head', 'rsd_link' );
remove_action( 'wp_head', 'wlwmanifest_link' );


// -------------------------------------------------------------------------------------------------------------------
// Override other people's plugins
// -------------------------------------------------------------------------------------------------------------------

// Disable logging of Akismet debug data when WP_DEBUG_LOG is true
add_filter( 'akismet_debug_log', '__return_false' );

// -------------------------------------------------------------------------------------------------------------------
// Registration
// -------------------------------------------------------------------------------------------------------------------

add_filter( 'gettext', '\Pressbooks\Registration\custom_signup_text', 20, 3 );
add_action( 'signup_extra_fields', '\Pressbooks\Registration\add_password_field', 9 );
add_filter( 'wpmu_validate_user_signup', '\Pressbooks\Registration\validate_passwords' );
add_filter( 'add_signup_meta', '\Pressbooks\Registration\add_temporary_password', 99 );
add_action( 'signup_blogform', '\Pressbooks\Registration\add_hidden_password_field' );
add_filter( 'random_password', '\Pressbooks\Registration\override_password_generation' );
