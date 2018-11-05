<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

use function \Pressbooks\l10n\use_book_locale;
use function \Pressbooks\Utility\include_plugins as include_symbionts;
use Pressbooks\Book;
use Pressbooks\Container;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// -------------------------------------------------------------------------------------------------------------------
// Includes
// -------------------------------------------------------------------------------------------------------------------

require_once( __DIR__ . '/requires.php' );
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
// Activation
// -------------------------------------------------------------------------------------------------------------------

// Disable SSL verification for development
if ( defined( 'WP_ENV' ) && WP_ENV === 'development' ) {
	add_filter( 'https_local_ssl_verify', '__return_false' );
	add_filter( 'https_ssl_verify', '__return_false' );
}

\Pressbooks\Activation::init();

// -------------------------------------------------------------------------------------------------------------------
// API
// -------------------------------------------------------------------------------------------------------------------

add_filter( 'rest_index', '\Pressbooks\Api\add_help_link' );

if ( $is_book ) {
	add_action( 'rest_api_init', '\Pressbooks\Api\init_book' );
	add_filter( 'rest_endpoints', '\Pressbooks\Api\hide_endpoints_from_book' );
	add_filter( 'rest_url', '\Pressbooks\Api\fix_book_urls', 10, 2 );
	add_filter( 'rest_prepare_attachment', '\Pressbooks\Api\fix_attachment', 10, 3 );
} elseif ( $enable_network_api ) {
	add_action( 'rest_api_init', '\Pressbooks\Api\init_root' );
	add_filter( 'rest_endpoints', '\Pressbooks\Api\hide_endpoints_from_root' );
}

// -------------------------------------------------------------------------------------------------------------------
// Login screen branding
// -------------------------------------------------------------------------------------------------------------------

add_filter( 'login_body_class', '\Pressbooks\Admin\Branding\login_body_class' );
add_action( 'login_head', '\Pressbooks\Admin\Branding\custom_color_scheme' );
add_action( 'login_head', '\Pressbooks\Admin\Branding\custom_login_logo' );
add_filter( 'login_headerurl', '\Pressbooks\Admin\Branding\login_url' );
add_filter( 'login_headertitle', '\Pressbooks\Admin\Branding\login_title' );
add_filter( 'login_title', '\Pressbooks\Admin\Branding\admin_title' );
add_action( 'login_footer', '\Pressbooks\Admin\Branding\login_scripts' );

// -------------------------------------------------------------------------------------------------------------------
// Analytics
// -------------------------------------------------------------------------------------------------------------------

add_action( 'init', '\Pressbooks\Analytics\migrate' );
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
add_filter( 'the_content', '\Pressbooks\Sanitize\sanitize_webbook_content' );
add_filter( 'the_export_content', '\Pressbooks\Sanitize\filter_export_content' );

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
\Pressbooks\Interactive\Content::init();

// -------------------------------------------------------------------------------------------------------------------
// Custom Post Types and Taxonomies
// -------------------------------------------------------------------------------------------------------------------

if ( $is_book ) {
	add_action( 'init', '\Pressbooks\PostType\register_post_types' );
	add_filter( 'comments_open', '\Pressbooks\PostType\comments_open', 10, 2 );
	\Pressbooks\Taxonomy::init();
	add_action( 'init', '\Pressbooks\PostType\register_meta' );
	add_action( 'init', '\Pressbooks\PostType\register_post_statii' );
	add_filter( 'request', '\Pressbooks\PostType\add_post_types_rss' );
	add_filter( 'hypothesis_supported_posttypes', '\Pressbooks\PostType\add_posttypes_to_hypothesis' );
	add_filter( 'pb_post_type_label', '\Pressbooks\PostType\filter_post_type_label', 10, 2 );
}

// -------------------------------------------------------------------------------------------------------------------
// Remove the "admin bar" from any public facing theme
// -------------------------------------------------------------------------------------------------------------------

if ( is_admin() === false ) {
	add_action(
		'init', function () {
			wp_deregister_script( 'admin-bar' );
			wp_deregister_style( 'admin-bar' );
			remove_action( 'init', '_wp_admin_bar_init' );
			remove_action( 'wp_footer', 'wp_admin_bar_render', 1000 );
			remove_action( 'admin_footer', 'wp_admin_bar_render', 1000 );
		}, 0
	);
}

// -------------------------------------------------------------------------------------------------------------------
// Redirects
// -------------------------------------------------------------------------------------------------------------------

