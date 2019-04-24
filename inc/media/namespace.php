<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Media;

/**
 * Filter to alter the list of acceptable file extensions
 *
 * @see \get_allowed_mime_types
 * @see \Pressbooks\Modules\Export\Epub3
 *
 * @param array $existing_mimes
 *
 * @return array
 */
function add_mime_types( $existing_mimes = [] ) {

	$add_mimes = [
		'aac' => 'audio/x-aac',
		'flac' => 'audio/flac',
		'flv' => 'video/x-flv',
		'm4a' => 'audio/m4a',
		'm4v' => 'video/mp4',
		'mp3' => 'audio/mpeg',
		'mp4' => 'video/mp4',
		'ogg' => 'audio/ogg',
		'ogv' => 'video/ogg',
		'vorbis' => 'audio/vorbis',
		'wav' => 'audio/wav',
		'webm' => 'video/webm',
	];

	return array_merge( $add_mimes, $existing_mimes );
}

/**
 * Checks for valid EPUB3 video or audio file names.
 *
 * @param string $path_to_file
 * @param string $filename
 *
 * @return boolean
 */
function is_valid_media( $path_to_file, $filename ) {

	$validate = wp_check_filetype( $filename, add_mime_types() );

	if ( false === $validate['ext'] || false === $validate['type'] ) {
		return false;
	}

	return true;
}

/**
 * Replaces and wraps images that are effected by automatic Wordpress paragraph tags.
 *
 * @param string $content
 *
 * @return string
 */
function force_wrap_images( $content ) {

	$pattern = [
		'#<p[^>]*>\s*?(<img class=\"([a-z0-9\- ]*).*?>)?\s*</p>#',
		'#<p[^>]*>\s*?(<a .*?><img class=\"([a-z0-9\- ]*).*?></a>)?\s*</p>#'
	];
	$replacement = '<div class="wp-nocaption $2">$1</div>';
	$content = preg_replace( $pattern, $replacement, $content );

	$pattern = [
		'#(<p[^>]*>)\s*?(<a .*?><img class=\"([a-z0-9\- ]*).*?></a>)?\s*<br />#'
	];
	$replacement = '<div class="wp-nocaption $3">$2</div>$1';
	$content = preg_replace( $pattern, $replacement, $content );

	return $content;
}

/**
 * @param array $params
 *
 * @see https://core.trac.wordpress.org/browser/trunk/wp-admin/includes/media.php?rev=22846
 *
 * @return array
 */

function force_attach_media( $params ) {
	// @codingStandardsIgnoreStart
	global $post_ID;
	if ( isset( $post_ID ) ) {
		$params['post_id'] = (int) $post_ID;
	}
	// @codingStandardsIgnoreEnd
	return $params;
}

/**
 * Detect MIME Content-type for a file.
 *
 * @param string $file fullpath
 *
 * @return string
 */
function mime_type( $file ) {

	if ( function_exists( 'finfo_open' ) ) {
		$finfo = finfo_open( FILEINFO_MIME );
		$mime = finfo_file( $finfo, $file );
		finfo_close( $finfo );
	} elseif ( function_exists( 'mime_content_type' ) ) {
		$mime = @mime_content_type( $file ); // Suppress deprecated message @codingStandardsIgnoreLine
	} else {
		exec( 'file -i -b ' . escapeshellarg( $file ), $output );
		$mime = $output[0];
	}

	return $mime;
}

/**
 * Purpose is to reliably determine the value of post_id for an image (only)
 * from an html string. Expects an array of html strings the likes of which can be
 * got from a function such as `get_media_embedded_in_content()`.
 *
 * @since 5.5.0
 * @author Brad Payne
 *
 * @param array $media html strings
 *
 * @return array $result post_id as key, guid as value
 */
function extract_id_from_media( $media ) {
	$result = [];
	if ( empty( $media ) ) {
		return $result;
	}

	// only look for images, for now
	foreach ( $media as $img ) {
		if ( ! preg_match_all( '/<img [^>]+>/', $img, $matches ) ) {
			continue;
		}
		preg_match( '/wp-image-([0-9]+)/i', $matches[0][0], $class_id );
		$attachment_id = ( isset( $class_id[1] ) ) ? absint( $class_id[1] ) : '';

		preg_match( '/src=[\'"](.*?)[\'"]/i', $matches[0][0], $source );
		$attachment_url = $source[1];

		$result[ $attachment_id ] = $attachment_url;
	}

	return $result;
}

/**
 * Seeks to reconcile the potential difference between media ids found in a
 * chapter with what is known to be available in the database. False media ids
 * could be left over from a cloning or import operation, for instance.
 * Comparing everything except filename (which is different in the page due
 * to size) gives assurance that there are enough similar attributes to
 * accept them as 'equal' or intersecting.
 *
 * @since 5.5.0
 * @author Brad Payne
 *
 * @param array $media_ids_in_page key is post_id, value is url
 * @param array $media_ids_found_in_book key is post_id, value is guid
 *
 * @return array
 */
function intersect_media_ids( $media_ids_in_page, $media_ids_found_in_book ) {
	$ids   = [];
	$found = array_intersect_key( $media_ids_in_page, $media_ids_found_in_book );

	foreach ( $found as $k => $v ) {
		$src       = wp_parse_url( $v );
		$guid      = wp_parse_url( $media_ids_found_in_book[ $k ] );
		$src_info  = pathinfo( $src['path'] );
		$guid_info = pathinfo( $guid['path'] );

		// must be from the same host
		if ( 0 !== strcmp( $src['host'], $guid['host'] ) ) {
			continue;
		}
		// must be same file extension
		if ( 0 !== strcmp( $src_info['extension'], $guid_info['extension'] ) ) {
			continue;
		}
		// must have same directory
		if ( 0 !== strcmp( $src_info['dirname'], $guid_info['dirname'] ) ) {
			continue;
		}

		$ids[] = $k;

	}

	return $ids;
}

/**
 * Returns the upload path and basename from attachment URL (ie. 2017/08/foo-bar.ext), or unchanged if no match is found.
 *
 * @param string $url
 *
 * @return string
 */
function strip_baseurl( $url ) {
	$extensions = implode( '|', array_keys( get_allowed_mime_types() ) );
	$preg = '#(19|20)\d\d/(0[1-9]|1[012])/.+\.(' . $extensions . ')$#i'; # YYYY/MM/foo-bar.ext
	if ( preg_match( $preg, $url, $matches ) ) {
		$url = $matches[0];
	}

	return $url;
}
