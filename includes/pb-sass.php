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
function compile( $scss, $options = array() ) {
	
	$css = '/* Silence is golden. */'; // If no SCSS input was passed, prevent file write errors by putting a comment in the CSS output.

	if ( $scss !== '' ) {
		$scss_file = array_search( 'uri', @array_flip( stream_get_meta_data( $GLOBALS[mt_rand()] = tmpfile() ) ) );
		rename( $scss_file, $scss_file .= '.scss' ); 
		register_shutdown_function( create_function( '', "unlink('{$scss_file}');" ) ); 
		file_put_contents( $scss_file, $scss );
		require_once( PB_PLUGIN_DIR . 'symbionts/phpsass/SassLoader.php' );
		
		$sass = new \SassParser( $options );
		$css = $sass->toCss( $scss_file );
	}
	
	return $css;
}

/**
 * Write CSS to a a file in subdir named 'export-css'
 *
 * @param string $css
 *
 * @param string $filename
 */
function debug( $css, $filename ) {
    // Output compiled CSS for debugging.
    $wp_upload_dir = wp_upload_dir();
    $debug_dir = $wp_upload_dir['basedir'] . '/export-css';
    if ( ! is_dir( $debug_dir ) ) {
        mkdir( $debug_dir );
    }
    $debug_file = $debug_dir . '/' . $filename;
    file_put_contents( $debug_file, $css );
}
