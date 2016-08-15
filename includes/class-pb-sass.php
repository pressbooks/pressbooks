<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */
namespace Pressbooks;


class Sass {

	/**
	 * Email addresses to send log errors.
	 *
	 * @var array
	 */
	public $errorsEmail = array(
		'errors@pressbooks.com',
	);


	/**
	 * Get default include paths
	 *
	 * @param string $type
	 */
	function defaultIncludePaths( $type, $theme = null ) {
		if ( $theme == null ) {
			$theme = wp_get_theme();
		}

		return [
			$this->pathToUserGeneratedSass(),
			$this->pathToGlobals(),
			$this->pathToFonts(),
			$theme->get_stylesheet_directory() . "/assets/styles/$type/",
		];

	}

	/**
	 * Fetch an array of strings in (S)CSS which need to be localized
	 *
	 * @return array
	 */
	function getStringsToLocalize() {

		return [
			'chapter' => __( 'Chapter', 'pressbooks' ),
			'part' => __( 'Part', 'pressbooks' ),
		];

	}

	/**
	 * Get the path to our PB Partials
	 *
	 * @return string
	 */
	function pathToPartials() {

		return PB_PLUGIN_DIR . 'assets/scss/partials';
	}

	/**
	 * Get the path to our PB Partials
	 *
	 * @return string
	 */
	function pathToGlobals() {

		return PB_PLUGIN_DIR . 'assets/book/styles';
	}


	/**
	 * Get the path to our PB Fonts
	 *
	 * @return string
	 */
	function pathToFonts() {

		return PB_PLUGIN_DIR . 'assets/scss/fonts';
	}


	/**
	 * Get path to a directory we can dump user transpiled CSS files into (create dir if it does not exist)
	 *
	 * @return string
	 */
	function pathToUserGeneratedCss() {

		$wp_upload_dir = wp_upload_dir();
		$upload_dir = $wp_upload_dir['basedir'] . '/css';

		if ( ! file_exists( $upload_dir ) ) {
			mkdir( $upload_dir, 0775, true );
		}

		return $upload_dir;
	}


	/**
	 * Get URI to user transpiled CSS files
	 *
	 * @return string
	 */
	function urlToUserGeneratedCss() {

		$wp_upload_dir = wp_upload_dir();
		$upload_dir = $wp_upload_dir['baseurl'] . '/css';
		$upload_dir = \Pressbooks\Sanitize\maybe_https( $upload_dir );
		return $upload_dir;
	}



	/**
	 * Get path to a directory we can dump user generated Sass files into (create dir if it does not exist)
	 *
	 * @return string
	 */
	function pathToUserGeneratedSass() {

		$wp_upload_dir = wp_upload_dir();
		$upload_dir = $wp_upload_dir['basedir'] . '/scss';

		if ( ! file_exists( $upload_dir ) ) {
			mkdir( $upload_dir, 0775, true );
		}

		return $upload_dir;
	}


	/**
	 * Get path to a directory we can dump debug files into
	 *
	 * @return string
	 */
	function pathToDebugDir() {

		$wp_upload_dir = wp_upload_dir();
		$upload_dir = $wp_upload_dir['basedir'] . '/scss-debug';

		if ( ! file_exists( $upload_dir ) ) {
			mkdir( $upload_dir, 0775, true );
		}

		return $upload_dir;
	}


	/**
	 * Returns the compiled CSS from SCSS input
	 *
	 * @param string $scss
	 * @param array $includes (optional)
	 * @return string
	 */
	function compile( $scss, $includes = array() ) {

		$scss = $this->prependLocalizedVars( $scss );

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
				} else { // use scssphp library
					$sass = new \Leafo\ScssPhp\Compiler;
					$sass->setImportPaths( $includes );
					$css = $sass->compile( $scss );
				}
			}

		}
		catch ( \Exception $e ) {

			$_SESSION['pb_errors'][] = sprintf(
					__( 'There was a problem with SASS. Contact your site administrator. Error: %s', 'pressbooks' ),
					$e->getMessage()
			);

			$this->logException( $e );

			if ( WP_DEBUG ) {
				$this->debug( "/* {$e->getMessage()} */", $scss, 'last-thrown-exception' );
			}

			return ''; // Return empty string on error
		}

		return $css;
	}

	/**
	 * Prepend localized version of content string variables.
	 *
	 * @param string $scss
	 * @return string
	 */
	function prependLocalizedVars( $scss ) {
		$strings = $this->getStringsToLocalize();
		$localizations = '';

		foreach ( $strings as $var => $string ) {
			$localizations .= "\$$var: '$string';\n";
		}

		if ( WP_DEBUG ) {
			$this->debug( "/* Silence is golden. */", $localizations, 'localizations' );
		}

		return $localizations . $scss;
	}

	/**
	 * Log Exceptions
	 *
	 * @param \Exception $e
	 */
	protected function logException( \Exception $e ) {

		$subject = __( 'SASS Error' );

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
				'trace' => $e->getTraceAsString(),
			),
		);

		$message = print_r( array_merge( $info ), true );

		\Pressbooks\Utility\email_error_log(
			$this->errorsEmail,
			$subject,
			$message
		);
	}


	/**
	 * Write CSS to a a debug dir
	 *
	 * @param string $css
	 *
	 * @param string $filename
	 */
	function debug( $css, $scss, $filename ) {

		$debug_dir = $this->pathToDebugDir();

		$css_debug_file = $debug_dir . "/{$filename}.css";
		file_put_contents( $css_debug_file, $css );

		$scss_debug_file = $debug_dir . "/{$filename}.scss";
		file_put_contents( $scss_debug_file, $scss );
	}


	/**
	 * Are the current theme's stylesheets SCSS compatible?
	 *
	 * @param int $version
	 *
	 * @return bool
	 */
	function isCurrentThemeCompatible( $version = 1, $theme = null ) {
		if ( $theme == null ) {
			$theme = wp_get_theme();
		}

		$types = array(
				'prince',
				'epub',
				'web'
		);

		foreach ( $types as $type ) {
			if ( $version == 1 && $type !== 'web' ) {
				$path = $theme->get_stylesheet_directory() . "/export/$type/style.scss";
			} elseif ( $version == 1 && $type == 'web' ) {
				$path = $theme->get_stylesheet_directory() . "/style.scss";
			}

			if ( $version == 2 ) {
				$path = $theme->get_stylesheet_directory() . "/assets/styles/$type/style.scss";
			}

			$fullpath = realpath( $path );
			if ( ! is_file( $fullpath ) ) {
				return false;
			}
		}

		return true;
	}


}
