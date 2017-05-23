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
	function __construct( array $options ) {
			$this->options = $options;
		$this->defaults = $this->getDefaults();
		$this->booleans = $this->getBooleanOptions();

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
			'social_media',
			__( 'Enable Social Media', 'pressbooks' ),
			array( $this, 'renderSocialMediaField' ),
			$_page,
			$_section,
			array(
				__( 'Add buttons to cover page and each chapter so that readers may share links to your book through social media: Facebook, Twitter, Google+', 'pressbooks' )
			)
		);

		add_settings_field(
			'part_title',
			__( 'Display Part Title', 'pressbooks' ),
			array( $this, 'renderPartTitle' ),
			$_page,
			$_section,
			array(
				__( 'Display the Part title on each chapter', 'pressbooks' )
			)
		);

		register_setting(
			$_page,
			$_option,
			array( $this, 'sanitize' )
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
	function render() {}

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
		$deprecated = array(
			'toc_collapse',
			'accessibility_fontsize',
		);

		foreach ( $options as $key => $value ) {
			if ( in_array( $key, $deprecated ) ) {
				unset( $options[ $key ] );
			}
		}

		update_option( 'pressbooks_theme_options_' . $_option, $options );
	}

	/**
	 * Render the social_media checkbox.
	 * @param array $args
	 */
	function renderSocialMediaField( $args ) {
		$this->renderCheckbox( 'social_media', 'pressbooks_theme_options_' . $this->getSlug(), 'social_media', @$this->options['social_media'], $args[0] );
	}

	/**
	 * Render the social_media checkbox.
	 * @param array $args
	 */
	function renderPartTitle( $args ) {
		$this->renderCheckbox( 'part_title', 'pressbooks_theme_options_' . $this->getSlug(), 'part_title', @$this->options['part_title'], $args[0] );
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
		return apply_filters( 'pressbooks_theme_options_web_defaults', array(
			'social_media' => 1,
			'part_title' => 0,
		) );
	}

	/**
	 * Filter the array of default values for the web options tab.
	 *
	 * @param array $defaults
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
		return array(
			'social_media',
			'part_title',
		);
	}
}
