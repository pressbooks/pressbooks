<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */
namespace Pressbooks\Modules\ThemeOptions;

use Pressbooks\Container;

class GlobalOptions extends \Pressbooks\Options {

	/**
	 * The value for option: pressbooks_theme_options_global_version
	 *
	 * @see upgrade()
	 * @var int
	 */
	static $currentVersion = 1;

	/**
   * Global theme options.
   *
   * @var array
   */
	public $options;

	/**
   * Global theme defaults.
   *
   * @var array
   */
	public $defaults;

	/**
   * Constructor.
   *
   * @param array $options
   */
	function __construct(array $options) {
 		$this->options = $options;
		$this->defaults = $this->getDefaults();
		$this->booleans = $this->getBooleanOptions();
		$this->strings = $this->getStringOptions();
		$this->integers = $this->getIntegerOptions();
		$this->floats = $this->getFloatOptions();
		$this->predefined = $this->getPredefinedOptions();

 		foreach ( $this->defaults as $key => $value ) {
 			if ( !isset ( $this->options[ $key ] ) ) {
 				$this->options[ $key ] = $value;
 			}
 		}
 	}

	/**
	 * Configure the global options tab using the settings API.
	 */
	function init() {
		$_page = $_option = 'pressbooks_theme_options_' . $this->getSlug();
		$_section = $this->getSlug() . '_options_section';

		if ( false == get_option( $_option ) ) {
			add_option( $_option, $this->defaults );
		}

		add_settings_section(
			$_section,
			$this->getTitle(),
			array( $this, 'display' ),
			$_page
		);

		add_settings_field(
			'chapter_numbers',
			__( 'Chapter Numbers', 'pressbooks' ),
			array( $this, 'renderChapterNumbersField' ),
			$_page,
			$_section,
			array(
				 __( 'Display chapter numbers', 'pressbooks' )
			)
		);

		add_settings_field(
			'parse_subsections',
			__( 'Two-Level TOC', 'pressbooks' ),
			array( $this, 'renderTwoLevelTOCField' ),
			$_page,
			$_section,
			array(
				 __( 'Enable two-level table of contents (displays headings under chapter titles)', 'pressbooks' )
			)
		);

		if ( \Pressbooks\Container::get('Sass')->isCurrentThemeCompatible( 1 ) == true || \Pressbooks\Container::get('Sass')->isCurrentThemeCompatible( 2 ) == true ) { // we can only enable foreign language typography for themes that use SCSS

			add_settings_field(
				'pressbooks_global_typography',
				__( 'Language Support', 'pressbooks' ),
				array( $this, 'renderLanguagesField' ),
				$_page,
				$_section,
				array(
					 __( 'Include fonts to support the following languages:', 'pressbooks' )
				)
			);

			register_setting(
				$_page,
				'pressbooks_global_typography',
				array( $this, 'sanitizeLanguages' )
			);

		}

		add_settings_field(
			'copyright_license',
			__( 'Copyright License', 'pressbooks' ),
			array( $this, 'renderCopyrightLicenseField' ),
			$_page,
			$_section,
			array(
				 __( 'Display the selected copyright license', 'pressbooks' )
			)
		);

		register_setting(
			$_page,
			$_option,
			array( $this, 'sanitize' )
		);
	}

	/**
	 * Display the global options tab description.
	 */
	function display() {
		echo '<p>' . __( 'These options apply universally to webbook, PDF and ebook exports.', 'pressbooks' ) . '</p>';
	}

	/**
	 * Render the PDF options tab form (NOT USED).
	 */
	function render() {}

	/**
	 * Sanitize the languages (just returns the array or an empty array, as these are predefined values).
	 */
	function sanitizeLanguages( $input ) {
		if ( !is_array( $input ) ) {
			$input = array();
		}
		return $input;
	}

	/**
	 * Upgrade handler for global options.
	 *
	 * @param int $version
	 */
	function upgrade( $version ) {
		if ( $version < 1 ) {
			$this->doInitialUpgrade();
		}
	}

	/**
	 * Remove deprecated keys from global options, clarify two-level TOC key name.
	 */
	function doInitialUpgrade() {
		$_option = $this->getSlug();
		$options = get_option( 'pressbooks_theme_options_' . $_option, $this->defaults );
		$deprecated = array(
			'pressbooks_enable_chapter_types',
		);

		foreach ( $options as $key => $value ) {
			if ( in_array( $key, $deprecated ) ) {
				unset( $options[ $key ] );
			}
		}

		// Change two-level TOC key to 'parse_subsections'.
		$options['parse_subsections'] = $options['parse_sections'];
		unset($options['parse_sections']);

		update_option( 'pressbooks_theme_options_' . $_option, $options );
	}

