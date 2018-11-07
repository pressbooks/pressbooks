<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks;

use function Pressbooks\Utility\str_starts_with;

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
			/**
			 * Filter the label used for post types (front matter/parts/chapters/back matter) in the TOC and section headings.
			 *
			 * @since 5.6.0
			 *
			 * @param string $label
			 * @param array $args
			 *
			 * @return string Filtered label
			 */
			'chapter' => apply_filters( 'pb_post_type_label', __( 'Chapter', 'pressbooks' ), [ 'post_type' => 'chapter' ] ),
			/**
			 * Filter the label used for post types (front matter/parts/chapters/back matter) in the TOC and section headings.
			 *
			 * @since 5.6.0
			 *
			 * @param string $label
			 * @param array $args
			 *
			 * @return string Filtered label
			 */
			'part' => apply_filters( 'pb_post_type_label', __( 'Part', 'pressbooks' ), [ 'post_type' => 'part' ] ),
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
		return apply_filters( 'pb_global_components_path', get_theme_root( 'pressbooks-book' ) . '/pressbooks-book/packages/buckram/assets/styles/' );
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
		return \Pressbooks\Utility\get_generated_content_path( '/css' );
	}


	/**
	 * Get URI to user transpiled CSS files
	 *
	 * @return string
	 */
	public function urlToUserGeneratedCss() {
		return \Pressbooks\Utility\get_generated_content_url( '/css' );
	}


	/**
	 * Get path to a directory we can dump user generated Sass files into (create dir if it does not exist)
	 *
	 * @return string
	 */
	public function pathToUserGeneratedSass() {
		return \Pressbooks\Utility\get_generated_content_path( '/scss' );
	}


	/**
	 * Get path to a directory we can dump debug files into
	 *
	 * @return string
	 */
	public function pathToDebugDir() {
		return \Pressbooks\Utility\get_generated_content_path( '/scss-debug' );
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
			$scssphp = new \Leafo\ScssPhp\Compiler;
			if ( ! empty( $scss ) || ! empty( $this->vars ) ) {
				$scssphp->setVariables( $this->vars );
				$scssphp->setImportPaths( $includes );
				$css = $scssphp->compile( $scss );
				$this->vars = []; // Reset
			}
		} catch ( \Exception $e ) {

			$error_message = print_r( $scssphp->getParsedFiles(), true ); // @codingStandardsIgnoreLine
			$_SESSION['pb_errors'][] = sprintf(
				__( 'There was a problem with SASS. Contact your site administrator. Error: %1$s %2$s', 'pressbooks' ),
				$e->getMessage(),
				"<pre>{$error_message}</pre>"
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
		$output = [];
		$parser = new \Leafo\ScssPhp\Parser( null );
		$tree = $parser->parse( $scss );
		foreach ( $tree->children as $item ) {
			if ( $item[0] === \Leafo\ScssPhp\Type::T_ASSIGN && $item[1][0] === \Leafo\ScssPhp\Type::T_VARIABLE && ! str_starts_with( $item[1][1], '_' ) ) {
				$key = $item[1][1];
				switch ( $item[2][0] ) {
					case \Leafo\ScssPhp\Type::T_VARIABLE:
						$val = '$' . $item[2][1];
						break;
					case \Leafo\ScssPhp\Type::T_FUNCTION_CALL:
						$fncall = $item[2][1];
						$fncall_params = '';
						foreach ( $item[2][2] as $param ) {
							$fncall_params .= $param[1][1] . ', ';
						}
						$fncall_params = rtrim( $fncall_params, ', ' );
						$val = "{$fncall}({$fncall_params})";
						break;
					default:
						$val = @( new \Leafo\ScssPhp\Compiler() )->compileValue( $item[2] ); // @codingStandardsIgnoreLine
				}
				$output[ $key ] = $val;
			}
		}
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

		$message = print_r( array_merge( $info ), true ); // @codingStandardsIgnoreLine

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
	 */
	public function debug( $css, $scss, $filename ) {

		$debug_dir = $this->pathToDebugDir();

		$css_debug_file = $debug_dir . "/{$filename}.css";
		\Pressbooks\Utility\put_contents( $css_debug_file, $css );

		$scss_debug_file = $debug_dir . "/{$filename}.scss";
		\Pressbooks\Utility\put_contents( $scss_debug_file, $scss );
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
