<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */
namespace Pressbooks\Admin;

class ExportOptions extends \Pressbooks\Options {
	/**
	 * The value for option: pressbooks_export_options_version
	 *
	 * @see upgrade()
	 * @var int
	 */
	static $currentVersion = 1;

	/**
   * Export options.
   *
   * @var array
   */
	public $options;

	/**
   * Export defaults.
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
	 * Configure the export options page using the settings API.
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
			'email_validation_logs',
			__( 'Email Validation Logs', 'pressbooks' ),
			array( $this, 'renderEmailValidationLogsField' ),
			$_page,
			$_section,
			array(
				'0' => __( 'No. Ignore validation errors.', 'pressbooks' ),
				'1' => __( 'Yes.', 'pressbooks' ) . ' ' . __( 'Email me validation error logs on export.', 'pressbooks' )
			)
		);

		register_setting(
			$_page,
			$_option,
			array( $this, 'sanitize' )
		);
	}

	/**
	 * Display the export options page description.
	 */
	function display() {
		echo '<p>' . __( 'Export settings.', 'pressbooks' ) . '</p>';
	}

	function render() { ?>
		<div class="wrap">
			<h1><?php echo $this->getTitle(); ?></h1>
			<form method="post" action="options.php">
				<?php settings_fields( $this->getSlug() );
				do_settings_sections( $this->getSlug() );
				submit_button(); ?>
			</form>
		</div> <?php
	}

	function upgrade( $version ) {
		if ( $version < 1 ) {
			$this->doInitialUpgrade();
		}
	}

	function doInitialUpgrade() {
		$_option = $this->getSlug();
		$options = array();

		$email_validation_logs = get_option('pressbooks_email_validation_logs', 0);

		$options['email_validation_logs'] = $email_validation_logs;

		update_option( $_option, $options );
		delete_option( 'pressbooks_email_validation_logs' );
	}

	/**
	 * Render the email_validation_logs radio buttons.
	 * @param array $args
	 */
	function renderEmailValidationLogsField( $args ) {
		$this->renderRadioButtons( 'email_validation_logs', $this->getSlug(), 'email_validation_logs', @$this->options['email_validation_logs'], $args);
	}

	/**
	 * Get the slug for the export options page.
	 *
	 * @return string $slug
	 */
	static function getSlug() {
  	return 'pressbooks_export_options';
  }

	/**
	 * Get the localized title of the export options page.
	 *
	 * @return string $title
	 */
  static function getTitle() {
  	return __('Export Settings', 'pressbooks');
  }

	/**
	 * Get an array of default values for the export options page.
	 *
	 * @return array $defaults
	 */
	static function getDefaults() {
		return array(
			'email_validation_logs' => 0,
		);
	}

	/**
	 * Get an array of options which return booleans.
	 *
	 * @return array $options
	 */
	static function getBooleanOptions() {
		return array(
			'email_validation_logs',
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
