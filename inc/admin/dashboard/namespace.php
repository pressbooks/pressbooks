<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Admin\Dashboard;

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
