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
	public const VERSION = 1;

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
	public function __construct( public array $options ) {
		$this->defaults = static::getDefaults();
		$this->booleans = static::getBooleanOptions();

		foreach ( $this->defaults as $key => $value ) {
			if ( ! isset( $this->options[ $key ] ) ) {
				$this->options[ $key ] = $value;
			}
		}
	}

	/**
	 * Configure the export options page using the settings API.
	 */
	public function init() {
		$_option = static::getSlug();
		$_page = $_option;
		$_section = static::getSlug() . '_section';

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
	public function display() {
		echo '<p>' . __( 'Export settings.', 'pressbooks' ) . '</p>';
	}

	public function render() {
		?>
		<div class="wrap">
			<h1><?php echo static::getTitle(); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( static::getSlug() );
				do_settings_sections( static::getSlug() );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	public function upgrade( $version ) {
		if ( $version < 1 ) {
			$this->doInitialUpgrade();
		}
	}

	public function doInitialUpgrade() {
		$_option = static::getSlug();
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
	public function renderEmailValidationLogsField( $args ) {
		static::renderRadioButtons([
			'id' => 'email_validation_logs',
			'name' => static::getSlug(),
			'option' => 'email_validation_logs',
			'value' => $this->options['email_validation_logs'] ?? '',
			'choices' => $args,
		]);
	}

	/**
	 * Render the lock_theme checkbox.
	 *
	 * @param array $args
	 */
	public function renderThemeLockField( $args ) {
		static::renderCheckbox([
			'id' => 'theme_lock',
			'name' => static::getSlug(),
			'option' => 'theme_lock',
			'value' => $this->options['theme_lock'] ?? '',
			'label' => $args[0],
			'description' => __( 'This will prevent any changes to your book&rsquo;s appearance and page count when themes are updated.', 'pressbooks' ),
		]);
	}

	/**
	 * Get the slug for the export options page.
	 *
	 * @return string $slug
	 */
	public static function getSlug() {
		return 'pressbooks_export_options';
	}

	/**
	 * Get the localized title of the export options page.
	 *
	 * @return string $title
	 */
	public static function getTitle() {
		return __( 'Export Settings', 'pressbooks' );
	}

	/**
	 * Get an array of default values for the export options page.
	 *
	 * @return array $defaults
	 */
	public static function getDefaults() {
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
	public static function getBooleanOptions() {
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
	public static function filterDefaults( $defaults ) {
		return $defaults;
	}
}
