<?php
/**
 * Generic utility functions.
 *
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Utility;

/**
 * Return a value for a given key even if not set
 *
 * @param mixed $arr either an array or a string that points to an array in $GLOBALS
 * @param string $key
 * @param mixed $default
 *
 * @return mixed
 */
function getset( $arr, $key, $default = null ) {

	// Get from array
	if ( is_array( $arr ) ) {
		return isset( $arr[ $key ] ) ? $arr[ $key ] : $default;
	}

	// Get from a global or superglobal
	if ( is_string( $arr ) && isset( $GLOBALS[ $arr ] ) && is_array( $GLOBALS[ $arr ] ) ) {
		return isset( $GLOBALS[ $arr ][ $key ] ) ? $GLOBALS[ $arr ][ $key ] : $default;
	}

	return $default;
}

/**
 * Scan a directory and return the files (not directories!) ordered by date, newest first.
 *
 * @param $dir
 *
 * @return array
 */
function scandir_by_date( $dir ) {

	$ignored = [ '.', '..', '.svn', '.git', '.htaccess' ];

	$dir = untrailingslashit( $dir ) . '/';

	$files = [];
	foreach ( scandir( $dir ) as $file ) {
		if ( in_array( $file, $ignored, true ) || is_dir( $dir . $file ) ) {
			continue;
		}
		$files[ $file ] = filemtime( $dir . $file );
	}
	arsort( $files );
	$files = array_keys( $files );

	return ( $files ) ? $files : [];
}


/**
 * Scan the exports directory, return the files grouped into intervals of 3 minutes, newest first.
 *
 * @param string $dir fullpath to the Exports folder. (optional)
 *
 * @return array
 */
function group_exports( $dir = null ) {

	$ignored = [ '.', '..', '.svn', '.git', '.htaccess' ];

	if ( ! $dir ) {
		$dir = \Pressbooks\Modules\Export\Export::getExportFolder();
	} else {
		$dir = rtrim( $dir, '/' ) . '/';
	}

	$files = [];
	foreach ( scandir( $dir ) as $file ) {
		if ( in_array( $file, $ignored, true ) || is_dir( $dir . $file ) ) {
			continue;
		}
		$files[ $file ] = filemtime( $dir . $file );
	}
	arsort( $files );

	$interval = 3 * MINUTE_IN_SECONDS; // Three minutes
	$pos = 0;
	$output = [];

	foreach ( $files as $file => $timestamp ) {
		if ( 0 === $pos ) {
			$pos = $timestamp;
		}
		if ( $pos - $timestamp > $interval ) {
			$pos = $timestamp;
		}
		$output[ $pos ][] = $file;
	}

	return $output;
}


/**
 * Truncate the exports directory, delete old files.
 *
 * @param int $max
 * @param string $dir fullpath to the Exports fo
 * lder. (optional)
 */
function truncate_exports( $max, $dir = null ) {

	if ( ! $dir ) {
		$dir = \Pressbooks\Modules\Export\Export::getExportFolder();
	} else {
		$dir = rtrim( $dir, '/' ) . '/';
	}

	$max = absint( $max );
	$files = group_exports( $dir );

	$i = 1;
	foreach ( $files as $date => $exports ) {
		if ( $i > $max ) {
			foreach ( $exports as $export ) {
				$export = realpath( $dir . $export );

				unlink( $export );
			}
		}
		++$i;
	}
}


/**
 * Return the full path to the directory containing media
 * Checks for `ms_files_rewriting` site option; uses /wp-content/blogs.dir/ if present, otherwise uses WordPress 3.5+ standard
 *
 * @return string path
 */
function get_media_prefix() {
	if ( get_site_option( 'ms_files_rewriting' ) ) {
		return WP_CONTENT_DIR . '/blogs.dir/' . get_current_blog_id() . '/files/';
	} else {
		return trailingslashit( get_generated_content_path( '', false ) );
	}
}

/**
 * Returns the full path to a media file, given its guid
 * Used for adding cover images to an EPUB file and for ajax deletion of uploaded cover images
 *
 * @param string $guid The guid of a media file (usually image)
 *
 * @return string the full path to the media file on the filesystem
 */
function get_media_path( $guid ) {

	$parts = wp_parse_url( $guid );
	$path = $parts['path'];
	$beginning = strpos( $path, 'files' );
	if ( $beginning ) {
		$path = substr( $path, $beginning );
		return WP_CONTENT_DIR . '/blogs.dir/' . get_current_blog_id() . '/' . $path;
	} else {
		$beginning = strpos( $path, 'uploads' );
		$path = substr( $path, $beginning );
		return WP_CONTENT_DIR . '/' . $path;
	}
}

/**
 * Scan the export directory, return latest of each file type
 *
 * @author Brad Payne <brad@bradpayne.ca>
 * @copyright 2014 Brad Payne
 * @since 3.8.0
 * @return array
 */
