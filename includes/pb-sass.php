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
 * @return string the compiled CSS (or empty string on error)
 */
function compile( $scss, $includes = array() ) {

	try {

		$css = '/* Silence is golden. */'; // If no SCSS input was passed, prevent file write errors by putting a comment in the CSS output.

		if ( $scss !== '' ) {
			if ( extension_loaded( 'sass' ) ) { // use sassphp extension
				$scss_file = array_search( 'uri', @array_flip( stream_get_meta_data( $GLOBALS[mt_rand()] = tmpfile() ) ) );
				rename( $scss_file, $scss_file .= '.scss' );
				register_shutdown_function( create_function( '', "unlink('{$scss_file}');" ) );
				file_put_contents( $scss_file, $scss );
				$sass = new \Sass();
				$include_paths = implode( ':', $includes );
				$sass->setIncludePath( $include_paths );
				$css = $sass->compileFile( $scss_file );
			}
			else { // use scssphp library
				require_once( PB_PLUGIN_DIR . 'symbionts/scssphp/scss.inc.php' );
				$sass = new \Leafo\ScssPhp\Compiler;
				$sass->setImportPaths( $includes );
				$css = $sass->compile( $scss );
 			}
		}

	} catch ( \Exception $e ) {

		$message = sprintf( __( 'There was a problem with SASS. Contact your site administrator. Error: %s', 'pressbooks' ), $e->getMessage() );
		$_SESSION['pb_errors'][] = $message;
		_logException( $e );
		return ''; // Return empty string on error
	}

	return $css;
}

/**
 * Log Exceptions
 *
 * @param \Exception $e
 */
function _logException( \Exception $e ) {

	// List of people who care about SASS Errors
	$emails = array(
		'errors@pressbooks.com',
	);

	$subject = '[ SASS Error ]';

	/** $var \WP_User $current_user */
	global $current_user;

	$info = array(
		'time' => strftime( '%c' ),
		'user' => ( isset( $current_user ) ? $current_user->user_login : '__UNKNOWN__' ),
		'site_url' => site_url(),
		'blog_id' => get_current_blog_id(),
		'Exception' => array(
			'code' => $e->getCode(),
			'error' => $e->getMessage(),
			'file' => $e->getFile(),
			'line' => $e->getLine(),
			'trace' => $e->getTraceAsString()
		)
	);

	$message = print_r( array_merge( $info ), true );

	\PressBooks\Utility\email_error_log(
		$emails,
		$subject,
		$message
	);
}

/**
 * Write CSS to a a file in subdir named '/css/debug'
 *
 * @param string $css
 *
 * @param string $filename
 */
function debug( $css, $scss, $filename ) {
	// Output SCSS and compiled CSS for debugging.
	$wp_upload_dir = wp_upload_dir();
	$debug_dir = $wp_upload_dir['basedir'] . '/css/debug';
	if ( ! is_dir( $debug_dir ) ) {
		mkdir( $debug_dir );
	}
	$css_debug_file = $debug_dir . '/' . $filename . '.css';
	$scss_debug_file = $debug_dir . '/' . $filename . '.scss';
	file_put_contents( $css_debug_file, $css );
	file_put_contents( $scss_debug_file, $scss );
}
