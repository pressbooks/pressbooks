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

		if ( null == $theme ) {
			$theme = wp_get_theme();
		}

		return [
			$this->pathToUserGeneratedSass(),
			$this->pathToGlobals(),
			$this->pathToFonts(),
			apply_filters( 'pb_stylesheet_directory', $theme->get_stylesheet_directory() ) . "/assets/styles/$type/",
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

			if ( '' !== $scss ) {
				$sass = new \Leafo\ScssPhp\Compiler;
				$sass->setImportPaths( $includes );
				$css = $sass->compile( $scss );
			}
		} catch ( \Exception $e ) {

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
			$this->debug( '/* Silence is golden. */', $localizations, 'localizations' );
		}

		return $localizations . $scss;
	}

	/**
	 * Parse an SCSS file into an array of variables.
	 *
	 * @param string $scss
	 * @return array
	 */
	function parseVariables( $scss ) {

		preg_match_all( '/\$(.*?):(.*?);/', $scss, $matches );
		$output = array_combine( $matches[1], $matches[2] );
		$output = array_map( function( $val ) {
			return ltrim( str_replace( ' !default', '', $val ) );
		}, $output );
		return $output;
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

		if ( null == $theme ) {
			$theme = wp_get_theme();
		}

		$basepath = apply_filters( 'pb_stylesheet_directory', $theme->get_stylesheet_directory() );

		$types = array(
				'prince',
				'epub',
				'web',
		);

		foreach ( $types as $type ) {
			if ( 1 == $version && 'web' !== $type ) {
				$path = $basepath . "/export/$type/style.scss";
			} elseif ( 1 == $version && 'web' == $type ) {
				$path = $basepath . '/style.scss';
			}

			if ( 2 == $version ) {
				$path = $basepath . "/assets/styles/$type/style.scss";
			}

			$fullpath = realpath( $path );
			if ( ! is_file( $fullpath ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Prepend or append SCSS overrides depending on which version of the theme architecture is in use.
	 *
	 * @param string $scss The theme SCSS.
	 * @param string $overrides The SCSS overrides.
	 *
	 * @return string
	 */
	function applyOverrides( $scss, $overrides ) {

		if ( $this->isCurrentThemeCompatible( 2 ) ) {
			// Prepend override variables (see: http://sass-lang.com/documentation/file.SASS_REFERENCE.html#variable_defaults_).
			$scss = $overrides . "\n" . $scss;
		} else {
			// Append overrides.
			$scss .= "\n" . $overrides;
		}

		return $scss;
	}

	/**
	 * Update and save the supplementary webBook stylesheet which incorporates user options, etc.
	 *
	 * @return void
	 */
	function updateWebBookStyleSheet() {

		$overrides = apply_filters( 'pb_web_css_override', '' ) . "\n";

		if ( $this->isCurrentThemeCompatible( 1 ) ) {
			$path_to_style = realpath( get_stylesheet_directory() . '/style.scss' );
			// Populate $url-base variable so that links to images and other assets remain intact
			$scss = '$url-base: \'' . get_stylesheet_directory_uri() . "/';\n";

			$scss .= $this->applyOverrides( file_get_contents( $path_to_style ), $overrides );

			$scss .= "\n";
			$css = $this->compile( $scss, [
				$this->pathToUserGeneratedSass(),
				$this->pathToPartials(),
				$this->pathToFonts(),
				get_stylesheet_directory(),
			] );

		} elseif ( $this->isCurrentThemeCompatible( 2 ) ) {
			$path_to_style = realpath( get_stylesheet_directory() . '/assets/styles/web/style.scss' );

			// Populate $url-base variable so that links to images and other assets remain intact
			$scss = '$url-base: \'' . get_stylesheet_directory_uri() . "/';\n";

			$scss .= $this->applyOverrides( file_get_contents( $path_to_style ), $overrides );
			$css = $this->compile( $scss, $this->defaultIncludePaths( 'web' ) );
		} else {
			return;
		}

		$css = $this->fixWebFonts( $css );

		$css_file = $this->pathToUserGeneratedCss() . '/style.css';
		file_put_contents( $css_file, $css );
	}

	/**
	 * Fix relative/ambiguous URLs to web fonts
	 *
	 * @param $css
	 * @return mixed
	 */
	function fixWebFonts( $css ) {

		// Search for url("*"), url('*'), and url(*)
		$url_regex = '/url\(([\s])?([\"|\'])?(.*?)([\"|\'])?([\s])?\)/i';
		$css = preg_replace_callback( $url_regex, function ( $matches ) {

			$url = $matches[3];
			$filename = sanitize_file_name( basename( $url ) );

			// Look for themes-book/pressbooks-book/fonts/*.otf (or .woff, or .ttf), update URL
			if ( preg_match( '#^themes-book/pressbooks-book/fonts/[a-zA-Z0-9_-]+(\.woff|\.otf|\.ttf)$#i', $url ) ) {
				return 'url(' . PB_PLUGIN_URL . $url . ')';
			}

			// Look for uploads/assets/fonts/*.otf (or .woff, or .ttf), update URL
			if ( preg_match( '#^uploads/assets/fonts/[a-zA-Z0-9_-]+(\.woff|\.otf|\.ttf)$#i', $url ) ) {
				return 'url(' . WP_CONTENT_URL . '/' . $url . ')';
			}

			return $matches[0]; // No change

		}, $css );

		return $css;
	}

	/**
	 * If the current theme's version has increased, call updateWebBookStyleSheet().
	 *
	 * @return bool
	 */
	static function maybeUpdateWebBookStylesheet() {
		$theme = wp_get_theme();
		$current_version = $theme->get( 'Version' );
		$last_version = get_option( 'pressbooks_theme_version', $current_version );

		if ( version_compare( $current_version, $last_version ) > 0 ) {
			\Pressbooks\Container::get( 'Sass' )->updateWebBookStyleSheet();
			update_option( 'pressbooks_theme_version', $current_version );
			return true;
		}

		return false;
	}
}