function latest_exports() {
	/**
	 * Add custom export formats to the latest exports filetype mapping array.
	 *
	 * For example, here's how one might add a hypothetical Word export format:
	 *
	 * add_filter( 'pb_latest_export_filetypes', function ( $filetypes ) {
	 *    $filetypes['word'] = '.docx';
	 *    return $filetypes;
	 * } );
	 *
	 * @since 3.9.8
	 *
	 * @param array $value
	 */
	$filetypes = apply_filters(
		'pb_latest_export_filetypes', [
			'epub3' => '._3.epub',
			'epub' => '.epub',
			'pdf' => '.pdf',
			'print-pdf' => '._print.pdf',
			'mobi' => '.mobi',
			'icml' => '.icml',
			'htmlbook' => '.-htmlbook.html',
			'xhtml' => '.html',
			'wxr' => '.xml',
			'vanillawxr' => '._vanilla.xml',
			'mpdf' => '._oss.pdf',
			'odf' => '.odt',
		]
	);

	$dir = \Pressbooks\Modules\Export\Export::getExportFolder();

	$files = [];

	// group by extension, sort by date newest first
	foreach ( \Pressbooks\Utility\scandir_by_date( $dir ) as $file ) {
		// only interested in the part of filename starting with the timestamp
		if ( preg_match( '/-\d{10,11}(.*)/', $file, $matches ) ) {

			// grab the first captured parenthisized subpattern
			$ext = $matches[1];

			$files[ $ext ][] = $file;
		}
	}

	// get only one of the latest of each type
	$latest = [];

	foreach ( $filetypes as $type => $ext ) {
		if ( array_key_exists( $ext, $files ) ) {
			$latest[ $type ] = $files[ $ext ][0];
		}
	}

	return $latest;
}


/**
 * Add sitemap to robots.txt
 */
function add_sitemap_to_robots_txt() {

	if ( 1 === absint( get_option( 'blog_public' ) ) ) {
		echo 'Sitemap: ' . get_option( 'siteurl' ) . "/?feed=sitemap.xml\n\n";
	}
}


/**
 * Echo a sitemap
 */
function do_sitemap() {

	if ( 1 === absint( get_option( 'blog_public' ) ) ) {
		$template = untrailingslashit( PB_PLUGIN_DIR ) . '/templates/pb-sitemap.php';
		load_template( $template );
	} else {
		status_header( 404 );
		nocache_headers();
		echo '<h1>404 Not Found</h1>';
		echo 'The page that you have requested could not be found.';
	}
}

/**
 * Create a temporary file that automatically gets deleted when PHP ends.
 *
 * @param string $resource_key (optional)
 *
 * @return string Path to temporary file
 */
function create_tmp_file( $resource_key = '' ) {
	if ( empty( $resource_key ) ) {
		$resource_key = uniqid( 'tmpfile-', true );
	}
	$stream = stream_get_meta_data( $GLOBALS[ $resource_key ] = tmpfile() ); // @codingStandardsIgnoreLine
	return $stream['uri'];
}

/**
 * Lightweight check to see if the Epubcheck executable is installed and up to date.
 *
 * @return boolean
 */
function check_epubcheck_install() {
	if ( ! defined( 'PB_EPUBCHECK_COMMAND' ) ) { // @see wp-config.php
		define( 'PB_EPUBCHECK_COMMAND', '/usr/bin/java -jar /opt/epubcheck/epubcheck.jar' );
	}

	$output = [];
	$return_val = 0;
	exec( PB_EPUBCHECK_COMMAND . ' -h 2>&1', $output, $return_val );

	$output = $output[0];
	if ( false !== strpos( $output, 'EpubCheck' ) ) { // Command found.
		$output = explode( 'EpubCheck v', $output );
		$version = $output[1];
		if ( version_compare( $version, '4.0.0' ) >= 0 ) {
			return true;
		}
	}

	return apply_filters( 'pb_epub_has_dependencies', false );
}

/**
 * Lightweight check to see if the Kindlegen executable is installed and up to date.
 *
 * @return boolean
 */
function check_kindlegen_install() {
	if ( ! defined( 'PB_KINDLEGEN_COMMAND' ) ) { // @see wp-config.php
		define( 'PB_KINDLEGEN_COMMAND', '/opt/kindlegen/kindlegen' );
	}

	$output = [];
	$return_val = 0;
	exec( PB_KINDLEGEN_COMMAND . ' 2>&1', $output, $return_val );

	if ( isset( $output[2] ) && false !== strpos( $output[2], 'kindlegen' ) ) { // Command found.
		$output = explode( ' V', $output[2] );
		$output = explode( ' build', $output[1] );
		$version = $output[0];
		if ( version_compare( $version, '2.9' ) >= 0 ) {
			return true;
		}
	}

	return false;
}

/**
 * Lightweight check to see if the Prince executable is installed and up to date.
 *
 * @return boolean
 */
function check_prince_install() {
	if ( ! defined( 'PB_PRINCE_COMMAND' ) ) { // @see wp-config.php
		define( 'PB_PRINCE_COMMAND', '/usr/bin/prince' );
	}

	$output = [];
	$return_val = 0;
	exec( PB_PRINCE_COMMAND . ' --version 2>&1', $output, $return_val );

	$output = $output[0];
	if ( false !== strpos( $output, 'Prince' ) ) { // Command found.
		$output = explode( 'Prince ', $output );
		$version = $output[1];
		if ( version_compare( $version, '11' ) >= 0 ) {
			return true;
		}
	}

	return false;
}

/**
 * Lightweight check to see if the xmllint executable is installed and up to date.
 *
 * @return boolean
 */
function check_xmllint_install() {
	if ( ! defined( 'PB_XMLLINT_COMMAND' ) ) { // @see wp-config.php
		define( 'PB_XMLLINT_COMMAND', '/usr/bin/xmllint' );
	}

	$output = [];
	$return_val = 0;
	exec( PB_XMLLINT_COMMAND . ' --version 2>&1', $output, $return_val );

	$output = $output[0];
	if ( false !== strpos( $output, 'libxml' ) ) { // Command found.
		$output = explode( PB_XMLLINT_COMMAND . ': using libxml version ', $output );
		$version = $output[1];
		if ( version_compare( $version, '20706' ) >= 0 ) {
			return true;
		}
	}

	return false;
}

