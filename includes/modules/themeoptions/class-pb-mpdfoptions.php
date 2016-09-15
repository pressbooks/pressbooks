<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */
namespace Pressbooks\Modules\ThemeOptions;

use Pressbooks\Container;
use Pressbooks\CustomCss;

class mPDFOptions extends \Pressbooks\Options {

	/**
	 * The value for option: pressbooks_theme_options_mpdf_version
	 *
	 * @see upgrade()
	 * @var int
	 */
	static $currentVersion = 0;

	/**
   * PDF theme options.
   *
   * @var array
   */
	public $options;

	/**
   * PDF theme defaults.
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
	 * Configure the mPDF options tab using the settings API.
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
			'mpdf_page_size',
			__( 'Page Size', 'pressbooks' ),
			array($this, 'renderPageSizeField'),
			$_page,
			$_section,
			array(
				'A0' => __( 'A0', 'pressbooks' ),
				'A1' => __( 'A1', 'pressbooks' ),
				'A2' => __( 'A2', 'pressbooks' ),
				'A3' => __( 'A3', 'pressbooks' ),
				'A4' => __( 'A4', 'pressbooks' ),
				'A5' => __( 'A5', 'pressbooks' ),
				'A6' => __( 'A6', 'pressbooks' ),
				'A7' => __( 'A7', 'pressbooks' ),
				'A8' => __( 'A8', 'pressbooks' ),
				'A9' => __( 'A9', 'pressbooks' ),
				'A10' => __( 'A10', 'pressbooks' ),
				'B0' => __( 'B0', 'pressbooks' ),
				'B1' => __( 'B1', 'pressbooks' ),
				'B2' => __( 'B2', 'pressbooks' ),
				'B3' => __( 'B3', 'pressbooks' ),
				'B4' => __( 'B4', 'pressbooks' ),
				'B5' => __( 'B5', 'pressbooks' ),
				'B6' => __( 'B6', 'pressbooks' ),
				'B7' => __( 'B7', 'pressbooks' ),
				'B8' => __( 'B8', 'pressbooks' ),
				'B9' => __( 'B9', 'pressbooks' ),
				'B10' => __( 'B10', 'pressbooks' ),
				'C0' => __( 'C0', 'pressbooks' ),
				'C1' => __( 'C1', 'pressbooks' ),
				'C2' => __( 'C2', 'pressbooks' ),
				'C3' => __( 'C3', 'pressbooks' ),
				'C4' => __( 'C4', 'pressbooks' ),
				'C5' => __( 'C5', 'pressbooks' ),
				'C6' => __( 'C6', 'pressbooks' ),
				'C7' => __( 'C7', 'pressbooks' ),
				'C8' => __( 'C8', 'pressbooks' ),
				'C9' => __( 'C9', 'pressbooks' ),
				'C10' => __( 'C10', 'pressbooks' ),
				'4A0' => __( '4A0', 'pressbooks' ),
				'2A0' => __( '2A0', 'pressbooks' ),
				'RA0' => __( 'RA0', 'pressbooks' ),
				'RA1' => __( 'RA1', 'pressbooks' ),
				'RA2' => __( 'RA2', 'pressbooks' ),
				'RA3' => __( 'RA3', 'pressbooks' ),
				'RA4' => __( 'RA4', 'pressbooks' ),
				'SRA0' => __( 'SRA0', 'pressbooks' ),
				'SRA1' => __( 'SRA1', 'pressbooks' ),
				'SRA2' => __( 'SRA2', 'pressbooks' ),
				'SRA3' => __( 'SRA3', 'pressbooks' ),
				'SRA4' => __( 'SRA4', 'pressbooks' ),
				'Letter' => __( 'Letter', 'pressbooks' ),
				'Legal' => __( 'Legal' , 'pressbooks' ),
				'Executive' => __( 'Executive' , 'pressbooks' ),
				'Folio' => __( 'Folio' , 'pressbooks' ),
				'Demy' => __( 'Demy' , 'pressbooks' ),
				'Royal' => __( 'Royal' , 'pressbooks' ),
				'A' => __( 'Type A paperback 111x178mm' , 'pressbooks' ),
				'B' => __( 'Type B paperback 128x198mm' , 'pressbooks' ),
			)
		);

		add_settings_field(
			'mpdf_margin_left',
			__( 'Left margin', 'pressbooks' ),
			array($this, 'renderLeftMarginField'),
			$_page,
			$_section,
			array(
				__(  'Left Margin (in millimetres)', 'pressbooks' )
			)
		);

		add_settings_field(
			'mpdf_margin_right',
			__( 'Right margin', 'pressbooks' ),
			array($this, 'renderRightMarginField'),
			$_page,
			$_section,
			array(
				__(  ' Right margin (in milimeters)', 'pressbooks' )
			)
		);

		add_settings_field(
			'mpdf_mirror_margins',
			__( 'Mirror Margins', 'pressbooks' ),
			array($this, 'renderMirrorMarginsField'),
			$_page,
			$_section,
			array(
				 __( 'The document will mirror the left and right margin values on odd and even pages (i.e. they become inner and outer margins)', 'pressbooks' )
			)
		);

		add_settings_field(
			'mpdf_include_cover',
			__( 'Cover Image', 'pressbooks' ),
			array($this, 'renderCoverImageField'),
			$_page,
			$_section,
			array(
				 __( 'Display cover image', 'pressbooks' )
			)
		);

		add_settings_field(
			'mpdf_include_toc',
			__( 'Table of Contents', 'pressbooks' ),
			array($this, 'renderTOCField'),
			$_page,
			$_section,
			array(
				 __( 'Display table of contents', 'pressbooks' )
			)
		);

		add_settings_field(
			'mpdf_indent_paragraphs',
			__( 'Indent paragraphs', 'pressbooks' ),
			array($this, 'renderIndentParagraphsField'),
			$_page,
			$_section,
			array(
				 __( 'Indent paragraphs', 'pressbooks' )
			)
		);

		add_settings_field(
			'mpdf_hyphens',
			__( 'Hyphens', 'pressbooks' ),
			array($this, 'renderHyphensField'),
			$_page,
			$_section,
			array(
				 __( 'Enable hyphenation', 'pressbooks' )
			)
		);

		add_settings_field(
			'mpdf_fontsize',
			__( 'Increase Font Size', 'pressbooks' ),
			array($this, 'renderFontSizeField'),
			$_page,
			$_section,
			array(
			    __('Increases font size and line height for greater accessibility', 'pressbooks' )
			)
		);

		register_setting(
			$_option,
			$_option,
			array( $this, 'sanitize' )
		);
	}

	/**
	 * Display the mPDF options tab description.
	 */
	function display() {
		echo '<p>' . __( 'These options apply to mPDF exports.', 'pressbooks' ) . '</p>';
	}

