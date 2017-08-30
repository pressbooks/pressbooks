<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */

namespace Pressbooks;

class Styles {

	/**
	 * @var Sass
	 */
	protected $sass;

	/**
	 * @param Sass $sass
	 */
	public function __construct( $sass ) {
		$this->sass = $sass;
	}

	/**
	 * @return Sass
	 */
	public function getSass() {
		return $this->sass;
	}

	/**
	 * Set filters & hooks for editor UI
	 */
	public function init() {

		if ( ! Book::isBook() ) {
			// Not a book, disable
			return;
		}
		if ( class_exists( '\Pressbooks\CustomCss' ) && CustomCss::isCustomCss() ) {
			// Uses old Custom CSS Editor, disable
			return;
		}

		add_action( 'admin_menu', function () {
			add_theme_page( __( 'Custom Styles', 'pressbooks' ), __( 'Custom Styles', 'pressbooks' ), 'edit_others_posts', 'pb_custom_styles', [ $this, 'editor' ] );
		}, 11 );
	}

	/**
	 *
	 */
	public function editor() {
		echo 'TODO';
		// require( PB_PLUGIN_DIR . 'templates/admin/custom-styles.php' );
	}

	/**
	 *
	 * Get stylesheet directory, applies filter: pb_stylesheet_directory
	 *
	 * @param \WP_Theme $theme (optional)
	 * @param bool $realpath (optional)
	 *
	 * @return string|false
	 */
	public function getDir( $theme = null, $realpath = false ) {

		if ( $theme ) {
			$dir = $theme->get_stylesheet_directory();
		} else {
			$dir = get_stylesheet_directory();
		}

		$basepath = apply_filters( 'pb_stylesheet_directory', $dir );

		if ( $realpath ) {
			$basepath = realpath( $basepath );
		}

		return $basepath;
	}

	/**
	 * Fullpath to SCSS file for Web
	 *
	 * @param \WP_Theme $theme (optional)
	 *
	 * @return false|string
	 */
	public function getPathToWebScss( $theme = null ) {
		return $this->getPathToScss( 'web', $theme );
	}

	/**
	 * Fullpath to SCSS file for Prince XML
	 *
	 * @param \WP_Theme $theme (optional)
	 *
	 * @return false|string
	 */
	public function getPathToPrinceScss( $theme = null ) {
		return $this->getPathToScss( 'prince', $theme );
	}

	/**
	 * Fullpath to SCSS file for Epub
	 *
	 * @param \WP_Theme $theme (optional)
	 *
	 * @return false|string
	 */
	public function getPathToEpubScss( $theme = null ) {
		return $this->getPathToScss( 'epub', $theme );
	}

	/**
	 * Fullpath to SCSS file
	 *
	 * @param string $type
	 * @param \WP_Theme $theme (optional)
	 *
	 * @return string|false
	 */
	public function getPathToScss( $type, $theme = null ) {

		if ( null === $theme ) {
			$theme = wp_get_theme();
		}

		if ( $this->isCurrentThemeCompatible( 1, $theme ) ) {
			if ( 'web' === $type ) {
				$path_to_style = realpath( $this->getDir( $theme ) . '/style.scss' );
			} else {
				$path_to_style = realpath( $this->getDir( $theme ) . "/export/$type/style.scss" );
			}
		} elseif ( $this->isCurrentThemeCompatible( 2, $theme ) ) {
			$path_to_style = realpath( $this->getDir( $theme ) . "/assets/styles/$type/style.scss" );
		} else {
			$path_to_style = false;
		}

		return $path_to_style;
	}