/**
 * Lightweight check to see if the Saxon-HE executable is installed and up to date.
 *
 * @return boolean
 */
function check_saxonhe_install() {
	if ( ! defined( 'PB_SAXON_COMMAND' ) ) { // @see wp-config.php
		define( 'PB_SAXON_COMMAND', '/usr/bin/java -jar /opt/saxon-he/saxon-he.jar' );
	}

	$output = [];
	$return_val = 0;
	exec( PB_SAXON_COMMAND . ' -? 2>&1', $output, $return_val );

	$output = $output[0];
	if ( false !== strpos( $output, 'Saxon-HE ' ) ) { // Command found.
		$output = explode( 'Saxon-HE ', $output );
		$version = explode( 'J from Saxonica', $output[1] )[0];
		if ( version_compare( $version, '9.7.0-10' ) >= 0 ) {
			return true;
		}
	}

	/**
	 * @since 3.9.8
	 *
	 * Allows the SaxonHE dependency error to be disabled.
	 *
	 * @param bool $value
	 */
	return apply_filters( 'pb_odt_has_dependencies', false );
}

/**
 * Function to determine whether or not experimental features should be visible to users.
 *
 * @param $host string
 *
 * @return boolean
 */
function show_experimental_features( $host = '' ) {

	if ( ! $host ) {
		$host = wp_parse_url( network_home_url(), PHP_URL_HOST );
	}

	// hosts where experimental features should be hidden
	$hosts_for_hiding = [
		'pressbooks.com',
		'pressbooks.pub',
	];

	foreach ( $hosts_for_hiding as $host_for_hiding ) {
		if ( $host === $host_for_hiding || strpos( $host, $host_for_hiding ) ) {
			return false;
		}
	}

	return true;
}

/**
 * Include plugins in /symbionts
 *
 * @since 2.5.1
 */
function include_plugins() {
	$plugins = [
		'custom-metadata/custom_metadata.php' => 1,
		'pressbooks-latex/pb-latex.php' => 1,
	];

	$plugins = filter_plugins( $plugins );

	if ( ! empty( $plugins ) ) {
		foreach ( $plugins as $key => $val ) {
			require_once( PB_PLUGIN_DIR . 'symbionts/' . $key );
		}
	}

	// Disable comments
	if ( true === disable_comments() ) {
		require_once( PB_PLUGIN_DIR . 'symbionts/disable-comments-mu/disable-comments-mu.php' );
	}
}

/**
 * Filters out active plugins, to avoid collisions with plugins already installed.
 *
 * @since 2.5.1
 *
 * @param array $plugins An array of plugins, key/values paired like so: 'pressbooks/pressbooks.php' => 1
 *
 * @return array
 */
function filter_plugins( $plugins ) {
	$already_active = get_option( 'active_plugins' );
	$network_already_active = get_site_option( 'active_sitewide_plugins' );

	// Don't include plugins already active at the site level or network level.
	if ( ! empty( $plugins ) ) {
		foreach ( $plugins as $key => $val ) {
			if ( in_array( $key, $already_active, true ) || array_key_exists( $key, $network_already_active ) ) {
				unset( $plugins[ $key ] );
			}
		}
	}

	// Don't include plugins we are trying to activate right now!
	if ( isset( $_REQUEST['action'] ) ) {
		if ( 'activate' === $_REQUEST['action'] && ! empty( $_REQUEST['plugin'] ) ) {
			$key = (string) $_REQUEST['plugin'];
			unset( $plugins[ $key ] );
		} elseif ( 'activate-selected' === $_REQUEST['action'] && is_array( $_REQUEST['checked'] ) ) {
			foreach ( $_REQUEST['checked'] as $key ) {
				unset( $plugins[ $key ] );
			}
		}
	}

	// Don't include Pressbooks LaTeX if QuickLaTeX is active.
	if ( in_array( 'wp-quicklatex/wp-quicklatex.php', $already_active, true ) || array_key_exists( 'wp-quicklatex/wp-quicklatex.php', $network_already_active ) ) {
		unset( $plugins['pressbooks-latex/pb-latex.php'] );
	}

	return $plugins;
}

/**
 * Check if we should disable comments.
 *
 * @return bool
 */
function disable_comments() {
	if ( ! \Pressbooks\Book::isBook() ) {
		/**
		 * Allows comments to be enabled on the root blog by adding a function to this filter that returns false.
		 *
		 * @since 3.9.6
		 *
		 * @param bool $value
		 */
		return apply_filters( 'pb_disable_root_comments', true );
	}

	$old_option = get_option( 'disable_comments_options' );
	$new_option = get_option(
		'pressbooks_sharingandprivacy_options', [
			'disable_comments' => 1,
		]
	);

	if ( false === (bool) $old_option ) {
		$retval = (bool) $new_option['disable_comments'];
	} elseif ( is_array( $old_option['disabled_post_types'] ) && in_array( 'chapter', $old_option['disabled_post_types'], true ) && in_array( 'front-matter', $old_option['disabled_post_types'], true ) && in_array( 'front-matter', $old_option['disabled_post_types'], true ) ) {
		$retval = true;
		$new_option['disable_comments'] = 1;
		update_option( 'pressbooks_sharingandprivacy_options', $new_option );
		delete_option( 'disable_comments_options' );
	} else {
		$retval = false;
		$new_option['disable_comments'] = 0;
		update_option( 'pressbooks_sharingandprivacy_options', $new_option );
		delete_option( 'disable_comments_options' );
	}

	return $retval;
}