	/**
	 * Render the mPDF options tab form (NOT USED).
	 */
	function render() {}

	/**
	 * Upgrade handler for mPDF options (none at present).
	 *
	 * @param int $version
	 */
	function upgrade( $version ) {}

	/**
	 * Render the mpdf_page_size input.
	 * @param array $args
	 */
	function renderPageSizeField( $args ) {
		$this->renderSelect('mpdf_page_size',  'pressbooks_theme_options_' . $this->getSlug(), 'mpdf_page_size', $this->options['mpdf_page_size'], $args, false);
	}

	/**
	 * Render the mpdf_margin_left input.
	 * @param array $args
	 */
	function renderLeftMarginField( $args ) {
		$this->renderField('mpdf_margin_left', 'pressbooks_theme_options_' . $this->getSlug(), 'mpdf_margin_left', $this->options['mpdf_margin_left'], $args[0]);
	}

	/**
	 * Render the mpdf_margin_right input.
	 * @param array $args
	 */
	function renderRightMarginField( $args ) {
		$this->renderField( 'mpdf_margin_right', 'pressbooks_theme_options_' . $this->getSlug(), 'mpdf_margin_right', $this->options['mpdf_margin_right'], $args[0] );
	}

	/**
	 * Render the mpdf_mirror_margins checkbox.
	 * @param array $args
	 */
	function renderMirrorMarginsField( $args ) {
		$this->renderCheckbox( 'mpdf_mirror_margins', 'pressbooks_theme_options_' . $this->getSlug(), 'mpdf_mirror_margins', $this->options['mpdf_mirror_margins'], $args[0] );
	}

