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
 * Get the attachment id from an image url.
 *
 * @param string $url
 *
 * @return int
 */
function get_attachment_id_from_url( $url ) {

	/** @var $wpdb \wpdb */
	global $wpdb;

	// If this is the URL of an auto-generated thumbnail, get the URL of the original image
	$url = preg_replace( '/-\d+x\d+(?=\.(jp?g|png|gif)$)/i', '', $url );

	// Remove the upload path base directory from the attachment URL
	$preg = '#(19|20)\d\d/(0[1-9]|1[012])/.+(\.jpe?g|\.gif|\.png)$#i'; # YYYY/MM/foo-Bar.png
	if ( preg_match( $preg, $url, $matches ) ) {
		$url = $matches[0];
	}

	// Get the attachment ID from the modified attachment URL
	$sql = "SELECT wposts.ID FROM {$wpdb->posts} wposts, {$wpdb->postmeta} wpostmeta WHERE wposts.ID = wpostmeta.post_id AND wpostmeta.meta_key = '_wp_attached_file' AND wpostmeta.meta_value = '%s' AND wposts.post_type = 'attachment' ";
	$sql = $wpdb->prepare( $sql, $url );
	$id = $wpdb->get_var( $sql );

	return absint( $id );
}

/**
 * Try to get a thumbnail url from an image url.
 *
 * @param string $url
 * @param string $size
 *
 * @return string
 *
 */
function get_thumbnail_from_url( $url, $size ) {

	$id = get_attachment_id_from_url( $url );
	$image_thumb = wp_get_attachment_image_src( $id, $size );

	if ( $image_thumb ) return $image_thumb[0]; // URL
	else return $url; // Couldn't find anything, return original
}


/**
 * Get a list of possible intermediate image sizes.
 * If $image_sizes is not empty, then use as WP hook
 *
 * @param array $image_sizes (optional)
 *
 * @return array
 */
function get_intermediate_image_sizes( array $image_sizes = array() ) {

	$our_sizes = array(
		'pb_cover_small' => array( 'width' => 65, 'height' => 0, 'crop' => false ),
		'pb_cover_medium' => array( 'width' => 225, 'height' => 0, 'crop' => false ),
		'pb_cover_large' => array( 'width' => 350, 'height' => 0, 'crop' => false ),
	);

	if ( empty( $image_sizes ) ) {
		return $our_sizes;
	} else {
		// Hook for filter 'intermediate_image_sizes'
		return array_merge( $image_sizes, array_keys( $our_sizes ) );
	}
}


/**
 * WP Hook for filter 'intermediate_image_sizes_advanced'
 *
 * @param $image_sizes
 *
 * @return array
 */
function intermediate_image_sizes_advanced( array $image_sizes ) {

	$image_sizes = array_merge( $image_sizes, get_intermediate_image_sizes() );

	return $image_sizes;
}


/**
 * WP Hook for action 'delete_attachment'. Deal with user deleting cover image from Media Library.
 *
 * @param $post_id
 */
function delete_attachment( $post_id ) {

	$post = get_post( $post_id );
	$meta_post = ( new \PressBooks\Metadata() )->getMetaPost(); // PHP 5.4+

	if ( $meta_post && $post && $post->post_parent == $meta_post->ID ) {
		// Reset pb_cover_image to default
		update_post_meta( $meta_post->ID, 'pb_cover_image', PB_PLUGIN_URL . 'assets/images/default-book-cover.jpg' );
		\PressBooks\Book::deleteBookObjectCache();
	}
}


/**
 * WP Hook for action 'update_attached_file'. Deal with user editing cover image from Media Library.
 */
function save_attachment( $data, $post_id ) {

	if ( empty( $data['file'] ) )
		return $data; // Bail

	$post = get_post( $post_id );
	$meta_post = ( new \PressBooks\Metadata() )->getMetaPost(); // PHP 5.4+
	if ( $meta_post && $post && $post->post_parent != $meta_post->ID )
		return $data; // Bail

	$upload_dir = wp_upload_dir();
	$url = untrailingslashit( $upload_dir['baseurl'] ) . "/{$data['file']}";
	$pid = $meta_post->ID;

	update_post_meta( $pid, 'pb_cover_image', $url );
	\PressBooks\Book::deleteBookObjectCache();

	return $data;
}


