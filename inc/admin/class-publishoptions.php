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
	const VERSION = 1;

	/**
	 * Publish options.
	 *
	 * @var array
	 */
	public $options;

	/**
	 * Publish defaults.
	 *
	 * @var array
	 */
	public $defaults;

	/**
	 * Publish URLs.
	 *
	 * @var array
	 */
	public $urls;

	/**
	 * Constructor.
	 *
	 * @param array $options
	 */
	function __construct( array $options ) {
		$this->options = $options;
		$this->defaults = $this->getDefaults();
		$this->urls = $this->getUrlOptions();

		foreach ( $this->defaults as $key => $value ) {
			if ( ! isset( $this->options[ $key ] ) ) {
				$this->options[ $key ] = $value;
			}
		}
	}

	/**
	 * Configure the publish options page using the settings API.
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

		$fields = [
			'amazon'         => __( 'Amazon URL', 'pressbooks' ),
			'barnesandnoble' => __( 'Barnes and Noble URL', 'pressbooks' ),
			'kobo'           => __( 'Rakuten Kobo URL', 'pressbooks' ),
			'applebooks'     => __( 'Apple Books URL', 'pressbooks' ),
			'otherservice'   => __( 'Other Service URL', 'pressbooks' ),
		];

		foreach ( $fields as $id => $label ) {
			add_settings_field(
				$id,
				$label,
				function() use ( $id ) {
					$this->renderPublisherUrlField( $id );
				},
				$_page,
				$_section,
				[ 'label_for' => $id ]
			);
		}

		register_setting(
			$_page,
			$_option,
			[ $this, 'sanitize' ]
		);
	}

	/**
	 * Display the publish options page description.
	 */
	function display() {
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
	}

	private function renderPublisherUrlField( string $id ): void {
		$this->renderField([
			'id'    => $id,
			'name'  => $this->getSlug(),
			'option' => $id,
			'value' => isset( $this->options[ $id ] ) ? esc_url( $this->options[ $id ] ) : '',
			'type'  => 'url',
			'class' => 'regular-text code',
		]);
	}

	/**
	 * Get the slug for the publish options page.
	 *
	 * @return string $slug
	 */
	static function getSlug() {
		return 'pressbooks_ecommerce_links';
	}

	/**
	 * Get the localized title of the export options page.
	 *
	 * @return string $title
	 */
	static function getTitle() {
		return __( 'Publish', 'pressbooks' );
	}

	/**
	 * Get an array of default values for the export options page.
	 *
	 * @return array $defaults
	 */
	static function getDefaults() {
		return [
			'amazon' => '',
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
	static function getUrlOptions() {
		return [
			'amazon',
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
	static function filterDefaults( $defaults ) {
		return $defaults;
	}
}
