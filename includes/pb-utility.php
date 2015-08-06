<?php
/**
 * Generic utility functions.
 *
 * @author  PressBooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */
namespace PressBooks\Utility;


/**
 * Scan a directory and return the files ordered by date, newest first.
 *
 * @param $dir
 *
 * @return array
 */
function scandir_by_date( $dir ) {

	$ignored = array( '.', '..', '.svn', '.git', '.htaccess' );

	$files = array();
	foreach ( scandir( $dir ) as $file ) {
		if ( in_array( $file, $ignored ) ) continue;
		$files[$file] = filemtime( $dir . '/' . $file );
	}
	arsort( $files );
	$files = array_keys( $files );

	return ( $files ) ? $files : array();
}


/**
 * Scan the exports directory, return the files grouped into intervals of 3 minutes, newest first.
 *
 * @return array
 */
function group_exports() {

	$ignored = array( '.', '..', '.svn', '.git', '.htaccess' );

	$dir = \PressBooks\Export\Export::getExportFolder();

	$files = array();
	foreach ( scandir( $dir ) as $file ) {
		if ( in_array( $file, $ignored ) ) continue;
		$files[$file] = filemtime( $dir . $file );
	}
	arsort( $files );

	$interval = 3 * 60; // Three minutes
	$pos = 0;
	$output = array();

	foreach ( $files as $file => $timestamp ) {
		if ( 0 == $pos ) {
			$pos = $timestamp;
		}
		if ( $pos - $timestamp > $interval ) {
			$pos = $timestamp;
		}
		$output[$pos][] = $file;
	}

	return $output;
}


/**
 * Truncate the exports directory, delete old files.
 *
 * @param int $max
 */