/**
 * Remove the Featured tab, change order on the others so that Recommended is first.
 * Adapted from https://github.com/secretpizzaparty/better-plugin-recommendations
 *
 * @since 4.0.0
 * @author Joey Kudish <info@jkudish.com>
 * @author Nick Hamze <me@nickhamze.com>
 *
 * @param array $tabs The Plugin Installer tabs.
 *
 * @return array
 */
function install_plugins_tabs( $tabs ) {
	unset( $tabs['featured'] );
	unset( $tabs['popular'] );
	unset( $tabs['favorites'] );
	$tabs['popular'] = _x( 'Popular', 'Plugin Installer' );
	$tabs['favorites'] = _x( 'Favorites', 'Plugin Installer' );
	return $tabs;
}

/**
 * Replace the core Recommended tab with ours.
 * Adapted from https://github.com/secretpizzaparty/better-plugin-recommendations
 *
 * @since 4.0.0
 * @author Joey Kudish <info@jkudish.com>
 * @author Nick Hamze <me@nickhamze.com>
 *
 * @param false|object|array $res The result object or array. Default false.
 * @param string $action The type of information being requested from the Plugin Install API.
 * @param object $args Plugin API arguments.
 *
 * @return object
 */
function hijack_recommended_tab( $res, $action, $args ) {
	if ( ! isset( $args->browse ) || 'recommended' !== $args->browse ) {
		return $res;
	}
	$res = get_site_transient( 'pressbooks_recommended_plugins_data' );
	if ( ! $res || ! isset( $res->plugins ) ) {
		$res = \Pressbooks\Utility\fetch_recommended_plugins();
		if ( isset( $res->plugins ) ) {
			set_site_transient( 'pressbooks_recommended_plugins_data', $res, HOUR_IN_SECONDS );
		}
	}
	return $res;
}

/**
 * Fetch recommended plugins from our server.
 * Adapted from https://github.com/secretpizzaparty/better-plugin-recommendations
 *
 * @since 4.0.0
 * @author Joey Kudish <info@jkudish.com>
 * @author Nick Hamze <me@nickhamze.com>
 *
 * @return object
 */
function fetch_recommended_plugins() {
	/**
	 * Filter the URL of the Pressbooks Recommended Plugins server.
	 *
	 * @since 4.0.0
	 *
	 * @param string $value
	 */
	$http_url = apply_filters( 'pb_recommended_plugins_url', 'https://pressbooks-plugins.now.sh' ) . '/api/plugin-recommendations';
	$url = $http_url;
	$ssl = wp_http_supports( [ 'ssl' ] );
	if ( $ssl ) {
		$url = set_url_scheme( $url, 'https' );
	}
	$request = wp_remote_get(
		$url, [
			'timeout' => 15,
		]
	);
	if ( $ssl && is_wp_error( $request ) ) {
		// @codingStandardsIgnoreLine
		trigger_error(
			__( 'An unexpected error occurred. Something may be wrong with the plugin recommendations server or your site&#8217;s server&#8217;s configuration.', 'pressbooks' ) . ' ' . __( '(Pressbooks could not establish a secure connection to the plugin recommendations server. Please contact your server administrator.)', 'pressbooks' ),
			headers_sent() || WP_DEBUG ? E_USER_WARNING : E_USER_NOTICE
		);
		$request = wp_remote_get(
			$http_url, [
				'timeout' => 15,
			]
		);
	}
	if ( is_wp_error( $request ) ) {
		$res = new \WP_Error(
			'plugins_api_failed', __( 'An unexpected error occurred. Something may be wrong with the plugin recommendations server or your site&#8217;s server&#8217;s configuration.', 'pressbooks' ),
			$request->get_error_message()
		);
	} else {
		$res = json_decode( wp_remote_retrieve_body( $request ) );
		$res->info = (array) $res->info; // WP wants this as an array...
		$res->plugins = array_map(
			function ( $plugin ) {
				$plugin->icons = (array) $plugin->icons; // WP wants this as an array...
				return $plugin;
			}, $res->plugins
		);
		if ( ! is_object( $res ) && ! is_array( $res ) ) {
			$res = new \WP_Error(
				'plugins_api_failed',
				__( 'An unexpected error occurred. Something may be wrong with the plugin recommendations server or your site&#8217;s server&#8217;s configuration.', 'pressbooks' ),
				wp_remote_retrieve_body( $request )
			);
		}
	}
	return $res;
}

/**
 * Replace the description on the Recommended tab.
 * Adapted from https://github.com/secretpizzaparty/better-plugin-recommendations
 *
 * @since 4.0.0
 * @author Joey Kudish <info@jkudish.com>
 * @author Nick Hamze <me@nickhamze.com>
 *
 * @param string $translation
 * @param string $text
 * @param string $domain
 *
 * @return string
 */
function change_recommendations_sentence( $translation, $text, $domain ) {
	if ( 'These suggestions are based on the plugins you and other users have installed.' === $text ) {
		return __( 'These plugins have been created and/or recommended by the Pressbooks community.', 'pressbooks' );
	}
	return $translation;
}


/**
 * Function to return a string representing max import size by comparing values of upload_max_filesize, post_max_size
 * Uses parse_size helper function since the values in php.ini are strings like 64M and 128K
 * @return string
 */

function file_upload_max_size() {
	static $return_val = false;
	// This function is adapted from Drupal and http://stackoverflow.com/questions/13076480/php-get-actual-maximum-upload-size
	if ( false === $return_val ) {
		$post_max_size_str = ini_get( 'post_max_size' );
		$upload_max_filesize_str = ini_get( 'upload_max_filesize' );
		$post_max_size = parse_size( $post_max_size_str );
		$upload_max_filesize = parse_size( $upload_max_filesize_str );

		// If upload_max_size is less, then reduce. Except if upload_max_size is
		// zero, which indicates no limit.
		$return_val = $post_max_size_str;
		if ( $upload_max_filesize > 0 && $upload_max_filesize < $post_max_size ) {
			$return_val = $upload_max_filesize_str;
		}
	}
	return $return_val;
}

