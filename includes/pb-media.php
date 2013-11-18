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
function addMimeTypes( $add_mimes = array() ) {
	
	$add_mimes = array(
	    'mp4' => 'video/mp4',
	    'webm' => 'video/webm',
	    'ogv' => 'video/ogg',
	    'ogg' => 'audio/ogg',
	    'mp3' => 'audio/mpeg',
	    'aac' => 'audio/x-aac',
	    'vorbis' => 'audio/vorbis',
	);

	return $add_mimes;
}
