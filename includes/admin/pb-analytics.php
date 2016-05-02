<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */
namespace Pressbooks\Admin\Analytics;

/**
 *
 */
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

/**
 *
 */
function add_menu() {
	add_options_page(
		__( 'Google Analytics', 'pressbooks' ),
		__( 'Google Analytics', 'pressbooks' ),
		'manage_options',
		'pb_analytics',
		__NAMESPACE__ . '\display_analytics_settings'
	);
}

/**
 * Analytics settings initialization (network level)
 */
function network_analytics_settings_init() {
	$_section = 'network_analytics_settings_section';
	$_page = 'pb_network_analytics';
	add_settings_section(
		$_section,
		'',
		__NAMESPACE__ . '\analytics_settings_section_callback',
		$_page
	);
	add_settings_field(
		'ga_mu_uaid',
		__( 'Google Analytics ID', 'pressbooks' ),
		__NAMESPACE__ . '\analytics_ga_mu_uaid_callback',
		$_page,
		$_section,
		array(
			__( 'The Google Analytics ID for your network, e.g. &lsquo;UA-01234567-8&rsquo;.', 'pressbooks' )
		)
	);
	register_setting(
		$_page,
		'ga_mu_uaid',
		__NAMESPACE__ . '\analytics_ga_mu_uaid_sanitize'
	);
	if ( SUBDOMAIN_INSTALL == true ) :
		add_settings_field(
			'ga_mu_site_specific_allowed',
			__( 'Site-Specific Tracking', 'pressbooks' ),
			__NAMESPACE__ . '\analytics_ga_mu_site_specific_allowed_callback',
			$_page,
			$_section,
			array(
				__( 'If enabled, the Google Analytics settings page will be visible to book administrators, allowing them to use their own Google Analytics accounts to track statistics at the book level.', 'pressbooks' )
			)
		);
		register_setting(
			$_page,
			'ga_mu_site_specific_allowed',
			__NAMESPACE__ . '\analytics_ga_mu_site_specific_allowed_sanitize'
		);

	endif;
}

/**
 * Analytics settings initialization (book level)
 */
function analytics_settings_init() {
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
		__NAMESPACE__ . '\analytics_ga_mu_uaid_callback',
		$_page,
		$_section,
		array(
			__( 'The Google Analytics ID for your book, e.g. &lsquo;UA-01234567-8&rsquo;.', 'pressbooks' )
		)
	);
	register_setting(
		$_page,
		'ga_mu_uaid',
		__NAMESPACE__ . '\analytics_ga_mu_uaid_sanitize'
	);
}

/**
 * Analytics settings section callback
 */
function analytics_settings_section_callback() {
	echo '<p>' . __( 'Google Analytics settings.', 'pressbooks' ) . '</p>';
}

/**
 * Analytics settings, ga_mu_uaid field callback
 *
 * @param $args
 */
function analytics_ga_mu_uaid_callback( $args ) {
	$ga_mu_uaid = get_option( 'ga_mu_uaid' );
	$html = '<input type="text" id="ga_mu_uaid" name="ga_mu_uaid" value="' . $ga_mu_uaid . '" />';
	$html .= '<p class="description">' . $args[0] . '</p>';
	echo $html;
}

/**
 * Analytics settings, ga_mu_site_specific_allowed field callback
 *
 * @param $args
 */
function analytics_ga_mu_site_specific_allowed_callback( $args ) {
	$ga_mu_site_specific_allowed = get_option( 'ga_mu_site_specific_allowed' );
	$html = '<input type="checkbox" id="ga_mu_site_specific_allowed" name="ga_mu_site_specific_allowed" value="1"' . checked( $ga_mu_site_specific_allowed, '1', false ) . '/>';
	$html .= '<p class="description">' . $args[0] . '</p>';
	echo $html;
}

/**
 * Analytics settings, ga_mu_uaid field sanitization
 *
 * @param $input
 * @return string
 */
function analytics_ga_mu_uaid_sanitize( $input ) {
	return sanitize_text_field( $input );
}