/**
 * parse_size converts php.ini values from strings (like 128M or 64K) into actual numbers that can be compared
 *
 * @param string $size
 *
 * @return float
 */
function parse_size( $size ) {
	$unit = preg_replace( '/[^bkmgtpezy]/i', '', $size ); // Remove the non-unit characters from the size.
	$size = preg_replace( '/[^0-9\.]/', '', $size ); // Remove the non-numeric characters from the size.
	if ( $unit ) { // Find the position of the unit in the ordered string which is the power of magnitude to multiply a kilobyte by.
		return round( $size * pow( 1024, stripos( 'bkmgtpezy', $unit[0] ) ) );
	} else {
		return round( $size );
	}
}

/**
 * format_bytes converts an byte value supplied as an integer into a string suffixed with the appropriate unit of measurement.
 *
 * @param float $bytes
 * @param int $precision
 *
 * @return string
 */
function format_bytes( $bytes, $precision = 2 ) {
	$units = [ 'B', 'KB', 'MB', 'GB', 'TB' ];
	$bytes = max( $bytes, 0 );
	$pow = floor( ( $bytes ? log( $bytes ) : 0 ) / log( 1024 ) );
	$pow = min( $pow, count( $units ) - 1 );
	$bytes /= ( 1 << ( 10 * $pow ) );

	return round( $bytes, $precision ) . ' ' . $units[ $pow ];
}

/**
 * @param $message
 * @param null $message_type
 */
function debug_error_log( $message, $message_type = null ) {
	if ( defined( 'WP_TESTS_MULTISITE' ) === false && WP_DEBUG ) {
		\error_log( $message, $message_type ); // @codingStandardsIgnoreLine
	}
}

/**
 * Email error to an array of recipients
 *
 * @param array $emails
 * @param string $subject
 * @param string $message
 */
function email_error_log( $emails, $subject, $message ) {

	// ------------------------------------------------------------------------------------------------------------
	// Write to generic error log to be safe

	debug_error_log( $subject . "\n" . $message );

	// ------------------------------------------------------------------------------------------------------------
	// Email logs

	add_filter(
		'wp_mail_from', function ( $from_email ) {
			return str_replace( 'wordpress@', 'pressbooks@', $from_email );
		}
	);
	add_filter(
		'wp_mail_from_name', function ( $from_name ) {
			return 'Pressbooks';
		}
	);

	/**
	 * Filter an array of email addresses error logs are sent to
	 *
	 * @since 4.3.3
	 *
	 * @param array $emails
	 */
	$emails = apply_filters( 'pb_error_log_emails', $emails );

	foreach ( $emails as $email ) {
		// Call pluggable
		\wp_mail( $email, $subject, $message );
	}
}


/**
 * Simple template system.
 *
 * @param string $path
 * @param array $vars (optional)
 *
 * @return string
 */
function template( $path, array $vars = [] ) {

	if ( ! file_exists( $path ) ) {
		throw new \InvalidArgumentException( "File not found: $path" );
	}

	ob_start();
	extract( $vars ); // @codingStandardsIgnoreLine
	include( $path );
	$output = ob_get_contents();
	ob_end_clean();

	return $output;
}

/**
 * If we get error code 400, retry wp_remote_get()
 *
 * @param string $url
 * @param array $args
 * @param int $retry
 * @param int $attempts
 * @param array $response
 *
 * @return array|\WP_Error
 */
function remote_get_retry( $url, $args, $retry = 3, $attempts = 0, $response = [] ) {
	$completed = false;

	if ( $attempts >= $retry ) {
		$completed = true;
	}

	if ( $completed ) {
		return $response;
	}

	$attempts++;

	$response = wp_remote_get( $url, $args );

	/**
	 * Filter the array of response codes which should prompt a retry.
	 *
	 * @since 4.3.0
	 */
	$retry_response_codes = apply_filters(
		'pb_remote_get_retry_response_codes',
		/**
		 * Filter the array of response codes which should prompt a retry.
		 *
		 * @since 3.9.6
		 * @deprecated 4.3.0 Use pb_remote_get_retry_response_codes isntead.
		 *
		 * @param array $value
		 */
		apply_filters( 'pressbooks_remote_get_retry_response_codes', [ 400 ] )
	);

	if ( ! is_array( $response ) || ! in_array( $response['response']['code'], $retry_response_codes, true ) ) {
		return $response;
	}

	/**
	 * Filter the sleep time for a retry.
	 *
	 * @since 4.3.0
	 */
	$sleep = apply_filters(
		'pb_remote_get_retry_wait_time',
		/**
		 * Filter the sleep time for a retry.
		 *
		 * @since 3.9.6
		 * @deprecated 4.3.0 Use pb_remote_get_retry_wait_time isntead.
		 *
		 * @param int $value
		 */
		apply_filters( 'pressbooks_remote_get_retry_wait_time', 1000 )
	);
	usleep( $sleep );
	return remote_get_retry( $url, $args, $retry, $attempts, $response );
}

/**
 * Set the wp_mail sender address
 *
 * @since 3.9.7
 *
 * @param string $email The default email address
 *
 * @return string
 */
