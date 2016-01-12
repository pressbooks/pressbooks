<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */
namespace PressBooks\Admin\Analytics;

/**
 *
 */
function add_network_menu() {
	add_submenu_page(
		'settings.php',
		__( 'Analytics', 'pressbooks' ),
		__( 'Analytics', 'pressbooks' ),
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
		__( 'Analytics', 'pressbooks' ),
		__( 'Analytics', 'pressbooks' ),
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
		'ga_mu_maindomain',
		__( 'Network Domain', 'pressbooks' ),
		__NAMESPACE__ . '\analytics_ga_mu_maindomain_callback',
		$_page,
		$_section,
		array(
			__( 'Your network domain e.g. &lsquo;.pressbooks.com&rsquo;. The domain must start with a dot.', 'pressbooks' )
		)
	);
	register_setting(
		$_page,
		'ga_mu_maindomain',
		__NAMESPACE__ . '\analytics_ga_mu_maindomain_sanitize'
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
 * Analytics settings, ga_mu_maindomain field callback
 *
 * @param $args
 */
function analytics_ga_mu_maindomain_callback( $args ) {
	$ga_mu_maindomain = get_option( 'ga_mu_maindomain' );		
	$html = '<input type="text" id="ga_mu_maindomain" name="ga_mu_maindomain" value="' . $ga_mu_maindomain . '" />';
	$html .= '<p class="description">' . $args[0] . '</p>';
	echo $html;
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
 * Analytics settings, ga_mu_maindomain field sanitization
 *
 * @param $input
 * @return string
 */
function analytics_ga_mu_maindomain_sanitize( $input ) {
	return sanitize_text_field( $input );
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
		<form method="POST" action="options.php">
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
<?php }