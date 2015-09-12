<?php
/**
 * SASS functions.
 *
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */
namespace PressBooks\SASS;

/**
 * Returns the compiled CSS from SCSS input
 *
 * @param string $scss
 *
 * @return string the compiled CSS
 */
function compile( $scss ) {
	
	$css = '/* Silence is golden. */'; // If no SCSS input was passed, prevent file write errors by putting a comment in the CSS output.

	if ( $scss !== '' ) {
		$scss_file = array_search( 'uri', @array_flip( stream_get_meta_data( $GLOBALS[mt_rand()] = tmpfile() ) ) );
		rename( $scss_file, $scss_file .= '.scss' ); 
		register_shutdown_function( create_function( '', "unlink('{$scss_file}');" ) ); 
		file_put_contents( $scss_file, $scss );
		require_once( PB_PLUGIN_DIR . 'symbionts/phpsass/SassParser.php' );
		$sass = new \SassParser();
		$css = $sass->toCss( $scss_file );
	}
	
	return $css;
}
