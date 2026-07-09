<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

use function Pressbooks\Api\is_enabled;
use function \Pressbooks\Utility\include_plugins as include_symbionts;
use Pressbooks\Book;
use Pressbooks\CloneComplete;
use Pressbooks\Container;
use Pressbooks\Privacy;
use Pressbooks\ServiceProvider;

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
$enable_network_api = is_enabled();

// -------------------------------------------------------------------------------------------------------------------
// Initialize services
// -------------------------------------------------------------------------------------------------------------------

ServiceProvider::init();

// -------------------------------------------------------------------------------------------------------------------
// Activation
// -------------------------------------------------------------------------------------------------------------------

// Disable SSL verification for development
if ( defined( 'WP_ENV' ) && WP_ENV === 'development' ) {
	add_filter( 'https_local_ssl_verify', '__return_false' );
	add_filter( 'https_ssl_verify', '__return_false' );
}

add_action( 'plugins_loaded', [ '\Pressbooks\Activation', 'init' ] );

// -------------------------------------------------------------------------------------------------------------------
// Archive Banner
// -------------------------------------------------------------------------------------------------------------------

add_filter( 'ms_site_check', function() {
	// Only intervene for Pressbooks books, not main site
	if ( ! Book::isBook() ) {
		return null;
	}

	$site_details = get_blog_details();

	$is_archived = ! empty( $site_details->archived ) && '1' === $site_details->archived;

	if ( $is_archived ) {
		// Archived public books remain accessible (with banner)
		if ( ! empty( $site_details->public ) && '1' === $site_details->public ) {
			return true;
		}

		// Archived private books: allow access for logged-in book members
		$user_id = get_current_user_id();
		if ( $user_id ) {
			global $wpdb;
			$capabilities_key = $wpdb->get_blog_prefix() . 'capabilities';
			$has_cap = get_user_meta( $user_id, $capabilities_key, true );
			if ( is_array( $has_cap ) ) {
				return true;
			}
		}
	}

	// Let WordPress handle normal archived/spam/deleted checks
	return null;
}, 1 );

// -------------------------------------------------------------------------------------------------------------------
// Sync WordPress native archive action with Pressbooks archive fields
// -------------------------------------------------------------------------------------------------------------------

add_action( 'wp_update_site', function( $new_site, $old_site ) {
	// Only process for books, not main site
	if ( ! Book::isBook() && $new_site->blog_id !== get_current_blog_id() ) {
		return;
	}

	// Check if archived status changed
	if ( isset( $new_site->archived ) && $new_site->archived !== $old_site->archived ) {
		if ( '1' === $new_site->archived ) {
			// Book was archived via WordPress native UI - sync to Pressbooks fields
			$existing_date = get_site_meta( $new_site->blog_id, \Pressbooks\DataCollector\Book::ARCHIVED_DATE, true );
			if ( empty( $existing_date ) ) {
				// Only set if not already archived via Pressbooks interface
				update_site_meta( $new_site->blog_id, \Pressbooks\DataCollector\Book::ARCHIVED_DATE, gmdate( 'Y-m-d H:i:s' ) );
				update_site_meta( $new_site->blog_id, \Pressbooks\DataCollector\Book::ARCHIVED_BY, get_current_user_id() );
			}
		} else {
			// Book was unarchived - remove Pressbooks fields
			delete_site_meta( $new_site->blog_id, \Pressbooks\DataCollector\Book::ARCHIVED_DATE );
			delete_site_meta( $new_site->blog_id, \Pressbooks\DataCollector\Book::ARCHIVED_BY );
		}
	}
}, 10, 2 );

// -------------------------------------------------------------------------------------------------------------------
// API
// -------------------------------------------------------------------------------------------------------------------

add_filter( 'rest_index', '\Pressbooks\Api\add_help_link' );
if ( $is_book ) {
	add_action( 'rest_api_init', '\Pressbooks\Api\init_book' );
	add_filter( 'rest_url', '\Pressbooks\Api\fix_book_urls', 10, 2 );
	add_filter( 'rest_prepare_attachment', '\Pressbooks\Api\fix_attachment', 10, 3 );
} elseif ( $enable_network_api ) {
	add_action( 'rest_api_init', '\Pressbooks\Api\init_book' );
	add_action( 'rest_api_init', '\Pressbooks\Api\init_root', 9 );
}

