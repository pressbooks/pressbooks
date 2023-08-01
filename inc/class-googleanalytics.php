<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks;

class GoogleAnalytics {

	/**
	 * @var null|GoogleAnalytics
	 */
	protected static ?GoogleAnalytics $instance = null;

	public static string $is_allowed_option = 'ga_mu_site_specific_allowed';

	private array $settings = [ 'option' => 'ga_4_mu_uaid' ];

	private string $network_page = 'pb_network_analytics';

	private string $book_page = 'pb_analytics';

	private string $menu_slug = 'pb_analytics';

	public function __construct() {
		$this->settings['input_label'] = __( 'Google Analytics ID', 'pressbooks' );
		$this->settings['input_legend'] = __( 'The Google Analytics ID for your network, e.g &lsquo;G-A123B4C5DE6&rsquo;.', 'pressbooks' );}

	public function getGoogleIDSiteOption( bool $for_book_context ): false|string {
		return ! $for_book_context ?
			get_site_option( $this->settings['option'] ) :
			get_option( $this->settings['option'] );
	}

	public function getInputLabel(): string {
		return $this->settings['input_label'];
	}

	public function getInputLegend(): string {
		return $this->settings['input_legend'];
	}

	public static function init(): GoogleAnalytics {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			self::hooks( self::$instance );
		}

