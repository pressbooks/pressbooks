<?php
/**
 * Generic utility functions.
 *
 * @author  PressBooks <code@pressbooks.org>
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
 * Supports optional ASC or DESC parameter by using : delimiter, example:
 *   multiSort($array, 'foo:asc', 'bar:desc', ...);
 *
 * @param array  $array
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

