<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */

use Pressbooks\Book;
use Pressbooks\Container;
use Pressbooks\Theme\Lock;
use function \Pressbooks\l10n\use_book_locale;
use function \Pressbooks\Utility\include_plugins as include_symbionts;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// -------------------------------------------------------------------------------------------------------------------
// Includes
// -------------------------------------------------------------------------------------------------------------------

require( PB_PLUGIN_DIR . 'inc/admin/branding/namespace.php' );
require( PB_PLUGIN_DIR . 'inc/analytics/namespace.php' );
require( PB_PLUGIN_DIR . 'inc/api/namespace.php' );
require( PB_PLUGIN_DIR . 'inc/editor/namespace.php' );
require( PB_PLUGIN_DIR . 'inc/image/namespace.php' );
require( PB_PLUGIN_DIR . 'inc/l10n/namespace.php' );
require( PB_PLUGIN_DIR . 'inc/media/namespace.php' );
require( PB_PLUGIN_DIR . 'inc/metadata/namespace.php' );
require( PB_PLUGIN_DIR . 'inc/posttype/namespace.php' );
require( PB_PLUGIN_DIR . 'inc/redirect/namespace.php' );
require( PB_PLUGIN_DIR . 'inc/registration/namespace.php' );
require( PB_PLUGIN_DIR . 'inc/sanitize/namespace.php' );
require( PB_PLUGIN_DIR . 'inc/theme/namespace.php' );
require( PB_PLUGIN_DIR . 'inc/utility/namespace.php' );

include_symbionts();

// -------------------------------------------------------------------------------------------------------------------
// Recycle, reduce, reuse
// -------------------------------------------------------------------------------------------------------------------

$is_book = Book::isBook();
$enable_network_api = \Pressbooks\Api\is_enabled();

// -------------------------------------------------------------------------------------------------------------------
// Initialize services
// -------------------------------------------------------------------------------------------------------------------

if ( ! empty( $GLOBALS['PB_PIMPLE_OVERRIDE'] ) ) {
	Container::init( $GLOBALS['PB_PIMPLE_OVERRIDE'] );
} else {
	Container::init();
}

// -------------------------------------------------------------------------------------------------------------------
// API
// -------------------------------------------------------------------------------------------------------------------

add_filter( 'rest_index', '\Pressbooks\Api\add_help_link' );

if ( $is_book ) {
	add_action( 'rest_api_init', '\Pressbooks\Api\init_book' );
	add_filter( 'rest_endpoints', 'Pressbooks\Api\hide_endpoints_from_book' );
	add_filter( 'rest_url', 'Pressbooks\Api\fix_book_urls', 10, 2 );
} elseif ( $enable_network_api ) {
	add_action( 'rest_api_init', '\Pressbooks\Api\init_root' );
	add_filter( 'rest_endpoints', 'Pressbooks\Api\hide_endpoints_from_root' );
}

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
add_action( 'wp_head', '\Pressbooks\Analytics\print_analytics' );

// -------------------------------------------------------------------------------------------------------------------
// Custom Metadata plugin
// -------------------------------------------------------------------------------------------------------------------

add_filter( 'custom_metadata_manager_wysiwyg_args_field_pb_custom_copyright', '\Pressbooks\Editor\metadata_manager_default_editor_args' );
add_filter( 'custom_metadata_manager_wysiwyg_args_field_pb_about_unlimited', '\Pressbooks\Editor\metadata_manager_default_editor_args' );

// -------------------------------------------------------------------------------------------------------------------
// Languages
// -------------------------------------------------------------------------------------------------------------------

add_action( 'init', '\Pressbooks\L10n\load_plugin_textdomain' );
add_action( 'admin_init', '\Pressbooks\L10n\update_user_locale' );
add_filter( 'gettext', '\Pressbooks\L10n\override_core_strings', 10, 3 );

if ( $is_book && use_book_locale() ) {
	add_filter( 'locale', '\Pressbooks\Modules\Export\Export::setLocale' );
} elseif ( $is_book ) {
	add_filter( 'locale', '\Pressbooks\L10n\set_locale' );
} elseif ( ! $is_book ) {
	add_filter( 'locale', '\Pressbooks\L10n\set_root_locale' );
}

// -------------------------------------------------------------------------------------------------------------------
// Content filters
// -------------------------------------------------------------------------------------------------------------------

add_action( 'init', '\Pressbooks\Sanitize\allow_post_content' );

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

add_filter( 'upload_mimes', '\Pressbooks\Media\add_mime_types' );

// -------------------------------------------------------------------------------------------------------------------
// Custom Post Types and Taxonomies
// -------------------------------------------------------------------------------------------------------------------