add_action( 'plugins_loaded', [ '\Pressbooks\DataCollector\User', 'init' ] );
add_action( 'plugins_loaded', [ '\Pressbooks\DataCollector\Book', 'init' ] );

// -------------------------------------------------------------------------------------------------------------------
// Login screen branding
// -------------------------------------------------------------------------------------------------------------------

add_filter( 'login_body_class', '\Pressbooks\Admin\Branding\login_body_class' );
add_action( 'login_head', '\Pressbooks\Admin\Branding\favicon' );
add_action( 'login_head', '\Pressbooks\Admin\Branding\custom_color_scheme' );
add_action( 'login_head', '\Pressbooks\Admin\Branding\custom_login_logo' );
add_filter( 'login_headerurl', '\Pressbooks\Admin\Branding\login_url' );
add_filter( 'login_headertext', '\Pressbooks\Admin\Branding\login_title' );
add_filter( 'login_title', '\Pressbooks\Admin\Branding\admin_title' );
add_action( 'login_footer', '\Pressbooks\Admin\Branding\login_scripts' );

// -------------------------------------------------------------------------------------------------------------------
// Analytics
// -------------------------------------------------------------------------------------------------------------------
add_action( 'init', [ '\Pressbooks\GoogleAnalytics', 'init' ] );

// -------------------------------------------------------------------------------------------------------------------
// Tracking
// -------------------------------------------------------------------------------------------------------------------

add_action( 'init', [ '\Pressbooks\Tracking\BookDownload', 'init' ] );

// -------------------------------------------------------------------------------------------------------------------
// Languages
// -------------------------------------------------------------------------------------------------------------------

add_action( 'init', '\Pressbooks\L10n\load_plugin_textdomain' );
add_action( 'switch_locale', '\Pressbooks\L10n\load_plugin_textdomain' );
add_action( 'restore_previous_locale', '\Pressbooks\L10n\load_plugin_textdomain' );
add_action( 'admin_init', '\Pressbooks\L10n\update_user_locale' );
add_filter( 'gettext', '\Pressbooks\L10n\override_core_strings', 10, 3 );

if ( $is_book ) {
	add_filter( 'locale', '\Pressbooks\L10n\set_locale' );
} else {
	add_filter( 'locale', '\Pressbooks\L10n\set_root_locale' );
}

// -------------------------------------------------------------------------------------------------------------------
// Content filters
// -------------------------------------------------------------------------------------------------------------------

add_action( 'init', '\Pressbooks\Sanitize\allow_post_content' );
add_filter( 'the_content', '\Pressbooks\Sanitize\sanitize_webbook_content' );
add_filter( 'the_export_content', '\Pressbooks\Sanitize\filter_export_content' );
add_filter( 'the_content', 'Pressbooks\Metadata\add_candela_citations', 13 );

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
add_filter( 'upload_mimes', '\Pressbooks\Media\add_lord_of_the_files_types', 11 );
add_action( 'plugins_loaded', [ '\Pressbooks\Interactive\Content', 'init' ] );

// -------------------------------------------------------------------------------------------------------------------
// Custom Post Types and Taxonomies
// -------------------------------------------------------------------------------------------------------------------

if ( $is_book ) {
	add_action( 'init', '\Pressbooks\PostType\register_post_types' );
	add_filter( 'comments_open', '\Pressbooks\PostType\comments_open', 10, 2 );
	add_action( 'plugins_loaded', [ '\Pressbooks\Taxonomy', 'init' ] );
	add_action( 'init', '\Pressbooks\PostType\register_post_statii' );
	add_filter( 'request', '\Pressbooks\PostType\add_post_types_rss' );
	add_filter( 'hypothesis_supported_posttypes', '\Pressbooks\PostType\add_posttypes_to_hypothesis' );
	add_filter( 'pb_post_type_label', '\Pressbooks\PostType\filter_post_type_label', 10, 2 );
}
// Register meta for both book and root for cloning metadata
add_action( 'init', '\Pressbooks\PostType\register_meta' );