function mail_from( $email ) {
	if ( defined( 'WP_MAIL_FROM' ) ) {
		$email = WP_MAIL_FROM;
	} else {
		$sitename = strtolower( $_SERVER['SERVER_NAME'] );
		if ( substr( $sitename, 0, 4 ) === 'www.' ) {
			$sitename = substr( $sitename, 4 );
		}
		$email = 'pressbooks@' . $sitename;
	}
	return $email;
}

/**
 * Set the wp_mail sender name
 *
 * @since 3.9.7
 *
 * @param string $name The default sender name
 *
 * @return string
 */
function mail_from_name( $name ) {
	if ( defined( 'WP_MAIL_FROM_NAME' ) ) {
		$name = WP_MAIL_FROM_NAME;
	} else {
		$name = 'Pressbooks';
	}
	return $name;
}

/**
 * Recursive directory copy. Props to https://ben.lobaugh.net/blog/864/php-5-recursively-move-or-copy-files
 *
 * @since 3.9.8
 * @author Ben Lobaugh <ben@lobaugh.net>
 *
 * @param string $src
 * @param string $dest
 * @param array $excludes (optional, supports shell wildcard patterns, add a unix like trailing slash for folders)
 * @param array $includes (optional, supports shell wildcard patterns, add a unis like trailing slash for folders)
 *
 * @return bool
 */
function rcopy( $src, $dest, $excludes = [], $includes = [] ) {

	// Remove trailing slashes
	$src = rtrim( $src, '\\/' );
	$dest = rtrim( $dest, '\\/' );

	if ( ! is_dir( $src ) ) {
		return false;
	}

	if ( ! is_dir( $dest ) ) {
		if ( ! mkdir( $dest ) ) {
			return false;
		}
	}

	$i = new \DirectoryIterator( $src );
	foreach ( $i as $f ) {
		$include_this_file = ( empty( $includes ) ? true : false );
		if ( $f->isFile() ) {
			// File
			foreach ( $excludes as $exclude ) {
				if ( fnmatch( $exclude, "$f" ) ) {
					continue 2; // Excluded, go to next file
				}
			}
			foreach ( $includes as $include ) {
				if ( fnmatch( $include, "$f" ) ) {
					$include_this_file = true;
					break;
				}
			}
			if ( $include_this_file ) {
				if ( false === copy( $f->getRealPath(), "$dest/$f" ) ) {
					return false;
				}
			}
		} elseif ( ! $f->isDot() && $f->isDir() ) {
			// Directory
			foreach ( $excludes as $exclude ) {
				if ( str_ends_with( $exclude, '/' ) ) {
					if ( fnmatch( rtrim( $exclude, '/' ), "$f" ) ) {
						continue 2; // Excluded, go to next file
					}
				}
			}
			$dir_pattern_count = 0;
			foreach ( $includes as $include ) {
				if ( str_ends_with( $include, '/' ) ) {
					$dir_pattern_count++;
					if ( fnmatch( rtrim( $include, '/' ), "$f" ) ) {
						$include_this_file = true;
						break;
					}
				}
			}
			if ( $include_this_file || $dir_pattern_count === 0 ) {
				\Pressbooks\Utility\rcopy( $f->getRealPath(), "$dest/$f", $excludes, $includes );
			}
		}
	}
	return true;
}

/**
 * @param string $haystack
 * @param string $needle
 *
 * @return bool
 */
function str_starts_with( $haystack, $needle ) {
	$length = strlen( $needle );
	return ( substr( $haystack, 0, $length ) === $needle );
}

/**
 * @param string $haystack
 * @param string $needle
 *
 * @return bool
 */
function str_ends_with( $haystack, $needle ) {
	$length = strlen( $needle );
	if ( $length === 0 ) {
		return true;
	}
	return ( substr( $haystack, -$length ) === $needle );
}

/**
 * Remove a string from the beginning of a string
 *
 * @param $haystack
 * @param $prefix
 *
 * @return bool|string
 */
function str_remove_prefix( $haystack, $prefix ) {
	if ( substr( $haystack, 0, strlen( $prefix ) ) === $prefix ) {
		$haystack = substr( $haystack, strlen( $prefix ) );
	}
	return $haystack;
}

/**
 * Replace last occurrence of a String
 *
 * @param string $search
 * @param string $replace
 * @param string  $subject
 *
 * @return string
 */
function str_lreplace( $search, $replace, $subject ) {
	$pos = strrpos( $subject, $search );
	if ( $pos !== false ) {
		$subject = substr_replace( $subject, $replace, $pos, strlen( $search ) );
	}
	return (string) $subject;
}

/**
 * Search a comma delimited string for a match
 *
 * @param string $haystack
 * @param string $needle
 *
 * @return bool
 */
function comma_delimited_string_search( $haystack, $needle ) {
	$haystack = explode( ',', $haystack );
	foreach ( $haystack as $hay ) {
		if ( trim( $needle ) === trim( $hay ) ) {
			return true;
		}
	}
	return false;
}

/**
 * @param string $content
 *
 * @return int
 */
function word_count( $content ) {

	$n = 0;
	$content = wp_strip_all_tags( $content, true );

	// Is this chinese?
	if ( preg_match( '/[\x{4e00}-\x{9fa5}]+/u', $content ) ) {
		$content = preg_replace( '/[！，。？、]/', ' ', $content ); // Remove chinese punctuation
		$content = preg_replace( '/[\x80-\xff]{1,3}/', ' ', $content, -1, $n ); // Count chinese characters, replace $n
	}

	if ( ! empty( trim( $content ) ) ) {
		$n += count( preg_split( '/\s+/', $content ) ); // Count between spaces
	}

	return $n;
}

/**
 * Because realpath() does not work on files that do not exist... Handles paths and URLs
 *
 * @param string $path
 *
 * @return string
 */