/**
 * Analytics settings, ga_mu_site_specific_allowed field sanitization
 *
 * @param $input
 * @return integer
 */
function analytics_ga_mu_site_specific_allowed_sanitize( $input ) {
	return absint( $input );
}

/**
 * Display Analytics settings (network)
 */
function display_network_analytics_settings() { ?>
	<div class="wrap">
		<h2><?php _e( 'Google Analytics', 'pressbooks' ); ?></h2>
		<?php $nonce = ( @$_REQUEST['_wpnonce'] ) ? $_REQUEST['_wpnonce'] : '';
		if ( !empty( $_POST ) ) {
			if ( !wp_verify_nonce( $nonce, 'pb_network_analytics-options' ) ) {
			    die( 'Security check' );
			} else {
				if ( @$_REQUEST['ga_mu_uaid' ] ) {
					update_option( 'ga_mu_uaid', $_REQUEST['ga_mu_uaid' ] );
				} else {
					delete_option( 'ga_mu_uaid' );
				}
				if ( @$_REQUEST['ga_mu_site_specific_allowed' ] ) {
					update_option( 'ga_mu_site_specific_allowed', $_REQUEST['ga_mu_site_specific_allowed' ] );
				} else {
					delete_option( 'ga_mu_site_specific_allowed' );
				} ?>
				<div id="message" class="updated notice is-dismissible"><p><strong><?php _e( 'Settings saved.', 'pressbooks' ); ?></strong></div>
			<?php }
		} ?>
		<form method="POST" action="">
			<?php settings_fields( 'pb_network_analytics' );
			do_settings_sections( 'pb_network_analytics' ); ?>
			<?php submit_button(); ?>
		</form>
	</div>
<?php }

/**
 * Display Analytics settings (book)
 */
function display_analytics_settings() { ?>
	<div class="wrap">
		<h2><?php _e( 'Google Analytics', 'pressbooks' ); ?></h2>
		<form method="POST" action="options.php">
			<?php settings_fields( 'pb_analytics' );
			do_settings_sections( 'pb_analytics' ); ?>
			<?php submit_button(); ?>
		</form>
	</div>
<?php
}

/**
 * Print the script.
 */
function print_admin_analytics() {

	switch_to_blog( 1 );
	$ga_mu_uaid_network = get_option( 'ga_mu_uaid' );
	$ga_mu_maindomain = get_option( 'ga_mu_maindomain' );
	$ga_mu_site_specific_allowed = get_option( 'ga_mu_site_specific_allowed' );
	restore_current_blog();

	$ga_mu_uaid = get_option( 'ga_mu_uaid' );

	$network = false;
	$book = false;

	if ( isset( $ga_mu_uaid_network ) && $ga_mu_uaid_network !== '' && $ga_mu_uaid_network !== '0') {
		$network = true;
	}
	if ( isset( $ga_mu_uaid ) && $ga_mu_uaid !== '' && $ga_mu_uaid !== '0') {
		$book = true;
	}

	if ( $network && $book ) {
		if ( $ga_mu_uaid_network == $ga_mu_uaid ) {
			$book = false;
		}
	}

	if ( $book == true && ( !isset( $ga_mu_site_specific_allowed ) || $ga_mu_site_specific_allowed == '' || $ga_mu_site_specific_allowed == '0' ) ) {
		$book = false;
	}

	if ( $network || $book ) {
		$html = "<!-- Google Analytics -->\n<script>\n(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)})(window,document,'script','//www.google-analytics.com/analytics.js','ga');\n";
		if ( $network ) {
			$html .= "ga('create', '". $ga_mu_uaid_network . "', 'auto');\n";
			$html .= "ga('send', 'pageview');\n";
		}
		$html .= apply_filters( 'pb_ecommerce_tracking', '' );
		if ( $book ) {
			$html .= "ga('create', '". $ga_mu_uaid . "', 'auto', 'bookTracker');";
			$html .= "ga('bookTracker.send', 'pageview');";
		}
		$html .= "</script>\n<!-- End Google Analytics -->";
	}
	echo $html;
}
