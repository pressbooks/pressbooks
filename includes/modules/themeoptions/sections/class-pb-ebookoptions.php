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
 			if ( !isset ( $this->options[ $key ] ) && !in_array( $key, $this->booleans ) ) {
 				$this->options[ $key ] = $value;
 			}
 		}
 	}

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

	function display() {
		echo '<p>' . __( 'These options apply to ebook exports.', 'pressbooks' ) . '</p>';
	}

	/**
	 * Upgrade options.
	 *
	 * @param int $version
	 */
	function upgrade( $version ) {
		if ( $version < 1 ) {
			// Remove defaults from database, change some values
			$this->doInitialUpgrade();
		}
	}

	function doInitialUpgrade() {
		$_option = $this->getSlug();
		$options = get_option( 'pressbooks_theme_options_' . $_option, $this->defaults );

		// Substitute human-readable values
		if ( !isset( $options['ebook_paragraph_separation'] ) || $options['ebook_paragraph_separation'] == '1' ) {
			$options['ebook_paragraph_separation'] = 'indent';
		} elseif ( $options['ebook_paragraph_separation'] == '2' ) {
			$options['ebook_paragraph_separation'] = 'skiplines';
		}

		update_option( 'pressbooks_theme_options_' . $_option, $options );
	}

	function renderParagraphSeparationField( $args ) {
		$this->renderRadioButtons( 'ebook_paragraph_separation', 'pressbooks_theme_options_' . $this->getSlug(), 'ebook_paragraph_separation', @$this->options['ebook_paragraph_separation'], $args);
	}

	function renderCompressImagesField( $args ) {
		$this->renderCheckbox( 'ebook_compress_images', 'pressbooks_theme_options_' . $this->getSlug(), 'ebook_compress_images', @$this->options['ebook_compress_images'], $args[0] );
	}

	protected function getSlug() {
  	return 'ebook';
  }

  protected function getTitle() {
  	return __('Ebook Options', 'pressbooks');
  }

	static function getDefaults() {
		return array(
			'ebook_paragraph_separation' => 'indent',
			'ebook_compress_images' => 0
		);
	}

	static function getBooleanOptions() {
		return array(
			'ebook_compress_images'
		);
	}

	static function getStringOptions() {
		return array();
	}

	static function getIntegerOptions() {
		return array();
	}

	static function getFloatOptions() {
		return array();
	}

	static function getPredefinedOptions() {
		return array(
			'ebook_paragraph_separation'
		);
	}
}