function absolute_path( $path ) {

	if ( filter_var( $path, FILTER_VALIDATE_URL ) !== false ) {
		$url = $path;
		$path = wp_parse_url( $path, PHP_URL_PATH );
	}

	$new_path = str_replace( '\\', '/', $path );
	$parts = array_filter( explode( '/', $new_path ), 'strlen' );
	$absolutes = [];
	foreach ( $parts as $part ) {
		if ( '.' === $part ) {
			continue;
		}
		if ( '..' === $part ) {
			array_pop( $absolutes );
		} else {
			$absolutes[] = $part;
		}
	}

	$new_path = '/' . implode( '/', $absolutes );
	if ( isset( $url ) ) {
		$new_path = str_lreplace( $path, $new_path, $url );
	}

	return $new_path;
}

/**
 * Compare URL domain names (not subdomain)
 *
 * @param string $url1
 * @param string $url2
 *
 * @return bool
 */
function urls_have_same_host( $url1, $url2 ) {

	$host1 = wp_parse_url( $url1, PHP_URL_HOST );
	$host2 = wp_parse_url( $url2, PHP_URL_HOST );
	if ( ! $host1 || ! $host2 ) {
		return false;
	}

	$host_names1 = explode( '.', $host1 );
	if ( count( $host_names1 ) > 1 ) {
		$bottom_host_name1 = $host_names1[ count( $host_names1 ) - 2 ] . '.' . $host_names1[ count( $host_names1 ) - 1 ];
	} else {
		$bottom_host_name1 = $host1;
	}

	$host_names2 = explode( '.', $host2 );
	if ( count( $host_names2 ) > 1 ) {
		$bottom_host_name2 = $host_names2[ count( $host_names2 ) - 2 ] . '.' . $host_names2[ count( $host_names2 ) - 1 ];
	} else {
		$bottom_host_name2 = $host2;
	}

	$same_host = ( $bottom_host_name1 === $bottom_host_name2 );

	return $same_host;
}

/**
 * Namespace our generated content
 *
 * @since 5.0.0
 *
 * @param string $suffix (optional)
 * @param bool $mkdir (optional)
 *
 * @return string
 */
function get_generated_content_path( $suffix = '', $mkdir = true ) {
	$path = wp_upload_dir()['basedir'] . '/pressbooks';
	if ( $suffix ) {
		$suffix = ltrim( $suffix, '/' );
		$path = absolute_path( "{$path}/{$suffix}" );
	}
	if ( $mkdir && ! file_exists( $path ) ) {
		wp_mkdir_p( $path );
	}
	return $path;
}

/**
 * Namespace our generated content
 *
 * @since 5.0.0
 *
 * @param string $suffix (optional)
 *
 * @return string
 */
function get_generated_content_url( $suffix = '' ) {
	$path = wp_get_upload_dir()['baseurl'] . '/pressbooks';
	if ( $suffix ) {
		$suffix = ltrim( $suffix, '/' );
		$path = absolute_path( "{$path}/{$suffix}" );
	}
	$path = \Pressbooks\Sanitize\maybe_https( $path );
	return $path;
}

/**
 * Blade cache path
 *
 * @return string
 */
function get_cache_path() {
	return get_generated_content_path( '/cache' );
}

/**
 * @since 5.0.0
 *
 * @see \WP_Filesystem
 * @return \WP_Filesystem_Direct
 */