		return self::$instance;
	}

	public static function hooks( GoogleAnalytics $obj ): void {
		if ( is_admin() ) {
			add_action( 'network_admin_menu', [ $obj, 'addNetworkMenu' ] );
			add_action( 'admin_init', [ $obj, 'networkAnalyticsSettingsInit' ] );
			if ( Book::isBook() && get_site_option( $obj::$is_allowed_option ) ) {
				add_action( 'admin_menu', [ $obj, 'addBookMenu' ] );
				add_action( 'admin_init', [ $obj, 'bookAnalyticsSettingsInit' ] );
			}
			add_action( 'admin_head', [ $obj, 'printScripts' ] );
		}
		add_action( 'wp_head', [ $obj, 'printScripts' ] );
	}

	public function addNetworkMenu(): void {
		add_submenu_page(
			'settings.php',
			__( 'Google Analytics', 'pressbooks' ),
			__( 'Google Analytics', 'pressbooks' ),
			'manage_network_options',
			$this->menu_slug,
			[ $this, 'displayNetworkAnalyticsSettings' ]
		);
	}

	public function addBookMenu(): void {
		add_options_page(
			__( 'Google Analytics', 'pressbooks' ),
			__( 'Google Analytics', 'pressbooks' ),
			'manage_options',
			$this->menu_slug,
			[ $this, 'displayBookAnalyticsSettings' ]
		);
	}

	public function networkAnalyticsSettingsInit(): void {
		$section = 'network_analytics_settings_section';

		add_settings_section(
			$section,
			'',
			[ $this, 'analyticsSettingsSectionCallback' ],
			$this->network_page
		);

		$this->addNetworkSettingsFields();
		$this->registerNetworkSettings();
	}

	private function addNetworkSettingsFields(): void {
		$section = 'network_analytics_settings_section';

		add_settings_field(
			$this->settings['option'],
			$this->settings['input_label'],
			[ $this, 'analyticsInputCallback' ],
			$this->network_page,
			$section,
			[
				'legend' => $this->settings['input_legend'],
				'for_book' => false,
			]
		);
		if ( is_subdomain_install() || defined( 'WP_TESTS_MULTISITE' ) ) {
			add_settings_field(
				self::$is_allowed_option,
				__( 'Site-Specific Tracking', 'pressbooks' ),
				[ $this, 'analyticsBooksAllowedCallback' ],
				$this->network_page,
				$section,
				[
					__( 'If enabled, the Google Analytics settings page will be visible to book administrators, allowing them to use their own Google Analytics accounts to track statistics at the book level.', 'pressbooks' ),
				]
			);
		}
	}

	private function registerNetworkSettings(): void {
		register_setting(
			$this->network_page,
			$this->settings['option'],
			[
				'type' => 'string',
				'default' => '',
			]
		);
		if ( is_subdomain_install() || defined( 'WP_TESTS_MULTISITE' ) ) {
			register_setting(
				$this->network_page,
				self::$is_allowed_option,
				[
					'type' => 'boolean',
					'default' => false,
				]
			);
		}
	}

	public function bookAnalyticsSettingsInit(): void {
		$section = 'analytics_settings_section';

		add_settings_section(
			$section,
			'',
			[ $this, 'analyticsSettingsSectionCallback' ],
			$this->book_page
		);

		$this->addBookSettingsFields();
		$this->registerBookSettings();
	}

	private function registerBookSettings(): void {
		register_setting(
			$this->book_page,
			$this->settings['option'],
			[
				'type' => 'string',
				'default' => '',
			]
		);
	}

	private function addBookSettingsFields(): void {
		$section = 'analytics_settings_section';

		add_settings_field(
			$this->settings['option'],
			$this->settings['input_label'],
			[ $this, 'analyticsInputCallback' ],
			$this->book_page,
			$section,
			[
				'legend' => $this->settings['input_legend'],
				'for_book' => true,
			]
		);

	}

	 // @phpcs:disable Pressbooks.Security.EscapeOutput.OutputNotEscaped
	 // @phpcs:disable Pressbooks.Security.ValidatedSanitizedInput.InputNotSanitized
	 // @phpcs:disable Pressbooks.Security.ValidatedSanitizedInput.MissingUnslash

	public function analyticsSettingsSectionCallback(): void {
		echo '<p>' . __( 'Google Analytics settings.', 'pressbooks' ) . '</p>';
	}

	public function analyticsInputCallback( array $args ): void {
		$option = $this->getGoogleIDSiteOption( for_book_context: $args['for_book'] );
		$html = '<input type="text" id="ga_4" name="ga_4" value="' . $option . '" />';
		$html .= '<p class="description">' . $args['legend'] . '</p>';
		echo $html;
	}

	public function analyticsBooksAllowedCallback( array $args ): void {
		$ga_mu_site_specific_allowed = get_site_option( self::$is_allowed_option );
		$html = '<input type="checkbox" id="' . self::$is_allowed_option . '" name="' . self::$is_allowed_option . '" value="1"' . checked( $ga_mu_site_specific_allowed, '1', false ) . '/>';
		$html .= '<p class="description">' . $args[0] . '</p>';
		echo $html;
	}

	public function displayNetworkAnalyticsSettings(): void {
		?>
		<div class="wrap">
			<h2><?php _e( 'Google Analytics', 'pressbooks' ); ?></h2>
			<?php
			$nonce = ( ! empty( $_REQUEST['_wpnonce'] ) ) ? $_REQUEST['_wpnonce'] : ''; // @codingStandardsIgnoreLine
			if ( ! empty( $_POST ) ) {
				if ( ! wp_verify_nonce( $nonce, 'pb_network_analytics-options' ) ) {
					wp_die( 'Security check' );
				} else {
					$this->saveNetworkIDOption( 'ga_4' );

					empty( $_REQUEST[ self::$is_allowed_option ] ) ?
							delete_site_option( self::$is_allowed_option ) :
							update_site_option( self::$is_allowed_option, true );
					?>
					<div id="message" role="status" class="updated notice is-dismissible"><p><strong><?php _e( 'Settings saved.', 'pressbooks' ); ?></strong></div>
					<?php
				}
			}
			?>
			<form method="POST" action="">
				<?php
				settings_fields( $this->network_page );
				do_settings_sections( $this->network_page );
				?>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Save Network Google Analytics ID options by $_REQUEST key
	 *
	 * @param string $request_key
	 * @return void
	 */
	public function saveNetworkIDOption( string $request_key ): void {
		if ( ! isset( $_REQUEST[ $request_key ] ) ) {
			return;
		}
		empty( $_REQUEST[ $request_key ] ) ?
			delete_site_option( $this->settings['option'] ) :
			update_site_option(
				$this->settings['option'],
				sanitize_text_field( $_REQUEST[ $request_key ] )
			);
	}

	public function displayBookAnalyticsSettings(): void {
		?>
		<div class="wrap">
			<h2><?php _e( 'Google Analytics', 'pressbooks' ); ?></h2>
			<form method="POST" action="options.php">
				<?php
				settings_fields( $this->book_page );
				do_settings_sections( $this->book_page );
				?>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	public function printScripts(): void {
		$network_google_code = $this->getGoogleIDSiteOption( for_book_context: false );
		if ( ! empty( $network_google_code ) ) {
			$book_google_code = get_site_option( self::$is_allowed_option ) ?
				$this->getGoogleIDSiteOption( for_book_context: true ) : '';

			$this->printScript( $network_google_code, $book_google_code );
		}

	}

	private static function printScript( string $network_google_v4_code, string $book_google_v4_code ): void {
		$tracking_html = '';

		if ( ! empty( $network_google_v4_code ) ) {
			$tracking_html .= "gtag('config', '{$network_google_v4_code}');\n";
		}

		$tracking_html .= self::getEcommerceTracking();

		if ( ! empty( $book_google_v4_code ) && Book::isBook() ) {
			if ( is_subdomain_install() || defined( 'WP_TESTS_MULTISITE' ) ) {
				$tracking_book_html = "gtag('config', '{$book_google_v4_code}', {'send_page_view': false});\n";
			} else {
				$path = trailingslashit( parse_url( home_url(), PHP_URL_PATH ) );
				$tracking_book_html = "gtag('config', '{$book_google_v4_code}', {'cookie_path': '{$path}', 'send_page_view': false});\n";
			}
		}
		$html = '';
		if ( ! empty( $tracking_html ) ) {
			$html .= self::getJSWrapper( $network_google_v4_code, $tracking_html );
		}
		if ( ! empty( $tracking_book_html ) ) {
			$html .= self::getJSWrapper( $book_google_v4_code, $tracking_book_html );
		}
		echo $html;
	}

	private static function getEcommerceTracking(): string {
		$ecommerce_tracking = apply_filters( 'pb_ecommerce_tracking', '' );
		return ! empty( $ecommerce_tracking ) ? $ecommerce_tracking : '';
	}

	private static function getJSWrapper( string $ga_v4_code, string $ga_config ): string {
		$ga_js_wrapper = "<!-- Google Analytics -->\n<script async src=\"https://www.googletagmanager.com/gtag/js?id={$ga_v4_code}\"></script>\n<script>\n";
		$ga_js_wrapper .= "window.dataLayer = window.dataLayer || [];\n";
		$ga_js_wrapper .= "function gtag(){dataLayer.push(arguments);}\n";
		$ga_js_wrapper .= "gtag('js', new Date());\n";
		$ga_js_wrapper .= $ga_config;
		$ga_js_wrapper .= "</script>\n<!-- End Google Analytics -->";
		return $ga_js_wrapper;
	}
}
