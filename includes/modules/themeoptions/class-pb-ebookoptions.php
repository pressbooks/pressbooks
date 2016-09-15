<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */
namespace Pressbooks\Modules\ThemeOptions;

class EbookOptions extends \Pressbooks\Options {

	/**
	 * The value for option: pressbooks_theme_options_ebook_version
	 *
	 * @see upgrade()
	 * @var int
	 */
	static $currentVersion = 1;

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
	 * Configure the ebook options tab using the settings API.
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
			'ebook_paragraph_separation',
			__( 'Paragraph Separation', 'pressbooks' ),
			array( $this, 'renderParagraphSeparationField' ),
			$_page,
			$_section,
			array(
				'indent' => __( 'Indent paragraphs', 'pressbooks' ),
				'skiplines' => __( 'Skip lines between paragraphs', 'pressbooks' )
			)
		);

		add_settings_field(
			'ebook_compress_images',
			__( 'Compress Images', 'pressbooks' ),
			array( $this, 'renderCompressImagesField' ),
			$_page,
			$_section,
			array(
				__( 'Reduce image size and quality', 'pressbooks' )
			)
		);

		register_setting(
			$_option,
			$_option,
			array( $this, 'sanitize' )
		);
	}

	/**
	 * Display the Ebook options tab description.
	 */
	function display() {
		echo '<p>' . __( 'These options apply to ebook exports.', 'pressbooks' ) . '</p>';
	}

	/**
	 * Render the Ebook options tab form (NOT USED).
	 */
	function render() {}

	/**
	 * Upgrade handler for Ebook options.
	 *
	 * @param int $version
	 */
	function upgrade( $version ) {
		if ( $version < 1 ) {
			$this->doInitialUpgrade();
		}
	}

	/**
	 * Update values to human-readable equivalents within Ebook options.
	 */
	function doInitialUpgrade() {
		$_option = $this->getSlug();
		$options = get_option( 'pressbooks_theme_options_' . $_option, $this->defaults );

		if ( !isset( $options['ebook_paragraph_separation'] ) || $options['ebook_paragraph_separation'] == '1' ) {
			$options['ebook_paragraph_separation'] = 'indent';
		} elseif ( $options['ebook_paragraph_separation'] == '2' ) {
			$options['ebook_paragraph_separation'] = 'skiplines';
		}

		update_option( 'pressbooks_theme_options_' . $_option, $options );
	}

	/**
	 * Render the ebook_paragraph_separation radio buttons.
	 * @param array $args
	 */
	function renderParagraphSeparationField( $args ) {
		$this->renderRadioButtons( 'ebook_paragraph_separation', 'pressbooks_theme_options_' . $this->getSlug(), 'ebook_paragraph_separation', @$this->options['ebook_paragraph_separation'], $args);
	}

	/**
	 * Render the ebook_compress_images checkbox.
	 * @param array $args
	 */
	function renderCompressImagesField( $args ) {
		$this->renderCheckbox( 'ebook_compress_images', 'pressbooks_theme_options_' . $this->getSlug(), 'ebook_compress_images', @$this->options['ebook_compress_images'], $args[0] );
	}

	/**
	 * Get the slug for the Ebook options tab.
	 *
	 * @return string $slug
	 */
	static function getSlug() {
  	return 'ebook';
  }

	/**
	 * Get the localized title of the Ebook options tab.
	 *
	 * @return string $title
	 */
	static function getTitle() {
  	return __('Ebook Options', 'pressbooks');
  }

	/**
	 * Get an array of default values for the Ebook options tab.
	 *
	 * @return array $defaults
	 */
	static function getDefaults() {
		return array(
			'ebook_paragraph_separation' => 'indent',
			'ebook_compress_images' => 0
		);
	}

	/**
	 * Get an array of options which return booleans.
	 *
	 * @return array $options
	 */
	static function getBooleanOptions() {
		return array(
			'ebook_compress_images'
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
		return array(
			'ebook_paragraph_separation'
		);
	}
}
