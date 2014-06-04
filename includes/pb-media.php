<?php
/**
 * @author  PressBooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */
namespace PressBooks\Media;

/**
 * Filter to alter the list of acceptable file extensions
 * 
 * @see \PressBooks\Export\Epub3
 * @return array
 */
function addMimeTypes( $existing_mimes = array() ) {
	
	$add_mimes = array(
	    'mp4' => 'video/mp4',
	    'webm' => 'video/webm',
	    'ogv' => 'video/ogg',
	    'ogg' => 'audio/ogg',
	    'mp3' => 'audio/mpeg',
	    'aac' => 'audio/x-aac',
	    'vorbis' => 'audio/vorbis',
	);

	return array_merge( $add_mimes, $existing_mimes );
}

/**
 * Checks for file validity on import.
 * 
 * @param type $data
 * @param type $filename
 * @return boolean
 */
function is_valid_media( $data, $filename ) {
	
	$mimes = array(
	    'mp4' => 'video/mp4',
	    'webm' => 'video/webm',
	    'ogv' => 'video/ogg',
	    'ogg' => 'audio/ogg',
	    'mp3' => 'audio/mpeg',
	    'aac' => 'audio/x-aac',
	    'vorbis' => 'audio/vorbis',
	);
	
	$validate = wp_check_filetype( $filename, $mimes );

	// check the file extension
	if ( ! array_key_exists( $validate['ext'], $mimes ) ) {
		return false;
	}

	// check the mimetype
	if ( ! in_array( $validate['type'], $mimes ) ) {
		return false;
	}
	
	return true;
}

/**
 * Wraps images in caption tag even if they aren't.
 * 
 * @param type $html
 * @return string
 */
function force_caption( $html ) {
  $a = strpos( $html, 'caption' );
  if ( $a !== 1 ) {
    preg_match( '/(alignnone|alignleft|alignright|aligncenter)/', $html, $c );
    preg_match( '/width="(\d*)"/', $html, $w );
    preg_match( '/alt="([^"]*)"/', $html, $m );
    preg_match( '/wp-image-(\d*)"/', $html, $n );
    if ( !isset( $n[1] ) ) $n[1] = '0';
    $html = '[caption id="attachment_' . $n[1] . '" align="' . ( $c ? $c[1] : 'alignnone' ) . '" width="' . $w[1] . '"]' . $html . "&nbsp;[/caption]";
  }
  return $html;
}
