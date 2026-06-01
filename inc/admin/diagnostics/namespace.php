<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */
// TODO: Security audit
// @phpcs:disable Pressbooks.Security.EscapeOutput.OutputNotEscaped
// @phpcs:disable Pressbooks.Security.ValidatedSanitizedInput.InputNotValidated
// @phpcs:disable Pressbooks.Security.ValidatedSanitizedInput.MissingUnslash
// @phpcs:disable Pressbooks.Security.ValidatedSanitizedInput.InputNotSanitized

namespace Pressbooks\Admin\Diagnostics;

use DeviceDetector\DeviceDetector;
use function Pressbooks\Redirect\location;
use function Pressbooks\Utility\check_epubcheck_install;
use function Pressbooks\Utility\check_prince_install;
use function Pressbooks\Utility\check_saxonhe_install;
use function Pressbooks\Utility\check_xmllint_install;
use Pressbooks\Book;
use Pressbooks\Container;
use Pressbooks\HtmLawed;
use Pressbooks\Modules\ThemeOptions\Admin;
use Pressbooks\Theme\Lock;

/**
 * Add the diagnostics menu (with parent page set to null)
 */

function add_menu() {
	add_submenu_page(
		'options.php',
		__( 'Diagnostics', 'pressbooks' ),
		__( 'Diagnostics', 'pressbooks' ),
		'edit_posts',
		'pressbooks_diagnostics',
		__NAMESPACE__ . '\render_page'
	);
}

/**
 * Render the diagnostics page (adapted from https://github.com/WordImpress/Give/blob/master/includes/admin/system-info.php)
 */
