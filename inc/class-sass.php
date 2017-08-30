<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */

namespace Pressbooks;

use function Pressbooks\Sanitize\normalize_css_urls;

class Sass {

	/**
	 * Email addresses to send log errors.
	 *
	 * @var array
	 */
	public $errorsEmail = [
		'errors@pressbooks.com',
	];


	/**
	 * Get default include paths
	 *
	 * @param string $type
	 * @param string $theme
	 *
	 * @return array
	 */
	function defaultIncludePaths( $type, $theme = null ) {

		if ( null === $theme ) {
			$theme = wp_get_theme();
		}

		return [
			$this->pathToUserGeneratedSass(),
			$this->pathToGlobals(),
			$this->pathToFonts(),
			Container::get( 'Styles' )->getDir( $theme ) . "/assets/styles/$type/",
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
	 * Get the path to legacy book theme partials.
	 *
	 * @return string
	 */
	function pathToPartials() {
		return get_theme_root( 'pressbooks-book' ) . '/pressbooks-book/assets/legacy/styles/';
	}

	/**
	 * Get the path to our PB Partials
	 *
	 * @return string
	 */
	function pathToGlobals() {
		return get_theme_root( 'pressbooks-book' ) . '/pressbooks-book/assets/book/styles/';
	}


	/**
	 * Get the path to our PB Fonts
	 *
	 * @return string
	 */
	function pathToFonts() {
		return get_theme_root( 'pressbooks-book' ) . '/pressbooks-book/assets/book/typography/styles/';
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
	 *
	 * @return string
	 */
	function compile( $scss, $includes = [] ) {

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
				__( 'There was a problem with SASS. Contact your site administrator. Error: %1$s %2$s', 'pressbooks' ),
				$e->getMessage(),
				'<pre>' . print_r( $sass->getParsedFiles(), true ) . '</pre>'
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
	 *
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
	 *
	 * @return array
	 */
	function parseVariables( $scss ) {

		preg_match_all( '/\$(.*?):(.*?);/', $scss, $matches );
		$output = array_combine( $matches[1], $matches[2] );
		$output = array_map(
			function ( $val ) {
				return ltrim( str_replace( ' !default', '', $val ) );
			}, $output
		);
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

		$info = [
			'time' => strftime( '%c' ),
			'user' => ( isset( $current_user ) ? $current_user->user_login : '__UNKNOWN__' ),
			'site_url' => site_url(),
			'blog_id' => get_current_blog_id(),
			'Exception' => [
				'code' => $e->getCode(),
				'error' => $e->getMessage(),
				'file' => $e->getFile(),
				'line' => $e->getLine(),
				'trace' => $e->getTraceAsString(),
			],
		];

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
	 * @param string $scss
	 * @param string $filename
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
	 * @deprecated Use the same function found in Styles instead
	 *
	 * @param int $version
	 * @param \WP_Theme $theme
	 *
	 * @return bool
	 */
	function isCurrentThemeCompatible( $version = 1, $theme = null ) {
		return Container::get( 'Styles' )->isCurrentThemeCompatible( $version, $theme );
	}

	/**
	 * Update and save the supplementary webBook stylesheet which incorporates user options, etc.
	 *
	 * @deprecated Use the same function found in Styles instead
	 *
	 * @return void
	 */
	function updateWebBookStyleSheet() {
		Container::get( 'Styles' )->updateWebBookStyleSheet();
	}
}
