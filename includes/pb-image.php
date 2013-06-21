<?php
/**
 * @author  PressBooks <code@pressbooks.org>
 * @license GPLv2 (or any later version)
 */
namespace PressBooks\Image;


/**
 * Check if a file (or stream) is a valid image type.
 *
 * @param string $data
 * @param string $filename
 * @param bool $is_stream (optional)
 *
 * @return bool
 */
function is_valid_image( $data, $filename, $is_stream = false ) {

	$format = explode( '.', $filename );
	$format = strtolower( end( $format ) ); // Extension
	if ( $format == 'jpg' ) $format = 'jpeg'; // Fix stupid mistake
	if ( ! ( $format == 'jpeg' || $format == 'gif' || $format == 'png' ) ) {
		return false;
	}

	if ( $is_stream ) {

		$image = @imagecreatefromstring( $data );
		if ( $image === false ) {
			return false;
		}

	} else {

		$func = 'imagecreatefrom' . $format;
		$image = @$func( $data );
		if ( $image === false ) {
			return false;
		}
	}

	imagedestroy( $image );
	unset( $image );

	return true;
}


/**
 * Rename image with arbitrary suffix (before extension)
 *
 * @param string $thumb
 * @param string $path
 *
 * @return string
 */
function thumbify( $thumb, $path ) {

	$thumb = preg_quote( $thumb, '/' );

	return stripslashes( preg_replace( '/(\.jpe?g|\.gif|\.png)/i', "$thumb$1", $path ) );
}


/**
 * Get a list of possible thumbnail names for an image
 *
 * @see make_thumbnails
 *
 * @param string $path
 *
 * @return array
 */
function get_possible_thumbnail_names( $path ) {

	$thumbs = array(
		'-100x100',
		'-65x0',
		'-225x0',
		'-225x0@2x',
	);

	$names = array();
	foreach ( $thumbs as $thumb ) {
		$names[] = thumbify( $thumb, $path );
	}

	return $names;
}


/**
 * Generate thumbnails for a given image
 * Note: if you change this, don't forget to also change get_possible_thumbnail_names()
 *
 * @see get_possible_thumbnail_names
 *
 * @param $path
 */
function make_thumbnails( $path ) {

	/* $img->resize() is ignored after save, re-instantiate for each action... */

	$img = wp_get_image_editor( $path );
	if ( ! is_wp_error( $img ) ) {
		$img->resize( 100, 100, true );
		$img->save( thumbify( '-100x100', $path ) );
	}

	$img = wp_get_image_editor( $path );
	if ( ! is_wp_error( $img ) ) {
		$img->resize( 65, null, false );
		$img->save( thumbify( '-65x0', $path ) );
	}

	$img = wp_get_image_editor( $path );
	if ( ! is_wp_error( $img ) ) {
		$img->resize( 225, null, false );
		$img->save( thumbify( '-225x0', $path ) );
	}

	$img = wp_get_image_editor( $path );
	if ( ! is_wp_error( $img ) ) {
		$img->resize( 450, null, false );
		$img->save( thumbify( '-225x0@2x', $path ) );
	}
}