	/**
	 * Are the current theme's stylesheets SCSS compatible?
	 *
	 * @param int $version
	 * @param \WP_Theme $theme (optional)
	 *
	 * @return bool
	 */
	public  function isCurrentThemeCompatible( $version = 1, $theme = null ) {

		if ( null === $theme ) {
			$theme = wp_get_theme();
		}

		$basepath = $this->getDir( $theme );

		$types = [
			'prince',
			'epub',
			'web',
		];

		foreach ( $types as $type ) {
			$path = '';
			if ( 1 === $version && 'web' !== $type ) {
				$path = $basepath . "/export/$type/style.scss";
			} elseif ( 1 === $version && 'web' === $type ) {
				$path = $basepath . '/style.scss';
			}

			if ( 2 === $version ) {
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
	 * @param array|string $overrides (optional)
	 *
	 * @return string
	 */
	public function customizeWeb( $overrides = [] ) {
		$path = $this->getPathToWebScss();
		if ( $path ) {
			return $this->customize( 'web', file_get_contents( $path ), $overrides );
		}
		return '';
	}

	/**
	 * @param array|string $overrides (optional)
	 *
	 * @return string
	 */
	public function customizePrince( $overrides = [] ) {
		$path = $this->getPathToPrinceScss();
		if ( $path ) {
			return $this->customize( 'prince', file_get_contents( $path ), $overrides );
		}
		return '';
	}

	/**
	 * @param array|string $overrides (optional)
	 *
	 * @return string
	 */
	public function customizeEpub( $overrides = [] ) {
		$path = $this->getPathToEpubScss();
		if ( $path ) {
			return $this->customize( 'epub', file_get_contents( $path ), $overrides );
		}
		return '';
	}

	/**
	 * Transpile SCSS based on theme compatibility
	 *
	 * @param string $type
	 * @param string $scss
	 * @param array|string $overrides
	 *
	 * @return string
	 */
	public function customize( $type, $scss, $overrides = [] ) {

		$scss = $this->applyOverrides( $scss, $overrides );

		if ( $this->isCurrentThemeCompatible( 1 ) ) {
			$css = $this->sass->compile(
				$scss,
				[
					$this->sass->pathToUserGeneratedSass(),
					$this->sass->pathToPartials(),
					$this->sass->pathToFonts(),
					get_stylesheet_directory(),
				]
			);
		} elseif ( $this->isCurrentThemeCompatible( 2 ) ) {
			$css = $this->sass->compile(
				$scss,
				$this->sass->defaultIncludePaths( $type )
			);
		} else {
			$css = $this->injectHouseStyles( $scss );
		}

		return $css;
	}


	/**
	 * Prepend or append SCSS overrides depending on which version of the theme architecture is in use.
	 *
	 * @param string $scss
	 * @param array|string $overrides
	 *
	 * @return string
	 */
	public function applyOverrides( $scss, $overrides = [] ) {

		if ( ! is_array( $overrides ) ) {
			$overrides = (array) $overrides;
		}
		$overrides = implode( "\n", $overrides );

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
	 * Inject house styles into CSS
	 *
	 * @param string $css
	 *
	 * @return string
	 */
	public function injectHouseStyles( $css ) {

		$scan = [
			'/*__INSERT_PDF_HOUSE_STYLE__*/' => get_theme_root( 'pressbooks-book' ) . '/pressbooks-book/assets/legacy/styles/_pdf-house-style.scss',
			'/*__INSERT_EPUB_HOUSE_STYLE__*/' => get_theme_root( 'pressbooks-book' ) . '/pressbooks-book/assets/legacy/styles/_epub-house-style.scss',
			'/*__INSERT_MOBI_HOUSE_STYLE__*/' => get_theme_root( 'pressbooks-book' ) . '/pressbooks-book/assets/legacy/styles/_mobi-house-style.scss',
		];

		foreach ( $scan as $token => $replace_with ) {
			if ( is_file( $replace_with ) ) {
				$css = str_replace( $token, file_get_contents( $replace_with ), $css );
			}
		}

		return $css;
	}

	/**
	 * Update and save the supplementary webBook stylesheet which incorporates user options, etc.
	 *
	 * @return void
	 */
	function updateWebBookStyleSheet() {

		$overrides = apply_filters( 'pb_web_css_override', '' ) . "\n";

		// Populate $url-base variable so that links to images and other assets remain intact
		$scss = '$url-base: \'' . get_stylesheet_directory_uri() . "/';\n";

		if ( $this->isCurrentThemeCompatible( 1 ) ) {
			$scss .= file_get_contents( realpath( get_stylesheet_directory() . '/style.scss' ) );
			$css = Container::get( 'Styles' )->customize( 'web', $scss, $overrides );

		} elseif ( $this->isCurrentThemeCompatible( 2 ) ) {
			$scss .= file_get_contents( realpath( get_stylesheet_directory() . '/assets/styles/web/style.scss' ) );
			$css = Container::get( 'Styles' )->customize( 'web', $scss, $overrides );
		} else {
			return;
		}

		$css = \Pressbooks\Sanitize\normalize_css_urls( $css );

		$css_file = $this->sass->pathToUserGeneratedCss() . '/style.css';
		file_put_contents( $css_file, $css );
	}

	/**
	 * If the current theme's version has increased, call updateWebBookStyleSheet().
	 *
	 * @return bool
	 */
	public function maybeUpdateWebBookStylesheet() {
		$theme = wp_get_theme();
		$current_version = $theme->get( 'Version' );
		$last_version = get_option( 'pressbooks_theme_version', $current_version );

		if ( version_compare( $current_version, $last_version ) > 0 ) {
			$this->updateWebBookStyleSheet();
			update_option( 'pressbooks_theme_version', $current_version );
			return true;
		}

		return false;
	}

}
