<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks;

class GoogleAnalytics {

	public const VERSION_3 = 3;

	public const VERSION_4 = 4;

	/**
	 * @var null|GoogleAnalytics
	 */
	protected static ?GoogleAnalytics $instance = null;

	public static string $is_allowed_option = 'ga_mu_site_specific_allowed';

	private array $versions_settings = [
		3 => [
			'option' => 'ga_mu_uaid',
		],
		4 => [
			'option' => 'ga_4_mu_uaid',
		],
	];

	private string $network_page = 'pb_network_analytics';

	private string $book_page = 'pb_analytics';

	private string $menu_slug = 'pb_analytics';

	public function __construct() {
		$this->versions_settings[ self::VERSION_3 ]['input_label'] = __( 'Google Analytics UA ID', 'pressbooks' );
		$this->versions_settings[ self::VERSION_4 ]['input_label'] = __( 'Google Analytics 4 ID', 'pressbooks' );
		$this->versions_settings[ self::VERSION_3 ]['input_legend'] = __( 'The Google Analytics UA ID for your network, &lsquo;UA-01234567-8&rsquo;.
			Google will <a href=\'https://support.google.com/analytics/answer/11583528\' target=\'_blank\'>stop processing data</a>
			for sites which use UA on July 1, 2023.', 'pressbooks' );
		$this->versions_settings[ self::VERSION_4 ]['input_legend'] = __( 'The Google Analytics 4 ID for your network, e.g &lsquo;G-A123B4C5DE6&rsquo;.', 'pressbooks' );}

	public function getGoogleIDSiteOption( int $version, bool $for_book ): false|string {
		if ( ! isset( $this->versions_settings[ $version ] ) ) {
			return false;
		}
		return ! $for_book ?
			get_site_option( $this->versions_settings[ $version ]['option'] ) :
			get_option( $this->versions_settings[ $version ]['option'] );
	}

	public function getInputLabel( int $version ): string {
		return $this->versions_settings[ $version ] ? $this->versions_settings[ $version ]['input_label'] : '';
	}

	public function getInputLegend( int $version ): string {
		return $this->versions_settings[ $version ] ? $this->versions_settings[ $version ]['input_legend'] : '';
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
			$this->versions_settings[ self::VERSION_3 ]['option'],
			$this->versions_settings[ self::VERSION_3 ]['input_label'],
			[ $this, 'analyticsInputCallback' ],
			$this->network_page,
			$section,
			[
				'legend' => $this->versions_settings[ self::VERSION_3 ]['input_legend'],
				'version' => self::VERSION_3,
				'for_book' => false,
			]
		);
		add_settings_field(
			$this->versions_settings[ self::VERSION_4 ]['option'],
			$this->versions_settings[ self::VERSION_4 ]['input_label'],
			[ $this, 'analyticsInputCallback' ],
			$this->network_page,
			$section,
			[
				'legend' => $this->versions_settings[ self::VERSION_4 ]['input_legend'],
				'version' => self::VERSION_4,
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
			$this->versions_settings[ self::VERSION_3 ]['option'],
			[
				'type' => 'string',
				'default' => '',
			]
		);
		register_setting(
			$this->network_page,
			$this->versions_settings[ self::VERSION_4 ]['option'],
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
			$this->versions_settings[ self::VERSION_3 ]['option'],
			[
				'type' => 'string',
				'default' => '',
			]
		);
		register_setting(
			$this->book_page,
			$this->versions_settings[ self::VERSION_4 ]['option'],
			[
				'type' => 'string',
				'default' => '',
			]
		);
	}

	private function addBookSettingsFields(): void {
		$section = 'analytics_settings_section';

		add_settings_field(
			$this->versions_settings[ self::VERSION_3 ]['option'],
			$this->versions_settings[ self::VERSION_3 ]['input_label'],
			[ $this, 'analyticsInputCallback' ],
			$this->book_page,
			$section,
			[
				'legend' => $this->versions_settings[ self::VERSION_3 ]['input_legend'],
				'version' => self::VERSION_3,
				'for_book' => true,
			]
		);
		add_settings_field(
			$this->versions_settings[ self::VERSION_4 ]['option'],
			$this->versions_settings[ self::VERSION_4 ]['input_label'],
			[ $this, 'analyticsInputCallback' ],
			$this->book_page,
			$section,
			[
				'legend' => $this->versions_settings[ self::VERSION_4 ]['input_legend'],
				'version' => self::VERSION_4,
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
		$option = $this->getGoogleIDSiteOption( $args['version'], $args['for_book'] );
		$html = '<input type="text" id="ga_' . $args['version'] . '" name="ga_' . $args['version'] . '" value="' . $option . '" />';
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
					$this->saveNetworkIDOption( 'ga_3', 3 );
					$this->saveNetworkIDOption( 'ga_4', 4 );

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
	 * @param int $version
	 * @return void
	 */
	public function saveNetworkIDOption( string $request_key, int $version ): void {
		if ( ! isset( $_REQUEST[ $request_key ] ) || ! isset( $this->versions_settings[ $version ] ) ) {
			return;
		}
		empty( $_REQUEST[ $request_key ] ) ?
			delete_site_option( $this->versions_settings[ $version ]['option'] ) :
			update_site_option(
				$this->versions_settings[ $version ]['option'],
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
		$network_google_v3_code = self::getGoogleIDSiteOption( 3, false );
		if ( ! empty( $network_google_v3_code ) ) {
			$book_google_v3_code = get_site_option( self::$is_allowed_option ) ?
				$this->getGoogleIDSiteOption( 3, true ) : '';

			$this->printV3Scripts( $network_google_v3_code, $book_google_v3_code );
		}

		$network_google_v4_code = $this->getGoogleIDSiteOption( 4, false );
		if ( ! empty( $network_google_v4_code ) ) {
			$book_google_v4_code = get_site_option( self::$is_allowed_option ) ?
				$this->getGoogleIDSiteOption( 4, true ) : '';

			$this->printV4Scripts( $network_google_v4_code, $book_google_v4_code );
		}

	}

	private function printV3Scripts( string $network_google_v3_code, string $book_google_v3_code ): void {
		$tracking_html = '';
		if ( ! empty( $network_google_v3_code ) ) {
			$tracking_html = "ga('create', '{$network_google_v3_code}', 'auto');\n";
			$tracking_html .= "ga('send', 'pageview');\n";
		}

		$tracking_html .= self::getEcommerceTracking();

		if ( ! empty( $book_google_v3_code ) && Book::isBook() ) {
			if ( is_subdomain_install() || defined( 'WP_TESTS_MULTISITE' ) ) {
				$tracking_html .= "ga('create', '{$book_google_v3_code}', 'auto', 'bookTracker');\n";
			} else {
				$path = trailingslashit( parse_url( home_url(), PHP_URL_PATH ) );
				$tracking_html .= "ga('create', '{$book_google_v3_code}', 'auto', 'bookTracker', {'cookiePath': '{$path}'});\n";
			}
			$tracking_html .= "ga('bookTracker.send', 'pageview');\n";
		}
		$html = '';
		if ( ! empty( $tracking_html ) ) {
			$html .= "<!-- Google Analytics -->\n<script>\n(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)})(window,document,'script','https://www.google-analytics.com/analytics.js','ga');\n";
			$html .= $tracking_html;
			$html .= "</script>\n<!-- End Google Analytics -->";
		}
		echo $html;
	}

	private static function printV4Scripts( string $network_google_v4_code, string $book_google_v4_code ): void {
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
