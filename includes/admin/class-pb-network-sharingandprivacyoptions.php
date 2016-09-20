<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */
namespace Pressbooks\Admin\Network;

class SharingAndPrivacyOptions extends \Pressbooks\Options {
	/**
	 * The value for option: pressbooks_network_sharingandprivacy_options_version
	 *
	 * @see upgrade()
	 * @var int
	 */
	static $currentVersion = 1;

	/**
   * Sharing and Privacy options.
   *
   * @var array
   */
	public $options;

	/**
   * Sharing and Privacy defaults.
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
	 * Configure the network export options page using the settings API.
	 */
	function init() {
		$_page = $_option = $this->getSlug();
		$_section = $this->getSlug() . '_section';

		add_settings_section(
			$_section,
			'',
			array( $this, 'display' ),
			$_page
		);

		add_settings_field(
			'allow_redistribution',
			__( 'Allow Redistribution', 'pressbooks' ),
			array( $this, 'renderAllowRedistributionField' ),
			$_page,
			$_section,
			array(
				__( 'Allow book administrators to enable redistribution of export files.', 'pressbooks' ),
			)
		);

		register_setting(
			$_page,
			$_option,
			array( $this, 'sanitize' )
		);
	}

	/**
	 * Display the network sharing and privacy options page description.
	 */
	function display() {
		echo '<p>' . __( 'Sharing and Privacy settings.', 'pressbooks' ) . '</p>';
	}

	function render() {
		$_option = $this->getSlug();
		?>
		<div class="wrap">
			<h1><?php echo $this->getTitle(); ?></h1>
			<?php $nonce = ( @$_REQUEST['_wpnonce'] ) ? $_REQUEST['_wpnonce'] : '';
			if ( !empty( $_POST ) ) {
				if ( !wp_verify_nonce( $nonce, $_option . '-options' ) ) {
				    die( 'Security check' );
				} else {
					if ( @$_REQUEST[ $_option ]['allow_redistribution'] ) {
						$options['allow_redistribution'] = 1;
					} else {
						$options['allow_redistribution'] = 0;
					}
					update_site_option( $_option, $options );
					?>
					<div id="message" class="updated notice is-dismissible"><p><strong><?php _e( 'Settings saved.', 'pressbooks' ); ?></strong></div>
				<?php }
			} ?>
			<form method="post" action="">
				<?php settings_fields( $this->getSlug() );
				do_settings_sections( $this->getSlug() );
				submit_button(); ?>
			</form>
		</div> <?php
	}

	function upgrade( $version ) {
		if ( $version < 1 ) {
			// Nothing doing.
		}
	}

	/**
	 * Render the allow_redistribution radio buttons.
	 * @param array $args
	 */
	function renderAllowRedistributionField( $args ) {
		$options = get_site_option( $this->getSlug() );
		$this->renderCheckbox( 'allow_redistribution', $this->getSlug(), 'allow_redistribution', @$options['allow_redistribution'], $args[0]);
	}

	/**
	 * Get the slug for the network export options page.
	 *
	 * @return string $slug
	 */
	static function getSlug() {
  	return 'pressbooks_sharingandprivacy_options';
  }

	/**
	 * Get the localized title of the network export options tab.
	 *
	 * @return string $title
	 */
  static function getTitle() {
  	return __('Sharing and Privacy Settings', 'pressbooks');
  }

	/**
	 * Get an array of default values for the network export options page.
	 *
	 * @return array $defaults
	 */
	static function getDefaults() {
		return array(
			'allow_redistribution' => 0
		);
	}

	/**
	 * Get an array of options which return booleans.
	 *
	 * @return array $options
	 */
	static function getBooleanOptions() {
		return array(
			'allow_redistribution'
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
