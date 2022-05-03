<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */
// TODO: Security audit
// @phpcs:disable Pressbooks.Security.EscapeOutput.OutputNotEscaped
// @phpcs:disable Pressbooks.Security.ValidatedSanitizedInput.InputNotSanitized
// @phpcs:disable Pressbooks.Security.ValidatedSanitizedInput.MissingUnslash

namespace Pressbooks\Admin\Analytics;

function add_network_menu() {
	add_submenu_page(
		'settings.php',
		__( 'Google Analytics', 'pressbooks' ),
		__( 'Google Analytics', 'pressbooks' ),
		'manage_network_options',
		'pb_analytics',
		__NAMESPACE__ . '\display_network_analytics_settings'
	);
}

function add_book_menu() {
	add_options_page(
		__( 'Google Analytics', 'pressbooks' ),
		__( 'Google Analytics', 'pressbooks' ),
		'manage_options',
		'pb_analytics',
		__NAMESPACE__ . '\display_book_analytics_settings'
	);
}

/**
 * Analytics settings initialization (network level)
 */
function network_analytics_settings_init() {
	$_section = 'network_analytics_settings_section';
	$_page = 'pb_network_analytics';

	// Network
	add_settings_section(
		$_section,
		'',
		__NAMESPACE__ . '\analytics_settings_section_callback',
		$_page
	);
	add_settings_field(
		'ga_mu_uaid',
		__( 'Google Analytics ID', 'pressbooks' ),
		__NAMESPACE__ . '\analytics_network_callback',
		$_page,
		$_section,
		[
			__( 'The Google Analytics ID for your network, e.g. &lsquo;UA-01234567-8&rsquo;.', 'pressbooks' ),
		]
	);
	register_setting(
		$_page,
		'ga_mu_uaid',
		[
			'type' => 'string',
			'default' => '',
		]
	);

	// Are books allowed?
	// TODO: https://developers.google.com/analytics/devguides/collection/upgrade/reference/gajs-analyticsjs#cookiepath
	// TODO: https://core.trac.wordpress.org/ticket/42093
	//  if ( is_subdomain_install() || defined( 'WP_TESTS_MULTISITE' ) ) {
		add_settings_field(
			'ga_mu_site_specific_allowed',
			__( 'Site-Specific Tracking', 'pressbooks' ),
			__NAMESPACE__ . '\analytics_books_allowed_callback',
			$_page,
			$_section,
			[
				__( 'If enabled, the Google Analytics settings page will be visible to book administrators, allowing them to use their own Google Analytics accounts to track statistics at the book level.', 'pressbooks' ),
			]
		);
		register_setting(
			$_page,
			'ga_mu_site_specific_allowed',
			[
				'type' => 'boolean',
				'default' => false,
			]
		);
	//  }
}

/**
 * Analytics settings initialization (book level)
 */
function book_analytics_settings_init() {
	$_section = 'analytics_settings_section';
	$_page = 'pb_analytics';
	add_settings_section(
		$_section,
		'',
		__NAMESPACE__ . '\analytics_settings_section_callback',
		$_page
	);
	add_settings_field(
		'ga_mu_uaid',
		__( 'Google Analytics ID', 'pressbooks' ),
		__NAMESPACE__ . '\analytics_book_callback',
		$_page,
		$_section,
		[
			__( 'The Google Analytics ID for your book, e.g. &lsquo;UA-01234567-8&rsquo;.', 'pressbooks' ),
		]
	);
	register_setting(
		$_page,
		'ga_mu_uaid',
		[
			'type' => 'string',
			'default' => '',
		]
	);
}

/**
 * Analytics settings section callback
 */
function analytics_settings_section_callback() {
	echo '<p>' . __( 'Google Analytics settings.', 'pressbooks' ) . '</p>';
}

/**
 * @param $args
 */
function analytics_book_callback( $args ) {
	$ga_mu_uaid = get_option( 'ga_mu_uaid' );
	$html = '<input type="text" id="ga_mu_uaid" name="ga_mu_uaid" value="' . $ga_mu_uaid . '" />';
	$html .= '<p class="description">' . $args[0] . '</p>';
	echo $html;
}

/**
 * @param $args
 */
function analytics_network_callback( $args ) {
	$ga_mu_uaid = get_site_option( 'ga_mu_uaid' );
	$html = '<input type="text" id="ga_mu_uaid" name="ga_mu_uaid" value="' . $ga_mu_uaid . '" />';
	$html .= '<p class="description">' . $args[0] . '</p>';
	echo $html;
}

/**
 * Analytics settings, ga_mu_site_specific_allowed field callback
 *
 * @param $args
 */
function analytics_books_allowed_callback( $args ) {
	$ga_mu_site_specific_allowed = get_site_option( 'ga_mu_site_specific_allowed' );
	$html = '<input type="checkbox" id="ga_mu_site_specific_allowed" name="ga_mu_site_specific_allowed" value="1"' . checked( $ga_mu_site_specific_allowed, '1', false ) . '/>';
	$html .= '<p class="description">' . $args[0] . '</p>';
	echo $html;
}

/**
 * Display Analytics settings (network)
 */
function display_network_analytics_settings() {
	?>
	<div class="wrap">
		<h2><?php _e( 'Google Analytics', 'pressbooks' ); ?></h2>
		<?php
		$nonce = ( ! empty( $_REQUEST['_wpnonce'] ) ) ? $_REQUEST['_wpnonce'] : ''; // @codingStandardsIgnoreLine
		if ( ! empty( $_POST ) ) {
			if ( ! wp_verify_nonce( $nonce, 'pb_network_analytics-options' ) ) {
				wp_die( 'Security check' );
			} else {
				if ( ! empty( $_REQUEST['ga_mu_uaid'] ) ) {
					update_site_option( 'ga_mu_uaid', $_REQUEST['ga_mu_uaid'] );
				} else {
					delete_site_option( 'ga_mu_uaid' );
				}
				if ( ! empty( $_REQUEST['ga_mu_site_specific_allowed'] ) ) {
					update_site_option( 'ga_mu_site_specific_allowed', true );
				} else {
					delete_site_option( 'ga_mu_site_specific_allowed' );
				}
				?>
				<div id="message" role="status" class="updated notice is-dismissible"><p><strong><?php _e( 'Settings saved.', 'pressbooks' ); ?></strong></div>
				<?php
			}
		}
		?>
		<form method="POST" action="">
			<?php
			settings_fields( 'pb_network_analytics' );
			do_settings_sections( 'pb_network_analytics' );
			?>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}

/**
 * Display Analytics settings (book)
 */
function display_book_analytics_settings() {
	?>
	<div class="wrap">
		<h2><?php _e( 'Google Analytics', 'pressbooks' ); ?></h2>
		<form method="POST" action="options.php">
			<?php
			settings_fields( 'pb_analytics' );
			do_settings_sections( 'pb_analytics' );
			?>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}

/**
 * Print the script.
 */
function print_admin_analytics() {
	\Pressbooks\Analytics\print_analytics();
}