// -------------------------------------------------------------------------------------------------------------------
// Reusable web components (available to core and downstream plugins on any page context)
// -------------------------------------------------------------------------------------------------------------------

add_action( 'init', '\Pressbooks\Utility\register_duet_date_picker' );

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

add_action( 'init', '\Pressbooks\Redirect\rewrite_rules_for_format', 1 );
add_action( 'init', '\Pressbooks\Redirect\rewrite_rules_for_catalog', 1 );
add_action( 'init', '\Pressbooks\Redirect\rewrite_rules_for_open', 1 );
add_action( 'plugins_loaded', '\Pressbooks\Redirect\migrate_generated_content', 1 );
add_filter( 'login_redirect', '\Pressbooks\Redirect\break_reset_password_loop', 10, 3 );
add_filter( 'login_redirect', '\Pressbooks\Redirect\handle_dashboard_redirect', 10, 3 );

// -------------------------------------------------------------------------------------------------------------------
// Sitemap
// -------------------------------------------------------------------------------------------------------------------

add_action( 'init', '\Pressbooks\Redirect\rewrite_rules_for_sitemap', 1 );
add_action( 'do_robotstxt', '\Pressbooks\Utility\add_sitemap_to_robots_txt' );
add_filter( 'wp_robots', '\Pressbooks\Utility\handle_book_indexing' );

// -------------------------------------------------------------------------------------------------------------------
// Shortcodes
// -------------------------------------------------------------------------------------------------------------------

if ( $is_book ) {
	remove_filter( 'the_content', 'wpautop' );
	add_filter( 'the_content', 'wpautop', 12 ); // execute wpautop after shortcode processing
	add_action( 'plugins_loaded', [ '\Pressbooks\Shortcodes\Footnotes\Footnotes', 'init' ] );
	add_action( 'plugins_loaded', [ '\Pressbooks\Shortcodes\Attributions\Attachments', 'init' ] );
	add_action( 'plugins_loaded', [ '\Pressbooks\Shortcodes\Glossary\Glossary', 'init' ] );
	add_action( 'plugins_loaded', [ 'Pressbooks\Contributors', 'init' ] );
	add_action( 'plugins_loaded', [ '\Pressbooks\Shortcodes\Complex\Complex', 'init' ] );
	add_action( 'plugins_loaded', [ '\Pressbooks\Shortcodes\Generics\Generics', 'init' ] );
	add_action( 'plugins_loaded', [ '\Pressbooks\Shortcodes\WikiPublisher\Glyphs', 'init' ] );
	add_action( 'plugins_loaded', [ '\Pressbooks\Shortcodes\TablePress', 'init' ] );
}

// Support QuickLaTeX in TablePress
if ( is_plugin_active_for_network( 'wp-quicklatex/wp-quicklatex.php' ) || is_plugin_active( 'wp-quicklatex/wp-quicklatex.php' ) ) {
	add_filter( 'tablepress_cell_content', 'quicklatex_parser' );
}

// -------------------------------------------------------------------------------------------------------------------
// Theme Lock
// -------------------------------------------------------------------------------------------------------------------

add_action( 'plugins_loaded', [ '\Pressbooks\Theme\Lock', 'init' ] );

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

add_action( 'init', function() {
	Container::get( 'Styles' )->maybeUpdateStylesheets();
} );

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
// Remove ability to disable file extension check when using H5P plugin
add_filter( 'user_has_cap', '\Pressbooks\Admin\Plugins\disable_h5p_security', 10, 3 );
add_filter( 'map_meta_cap', '\Pressbooks\Admin\Plugins\disable_h5p_security_superadmin', 10, 2 );
add_action( 'activated_plugin', '\Pressbooks\Admin\Plugins\quicklatex_svg_warning' );

// -------------------------------------------------------------------------------------------------------------------
// Registration
// -------------------------------------------------------------------------------------------------------------------

