<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */
// TODO: Security audit
// @phpcs:disable Pressbooks.Security.EscapeOutput.OutputNotEscaped

namespace Pressbooks\Modules\ThemeOptions;

use Pressbooks\Container;
use Pressbooks\Metadata;

class WebOptions extends \Pressbooks\Options {

	/**
	 * The value for option: pressbooks_theme_options_web_version
	 *
	 * @see upgrade()
	 * @var int
	 */
	const VERSION = 1;

	/**
	 * Web theme options.
	 *
	 * @var array
	 */
	public $options;

	/**
	 * Web theme defaults.
	 *
	 * @var array
	 */
	public $defaults;

	/**
	 * Web theme booleans.
	 *
	 * @var array
	 */
	public $booleans;

	/**
	 * Web theme strings.
	 *
	 * @var array
	 */
	public $strings;

	/**
	 * Web theme integers.
	 *
	 * @var array
	 */
	public $integers;

	/**
	 * Web theme floats.
	 *
	 * @var array
	 */
	public $floats;

	/**
	 * Web theme predefined options.
	 *
	 * @var array
	 */
	public $predefined;

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
	 * Configure the web options tab using the settings API.
	 */
	function init() {
		$_option = 'pressbooks_theme_options_' . $this->getSlug();
		$_page = $_option;
		$_section = $this->getSlug() . '_options_section';
		$meta = new Metadata();

		if ( false === get_option( $_option ) ) {
			add_option( $_option, $this->defaults );
		}

		add_settings_section(
			$_section,
			$this->getTitle(),
			[ $this, 'display' ],
			$_page
		);

		$styles = \Pressbooks\Container::get( 'Styles' );
		$shape_shifter_compatible = $styles->isShapeShifterCompatible();

		if ( $shape_shifter_compatible ) {
			add_settings_field(
				'webbook_header_font',
				__( 'Header Font', 'pressbooks' ),
				[ $this, 'renderHeaderFontField' ],
				$_page,
				$_section,
				array_merge( $styles->getShapeShifterFonts(), [ 'label_for' => 'webbook_header_font' ] )
			);
			add_settings_field(
				'webbook_body_font',
				__( 'Body Font', 'pressbooks' ),
				[ $this, 'renderBodyFontField' ],
				$_page,
				$_section,
				array_merge( $styles->getShapeShifterFonts(), [ 'label_for' => 'webbook_body_font' ] )
			);
		}

		add_settings_field(
			'social_media',
			__( 'Enable Social Media', 'pressbooks' ),
			[ $this, 'renderSocialMediaField' ],
			$_page,
			$_section,
			[
				__( 'Adds a button to cover page and each chapter which allows readers to share links to your book through Twitter', 'pressbooks' ),
			]
		);

		add_settings_field(
			'webbook_width',
			__( 'Webbook Width', 'pressbooks' ),
			[ $this, 'renderWebbookWidthField' ],
			$_page,
			$_section,
			[
				'30em' => __( 'Narrow', 'pressbooks' ),
				'40em' => __( 'Standard', 'pressbooks' ),
				'48em' => __( 'Wide', 'pressbooks' ),
				'label_for' => 'webbook_width',
			]
		);

		add_settings_field(
			'paragraph_separation',
			__( 'Paragraph Separation', 'pressbooks' ),
			[ $this, 'renderParagraphSeparationField' ],
			$_page,
			$_section,
			[
				'indent' => __( 'Indent paragraphs', 'pressbooks' ),
				'skiplines' => __( 'Skip lines between paragraphs', 'pressbooks' ),
			]
		);

		add_settings_field(
			'part_title',
			__( 'Display Part Title', 'pressbooks' ),
			[ $this, 'renderPartTitle' ],
			$_page,
			$_section,
			[
				__( 'Display the Part title on each chapter', 'pressbooks' ),
			]
		);

		if ( Container::get( 'Styles' )->hasBuckram() ) {
			add_settings_field(
				'collapse_sections',
				__( 'Collapse Sections', 'pressbooks' ),
				[ $this, 'renderCollapseSections' ],
				$_page,
				$_section,
				[
					__( 'Collapse sections within front matter, chapters, and back matter', 'pressbooks' ),
				]
			);
		}

		if ( get_post_meta( $meta->getMetaPostId(), 'pb_is_based_on', true ) ) {
			add_settings_field(
				'enable_source_comparison',
				__( 'Enable Source Comparison', 'pressbooks' ),
				[ $this, 'renderEnableSourceComparison' ],
				$_page,
				$_section,
				[
					__( 'Add comparison tool to the end of each front matter, part, chapter, and back matter', 'pressbooks' ),
					__( 'Allows readers to compare content with the original book from which it was cloned.', 'pressbooks' ),
				]
			);
		}

		/**
		 * Add custom settings fields.
		 *
		 * @param string $arg1
		 * @param string $arg2
		 *
		 * @since 3.9.7
		 */
		do_action( 'pb_theme_options_web_add_settings_fields', $_page, $_section );

		register_setting(
			$_page,
			$_option,
			[ $this, 'sanitize' ]
		);
	}

