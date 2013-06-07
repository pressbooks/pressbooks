<?php
/**
 * @author  PressBooks <code@pressbooks.org>
 * @license GPLv2 (or any later version)
 */
namespace PressBooks\Admin\Catalog;


/**
 * Add My Catalog menu.
 */
function add_menu() {

	add_submenu_page( 'index.php', __( 'My Catalog', 'pressbooks' ), __( 'My Catalog', 'pressbooks' ), 'read', 'catalog', __NAMESPACE__ . '\display_catalog_page' );
}


/**
 * Displays catalog administration page.
 */
function display_catalog_page() {

	$vars = array();

	// Are we editing our own catalog or another users?
	if ( isset( $_REQUEST['user_id'] ) && current_user_can( 'edit_user', (int) $_REQUEST['user_id'] ) ) {
		$vars['user_id'] = (int) $_REQUEST['user_id'];
	}

	load_catalog_template( $vars );
}


/**
 * Simple templating function.
 *
 * @param array $vars
 */
function load_catalog_template( $vars ) {

	extract( $vars );
	require( PB_PLUGIN_DIR . 'admin/templates/catalog.php' );
}