add_filter( 'gettext', '\Pressbooks\Registration\custom_signup_text', 20, 3 );
add_action( 'signup_extra_fields', '\Pressbooks\Registration\add_password_field', 9 );
add_filter( 'wpmu_validate_user_signup', '\Pressbooks\Registration\validate_passwords' );
add_filter( 'add_signup_meta', '\Pressbooks\Registration\add_temporary_password', 99 );
add_action( 'signup_blogform', '\Pressbooks\Registration\add_hidden_password_field' );
add_filter( 'random_password', '\Pressbooks\Registration\override_password_generation' );
add_filter( 'lostpassword_url', '\Pressbooks\Registration\remove_wp_prefix', 12 );
// Hooks to have pending invitation information
add_action( 'invite_user', '\Pressbooks\Registration\save_invitation_data', 10, 3 );
add_action( 'added_existing_user', '\Pressbooks\Registration\clean_invitation_data', 10, 2 );

// Email configuration
add_filter( 'wp_mail_from', '\Pressbooks\Utility\mail_from' );
add_filter( 'wp_mail_from_name', '\Pressbooks\Utility\mail_from_name' );
add_filter( 'retrieve_password_message', '\Pressbooks\Registration\remove_ip_from_password_reset_email' );

// -------------------------------------------------------------------------------------------------------------------
// (Custom) Styles
// -------------------------------------------------------------------------------------------------------------------

Container::get( 'Styles' )->init();

if ( $is_book ) {
	// Overrides (sometimes a web stylesheet update will be triggered by a visitor so this filter needs to be active outside of the admin)
	add_filter( 'pb_web_css_override', [ '\Pressbooks\Modules\ThemeOptions\WebOptions', 'scssOverrides' ] );
	// Overrides for ebook and PDF stylesheets
	if ( ! defined( 'WP_TESTS_DOMAIN' ) ) {
		add_filter( 'pb_epub_css_override', [ '\Pressbooks\Modules\ThemeOptions\EbookOptions', 'scssOverrides' ] );
		add_filter( 'pb_pdf_css_override', [ '\Pressbooks\Modules\ThemeOptions\PDFOptions', 'scssOverrides' ] );
	}
}

// -------------------------------------------------------------------------------------------------------------------
// GDPR
// -------------------------------------------------------------------------------------------------------------------

add_action( 'init', [ '\Pressbooks\Privacy', 'init' ], 9 ); // Must come before `add_action( 'init', 'wp_schedule_delete_old_privacy_export_files' );`

// -------------------------------------------------------------------------------------------------------------------
// MathJax
// -------------------------------------------------------------------------------------------------------------------

add_action( 'init', [ '\Pressbooks\MathJax', 'init' ] );

// Disable admin check email
add_filter( 'admin_email_check_interval', '__return_false' );

// -------------------------------------------------------------------------------------------------------------------
// Book directory event actions
// -------------------------------------------------------------------------------------------------------------------
add_action( 'init', [ '\Pressbooks\BookDirectory', 'init' ] );

add_action( 'activated_plugin', '\Pressbooks\Utility\delete_options_cached' );

// Clone complete table
register_deactivation_hook( 'pressbooks/pressbooks.php', [ CloneComplete::class, 'uninstall' ] );
add_action( 'init', [ CloneComplete::class, 'install' ] );

add_action( 'init', [ '\Pressbooks\Utility\ErrorHandler', 'init' ] );

// Open up private content to subscribers and collaborators when permissive_private_content is enabled
add_action( 'init', [ Privacy::class, 'showPermissivePrivateContent' ] );

add_action( 'wp_initialize_site', [ Privacy::class, 'setDefaultPermissivePrivateContent' ], 100, 1 );

//Network Managers hooks via CLI
add_action( 'revoked_super_admin', '\Pressbooks\Admin\NetworkManagers\remove_from_pressbooks_network_managers' );
add_action( 'deleted_user', '\Pressbooks\Admin\NetworkManagers\remove_from_pressbooks_network_managers' );

// Optimize H5P CSS files for export
add_action( 'pb_xhtml_after_content_processed', '\Pressbooks\Modules\Export\pb_xhtml_after_content_processed' );