if ( $enable_network_api ) {
	add_filter( 'init', '\Pressbooks\Redirect\rewrite_rules_for_api', 1 ); // API V1
}
add_filter( 'init', '\Pressbooks\Redirect\rewrite_rules_for_format', 1 );
add_filter( 'init', '\Pressbooks\Redirect\rewrite_rules_for_catalog', 1 );
add_filter( 'init', '\Pressbooks\Redirect\rewrite_rules_for_open', 1 );
add_action( 'plugins_loaded', '\Pressbooks\Redirect\migrate_generated_content', 1 );

// -------------------------------------------------------------------------------------------------------------------
// Sitemap
// -------------------------------------------------------------------------------------------------------------------

add_filter( 'init', '\Pressbooks\Redirect\rewrite_rules_for_sitemap', 1 );
add_action( 'do_robotstxt', '\Pressbooks\Utility\add_sitemap_to_robots_txt' );

// -------------------------------------------------------------------------------------------------------------------
// Shortcodes
// -------------------------------------------------------------------------------------------------------------------

if ( $is_book ) {
	remove_filter( 'the_content', 'wpautop' );
	add_filter( 'the_content', 'wpautop', 12 ); // execute wpautop after shortcode processing

	\Pressbooks\Shortcodes\Footnotes\Footnotes::init();
	\Pressbooks\Shortcodes\Attributions\Attachments::init();
	\Pressbooks\Shortcodes\Glossary\Glossary::init();
	\Pressbooks\Shortcodes\Complex\Complex::init();
	\Pressbooks\Shortcodes\Generics\Generics::init();
	\Pressbooks\Shortcodes\WikiPublisher\Glyphs::init();
	\Pressbooks\Shortcodes\TablePress::init();
}

// Support QuickLaTeX in TablePress
if ( is_plugin_active_for_network( 'wp-quicklatex/wp-quicklatex.php' ) || is_plugin_active( 'wp-quicklatex/wp-quicklatex.php' ) ) {
	add_filter( 'tablepress_cell_content', 'quicklatex_parser' );
}

// -------------------------------------------------------------------------------------------------------------------
// Theme Lock
// -------------------------------------------------------------------------------------------------------------------

\Pressbooks\Theme\Lock::init();

// -------------------------------------------------------------------------------------------------------------------
// Upgrade Book Metadata
// -------------------------------------------------------------------------------------------------------------------

if ( $is_book ) {
	add_action(
		'init', function () {
			$meta_version = get_option( 'pressbooks_metadata_version', 0 );
			if ( $meta_version < \Pressbooks\Metadata::VERSION ) {
				( new \Pressbooks\Metadata() )->upgrade( $meta_version );
				update_option( 'pressbooks_metadata_version', \Pressbooks\Metadata::VERSION );
			}
		}, 1000
	);
}

// -------------------------------------------------------------------------------------------------------------------
// Upgrade Catalog
// -------------------------------------------------------------------------------------------------------------------

add_action(
	'init', function () {
		$catalog_version = get_site_option( 'pressbooks_catalog_version', 0 );
		if ( $catalog_version < \Pressbooks\Catalog::VERSION ) {
			( new \Pressbooks\Catalog() )->upgrade( $catalog_version );
			update_site_option( 'pressbooks_catalog_version', \Pressbooks\Catalog::VERSION );
		}
	}, 1000
);

// -------------------------------------------------------------------------------------------------------------------
// Migrate Themes
// -------------------------------------------------------------------------------------------------------------------

add_action( 'init', '\Pressbooks\Theme\migrate_book_themes' );
add_action( 'init', '\Pressbooks\Theme\update_template_root' );

// -------------------------------------------------------------------------------------------------------------------
// Regenerate stylesheets
// -------------------------------------------------------------------------------------------------------------------

add_action(
	'init', function() {
		Container::get( 'Styles' )->maybeUpdateStylesheets();
	}
);

// -------------------------------------------------------------------------------------------------------------------
// Force Flush
// -------------------------------------------------------------------------------------------------------------------

if ( ! empty( $GLOBALS['PB_SECRET_SAUCE']['FORCE_FLUSH'] ) ) {
	add_action(
		'init', function () {
			flush_rewrite_rules( false );
		}, 9999
	);
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
// (Custom) Styles
// -------------------------------------------------------------------------------------------------------------------

Container::get( 'Styles' )->init();

if ( $is_book ) {
	// Overrides (sometimes a web stylesheet update will be triggered by a visitor so this filter needs to be active outside of the admin)
	add_filter( 'pb_web_css_override', [ '\Pressbooks\Modules\ThemeOptions\WebOptions', 'scssOverrides' ] );
}

// -------------------------------------------------------------------------------------------------------------------
// GDPR
// -------------------------------------------------------------------------------------------------------------------

add_action( 'init', [ '\Pressbooks\Privacy', 'init' ], 9 ); // Must come before `add_action( 'init', 'wp_schedule_delete_old_privacy_export_files' );`