	/**
	 * Render the chapter_numbers checkbox.
	 * @param array $args
	 */
	function renderChapterNumbersField( $args ) {
		$this->renderCheckbox( 'chapter_numbers', 'pressbooks_theme_options_' . $this->getSlug(), 'chapter_numbers', $this->options['chapter_numbers'], $args[0] );
	}

	/**
	 * Render the parse_subsections checkbox.
	 * @param array $args
	 */
	function renderTwoLevelTOCField( $args ) {
		$this->renderCheckbox( 'parse_subsections', 'pressbooks_theme_options_' . $this->getSlug(), 'parse_subsections', $this->options['parse_subsections'], $args[0] );
	}

	/**
	 * Render the pressbooks_global_typography select.
	 * @param array $args
	 */
	function renderLanguagesField( $args ) {
		$foreign_languages = get_option( 'pressbooks_global_typography' );

		if ( ! $foreign_languages ) {
			$foreign_languages = array();
		}

		$languages = \Pressbooks\Container::get( 'GlobalTypography' )->getSupportedLanguages();

		$already_supported_languages = \Pressbooks\Container::get( 'GlobalTypography' )->getThemeSupportedLanguages();

		if ( $already_supported_languages == false ) {
			$already_supported_languages = [];
		}

		$already_supported_languages_string = '';

		$i = 1;
		$c = count( $already_supported_languages );
		foreach ( $already_supported_languages as $lang ) {
			$already_supported_languages_string .= $languages[ $lang ];
			if ( $i < $c && $i == $c - 1 ) {
				$already_supported_languages_string .= ' ' . __( 'and', 'pressbooks' ) . ' ';
			} elseif ( $i < $c ) {
				$already_supported_languages_string .= ', ';
			}
			unset( $languages[ $lang ] );
			$i++;
		}

		$html = '<label for="global_typography"> ' . $args[0] . '</label><br /><br />';
		$html .= '<select id="global_typography" class="select2" style="width: 75%" data-placeholder="' . __( 'Select languages&hellip;', 'pressbooks' ) . '" name="pressbooks_global_typography[]" multiple>';
		foreach ( $languages as $key => $value ) {
			$selected = ( in_array( $key, $foreign_languages ) || in_array( $key, $already_supported_languages ) ) ? ' selected' : '';
			$html .= '<option value="' . $key . '" ' . $selected . '>' . $value . '</option>';
		}
		$html .= '</select>';

		if ( $already_supported_languages_string ) {
			$html .= '<br /><br />' . sprintf( __( 'This theme includes built-in support for %s.', 'pressbooks' ), $already_supported_languages_string );
		}

		echo $html;
	}

	/**
	 * Render the copyright_license checkbox.
	 * @param array $args
	 */
	function renderCopyrightLicenseField( $args ) {
		$this->renderCheckbox( 'copyright_license', 'pressbooks_theme_options_' . $this->getSlug(), 'copyright_license', $this->options['copyright_license'], $args[0] );
	}

	/**
	 * Get the slug for the global options tab.
	 *
	 * @return string $slug
	 */
	static function getSlug() {
  	return 'global';
  }

	/**
	 * Get the localized title of the global options tab.
	 *
	 * @return string $title
	 */
  static function getTitle() {
  	return __('Global Options', 'pressbooks');
  }

	/**
	 * Get an array of default values for the global options tab.
	 *
	 * @return array $defaults
	 */
	static function getDefaults() {
		return array(
			'chapter_numbers' => 1,
			'parse_subsections' => 0,
			'copyright_license' => 0
		);
	}

	/**
	 * Get an array of options which return booleans.
	 *
	 * @return array $options
	 */
	static function getBooleanOptions() {
		return array(
			'chapter_numbers',
			'parse_subsections',
			'copyright_license'
		);
	}

	/**
	 * Get an array of options which return strings.
	 *
	 * @return array $options
	 */
	static function getStringOptions() {
		return array();
	}

	/**
	 * Get an array of options which return integers.
	 *
	 * @return array $options
	 */
	static function getIntegerOptions() {
		return array();
	}

	/**
	 * Get an array of options which return floats.
	 *
	 * @return array $options
	 */
	static function getFloatOptions() {
		return array();
	}

	/**
	 * Get an array of options which return predefined values.
	 *
	 * @return array $options
	 */
	static function getPredefinedOptions() {
		return array();
	}
}