	/**
	 * Display the web options tab description.
	 */
	function display() {
		echo '<p>' . __( 'These options apply to the webbook.', 'pressbooks' ) . '</p>';
	}

	/**
	 * Render the web options tab form (NOT USED).
	 */
	function render() {
	}

	/**
	 * Upgrade handler for web options.
	 *
	 * @param int $version
	 */
	function upgrade( $version ) {
		if ( $version < 1 ) {
			$this->doInitialUpgrade();
		}
	}

	/**
	 * Remove deprecated keys from web options.
	 */
	function doInitialUpgrade() {
		$_option = $this->getSlug();
		$options = get_option( 'pressbooks_theme_options_' . $_option, $this->defaults );
		$deprecated = [
			'toc_collapse',
			'accessibility_fontsize',
		];

		foreach ( $options as $key => $value ) {
			if ( in_array( $key, $deprecated, true ) ) {
				unset( $options[ $key ] );
			}
		}

		update_option( 'pressbooks_theme_options_' . $_option, $options );
	}

	/**
	 * Render the social_media checkbox.
	 *
	 * @param array $args
	 */
	function renderSocialMediaField( $args ) {
		unset( $args['label_for'], $args['class'] );
		$this->renderCheckbox(
			[
				'id' => 'social_media',
				'name' => 'pressbooks_theme_options_' . $this->getSlug(),
				'option' => 'social_media',
				'value' => ( isset( $this->options['social_media'] ) ) ? $this->options['social_media'] : '',
				'label' => $args[0],
			]
		);
	}

	/**
	 * Render the webbook_width dropdown.
	 *
	 * @param array $args
	 */
	function renderWebbookWidthField( $args ) {
		unset( $args['label_for'], $args['class'] );
		$this->renderSelect(
			[
				'id' => 'webbook_width',
				'name' => 'pressbooks_theme_options_' . $this->getSlug(),
				'option' => 'webbook_width',
				'value' => ( isset( $this->options['webbook_width'] ) ) ? $this->options['webbook_width'] : $this->defaults['webbook_width'],
				'choices' => $args,
			]
		);
	}

	/**
	 * Render the paragraph_separation radio buttons.
	 *
	 * @param array $args
	 */
	function renderParagraphSeparationField( $args ) {
		unset( $args['label_for'], $args['class'] );
		$this->renderRadioButtons(
			[
				'id' => 'paragraph_separation',
				'name' => 'pressbooks_theme_options_' . $this->getSlug(),
				'option' => 'paragraph_separation',
				'value' => ( isset( $this->options['paragraph_separation'] ) ) ? $this->options['paragraph_separation'] : '',
				'choices' => $args,
			]
		);
	}

