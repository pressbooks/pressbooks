<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Modules\ThemeOptions;

use function \Pressbooks\Utility\getset;
use Pressbooks\Container;

class GlobalOptions extends \Pressbooks\Options {

	/**
	 * The value for option: pressbooks_theme_options_global_version
	 *
	 * @see upgrade()
	 * @var int
	 */
	const VERSION = 2;

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
	function __construct( array $options ) {
		$this->options = $options;
		$this->defaults = $this->getDefaults();
		$this->booleans = $this->getBooleanOptions();
		$this->strings = $this->getStringOptions();
		$this->integers = $this->getIntegerOptions();
		$this->floats = $this->getFloatOptions();
		$this->predefined = $this->getPredefinedOptions();

		foreach ( $this->defaults as $key => $value ) {
			if ( ! isset( $this->options[ $key ] ) ) {
				$this->options[ $key ] = $value;
			}
		}
	}

	/**
	 * Configure the global options tab using the settings API.
	 */
	function init() {
		$_option = 'pressbooks_theme_options_' . $this->getSlug();
		$_page = $_option;
		$_section = $this->getSlug() . '_options_section';

		if ( false === get_option( $_option ) ) {
			add_option( $_option, $this->defaults );
		}

		$custom_styles = Container::get( 'Styles' );
		$v2_compatible = $custom_styles->isCurrentThemeCompatible( 2 );

		add_settings_section(
			$_section,
			$this->getTitle(),
			[ $this, 'display' ],
			$_page
		);

		add_settings_field(
			'chapter_numbers',
			__( 'Part and Chapter Numbers', 'pressbooks' ),
			[ $this, 'renderChapterNumbersField' ],
			$_page,
			$_section,
			[
				__( 'Display part and chapter numbers', 'pressbooks' ),
			]
		);

		add_settings_field(
			'part_label',
			__( 'Part Label', 'pressbooks' ),
			[ $this, 'renderPartLabelField' ],
			$_page,
			$_section,
			[
				__( 'The "part" label is used in the table of contents and in part titles in your webbook and exports.', 'pressbooks' ),
			]
		);

		add_settings_field(
			'chapter_label',
			__( 'Chapter Label', 'pressbooks' ),
			[ $this, 'renderChapterLabelField' ],
			$_page,
			$_section,
			[
				__( 'The "chapter" label is used in the table of contents and in chapter titles in your webbook and exports.', 'pressbooks' ),
			]
		);

		add_settings_field(
			'parse_subsections',
			__( 'Two-Level TOC', 'pressbooks' ),
			[ $this, 'renderTwoLevelTOCField' ],
			$_page,
			$_section,
			[
				__( 'Enable two-level table of contents (displays headings under chapter titles)', 'pressbooks' ),
			]
		);

		add_settings_field(
			'attachment_attributions',
			__( 'Media Attributions', 'pressbooks' ),
			[ $this, 'renderAttachmentAttributionsField' ],
			$_page,
			$_section,
			[
				__( 'Display attributions at the end of a chapter', 'pressbooks' ),
			]
		);

		if ( Container::get( 'Styles' )->isCurrentThemeCompatible( 1 ) === true || Container::get( 'Styles' )->isCurrentThemeCompatible( 2 ) === true ) { // we can only enable foreign language typography for themes that use SCSS

			add_settings_field(
				'pressbooks_global_typography',
				__( 'Language & Script Support', 'pressbooks' ),
				[ $this, 'renderLanguagesField' ],
				$_page,
				$_section,
				[
					__( 'Include fonts to support the following languages and scripts:', 'pressbooks' ),
				]
			);

			register_setting(
				$_page,
				'pressbooks_global_typography',
				[ $this, 'sanitizeLanguages' ]
			);

		}

		add_settings_field(
			'copyright_license',
			__( 'Chapter Licenses', 'pressbooks' ),
			[ $this, 'renderCopyrightLicenseField' ],
			$_page,
			$_section,
			[
				0 => __( 'Do not display section level copyright license', 'pressbooks' ),
				1 => __( 'Display section level license on table of contents in export formats', 'pressbooks' ),
				2 => __( 'Display section level license at end of chapter in export formats', 'pressbooks' ),
			]
		);

		if ( $v2_compatible ) {
			foreach ( [
				'examples' => __( 'Examples', 'pressbooks' ),
				'exercises' => __( 'Exercises', 'pressbooks' ),
				'takeaways' => __( 'Key Takeaways', 'pressbooks' ),
				'objectives' => __( 'Learning Objectives', 'pressbooks' ),
			] as $key => $label ) {
				add_settings_field(
					"edu_textbox_{$key}_section",
					sprintf( '<h3>%s</h3>', $label ),
					[ $this, 'renderTextboxHeader' ],
					$_page,
					$_section,
					[
						sprintf( __( 'Customize colors for %s textboxes using the fields below.', 'pressbooks' ), $label ),
					]
				);

				add_settings_field(
					"edu_textbox_{$key}_header_color",
					sprintf( '<span class="screen-reader-text">%1$s </span>%2$s', $label, __( 'Header Color', 'pressbooks' ) ),
					[ $this, 'renderTextboxColorField' ],
					$_page,
					$_section,
					[
						'key' => "edu_textbox_{$key}_header_color",
						'description' => sprintf( __( 'The header text color for a %s textbox.', 'pressbooks' ), $label ),
					]
				);
				add_settings_field(
					"edu_textbox_{$key}_header_background",
					sprintf( '<span class="screen-reader-text">%1$s </span>%2$s', $label, __( 'Header Background', 'pressbooks' ) ),
					[ $this, 'renderTextboxColorField' ],
					$_page,
					$_section,
					[
						'key' => "edu_textbox_{$key}_header_background",
						'description' => sprintf( __( 'The header background color for a %s textbox.', 'pressbooks' ), $label ),
					]
				);
				add_settings_field(
					"edu_textbox_{$key}_background",
					sprintf( '<span class="screen-reader-text">%1$s </span>%2$s', $label, __( 'Background', 'pressbooks' ) ),
					[ $this, 'renderTextboxColorField' ],
					$_page,
					$_section,
					[
						'key' => "edu_textbox_{$key}_background",
						'description' => sprintf( __( 'The background color for a %s textbox.', 'pressbooks' ), $label ),
					]
				);
			}
		}

		/**
		 * Add custom settings fields.
		 *
		 * @since 3.9.7
		 *
		 * @param string $arg1
		 * @param string $arg2
		 */
		do_action( 'pb_theme_options_global_add_settings_fields', $_page, $_section );

		register_setting(
			$_page,
			$_option,
			[ $this, 'sanitize' ]
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
	function render() {
	}

	/**
	 * Sanitize the languages (just returns the array or an empty array, as these are predefined values).
	 *
	 * @param mixed $input
	 *
	 * @return array
	 */
	function sanitizeLanguages( $input ) {
		if ( ! is_array( $input ) ) {
			$input = [];
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
		$deprecated = [
			'pressbooks_enable_chapter_types',
		];

		foreach ( $options as $key => $value ) {
			if ( in_array( $key, $deprecated, true ) ) {
				unset( $options[ $key ] );
			}
		}

		// Change two-level TOC key to 'parse_subsections'.
		if ( isset( $options['parse_sections'] ) ) {
			$options['parse_subsections'] = $options['parse_sections'];
		}

		unset( $options['parse_sections'] );

		update_option( 'pressbooks_theme_options_' . $_option, $options );
	}

	/**
	 * Render the chapter_numbers checkbox.
	 *
	 * @param array $args
	 */
	function renderChapterNumbersField( $args ) {
		$this->renderCheckbox(
			[
				'id' => 'chapter_numbers',
				'name' => 'pressbooks_theme_options_' . $this->getSlug(),
				'option' => 'chapter_numbers',
				'value' => ( isset( $this->options['chapter_numbers'] ) ) ? $this->options['chapter_numbers'] : '',
				'label' => $args[0],
			]
		);
	}

	/**
	 * Render the parse_subsections checkbox.
	 *
	 * @param array $args
	 */
	function renderTwoLevelTOCField( $args ) {
		$this->renderCheckbox(
			[
				'id' => 'parse_subsections',
				'name' => 'pressbooks_theme_options_' . $this->getSlug(),
				'option' => 'parse_subsections',
				'value' => ( isset( $this->options['parse_subsections'] ) ) ? $this->options['parse_subsections'] : '',
				'label' => $args[0],
			]
		);
	}

	/**
	 * Render the attachment_attributions checkbox.
	 *
	 * @since 5.5.0
	 *
	 * @param array $args
	 */
	function renderAttachmentAttributionsField( $args ) {
		$this->renderCheckbox(
			[
				'id' => 'attachment_attributions',
				'name' => 'pressbooks_theme_options_' . $this->getSlug(),
				'option' => 'attachment_attributions',
				'value' => ( isset( $this->options['attachment_attributions'] ) ) ? $this->options['attachment_attributions'] : '',
				'label' => $args[0],
			]
		);
	}

	/**
	 * Render the pressbooks_global_typography select.
	 *
	 * @param array $args
	 */
	function renderLanguagesField( $args ) {
		$foreign_languages = get_option( 'pressbooks_global_typography' );

		if ( ! $foreign_languages ) {
			$foreign_languages = [];
		}

		$languages = Container::get( 'GlobalTypography' )->getSupportedLanguages();

		$already_supported_languages = Container::get( 'GlobalTypography' )->getThemeSupportedLanguages();

		if ( false === $already_supported_languages ) {
			$already_supported_languages = [];
		}

		$already_supported_languages_string = '';

		$i = 1;
		$c = count( $already_supported_languages );
		foreach ( $already_supported_languages as $lang ) {
			$already_supported_languages_string .= $languages[ $lang ];
			if ( $i < $c && $i === $c - 1 ) {
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
			$selected = ( in_array( $key, $foreign_languages, true ) || in_array( $key, $already_supported_languages, true ) ) ? ' selected' : '';
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
	 *
	 * @param array $args
	 */
	function renderCopyrightLicenseField( $args ) {
		$this->renderRadioButtons(
			[
				'id' => 'copyright_license',
				'name' => 'pressbooks_theme_options_' . $this->getSlug(),
				'option' => 'copyright_license',
				'value' => getset( $this->options, 'copyright_license', 0 ),
				'choices' => $args,
			]
		);
	}

	/**
	 * Render a textbox header.
	 *
	 * @param array $args
	 */
	function renderTextboxHeader( $args ) {
		printf( $args[0] );
	}

	/**
	 * Render the header color inputs.
	 *
	 * @param array $args
	 */
	function renderTextboxColorField( $args ) {
		$this->renderColorField(
			[
				'id' => $args['key'],
				'name' => 'pressbooks_theme_options_' . $this->getSlug(),
				'option' => $args['key'],
				'value' => ( isset( $this->options[ $args['key'] ] ) ) ? $this->options[ $args['key'] ] : $this->defaults[ $args['key'] ],
				'default' => $this->defaults[ $args['key'] ],
				'description' => $args['description'],
			]
		);
	}

	/**
	 * Render the part_label input.
	 *
	 * @param array $args
	 */
	function renderPartLabelField( $args ) {
		$this->renderField(
			[
				'id' => 'part_label',
				'name' => 'pressbooks_theme_options_' . $this->getSlug(),
				'option' => 'part_label',
				'value' => getset( $this->options, 'part_label' ),
				'description' => $args[0],
				'type' => 'text',
				'class' => 'regular-text',
			]
		);
	}

	/**
	 * Render the chapter_label input.
	 *
	 * @param array $args
	 */
	function renderChapterLabelField( $args ) {
		$this->renderField(
			[
				'id' => 'chapter_label',
				'name' => 'pressbooks_theme_options_' . $this->getSlug(),
				'option' => 'chapter_label',
				'value' => getset( $this->options, 'chapter_label' ),
				'description' => $args[0],
				'type' => 'text',
				'class' => 'regular-text',
			]
		);
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
		return __( 'Global Options', 'pressbooks' );
	}

	/**
	 * Get an array of default values for the global options tab.
	 *
	 * @return array $defaults
	 */
	static function getDefaults() {
		/**
		 * @since 3.9.7
		 *
		 * @param array $value
		 */
		return apply_filters(
			'pb_theme_options_global_defaults', [
				'chapter_numbers' => 1,
				'parse_subsections' => 0,
				'part_label' => __( 'Part', 'pressbooks' ),
				'chapter_label' => __( 'Chapter', 'pressbooks' ),
				'attachment_attributions' => 0,
				'copyright_license' => 0,
				'edu_textbox_examples_header_color' => '#fff',
				'edu_textbox_examples_header_background' => '#7a333a',
				'edu_textbox_examples_background' => '#f3e1e3',
				'edu_textbox_exercises_header_color' => '#fff',
				'edu_textbox_exercises_header_background' => '#0b6396',
				'edu_textbox_exercises_background' => '#e3eff6',
				'edu_textbox_objectives_header_color' => '#fff',
				'edu_textbox_objectives_header_background' => '#111',
				'edu_textbox_objectives_background' => '#f7f7f9',
				'edu_textbox_takeaways_header_color' => '#fff',
				'edu_textbox_takeaways_header_background' => '#3a7a33',
				'edu_textbox_takeaways_background' => '#eaf5ea',
			]
		);
	}

	/**
	 * Filter the array of default values for the Global options tab.
	 *
	 * @param array $defaults
	 *
	 * @return array $defaults
	 */
	static function filterDefaults( $defaults ) {

		// SASS => WP
		$overrides = [
			'examples-header-color' => 'edu_textbox_examples_header_color',
			'examples-header-background' => 'edu_textbox_examples_header_background',
			'examples-background' => 'edu_textbox_examples_background',
			'exercises-header-color' => 'edu_textbox_exercises_header_color',
			'exercises-header-background' => 'edu_textbox_exercises_header_background',
			'exercises-background' => 'edu_textbox_exercises_background',
			'learning-objectives-header-color' => 'edu_textbox_objectives_header_color',
			'learning-objectives-header-background' => 'edu_textbox_objectives_header_background',
			'learning-objectives-background' => 'edu_textbox_objectives_background',
			'key-takeaways-header-color' => 'edu_textbox_takeaways_header_color',
			'key-takeaways-header-background' => 'edu_textbox_takeaways_header_background',
			'key-takeaways-background' => 'edu_textbox_takeaways_background',
		];

		$transient_name = 'pressbooks_theme_options_global_parsed_sass_variables';
		$parsed_sass_variables = get_transient( $transient_name );
		if ( $parsed_sass_variables === false ) {
			// Order of files matter. If a variable is duplicated in other files then the last one takes precedence
			$parsed_sass_variables = [];
			$sass = Container::get( 'Sass' );
			$path_to_global = $sass->pathToGlobals();
			$path_to_theme = get_stylesheet_directory();
			$files = [
				$path_to_global . '/variables/_specials.scss',
				$path_to_theme . '/assets/styles/components/_specials.scss',
			];
			foreach ( $files as $file ) {
				if ( file_exists( $file ) ) {
					$parsed_sass_variables[] = $sass->parseVariables( \Pressbooks\Utility\get_contents( $file ) );
				}
			}
			set_transient( $transient_name, $parsed_sass_variables );
		}

		foreach ( $parsed_sass_variables as $parsed_variables ) {
			foreach ( $overrides as $sass_var => $wp_option ) {
				if ( isset( $parsed_variables[ $sass_var ] ) ) {
					if ( ! empty( $parsed_variables[ $sass_var ] ) ) {
						$defaults[ $wp_option ] = $parsed_variables[ $sass_var ]; // Override default with new value
					}
				}
			}
		}

		return $defaults;
	}

	/**
	 * Get an array of options which return booleans.
	 *
	 * @return array $options
	 */
	static function getBooleanOptions() {
		/**
		 * Allow custom boolean options to be passed to sanitization routines.
		 *
		 * @since 3.9.7
		 *
		 * @param array $value
		 */
		return apply_filters(
			'pb_theme_options_global_booleans', [
				'chapter_numbers',
				'parse_subsections',
				'attachment_attributions',
			]
		);
	}

	/**
	 * Get an array of options which return strings.
	 *
	 * @return array $options
	 */
	static function getStringOptions() {
		/**
		 * Allow custom string options to be passed to sanitization routines.
		 *
		 * @since 3.9.7
		 *
		 * @param array $value
		 */
		return apply_filters(
			'pb_theme_options_global_strings', [
				'edu_textbox_examples_header_color',
				'edu_textbox_examples_header_background',
				'edu_textbox_examples_background',
				'edu_textbox_exercises_header_color',
				'edu_textbox_exercises_header_background',
				'edu_textbox_exercises_background',
				'edu_textbox_objectives_header_color',
				'edu_textbox_objectives_header_background',
				'edu_textbox_objectives_background',
				'edu_textbox_takeaways_header_color',
				'edu_textbox_takeaways_header_background',
				'edu_textbox_takeaways_background',
				'part_label',
				'chapter_label',
			]
		);
	}

	/**
	 * Get an array of options which return integers.
	 *
	 * @return array $options
	 */
	static function getIntegerOptions() {
		/**
		 * Allow custom integer options to be passed to sanitization routines.
		 *
		 * @since 3.9.7
		 *
		 * @param array $value
		 */
		return apply_filters(
			'pb_theme_options_global_integers', [
				'copyright_license',
			]
		);
	}

	/**
	 * Get an array of options which return floats.
	 *
	 * @return array $options
	 */
	static function getFloatOptions() {
		/**
		 * Allow custom float options to be passed to sanitization routines.
		 *
		 * @since 3.9.7
		 *
		 * @param array $value
		 */
		return apply_filters( 'pb_theme_options_global_floats', [] );
	}

	/**
	 * Get an array of options which return predefined values.
	 *
	 * @return array $options
	 */
	static function getPredefinedOptions() {
		/**
		 * Allow custom predifined options to be passed to sanitization routines.
		 *
		 * @since 3.9.7
		 *
		 * @param array $value
		 */
		return apply_filters( 'pb_theme_options_global_predefined', [] );
	}
}