function init_direct_filesystem() {
	if ( ! class_exists( 'WP_Filesystem_Direct' ) ) {
		$abstraction_file = apply_filters( 'filesystem_method_file', ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php', 'direct' ); // Use for mocks / testing
		require_once( ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php' );
		require_once( $abstraction_file );

		// Set the permission constants if not already set.
		if ( ! defined( 'FS_CHMOD_DIR' ) ) {
			define( 'FS_CHMOD_DIR', ( fileperms( ABSPATH ) & 0777 | 0755 ) );
		}
		if ( ! defined( 'FS_CHMOD_FILE' ) ) {
			define( 'FS_CHMOD_FILE', ( fileperms( ABSPATH . 'index.php' ) & 0777 | 0644 ) );
		}
	}
	return new \WP_Filesystem_Direct( [] );
}

/**
 * @since 5.0.0
 *
 * @param string $filename
 *
 * @return bool|string
 */
function get_contents( $filename ) {
	$fs = init_direct_filesystem();
	return $fs->get_contents( $filename );
}

/**
 * @since 5.0.0
 *
 * @param string $filename
 * @param mixed $data
 *
 * @return bool
 */
function put_contents( $filename, $data ) {
	$fs = init_direct_filesystem();
	return $fs->put_contents( $filename, $data );
}

/**
 * Delete all contents of a directory without using `RecursiveDirectoryIterator`
 * (E_WARNING: Too many open files, @see https://stackoverflow.com/a/37754469 )
 *
 * @since 5.0.0
 *
 * @param string $dirname
 * @param bool $only_empty
 *
 * @return bool
 */
function rmrdir( $dirname, $only_empty = false ) {

	if ( ! is_dir( $dirname ) ) {
		return false;
	}

	$dscan = [ realpath( $dirname ) ];
	$darr = [];
	while ( ! empty( $dscan ) ) {
		$dcur = array_pop( $dscan );
		$darr[] = $dcur;
		$d = opendir( $dcur );
		if ( $d ) {
			while ( $f = readdir( $d ) ) {
				if ( '.' === $f || '..' === $f ) {
					continue;
				}
				$f = $dcur . '/' . $f;
				if ( is_dir( $f ) ) {
					$dscan[] = $f;
				} else {
					unlink( $f );
				}
			}
			closedir( $d );
		}
	}
	$i_until = ( $only_empty ) ? 1 : 0;
	for ( $i = count( $darr ) - 1; $i >= $i_until; $i-- ) {
		if ( ! rmdir( $darr[ $i ] ) ) {
			trigger_error( "Warning: There was a problem deleting a temporary file in $dirname", E_USER_WARNING );
		}
	}

	return ( ( $only_empty ) ? ( count( scandir( $dirname ) ) <= 2 ) : ( ! is_dir( $dirname ) ) );
}


/**
 * Comma separated, Oxford comma, localized and between the last two items
 *
 * @since 5.0.0
 *
 * @param array $vars
 *
 * @return string
 */
function oxford_comma( array $vars ) {
	if ( count( $vars ) === 2 ) {
		return $vars[0] . ' ' . __( 'and', 'pressbooks' ) . ' ' . $vars[1];
	} else {
		$last = array_pop( $vars );
		$output = implode( ', ', $vars );
		if ( $output ) {
			$output .= ', ' . __( 'and', 'pressbooks' ) . ' ';
		}
		$output .= $last;
		return $output;
	}
}

/**
 * Explode an oxford comma seperated list of items
 *
 * @param $string
 *
 * @return array
 */
function oxford_comma_explode( $string ) {
	$results = [];
	if ( strpos( $string, ',' ) !== false ) {
		$items = explode( ',', $string );
		foreach ( $items as $item ) {
			$item = trim( $item );
			$item = str_remove_prefix( $item, __( 'and', 'pressbooks' ) . ' ' );
			if ( ! empty( $item ) ) {
				$results[] = $item;
			}
		}
	} else {
		$items = explode( ' ' . __( 'and', 'pressbooks' ) . ' ', $string );
		foreach ( $items as $item ) {
			$item = trim( $item );
			if ( ! empty( $item ) ) {
				$results[] = $item;
			}
		}
	}
	return $results;
}

/**
 * Converts a space separated string, to lowercase separated by dashes
 *
 * @since 5.5.0
 *
 * @param $string
 *
 * @return string
 */
function str_lowercase_dash( $string ) {
	$low = '';

	if ( ! empty( $string ) ) {
		$low = strtolower( trim( $string ) );
		$results = explode( ' ', $low );

		if ( count( $results ) > 1 ) {
			$low = implode( '-', $results );
		}
	}

	return $low;
}

/**
 * Check whether an array is zero-indexed and sequential
 *
 * @param mixed $arr
 *
 * @return bool
 */
function is_assoc( $arr ) {
	if ( ! is_array( $arr ) ) {
		return false;
	}
	if ( [] === $arr ) {
		return false;
	}
	return array_keys( $arr ) !== range( 0, count( $arr ) - 1 );
}

/**
 * Like PHP empty(), but also checks if a string is just white space
 *
 * @param mixed $var
 *
 * @return bool
 */
function empty_space( $var ) {
	if ( is_string( $var ) ) {
		if ( ctype_space( $var ) ) {
			$var = '';
		}
		$var = trim( $var );
	}
	return empty( $var );
}

/**
 * Best guess the main contact's email
 *
 * @return string
 */
function main_contact_email() {
	$main_site_id = get_main_site_id();
	$email = get_blog_option( $main_site_id, 'pb_network_contact_email' ); // Aldine
	if ( empty( $email ) ) {
		$email = get_blog_option( $main_site_id, 'admin_email' ); // Main Site
		if ( empty( $email ) ) {
			$email = get_site_option( 'admin_email' ); // Main Network
		}
	}
	return $email ? $email : '';
}

/**
 * Find a shortcode in content, look for an attribute in that shortcode, if value matches $from, change it to $to, return fixed content.
 *
 * @param string $content
 * @param string $tag
 * @param string $att
 * @param string $from
 * @param string $to
 *
 * @return string
 */
function shortcode_att_replace( $content, $tag, $att, $from, $to ) {
	$fixed_content = $content;
	$regex = get_shortcode_regex( [ $tag ] );
	if ( preg_match_all( '/' . $regex . '/s', $content, $matches, PREG_SET_ORDER ) ) {
		foreach ( $matches as $shortcode ) {
			$shortcode_attrs = shortcode_parse_atts( $shortcode[3] );
			if ( ! is_array( $shortcode_attrs ) ) {
				$shortcode_attrs = [];
			}
			if ( isset( $shortcode_attrs[ $att ] ) ) {
				if ( $shortcode_attrs[ $att ] === "&quot;{$from}&quot;" ) {
					$preg_from = "&quot;{$from}&quot;";
					$preg_to = "&quot;{$to}&quot;";
				} elseif ( $shortcode_attrs[ $att ] === '"' . $from . '"' ) {
					$preg_from = '"' . $from . '"';
					$preg_to = '"' . $to . '"';
				} elseif ( $shortcode_attrs[ $att ] === "'{$from}'" ) {
					$preg_from = "'{$from}'";
					$preg_to = "'{$to}'";
				} elseif ( (string) $shortcode_attrs[ $att ] === (string) $from ) {
					$preg_from = $from;
					$preg_to = $to;
				} else {
					continue;
				}
				$preg_from = '/(' . preg_quote( $att, '/' ) . '\s*=.*?)' . preg_quote( $preg_from, '/' ) . '/';
				$preg_to = '${1}' . $preg_to;
				$fixed_shortcode = preg_replace( $preg_from, $preg_to, $shortcode[0] );
				$fixed_content = str_replace( $shortcode[0], $fixed_shortcode, $fixed_content );
			}
		}
	}
	return $fixed_content;
}

