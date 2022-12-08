<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Admin\Dashboard;

/**
 *
 */
function add_menu() {
	add_submenu_page(
		'settings.php',
		esc_html__( 'Dashboard', 'pressbooks' ),
		esc_html__( 'Dashboard', 'pressbooks' ),
		'manage_network',
		'pb_dashboard',
		__NAMESPACE__ . '\options'
	);
}

/**
 * Init pb_network_integrations menu, removes itself from sub-menus
 *
 * @since 5.3.0
 *
 * @return string
 */
function init_network_integrations_menu() {
	$parent_slug = 'pb_network_integrations';
	static $init_pb_network_integrations_menu = false;
	if ( ! $init_pb_network_integrations_menu ) {
		add_menu_page(
			esc_html__( 'Integrations', 'pressbooks-lti-provider' ),
			esc_html__( 'Integrations', 'pressbooks-lti-provider' ),
			'manage_network',
			$parent_slug,
			'',
			'dashicons-networking'
		);
		add_action(
			'admin_bar_init', function () {
				remove_submenu_page( 'pb_network_integrations', 'pb_network_integrations' );
			}
		);
		$init_pb_network_integrations_menu = true;
	}
	return $parent_slug;
}

/**
 *
 */
function options() {
	require( PB_PLUGIN_DIR . 'templates/admin/dashboard.php' );
}

function dashboard_options_init() {

	$_page = 'pb_dashboard';

	add_settings_section(
		'dashboard_feed',
		esc_html__( 'Dashboard Feed', 'pressbooks' ),
		__NAMESPACE__ . '\dashboard_feed_callback',
		$_page
	);

	add_settings_field(
		'display_feed',
		esc_html__( 'Display Feed', 'pressbooks' ),
		__NAMESPACE__ . '\display_feed_callback',
		$_page,
		'dashboard_feed',
		[
			'description' => esc_html__( 'Display an RSS feed widget on the dashboard.', 'pressbooks' ),
		]
	);

	add_settings_field(
		'title',
		esc_html__( 'Feed Title', 'pressbooks' ),
		__NAMESPACE__ . '\title_callback',
		$_page,
		'dashboard_feed'
	);

	add_settings_field(
		'url',
		esc_html__( 'Feed URL', 'pressbooks' ),
		__NAMESPACE__ . '\url_callback',
		$_page,
		'dashboard_feed'
	);

	register_setting(
		$_page,
		'display_feed',
		__NAMESPACE__ . '\display_feed_sanitize'
	);

	register_setting(
		$_page,
		'title',
		__NAMESPACE__ . '\title_sanitize'
	);

	register_setting(
		$_page,
		'url',
		__NAMESPACE__ . '\url_sanitize'
	);
}
