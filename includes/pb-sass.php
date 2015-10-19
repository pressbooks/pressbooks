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
 * @return string|false the compiled CSS, false on failiure
 */
function compile( $scss, $includes = array() ) {
	
	$css = '/* Silence is golden. */'; // If no SCSS input was passed, prevent file write errors by putting a comment in the CSS output.

	try {

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
		_logError( '[ SASS Compile Error ]', $e );
		return false;
	}

	return $css;
}

/**
 * Write CSS to a a file in subdir named '/css/debug'
 *
 * @param string $css
 *
 * @param string $filename
 */
function debug( $css, $filename ) {
    // Output compiled CSS for debugging.
    $wp_upload_dir = wp_upload_dir();
	$debug_dir = $wp_upload_dir['basedir'] . '/css/debug';
	if ( ! is_dir( $debug_dir ) ) {
		mkdir( $debug_dir );
	}
	$debug_file = $debug_dir . '/' . $filename;
	file_put_contents( $debug_file, $css );
}


/**
 * Log an error we care about.
 *
 * @param string $subject
 * @param \Exception $e
 */
function _logError( $subject, $e ) {

	// List of people who care about SASS Errors
	$emails = array(
		'errors@pressbooks.com',
	);

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

	\PressBooks\Utility\emailErrorLog(
		$emails,
		$subject,
		$message
	);
}
