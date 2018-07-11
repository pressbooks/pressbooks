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
 * @param string $content
 *
 * @return string
 */
function force_wrap_images( $content ) {

	$pattern = [
		'#<p[^>]*>\s*?(<img class=\"([a-z0-9\- ]*).*?>)?\s*</p>#',
		'#<p[^>]*>\s*?(<a .*?><img class=\"([a-z0-9\- ]*).*?></a>)?\s*</p>#',
	];
	$replacement = '<div class="wp-nocaption $2">$1</div>';

	return preg_replace( $pattern, $replacement, $content );
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
