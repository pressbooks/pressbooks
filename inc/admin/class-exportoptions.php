<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */
// TODO: Security audit
// @phpcs:disable Pressbooks.Security.EscapeOutput.OutputNotEscaped

namespace Pressbooks\Admin;

class ExportOptions extends \Pressbooks\Options {
	/**
	 * The value for option: pressbooks_export_options_version
	 *
	 * @see upgrade()
	 * @var int
	 */
	const VERSION = 1;

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
	 * Export booleans.
	 *
	 * @var array
	 */
	public $booleans;

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
	 * Configure the export options page using the settings API.
	 */
	function init() {
		$_option = $this->getSlug();
		$_page = $_option;
		$_section = $this->getSlug() . '_section';

		add_settings_section(
			$_section,
			'',
			[ $this, 'display' ],
			$_page
		);

		add_settings_field(
			'email_validation_logs',
			__( 'Email Validation Logs', 'pressbooks' ),
			[ $this, 'renderEmailValidationLogsField' ],
			$_page,
			$_section,
			[
				'0' => __( 'No. Ignore validation errors.', 'pressbooks' ),
				'1' => __( 'Yes.', 'pressbooks' ) . ' ' . __( 'Email me validation error logs on export.', 'pressbooks' ),
			]
		);

		if ( ! \Pressbooks\CustomCss::isCustomCss() ) {
			add_settings_field(
				'theme_lock',
				__( 'Lock Theme', 'pressbooks' ),
				[ $this, 'renderThemeLockField' ],
				$_page,
				$_section,
				[
					__( 'Lock your theme at its current version.', 'pressbooks' ),
				]
			);
		}

		register_setting(
			$_page,
			$_option,
			[ $this, 'sanitize' ]
		);
	}

	/**
	 * Display the export options page description.
	 */
	function display() {
		echo '<p>' . __( 'Export settings.', 'pressbooks' ) . '</p>';
	}

	function render() {
		?>
		<div class="wrap">
			<h1><?php echo $this->getTitle(); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( $this->getSlug() );
				do_settings_sections( $this->getSlug() );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	function upgrade( $version ) {
		if ( $version < 1 ) {
			$this->doInitialUpgrade();
		}
	}

	function doInitialUpgrade() {
		$_option = $this->getSlug();
		$options = [];

		$email_validation_logs = get_option( 'pressbooks_email_validation_logs', 0 );

		$options['email_validation_logs'] = $email_validation_logs;

		update_option( $_option, $options );
		delete_option( 'pressbooks_email_validation_logs' );
	}

	/**
	 * Render the email_validation_logs radio buttons.
	 *
	 * @param array $args
	 */
	function renderEmailValidationLogsField( $args ) {
		$this->renderRadioButtons(
			[
				'id' => 'email_validation_logs',
				'name' => $this->getSlug(),
				'option' => 'email_validation_logs',
				'value' => ( isset( $this->options['email_validation_logs'] ) ) ? $this->options['email_validation_logs'] : '',
				'choices' => $args,
			]
		);
	}

	/**
	 * Render the lock_theme checkbox.
	 *
	 * @param array $args
	 */
	function renderThemeLockField( $args ) {
		$this->renderCheckbox(
			[
				'id' => 'theme_lock',
				'name' => $this->getSlug(),
				'option' => 'theme_lock',
				'value' => ( isset( $this->options['theme_lock'] ) ) ? $this->options['theme_lock'] : '',
				'label' => $args[0],
				'description' => __( 'This will prevent any changes to your book&rsquo;s appearance and page count when themes are updated.', 'pressbooks' ),
			]
		);
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
		return __( 'Export Settings', 'pressbooks' );
	}

	/**
	 * Get an array of default values for the export options page.
	 *
	 * @return array $defaults
	 */
	static function getDefaults() {
		return [
			'email_validation_logs' => 0,
			'theme_lock' => 0,
		];
	}

	/**
	 * Get an array of options which return booleans.
	 *
	 * @return array $options
	 */
	static function getBooleanOptions() {
		return [
			'email_validation_logs',
			'theme_lock',
		];
	}

	/**
	 * Filter the array of default values for this set of options
	 *
	 * @param array $defaults
	 *
	 * @return array $defaults
	 */
	static function filterDefaults( $defaults ) {
		return $defaults;
	}
}