function render_page() {
	global $wpdb;
	$user_agent = $_SERVER['HTTP_USER_AGENT'];
	$dd = new DeviceDetector( $user_agent );
	$dd->parse();
	$is_book = Book::isBook();
	$lock = Lock::init();
	$regenerate_webbook_stylesheet_url = wp_nonce_url( get_admin_url( get_current_blog_id(), '/admin-post.php?action=pb_regenerate_webbook_stylesheet' ), 'pb-regenerate-webbook-stylesheet' );
	$pdf_preview_url = wp_nonce_url( get_admin_url( get_current_blog_id(), '/admin-post.php?action=pdf_preview' ), 'pdf-preview' );
	$output = "### System Information\n\n";
	if ( Book::isBook() ) {
		$output .= "#### Book Info\n\n";
		$output .= __( 'Book ID: ', 'pressbooks' ) . get_current_blog_id() . "\n";
		$output .= __( 'Book URL: ', 'pressbooks' ) . trailingslashit( get_bloginfo( 'url' ) ) . "\n";
		$output .= __( 'Book Privacy: ', 'pressbooks' ) . ( get_bloginfo( 'blog_public' ) ? __( 'Public', 'pressbooks' ) : __( 'Private', 'pressbooks' ) ) . "\n\n";
	} else {
		$output .= "#### Root Blog Info\n\n";
		$output .= __( 'Root Blog ID: ', 'pressbooks' ) . get_current_blog_id() . "\n";
		$output .= __( 'Root Blog URL: ', 'pressbooks' ) . trailingslashit( get_bloginfo( 'url' ) ) . "\n\n";
	}
	$output .= "#### Browser\n\n";
	$output .= __( 'Platform: ', 'pressbooks' ) . $dd->getOs( 'name' ) . ' ' . $dd->getOs( 'version' ) . "\n";
	$output .= __( 'Browser Name: ', 'pressbooks' ) . $dd->getClient( 'name' ) . "\n";
	$output .= __( 'Browser Version: ', 'pressbooks' ) . $dd->getClient( 'version' ) . "\n";
	$output .= __( 'User Agent String: ', 'pressbooks' ) . $user_agent . "\n\n";
	$output .= __( '#### WordPress Configuration', 'pressbooks' ) . "\n\n";
	$output .= __( 'Network URL: ', 'pressbooks' ) . network_home_url() . "\n";
	$output .= __( 'Network Type: ', 'pressbooks' ) . ( is_subdomain_install() ? __( 'Subdomain', 'pressbooks' ) : __( 'Subdirectory', 'pressbooks' ) ) . "\n";
	$output .= __( 'Version: ', 'pressbooks' ) . get_bloginfo( 'version' ) . "\n";
	$output .= __( 'Language: ', 'pressbooks' ) . get_locale() . "\n";
	$output .= __( 'WP_ENV: ', 'pressbooks' ) . ( defined( 'WP_ENV' ) ? WP_ENV : __( 'Not set', 'pressbooks' ) ) . "\n";
	$output .= __( 'WP_DEBUG: ', 'pressbooks' ) . ( defined( 'WP_DEBUG' ) ? WP_DEBUG ? __( 'Enabled', 'pressbooks' ) : __( 'Disabled', 'pressbooks' ) : __( 'Not set', 'pressbooks' ) ) . "\n";
	$output .= __( 'Memory Limit: ', 'pressbooks' ) . WP_MEMORY_LIMIT . "\n\n";
	$output .= __( '#### Pressbooks Configuration', 'pressbooks' ) . "\n\n";
	$output .= __( 'Version: ', 'pressbooks' ) . PB_PLUGIN_VERSION . "\n";
	if ( $is_book ) {
		switch_to_blog( $GLOBALS['current_site']->blog_id );
		$root_theme = wp_get_theme();
		restore_current_blog();
		if ( $lock->isLocked() ) {
			$theme = wp_get_theme();
			$data = $lock->getLockData();
			$datetime = date( 'm/d/y', $data['timestamp'] ) . ' at ' . date( 'H:i:s', $data['timestamp'] );
			$output .= __( 'Book Theme: ', 'pressbooks' ) . $data['name'] . " (LOCKED on $datetime)\n";
			$output .= __( 'Book Theme Version: ', 'pressbooks' ) . $data['version'] . " (LOCKED on $datetime &mdash; " . __( 'Current Version', 'pressbooks' ) . ' ' . $theme->get( 'Version' ) . ")\n";
		} else {
			$theme = wp_get_theme();
			$output .= __( 'Book Theme: ', 'pressbooks' ) . $theme->get( 'Name' ) . "\n";
			$output .= __( 'Book Theme Version: ', 'pressbooks' ) . $theme->get( 'Version' ) . "\n";
		}
	} else {
		$root_theme = wp_get_theme();
	}
	$output .= __( 'Root Theme: ', 'pressbooks' ) . $root_theme->get( 'Name' ) . "\n";
	$output .= __( 'Root Theme Version: ', 'pressbooks' ) . $root_theme->get( 'Version' ) . "\n\n";
	$output .= __( '#### Pressbooks Dependencies', 'pressbooks' ) . "\n\n";
	$output .= __( 'Epubcheck: ', 'pressbooks' ) . ( check_epubcheck_install() ? __( 'Installed', 'pressbooks' ) : __( 'Not Installed', 'pressbooks' ) ) . "\n"; // TODO: version
	$output .= __( 'xmllint: ', 'pressbooks' ) . ( check_xmllint_install() ? __( 'Installed', 'pressbooks' ) : __( 'Not Installed', 'pressbooks' ) ) . "\n"; // TODO: version
	$output .= __( 'PrinceXML: ', 'pressbooks' ) . ( check_prince_install() ? __( 'Installed', 'pressbooks' ) : __( 'Not Installed', 'pressbooks' ) ) . "\n"; // TODO: version
	$output .= __( 'Saxon-HE: ', 'pressbooks' ) . ( check_saxonhe_install() ? __( 'Installed', 'pressbooks' ) : __( 'Not Installed', 'pressbooks' ) ) . "\n\n"; // TODO: version
	$muplugins = get_mu_plugins();
	if ( count( $muplugins ) > 0 ) {
		$output .= __( '#### Must-Use Plugins', 'pressbooks' ) . "\n\n";
		foreach ( $muplugins as $plugin => $plugin_data ) {
			$output .= $plugin_data['Name'] . ': ' . ( $plugin_data['Version'] ? $plugin_data['Version'] : __( 'n/a', 'pressbooks' ) ) . "\n";
		}
	}
	$output .= __( '#### Network Active Plugins', 'pressbooks' ) . "\n\n";
	$plugins = get_plugins();
	foreach ( $plugins as $plugin_path => $plugin ) {
		if ( ! is_plugin_active_for_network( $plugin_path ) ) {
			continue;
		}
		$output .= $plugin['Name'] . ': ' . $plugin['Version'] . "\n";
	}
	if ( $is_book ) {
		$output .= __( '#### Book Active Plugins', 'pressbooks' ) . "\n\n";
	} else {
		$output .= __( '#### Root Blog Active Plugins', 'pressbooks' ) . "\n\n";
	}
	$plugins = get_plugins();
	foreach ( $plugins as $plugin_path => $plugin ) {
		if ( ! is_plugin_active( $plugin_path ) ) {
			continue;
		}
		$output .= $plugin['Name'] . ': ' . $plugin['Version'] . "\n";
	}
	$output .= __( '#### Inactive Plugins', 'pressbooks' ) . "\n\n";
	foreach ( $plugins as $plugin_path => $plugin ) {
		if ( is_plugin_active_for_network( $plugin_path ) || is_plugin_active( $plugin_path ) ) {
			continue;
		}
		$output .= $plugin['Name'] . ': ' . $plugin['Version'] . "\n";
	}
	$output .= __( '#### Server Configuration', 'pressbooks' ) . "\n\n";
	$output .= __( 'PHP Version: ', 'pressbooks' ) . PHP_VERSION . "\n";
	$output .= __( 'MySQL Version: ', 'pressbooks' ) . $wpdb->db_version() . "\n";
	$output .= __( 'Webserver Info: ', 'pressbooks' ) . $_SERVER['SERVER_SOFTWARE'] . "\n\n";
	$output .= __( '#### PHP Configuration', 'pressbooks' ) . "\n\n";
	$output .= __( 'Memory Limit: ', 'pressbooks' ) . ini_get( 'memory_limit' ) . "\n";
	$output .= __( 'Upload Max Size: ', 'pressbooks' ) . ini_get( 'upload_max_filesize' ) . "\n";
	$output .= __( 'Post Max Size: ', 'pressbooks' ) . ini_get( 'post_max_size' ) . "\n";
	$output .= __( 'Upload Max Filesize: ', 'pressbooks' ) . ini_get( 'upload_max_filesize' ) . "\n";
	$output .= __( 'Time Limit: ', 'pressbooks' ) . ini_get( 'max_execution_time' ) . "\n";
	$output .= __( 'Max Input Vars: ', 'pressbooks' ) . ini_get( 'max_input_vars' ) . "\n";
	$output .= __( 'URL-aware fopen: ', 'pressbooks' ) . ( ini_get( 'allow_url_fopen' ) ? 'On (' . ini_get( 'allow_url_fopen' ) . ')' : 'N/A' ) . "\n";
	$output .= __( 'Display Errors: ', 'pressbooks' ) . ( ini_get( 'display_errors' ) ? 'On (' . ini_get( 'display_errors' ) . ')' : 'N/A' ) . "\n\n";
	$output .= __( '#### PHP Extensions', 'pressbooks' ) . "\n\n";
	$output .= __( 'OPcache: ', 'pressbooks' );
	$opcache = false;
	if ( extension_loaded( 'Zend OPcache' ) ) {
		$output .= 'Zend,';
		$opcache = true;
	}
	if ( extension_loaded( 'apc' ) ) {
		$output .= 'APC,';
		$opcache = true;
	}
	if ( $opcache ) {
		$output = rtrim( $output, ',' ) . "\n";
	} else {
		$output .= 'Disabled' . "\n";
	}

	$output .= 'XDebug: ' . ( extension_loaded( 'xdebug' ) ? 'Enabled' : 'Disabled' ) . "\n";
	$output .= 'cURL: ' . ( function_exists( 'curl_init' ) ? 'Supported' : 'Not Supported' ) . "\n";
	if ( function_exists( 'curl_init' ) && function_exists( 'curl_version' ) ) {
		$curl_values = curl_version(); // @codingStandardsIgnoreLine
		$output .= 'cURL Version: ' . $curl_values['version'] . "\n";
	}
	$output .= 'imagick: ' . ( extension_loaded( 'imagick' ) ? 'Installed' : 'Not Installed' ) . "\n";
	$output .= 'xsl: ' . ( extension_loaded( 'xsl' ) ? 'Installed' : 'Not Installed' );
	echo \Pressbooks\Container::get( 'Blade' )
		->render(
			'admin.diagnostics', [
				'output' => $output,
				'regenerate_webbook_stylesheet_url' => HtmLawed::filter( $regenerate_webbook_stylesheet_url, [ 'safe' => 1 ] ),
				'pdf_preview_url' => HtmLawed::filter( $pdf_preview_url, [ 'safe' => 1 ] ),
				'is_book' => $is_book,
			]
		);
}

/**
 * @since 5.7.0
 *
 * Handle form submission on the diagnostics page which triggers regeneration of the webbook stylesheet.
 *
 * @return null
 */
function handle_stylesheet_regeneration() {
	if ( check_admin_referer( 'pb-regenerate-webbook-stylesheet' ) ) {
		( new Admin() )->clearCache();
		Container::get( 'Styles' )->updateWebBookStyleSheet();

		// Ok!
		\Pressbooks\add_notice( __( 'Stylesheet regenerated.', 'pressbooks' ) );
	}
	location( admin_url( 'options.php?page=pressbooks_diagnostics' ) );
}

/**
 * @since 6.23.0
 *
 * Update PDF stylesheet before loading page preview.
 *
 * @return void
 */
function handle_pdf_preview(): void {
	if ( check_admin_referer( 'pdf-preview' ) ) {
		( new Admin() )->clearCache();
		Container::get( 'Styles' )->updatePdfStyleSheet();
	}
	$url = get_site_url( get_current_blog_id() ) . '/format/xhtml?debug=prince';
	if ( ! empty( $_POST['optimize_for_print'] ) ) {
		$url .= '&optimize-for-print=1';
	}
	location( $url );
}



