<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */
// TODO: Security audit
// @phpcs:disable Pressbooks.Security.EscapeOutput.OutputNotEscaped

namespace Pressbooks\Admin;

class PublishOptions extends \Pressbooks\Options {
	/**
	 * The value for option: pressbooks_ecommerce_links_version
	 *
	 * @see upgrade()
	 * @var int
	 */
	public const VERSION = 1;

	/**
	 * Publish defaults.
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
		$this->urls = static::getUrlOptions();

		foreach ( $this->defaults as $key => $value ) {
			if ( ! isset( $this->options[ $key ] ) ) {
				$this->options[ $key ] = $value;
			}
		}
	}

	/**
	 * Configure the publish options page using the settings API.
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
			'amazon',
			__( 'Amazon URL', 'pressbooks' ),
			[ $this, 'renderAmazonField' ],
			$_page,
			$_section,
			[ 'label_for' => 'amazon' ]
		);

		add_settings_field(
			'oreilly',
			__( 'O\'Reilly URL', 'pressbooks' ),
			[ $this, 'renderOReillyField' ],
			$_page,
			$_section,
			[ 'label_for' => 'oreilly' ]
		);

		add_settings_field(
			'barnesandnoble',
			__( 'Barnes and Noble URL', 'pressbooks' ),
			[ $this, 'renderBarnesAndNobleField' ],
			$_page,
			$_section,
			[ 'label_for' => 'barnesandnoble' ]
		);

		add_settings_field(
			'kobo',
			__( 'Kobo URL', 'pressbooks' ),
			[ $this, 'renderKoboField' ],
			$_page,
			$_section,
			[ 'label_for' => 'kobo' ]
		);

		add_settings_field(
			'applebooks',
			__( 'Apple Books URL', 'pressbooks' ),
			[ $this, 'renderAppleBooksField' ],
			$_page,
			$_section,
			[ 'label_for' => 'applebooks' ]
		);

		add_settings_field(
			'otherservice',
			__( 'Other Service URL', 'pressbooks' ),
			[ $this, 'renderOtherServiceField' ],
			$_page,
			$_section,
			[ 'label_for' => 'otherservice' ]
		);

		register_setting(
			$_page,
			$_option,
			[ $this, 'sanitize' ]
		);
	}

	/**
	 * Display the publish options page description.
	 */
	public function display() {
		ob_start(); ?>
		<h2><?php _e( 'Add BUY Links to Your Pressbooks Webbook', 'pressbooks' ); ?></h2>
		<p><?php _e( 'Enter the URLs for locations where your book can be purchased below. <a href="https://guide.pressbooks.com/chapter/publish/">Our guide</a> provides additional information about selling and distributing your book.', 'pressbooks' ); ?></p>

		<?php
		$output = ob_get_contents();
		ob_end_clean();

		/**
		* Filter the contents of the Dashboard Publish page.
		 *
		 * @since 4.3.0
		 */
		echo apply_filters(
			'pb_publish_page',
			/**
			 * Filter the contents of the Dashboard Publish page.
			 *
			 * @since 3.9.3
			 * @deprecated 4.3.0 Use pb_publish_page instead.
			 *
			 * @param string $output
			 */
			apply_filters( 'pressbooks_publish_page', $output )
		);
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
	}

	/**
	 * Render the amazon field.
	 */
	public function renderAmazonField() {
		static::renderField([
			'id' => 'amazon',
			'name' => static::getSlug(),
			'option' => 'amazon',
			'value' => $this->options['amazon'] ?? '',
			'type' => 'url',
			'class' => 'regular-text code',
		]);
	}

	/**
	 * Render the oreilly field.
	 */
	public function renderOReillyField() {
		static::renderField([
			'id' => 'oreilly',
			'name' => static::getSlug(),
			'option' => 'oreilly',
			'value' => $this->options['oreilly'] ?? '',
			'type' => 'url',
			'class' => 'regular-text code',
		]);
	}

	/**
	 * Render the barnesandnoble field.
	 */
	public function renderBarnesAndNobleField() {
		static::renderField([
			'id' => 'barnesandnoble',
			'name' => static::getSlug(),
			'option' => 'barnesandnoble',
			'value' => $this->options['barnesandnoble'] ?? '',
			'type' => 'url',
			'class' => 'regular-text code',
		]);
	}

	/**
	 * Render the barnesandnoble field.
	 */
	public function renderKoboField() {
		static::renderField([
			'id' => 'kobo',
			'name' => static::getSlug(),
			'option' => 'kobo',
			'value' => $this->options['kobo'] ?? '',
			'type' => 'url',
			'class' => 'regular-text code',
		]);
	}

	/**
	 * Render the Apple Books field.
	 */
	public function renderAppleBooksField() {
		static::renderField([
			'id' => 'applebooks',
			'name' => static::getSlug(),
			'option' => 'applebooks',
			'value' => $this->options['applebooks'] ?? '',
			'type' => 'url',
			'class' => 'regular-text code',
		]);
	}

	/**
	 * Render the other service field.
	 */
	public function renderOtherServiceField() {
		static::renderField([
			'id' => 'otherservice',
			'name' => static::getSlug(),
			'option' => 'otherservice',
			'value' => $this->options['otherservice'] ?? '',
			'type' => 'url',
			'class' => 'regular-text code',
		]);
	}

	/**
	 * Get the slug for the publish options page.
	 *
	 * @return string $slug
	 */
	public static function getSlug() {
		return 'pressbooks_ecommerce_links';
	}

	/**
	 * Get the localized title of the export options page.
	 *
	 * @return string $title
	 */
	public static function getTitle() {
		return __( 'Publish', 'pressbooks' );
	}

	/**
	 * Get an array of default values for the export options page.
	 *
	 * @return array $defaults
	 */
	public static function getDefaults() {
		return [
			'amazon' => '',
			'oreilly' => '',
			'barnesandnoble' => '',
			'kobo' => '',
			'ibooks' => '',
			'otherservice' => '',
		];
	}

	/**
	 * Get an array of options which return URLs.
	 *
	 * @return array $options
	 */
	public static function getUrlOptions() {
		return [
			'amazon',
			'oreilly',
			'barnesandnoble',
			'kobo',
			'applebooks',
			'otherservice',
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