if ( $is_book ) {
	add_action( 'init', '\Pressbooks\PostType\register_post_types' );
	add_action( 'init', '\Pressbooks\PostType\register_meta' );
	add_action( 'init', '\Pressbooks\Taxonomy::registerTaxonomies' );
	add_action( 'post_updated_messages', '\Pressbooks\PostType\post_type_messages' );
	add_filter( 'request', '\Pressbooks\PostType\add_post_types_rss' );
	add_filter( 'hypothesis_supported_posttypes', '\Pressbooks\PostType\add_posttypes_to_hypothesis' );
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
	( new \Pressbooks\Activation() )->wpmuNewBlog( $b, $u );
}, 9, 2 );

// Force PB colors
add_action( 'wp_login', '\Pressbooks\Activation::forcePbColors', 10, 2 );
add_action( 'profile_update', '\Pressbooks\Activation::forcePbColors' );
add_action( 'user_register', '\Pressbooks\Activation::forcePbColors' );

// -------------------------------------------------------------------------------------------------------------------
// Redirects
// -------------------------------------------------------------------------------------------------------------------

if ( $enable_network_api ) {
	add_filter( 'init', '\Pressbooks\Redirect\rewrite_rules_for_api', 1 ); // API V1
}
add_filter( 'init', '\Pressbooks\Redirect\rewrite_rules_for_format', 1 );
add_filter( 'init', '\Pressbooks\Redirect\rewrite_rules_for_catalog', 1 );
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
add_filter( 'the_content', 'wpautop' , 12 ); // execute wpautop after shortcode processing

$_ = \Pressbooks\Shortcodes\Footnotes\Footnotes::init();
$_ = \Pressbooks\Shortcodes\Generics\Generics::init();
$_ = \Pressbooks\Shortcodes\WikiPublisher\Glyphs::init();

// Theme Lock
if ( $is_book && Lock::isLocked() ) {
	add_filter( 'pb_stylesheet_directory', [ '\Pressbooks\Theme\Lock', 'getLockDir' ] );
	add_filter( 'pb_stylesheet_directory_uri', [ '\Pressbooks\Theme\Lock', 'getLockDirURI' ] );
}

// -------------------------------------------------------------------------------------------------------------------
// Upgrade Book Metadata
// -------------------------------------------------------------------------------------------------------------------

if ( $is_book ) {
	add_action( 'init', function () {
		$meta_version = get_option( 'pressbooks_metadata_version', 0 );
		if ( $meta_version < \Pressbooks\Metadata::VERSION ) {
			( new \Pressbooks\Metadata() )->upgrade( $meta_version );
			update_option( 'pressbooks_metadata_version', \Pressbooks\Metadata::VERSION );
		}
	}, 1000 );
}

// -------------------------------------------------------------------------------------------------------------------
// Upgrade Taxonomies
// -------------------------------------------------------------------------------------------------------------------

// TODO: Before this commit, we were updating 'pressbooks_taxonomy_version' with \Pressbooks\Metadata::VERSION (bug)

if ( $is_book ) {
	add_action( 'init', function () {
		$taxonomy_version = get_option( 'pressbooks_taxonomy_version', 0 );
		if ( $taxonomy_version < \Pressbooks\Taxonomy::VERSION ) {
			( new \Pressbooks\Taxonomy() )->upgrade( $taxonomy_version );
			update_option( 'pressbooks_taxonomy_version', \Pressbooks\Taxonomy::VERSION );
		}
	}, 1000 );
}

// -------------------------------------------------------------------------------------------------------------------
// Upgrade Catalog
// -------------------------------------------------------------------------------------------------------------------

add_action( 'init', function () {
	$catalog_version = get_site_option( 'pressbooks_catalog_version', 0 );
	if ( $catalog_version < \Pressbooks\Catalog::VERSION ) {
		( new \Pressbooks\Catalog() )->upgrade( $catalog_version );
		update_site_option( 'pressbooks_catalog_version', \Pressbooks\Catalog::VERSION );
	}
}, 1000 );

// -------------------------------------------------------------------------------------------------------------------
// Migrate Themes
// -------------------------------------------------------------------------------------------------------------------

add_action( 'init', '\Pressbooks\Theme\migrate_book_themes' );
add_action( 'init', '\Pressbooks\Theme\update_template_root' );

// -------------------------------------------------------------------------------------------------------------------
// Regenerate web theme stylesheet
// -------------------------------------------------------------------------------------------------------------------

add_action( 'init', function() {
	Container::get( 'Styles' )->maybeUpdateWebBookStyleSheet();
} );

// -------------------------------------------------------------------------------------------------------------------
// Force Flush
// -------------------------------------------------------------------------------------------------------------------

if ( ! empty( $GLOBALS['PB_SECRET_SAUCE']['FORCE_FLUSH'] ) ) {
	add_action( 'init', function () { flush_rewrite_rules( false );
	}, 9999 );
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

// Email configuration
add_filter( 'wp_mail_from', '\Pressbooks\Utility\mail_from' );
add_filter( 'wp_mail_from_name', '\Pressbooks\Utility\mail_from_name' );

// -------------------------------------------------------------------------------------------------------------------
// Custom Styles
// -------------------------------------------------------------------------------------------------------------------

Container::get( 'Styles' )->init();


