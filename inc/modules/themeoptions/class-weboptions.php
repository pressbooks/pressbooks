<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */

namespace Pressbooks\Modules\ThemeOptions;

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
		$_page = $_option = 'pressbooks_theme_options_' . $this->getSlug();
		$_section = $this->getSlug() . '_options_section';

		if ( false === get_option( $_option ) ) {
			add_option( $_option, $this->defaults );
		}

		add_settings_section(
			$_section,
			$this->getTitle(),
			[ $this, 'display' ],
			$_page
		);

		add_settings_field(
			'social_media',
			__( 'Enable Social Media', 'pressbooks' ),
			[ $this, 'renderSocialMediaField' ],
			$_page,
			$_section,
			[
				__( 'Add buttons to cover page and each chapter so that readers may share links to your book through social media: Facebook, Twitter, Google+', 'pressbooks' ),
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

		/**
		 * Add custom settings fields.
		 *
		 * @since 3.9.7
		 *
		 * @param string $arg1
		 * @param string $arg2
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
	 * Render the paragraph_separation radio buttons.
	 *
	 * @param array $args
	 */
	function renderParagraphSeparationField( $args ) {
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
	 * Render the social_media checkbox.
	 *
	 * @param array $args
	 */
	function renderPartTitle( $args ) {
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
		 * @since 3.9.7
		 *
		 * @param array $value
		 */
		return apply_filters(
			'pb_theme_options_web_defaults', [
			'social_media' => 1,
			'paragraph_separation' => 'skiplines',
			'part_title' => 0,
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
		 * @since 3.9.7
		 *
		 * @param array $value
		 */
		return apply_filters(
			'pb_theme_options_web_booleans', [
			'social_media',
			'part_title',
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
		return apply_filters( 'pb_theme_options_web_strings', [] );
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
		 * @since 3.9.7
		 *
		 * @param array $value
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
		 * @since 3.9.7
		 *
		 * @param array $value
		 */
		return apply_filters(
			'pb_theme_options_web_predefined', [
			'paragraph_separation',
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

		$styles = \Pressbooks\Container::get( 'Styles' );
		$v2_compatible = $styles->isCurrentThemeCompatible( 2 );

		$options = get_option( 'pressbooks_theme_options_web' );

		if ( isset( $options['paragraph_separation'] ) ) {
			if ( 'indent' === $options['paragraph_separation'] ) {
				if ( $v2_compatible ) {
					$styles->getSass()->setVariables( [
						'para-margin-top' => '0',
						'para-indent' => '1em',
					] );
				} else {
					$scss .= "* + p { text-indent: 1em; margin-top: 0; margin-bottom: 0; } \n";
				}
			} elseif ( 'skiplines' === $options['paragraph_separation'] ) {
				if ( $v2_compatible ) {
					$styles->getSass()->setVariables( [
						'para-margin-top' => '1em',
						'para-indent' => '0',
					] );
				} else {
					$scss .= "p + p { text-indent: 0em; margin-top: 1em; } \n";
				}
			}
		}

		return $scss;
	}
}