	/**
	 * Render the part_title checkbox.
	 *
	 * @param array $args
	 */
	function renderPartTitle( $args ) {
		unset( $args['label_for'], $args['class'] );
		$this->renderCheckbox(
			[
				'id' => 'part_title',
				'name' => 'pressbooks_theme_options_' . $this->getSlug(),
				'option' => 'part_title',
				'value' => ( isset( $this->options['part_title'] ) ) ? $this->options['part_title'] : '',
				'label' => $args[0],
			]
		);
	}

	/**
	 * Render the collapse_sections checkbox.
	 *
	 * @param array $args
	 */
	function renderCollapseSections( $args ) {
		unset( $args['label_for'], $args['class'] );
		$this->renderCheckbox(
			[
				'id' => 'collapse_sections',
				'name' => 'pressbooks_theme_options_' . $this->getSlug(),
				'option' => 'collapse_sections',
				'value' => ( isset( $this->options['part_title'] ) ) ? $this->options['collapse_sections'] : '',
				'label' => $args[0],
			]
		);
	}

	/**
	 * Render the allow_comparison checkbox.
	 *
	 * @param array $args
	 */
	function renderEnableSourceComparison( $args ) {
		unset( $args['label_for'], $args['class'] );
		$this->renderCheckbox(
			[
				'id' => 'enable_source_comparison',
				'name' => 'pressbooks_theme_options_' . $this->getSlug(),
				'option' => 'enable_source_comparison',
				'value' => ( isset( $this->options['enable_source_comparison'] ) ) ? $this->options['enable_source_comparison'] : '',
				'label' => $args[0],
				'description' => $args[1],
			]
		);
	}

	/**
	 * Render the webbook_header_font input.
	 *
	 * @param array $args
	 */
	function renderHeaderFontField( $args ) {
		unset( $args['label_for'], $args['class'] );
		$this->renderSelectOptGroup(
			[
				'id' => 'webbook_header_font',
				'name' => 'pressbooks_theme_options_' . $this->getSlug(),
				'option' => 'webbook_header_font',
				'value' => ( isset( $this->options['webbook_header_font'] ) ) ? $this->options['webbook_header_font'] : '',
				'choices' => $args,
			]
		);
	}

	/**
	 * Render the webbook_body_font input.
	 *
	 * @param array $args
	 */
	function renderBodyFontField( $args ) {
		unset( $args['label_for'], $args['class'] );
		$this->renderSelectOptGroup(
			[
				'id' => 'webbook_body_font',
				'name' => 'pressbooks_theme_options_' . $this->getSlug(),
				'option' => 'webbook_body_font',
				'value' => ( isset( $this->options['webbook_body_font'] ) ) ? $this->options['webbook_body_font'] : '',
				'choices' => $args,
			]
		);
	}

	/**
	 * Get the slug for the web options tab.
	 *
	 * @return string $slug
	 */
	static function getSlug() {
		return 'web';
	}

	/**
	 * Get the localized title of the web options tab.
	 *
	 * @return string $title
	 */
	static function getTitle() {
		return __( 'Web Options', 'pressbooks' );
	}

	/**
	 * Get an array of default values for the web options tab.
	 *
	 * @return array $defaults
	 */
	static function getDefaults() {
		/**
		 * @param array $value
		 *
		 * @since 3.9.7
		 */
		return apply_filters(
			'pb_theme_options_web_defaults', [
				'webbook_header_font' => '',
				'webbook_body_font' => '',
				'social_media' => 1,
				'paragraph_separation' => 'skiplines',
				'part_title' => 0,
				'webbook_width' => '40em',
				'collapse_sections' => 0,
				'enable_source_comparison' => 0,
			]
		);
	}