function truncate_exports( $max ) {

	$max = absint( $max );
	$dir = \PressBooks\Export\Export::getExportFolder();
	$files = group_exports();

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
 * Checks for existence of /wp-content/blogs.dir/; otherwise uses WordPress 3.5+ standard, /wp-content/uploads/sites/
 *
 * @return string path
 */
function get_media_prefix() {
	if ( is_dir( WP_CONTENT_DIR . '/blogs.dir' ) ) {
		return WP_CONTENT_DIR . '/blogs.dir/' . get_current_blog_id() . '/files/';
	} else {
		return WP_CONTENT_DIR . '/uploads/sites/' . get_current_blog_id() . '/';
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

	$parts = parse_url( $guid );
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
 * Array multisort function for sorting on multiple fields like in SQL, e.g: 'ORDER BY field1, field2'
 *
 * Supports optional ASC or DESC parameter by using : delimiter, example:
 *   multiSort($array, 'foo:asc', 'bar:desc', ...);
 *
 * @param array $array
 * @param string $a, $b, $c ...
 *
 * @return array
 */
function multi_sort() {
	//get args of the function
	$args = func_get_args();
	$c = count( $args );
	if ( $c < 2 ) {
		return false;
	}
	// get the array to sort
	$array = array_splice( $args, 0, 1 );
	$array = $array[0];
	// sort with an anonymous function using args
	usort( $array, function ( $a, $b ) use ( $args ) {
		$orderBy = 'asc';
		$i = 0;
		$c = count( $args );
		$cmp = 0;
		while ( $cmp == 0 && $i < $c ) {
			@list( $arg, $orderBy ) = explode( ':', $args[$i] );
			$orderBy = strtolower( $orderBy ) == 'desc' ? 'desc' : 'asc';
			$cmp = strcmp( $a[$arg], $b[$arg] );
			$i ++;
		}
		if ( $orderBy == 'desc' ) {
			return - $cmp; // Negate the value
		} else {
			return $cmp; // As is
		}
	} );

	return $array;
}


/**
 * Override \wp_mail() to always use Postmark API
 *
 * @param string|array $to Array or comma-separated list of email addresses to send message.
 * @param string $subject Email subject
 * @param string $message Message contents
 * @param string|array $headers Optional. Additional headers.
 * @param string|array $attachments Optional. Files to attach.
 *
 * @global $GLOBALS['PB_SECRET_SAUCE']['POSTMARK_API_KEY']
 * @global $GLOBALS['PB_SECRET_SAUCE']['POSTMARK_SENDER_ADDRESS']
 *
 * @return bool Whether the email contents were sent successfully.
 */
function wp_mail( $to, $subject, $message, $headers = '', $attachments = array() ) {

	$response = false;

	// Define Headers

	$postmark_headers = array(
		'Accept' => 'application/json',
		'Content-Type' => 'application/json',
		'X-Postmark-Server-Token' => $GLOBALS['PB_SECRET_SAUCE']['POSTMARK_API_KEY'],
	);

	// Send Email

	if ( ! is_array( $to ) ) {
		$recipients = explode( ',', $to );
	} else {
		$recipients = $to;
	}

	foreach ( $recipients as $recipient ) {

		$email = array();
		$email['To'] = $recipient;
		$email['From'] = $GLOBALS['PB_SECRET_SAUCE']['POSTMARK_SENDER_ADDRESS'];
		$email['Subject'] = $subject;
		$email['TextBody'] = $message;

		if ( strpos( $headers, 'text/html' ) ) {
			$email['HtmlBody'] = $message;
		}

		$response = pm_send_mail( $postmark_headers, $email );
	}

	return $response;
}


/**
 * Send JSON to Postmark API via POST method
 *
 * @param array $headers
 * @param array $email
 *
 * @return bool
 */
function pm_send_mail( array $headers, array $email ) {

	$postmark_endpoint = 'http://api.postmarkapp.com/email';

	$args = array(
		'headers' => $headers,
		'body' => json_encode( $email )
	);

	$response = wp_remote_post( $postmark_endpoint, $args );

	if ( is_wp_error( $response ) ) {
		return false;
	} elseif ( 200 == $response['response']['code'] ) {
		return true;
	} else {
		return false;
	}
}


/**
 * Add sitemap to robots.txt
 */
function add_sitemap_to_robots_txt() {

	if ( 1 == get_option( 'blog_public' ) ) {
		echo 'Sitemap: ' . get_option( 'siteurl' ) . "/?feed=sitemap.xml\n\n";
	}
}


/**
 * Echo a sitemap
 */
function do_sitemap() {

	if ( 1 == get_option( 'blog_public' ) ) {
		$template = untrailingslashit( PB_PLUGIN_DIR ) . '/includes/pb-sitemap.php';
		load_template( $template );
	} else {
		status_header( 404 );
		nocache_headers();
		echo '<h1>404 Not Found</h1>';
		echo 'The page that you have requested could not be found.';
	}

	exit;
}

/**
 * Create a temporary file that automatically gets deleted when php ends
 *
 * @return string path to file
 */
function create_tmp_file() {

	return array_search( 'uri', @array_flip( stream_get_meta_data( $GLOBALS[mt_rand()] = tmpfile() ) ) );
}

/**
 * Lightweight check to see if the prince constant is defined and if the 
 * executable file exists
 * 
 * @return boolean
 */
function check_prince_install() {
	$result = false;

	// @see wp-config.php
	if ( ! defined( 'PB_PRINCE_COMMAND' ) ) {
		define( 'PB_PRINCE_COMMAND', '/usr/bin/prince' );
	}
	// check if the file exists, assume that's enough
	if ( ! file_exists( PB_PRINCE_COMMAND ) ) {
		$result = false;
	} else {
		$result = true;
	}
	return $result;
}

/**
 * Function to determine whether or not experimental features should be visible to users. Currently just hides them from *.pressbooks.com.
 * 
 * @return boolean
 */
function show_experimental_features() {
	$result = true;

	// hosts where experimental features should be hidden
	$hosts_for_hiding = array( 
		'pressbooks.com',
		'pressbooks.pub',
	);

	$host = parse_url( network_site_url(), PHP_URL_HOST );
	
	foreach( $hosts_for_hiding as $host_for_hiding ) {
		if ( $host == $host_for_hiding || strpos( $host, $host_for_hiding ) ) {
			$result = false;
		}
	}

	return $result;
}

/**
 * Include plugins in /symbionts
 * 
 * @since 2.5.1
 */
function include_plugins() {
	$symbionts = array(
	    'custom-metadata/custom_metadata.php' => 1,
	    'search-regex/search-regex.php' => 1,
	    'mce-table-buttons/mce_table_buttons.php' => 1,
	    'mce-superscript-subscript-buttons/mce-superscript-subscript-buttons.php' => 1,
	);

	$symbionts = filter_plugins( $symbionts );

	// include plugins
	if ( ! empty( $symbionts ) ) {
		foreach ( $symbionts as $key => $val ) {
			require_once( PB_PLUGIN_DIR . 'symbionts/' . $key);
		}
	}
}

/**
 * Filters out active plugins, to avoid collisions with plugins already installed.
 * 
 * @since 2.5.1
 * @param array $symbionts
 * @return array
 */
function filter_plugins( $symbionts ) {
	$already_active = get_option( 'active_plugins' );
	$network_already_active = get_site_option( 'active_sitewide_plugins' );
	
	// don't include plugins already active at the site level, network level
	if ( ! empty( $symbionts ) ) {
		foreach ( $symbionts as $key => $val ) {
			if ( in_array( $key, $already_active ) || array_key_exists( $key, $network_already_active )) {
				unset( $symbionts[$key] );
			}
			
		}
	}
	return $symbionts;
}


/**
 * Function to return a string representing max import size by comparing values of upload_max_filesize, post_max_size
 * Uses parse_size helper function since the values in php.ini are strings like 64M and 128K
 * @return string
 */

function file_upload_max_size() {
	static $max_size = -1;
	// This function is adapted from Drupal and http://stackoverflow.com/questions/13076480/php-get-actual-maximum-upload-size 
	if ($max_size < 0) {
	
		$post_max_size_str = ini_get( 'post_max_size' );
		$upload_max_filesize_str = ini_get( 'upload_max_filesize' );
		$post_max_size = parse_size( $post_max_size_str );
		$upload_max_filesize = parse_size( $upload_max_filesize_str );
		
		// If upload_max_size is less, then reduce. Except if upload_max_size is
		// zero, which indicates no limit.
		$returnVal = $post_max_size_str;
		if ( $upload_max_filesize > 0 && $upload_max_filesize < $post_max_size ) {
		  $returnVal = $upload_max_filesize_str;
		}
	}
	return $returnVal;
}

/**
 * parse_size converts php.ini values from strings (like 128M or 64K) into actual numbers that can be compared
 * @return integer
 */
function parse_size( $size ) {
	$unit = preg_replace( '/[^bkmgtpezy]/i', '', $size ); // Remove the non-unit characters from the size.
	$size = preg_replace( '/[^0-9\.]/', '', $size ); // Remove the non-numeric characters from the size.
	if ( $unit ) { // Find the position of the unit in the ordered string which is the power of magnitude to multiply a kilobyte by.
		return round( $size * pow( 1024, stripos( 'bkmgtpezy', $unit[0] ) ) );
	}
	else {
		return round( $size );
	}
}

/**
 * format_bytes converts an byte value supplied as an integer into a string suffixed with the appropriate unit of measurement.
 * @return string
 */
function format_bytes( $bytes, $precision = 2 ) { 
    $units = array('B', 'KB', 'MB', 'GB', 'TB'); 
    $bytes = max( $bytes, 0 ); 
    $pow = floor( ( $bytes ? log( $bytes ) : 0 ) / log( 1024 ) ); 
    $pow = min( $pow, count( $units ) - 1 ); 
    $bytes /= (1 << (10 * $pow)); 

    return round( $bytes, $precision ) . ' ' . $units[$pow]; 
}