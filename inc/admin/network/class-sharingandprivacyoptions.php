<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Admin\Network;

class SharingAndPrivacyOptions extends \Pressbooks\Options {
	/**
	 * The value for *site* option: pressbooks_sharingandprivacy_options_version
	 *
	 * @see upgrade()
	 * @var int
	 */
	const VERSION = 4;

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
	 * Configure the network export options page using the settings API.
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
			'allow_redistribution',
			__( 'Allow Redistribution', 'pressbooks' ),
			[ $this, 'renderAllowRedistributionField' ],
			$_page,
			$_section,
			[
				__( 'Allow book administrators to enable redistribution of export files.', 'pressbooks' ),
			]
		);

		add_settings_field(
			'enable_network_api',
			__( 'Enable network API', 'pressbooks' ),
			[ $this, 'renderAllowRootApi' ],
			$_page,
			$_section,
			[
				__( 'Enable access to your network of books via the Pressbooks REST API.', 'pressbooks' ),
			]
		);

		add_settings_field(
			'enable_cloning',
			__( 'Enable Cloning', 'pressbooks' ),
			[ $this, 'renderAllowCloning' ],
			$_page,
			$_section,
			[
				__( 'Enable book cloning via the Pressbooks REST API.', 'pressbooks' ),
			]
		);

		add_settings_field(
			'enable_thincc_weblinks',
			__( 'Enable CC with Weblinks', 'pressbooks' ),
			[ $this, 'renderAllowThinCcWeblinks' ],
			$_page,
			$_section,
			[
				__( 'Allow users to produce Common Cartridge exports with simple Web Links.', 'pressbooks' ),
			]
		);

		register_setting(
			$_page,
			$_option,
			[ $this, 'sanitize' ]
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
			<?php
			$nonce = ( ! empty( $_REQUEST['_wpnonce'] ) ) ? $_REQUEST['_wpnonce'] : '';
			if ( ! empty( $_POST ) ) {
				if ( ! wp_verify_nonce( $nonce, $_option . '-options' ) ) {
					wp_die( 'Security check' );
				} else {
					if ( isset( $_REQUEST[ $_option ] ) ) {
						$options = $this->sanitize( $_REQUEST[ $_option ] );
					} else {
						$options = $this->sanitize( [] ); // Get sanitized defaults
					}
					update_site_option( $_option, $options );
					?>
					<div id="message" role="status" class="updated notice is-dismissible"><p><strong><?php _e( 'Settings saved.', 'pressbooks' ); ?></strong></div>
					<?php
				}
			}
			?>
			<form method="post" action="">
				<?php
				settings_fields( $this->getSlug() );
				do_settings_sections( $this->getSlug() );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * @param int $version
	 */
	function upgrade( $version ) {

		$slug = $this->getSlug();
		$options = get_site_option( $slug, [] );

		if ( $version < 2 ) {
			$options['enable_network_api'] = 1;
		}

		if ( $version < 3 ) {
			$options['enable_cloning'] = 1;
		}

		if ( $version < 4 ) {
			$options['enable_thincc_weblinks'] = 1;
		}

		update_site_option( $slug, $options );
	}

	/**
	 * Render the allow_redistribution checkbox.
	 *
	 * @param array $args
	 */
	function renderAllowRedistributionField( $args ) {
		$options = get_site_option( $this->getSlug() );
		$this->renderCheckbox(
			[
				'id' => 'allow_redistribution',
				'name' => $this->getSlug(),
				'option' => 'allow_redistribution',
				'value' => ( isset( $options['allow_redistribution'] ) ) ? $options['allow_redistribution'] : '',
				'label' => $args[0],
			]
		);
	}

	/**
	 * Render the enable_network_api checkbox.
	 *
	 * @param array $args
	 */
	function renderAllowRootApi( $args ) {
		$options = get_site_option( $this->getSlug() );
		$this->renderCheckbox(
			[
				'id' => 'enable_network_api',
				'name' => $this->getSlug(),
				'option' => 'enable_network_api',
				'value' => ( isset( $options['enable_network_api'] ) ) ? $options['enable_network_api'] : '',
				'label' => $args[0],
			]
		);
	}

	/**
	 * Render the enable_cloning checkbox.
	 *
	 * @param array $args
	 */
	function renderAllowCloning( $args ) {
		$options = get_site_option( $this->getSlug() );
		$this->renderCheckbox(
			[
				'id' => 'enable_cloning',
				'name' => $this->getSlug(),
				'option' => 'enable_cloning',
				'value' => ( isset( $options['enable_cloning'] ) ) ? $options['enable_cloning'] : '',
				'label' => $args[0],
			]
		);
	}

	/**
	 * Render the enable_thincc_weblinks checkbox.
	 *
	 * @param array $args
	 */
	function renderAllowThinCcWeblinks( $args ) {
		$options = get_site_option( $this->getSlug() );
		$this->renderCheckbox(
			[
				'id' => 'enable_thincc_weblinks',
				'name' => $this->getSlug(),
				'option' => 'enable_thincc_weblinks',
				'value' => ( isset( $options['enable_thincc_weblinks'] ) ) ? $options['enable_thincc_weblinks'] : '',
				'label' => $args[0],
			]
		);
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
		return __( 'Sharing and Privacy Settings', 'pressbooks' );
	}

	/**
	 * Get an array of default values for the network export options page.
	 *
	 * @return array $defaults
	 */
	static function getDefaults() {
		return [
			'allow_redistribution' => 0,
			'enable_network_api' => 1,
			'enable_cloning' => 1,
			'enable_thincc_weblinks' => 1,
		];
	}

	/**
	 * Get an array of options which return booleans.
	 *
	 * @return array $options
	 */
	static function getBooleanOptions() {
		return [
			'allow_redistribution',
			'enable_network_api',
			'enable_cloning',
			'enable_thincc_weblinks',
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
