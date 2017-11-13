<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */

namespace Pressbooks;

/**
 * SCSS Compiler and Build Tools
 */
class Sass {

	/**
	 * Email addresses to send log errors.
	 *
	 * @var array
	 */
	public $errorsEmail = [];

	/**
	 * @var array
	 */
	protected $vars = [];

	/**
	 * @param array $vars
	 */
	public function setVariables( array $vars ) {
		$this->vars = array_merge( $this->vars, $vars );
	}

	/**
	 * Get default include paths
	 *
	 * @param string $type
	 * @param string $theme
	 *
	 * @return array
	 */
	public function defaultIncludePaths( $type, $theme = null ) {

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
	public function getStringsToLocalize() {

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
	public function pathToPartials() {
		return get_theme_root( 'pressbooks-book' ) . '/pressbooks-book/assets/legacy/styles/';
	}

	/**
	 * Get the path to our PB Partials
	 *
	 * @return string
	 */
	public function pathToGlobals() {
		/**
		 * Filter the path to global book theme components.
		 *
		 * @since 4.4.0
		 */
		return apply_filters( 'pb_global_components_path', get_theme_root( 'pressbooks-book' ) . '/pressbooks-book/assets/book/styles/' );
	}


	/**
	 * Get the path to our PB Fonts
	 *
	 * @return string
	 */
	public function pathToFonts() {
		return get_theme_root( 'pressbooks-book' ) . '/pressbooks-book/assets/book/typography/styles/';
	}


	/**
	 * Get path to a directory we can dump user transpiled CSS files into (create dir if it does not exist)
	 *
	 * @return string
	 */
	public function pathToUserGeneratedCss() {

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
	public function urlToUserGeneratedCss() {

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
	public function pathToUserGeneratedSass() {

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
	public function pathToDebugDir() {

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
	public function compile( $scss, $includes = [] ) {

		$scss = $this->prependLocalizedVars( $scss );

		try {
			$css = '/* Silence is golden. */'; // If no SCSS input was passed, prevent file write errors by putting a comment in the CSS output.
			$sass = new \Leafo\ScssPhp\Compiler;
			if ( ! empty( $scss ) || ! empty( $this->vars ) ) {
				$sass->setVariables( $this->vars );
				$sass->setImportPaths( $includes );
				$css = $sass->compile( $scss );
				$this->vars = []; // Reset
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
	public function prependLocalizedVars( $scss ) {
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
	public function parseVariables( $scss ) {

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
	public function debug( $css, $scss, $filename ) {

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
	public function isCurrentThemeCompatible( $version = 1, $theme = null ) {
		return Container::get( 'Styles' )->isCurrentThemeCompatible( $version, $theme );
	}

	/**
	 * Update and save the supplementary webBook stylesheet which incorporates user options, etc.
	 *
	 * @deprecated Use the same function found in Styles instead
	 *
	 * @return void
	 */
	public function updateWebBookStyleSheet() {
		Container::get( 'Styles' )->updateWebBookStyleSheet();
	}
}