	/**
	 * Filter the array of default values for the web options tab.
	 *
	 * @param array $defaults
	 *
	 * @return array $defaults
	 */
	static function filterDefaults( $defaults ) {
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
		 * @param array $value
		 *
		 * @since 3.9.7
		 */
		return apply_filters(
			'pb_theme_options_web_booleans', [
				'social_media',
				'part_title',
				'collapse_sections',
				'enable_source_comparison',
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
		 * @param array $value
		 *
		 * @since 3.9.7
		 */
		return apply_filters(
			'pb_theme_options_web_strings', [
				'webbook_header_font',
				'webbook_body_font',
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
		 * @param array $value
		 *
		 * @since 3.9.7
		 */
		return apply_filters( 'pb_theme_options_web_integers', [] );
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
		 * @param array $value
		 *
		 * @since 3.9.7
		 */
		return apply_filters( 'pb_theme_options_web_floats', [] );
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
		 * @param array $value
		 *
		 * @since 3.9.7
		 */
		return apply_filters(
			'pb_theme_options_web_predefined', [
				'paragraph_separation',
				'webbook_width',
			]
		);
	}

	/**
	 * Apply overrides.
	 *
	 * @param string $scss
	 *
	 * @return string
	 *
	 * @since 3.9.8
	 */
	static function scssOverrides( $scss ) {

		$styles = Container::get( 'Styles' );
		$v2_compatible = $styles->isCurrentThemeCompatible( 2 );
		$shape_shifter_compatible = $styles->isShapeShifterCompatible();

		// Global Options
		$options = get_option( 'pressbooks_theme_options_global' );

		// Textbox colours.

		if ( $v2_compatible ) {
			foreach (
				[
					'edu_textbox_examples_header_color' => 'examples-header-color',
					'edu_textbox_examples_header_background' => 'examples-header-background',
					'edu_textbox_examples_background' => 'examples-background',
					'edu_textbox_exercises_header_color' => 'exercises-header-color',
					'edu_textbox_exercises_header_background' => 'exercises-header-background',
					'edu_textbox_exercises_background' => 'exercises-background',
					'edu_textbox_objectives_header_color' => 'learning-objectives-header-color',
					'edu_textbox_objectives_header_background' => 'learning-objectives-header-background',
					'edu_textbox_objectives_background' => 'learning-objectives-background',
					'edu_textbox_takeaways_header_color' => 'key-takeaways-header-color',
					'edu_textbox_takeaways_header_background' => 'key-takeaways-header-background',
					'edu_textbox_takeaways_background' => 'key-takeaways-background',
				] as $option => $variable
			) {
				if ( isset( $options[ $option ] ) ) {
					$styles->getSass()->setVariables(
						[
							"$variable" => $options[ $option ],
						]
					);
				}
			}
		}

		$options = get_option( 'pressbooks_theme_options_web' );

		$paragraph_separation = $options['paragraph_separation'] ?? 'skiplines';

		if ( 'indent' === $paragraph_separation ) {
			if ( $v2_compatible ) {
				$styles->getSass()->setVariables(
					[
						'para-margin-top' => '0',
						'para-indent' => '1em',
					]
				);
			} else {
				$scss .= "#content * + p { text-indent: 1em; margin-top: 0; margin-bottom: 0; } \n";
			}
		} elseif ( 'skiplines' === $paragraph_separation ) {
			if ( $v2_compatible ) {
				$styles->getSass()->setVariables(
					[
						'para-margin-top' => '1em',
						'para-indent' => '0',
					]
				);
			} else {
				$scss .= "#content p + p { text-indent: 0em; margin-top: 1em; } \n";
			}
		}

		// Shape Shifter Features
		if ( $shape_shifter_compatible ) {
			if ( ! empty( $options['webbook_header_font'] ) ) {
				$webbook_header_font = str_replace( '"', '', $options['webbook_header_font'] );
				$styles->getSass()->setVariables(
					[
						'shapeshifter-font-2' => '"' . $webbook_header_font . '"',
						'shapeshifter-font-2-is-serif' => $styles->isShaperShifterFontSerif( $webbook_header_font ),
					]
				);
			}
			if ( ! empty( $options['webbook_body_font'] ) ) {
				$webbook_body_font = str_replace( '"', '', $options['webbook_body_font'] );
				$styles->getSass()->setVariables(
					[
						'shapeshifter-font-1' => '"' . $webbook_body_font . '"',
						'shapeshifter-font-1-is-serif' => $styles->isShaperShifterFontSerif( $webbook_body_font ),
					]
				);
			}
		}

		return $scss;
	}
}