	/**
	 * Render the mpdf_include_cover checkbox.
	 * @param array $args
	 */
	function renderCoverImageField( $args ) {
		$this->renderCheckbox( 'mpdf_include_cover', 'pressbooks_theme_options_' . $this->getSlug(), 'mpdf_include_cover', $this->options['mpdf_include_cover'], $args[0] );
	}

	/**
	 * Render the mpdf_include_toc checkbox.
	 * @param array $args
	 */
	function renderTOCField( $args ) {
		$this->renderCheckbox( 'mpdf_include_toc', 'pressbooks_theme_options_' . $this->getSlug(), 'mpdf_include_toc', $this->options['mpdf_include_toc'], $args[0] );
	}

	/**
	 * Render the mpdf_indent_paragraphs checkbox.
	 * @param array $args
	 */
	function renderIndentParagraphsField( $args ) {
		$this->renderCheckbox( 'mpdf_indent_paragraphs', 'pressbooks_theme_options_' . $this->getSlug(), 'mpdf_indent_paragraphs', $this->options['mpdf_indent_paragraphs'], $args[0] );
	}

	/**
	 * Render the mpdf_hyphens checkbox.
	 * @param array $args
	 */
	function renderHyphensField( $args ) {
		$this->renderCheckbox( 'mpdf_hyphens', 'pressbooks_theme_options_' . $this->getSlug(), 'mpdf_hyphens', $this->options['mpdf_hyphens'], $args[0] );
	}

	/**
	 * Render the mpdf_fontsize checkbox.
	 * @param array $args
	 */
	function renderFontSizeField( $args ) {
		$this->renderCheckbox( 'mpdf_fontsize', 'pressbooks_theme_options_' . $this->getSlug(), 'mpdf_fontsize', $this->options['mpdf_fontsize'], $args[0] );
	}

	/**
	 * Get the slug for the mPDF options tab.
	 *
	 * @return string $slug
	 */
	static function getSlug() {
  	return 'mpdf';
  }

	/**
	 * Get the localized title of the mPDF options tab.
	 *
	 * @return string $title
	 */
  static function getTitle() {
  	return __('mPDF Options', 'pressbooks');
  }

	/**
	 * Get an array of default values for the mPDF options tab.
	 *
	 * @return array $defaults
	 */
	static function getDefaults() {
		return array(
			'mpdf_page_size' => 'Letter',
			'mpdf_include_cover' => 1,
			'mpdf_indent_paragraphs' => 0,
			'mpdf_include_toc' => 1,
			'mpdf_mirror_margins' => 1,
			'mpdf_margin_left' => 15,
			'mpdf_margin_right' => 30,
			'mpdf_hyphens' => 0
		);
	}

	/**
	 * Get an array of options which return booleans.
	 *
	 * @return array $options
	 */
	static function getBooleanOptions() {
		return array(
			'mpdf_mirror_margins',
			'mpdf_include_cover',
			'mpdf_include_toc',
			'mpdf_indent_paragraphs',
			'mpdf_hyphens',
			'mpdf_fontsize'
		);
	}

	/**
	 * Get an array of options which return strings.
	 *
	 * @return array $options
	 */
	static function getStringOptions() {
		return array(
		);
	}

	/**
	 * Get an array of options which return integers.
	 *
	 * @return array $options
	 */
	static function getIntegerOptions() {
		return array(
			'mpdf_left_margin',
			'mpdf_right_margin'
		);
	}

	/**
	 * Get an array of options which return floats.
	 *
	 * @return array $options
	 */
	static function getFloatOptions() {
		return array(
		);
	}

	/**
	 * Get an array of options which return predefined values.
	 *
	 * @return array $options
	 */
	static function getPredefinedOptions() {
		return array(
			'mpdf_page_size'
		);
	}
}
