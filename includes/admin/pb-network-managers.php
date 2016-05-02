<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */
namespace Pressbooks\Admin\NetworkManagers;


/**
 *
 */
function add_menu() {
	$page = add_submenu_page(
		'settings.php',
		__( 'Network Managers', 'pressbooks' ),
		__( 'Network Managers', 'pressbooks' ),
		'manage_network',
		'pb_network_managers',
		__NAMESPACE__ . '\options'
	);
	add_action( 'admin_print_styles-' . $page, __NAMESPACE__ . '\admin_enqueues' );
}

/**
 * Enqueue css and javascript for the network manager administration page
 */
function admin_enqueues() {
	wp_enqueue_style( 'pb-network-managers', \Pressbooks\Utility\asset_path( 'styles/network-managers.css' ) );
	wp_enqueue_script( 'pb-network-managers', \Pressbooks\Utility\asset_path( 'scripts/network-managers.js' ), ['jquery'] );
	wp_localize_script( 'pb-network-managers', 'PB_NetworkManagerToken', array(
		'networkManagerNonce' => wp_create_nonce( 'pb-network-managers' ),
	) );
}

/**
 *
 */
function update_admin_status() {
	global $wpdb;

	if ( check_ajax_referer( 'pb-network-managers' ) ) {
		$restricted = $wpdb->get_results( "SELECT * FROM {$wpdb->sitemeta} WHERE meta_key = 'pressbooks_network_managers'" );
		if ( $restricted ) {
			$restricted = maybe_unserialize( $restricted[0]->meta_value );
		}
		else {
			$restricted = array();
		}

		$id = absint( $_POST['admin_id'] );

		if ( 1 === absint( $_POST['status'] ) ) {
			if ( ! in_array( $id, $restricted ) ) {
				$restricted[] = $id;
			}
		}
		elseif ( 0 === absint( $_POST['status'] ) ) {
			if ( ( $key = array_search( $id, $restricted ) ) !== false ) {
				unset( $restricted[$key] );
			}
		}

		if ( is_array( $restricted ) && ! empty( $restricted ) ) {
			update_site_option( 'pressbooks_network_managers', $restricted );
		}
		else {
			delete_site_option( 'pressbooks_network_managers' );
		}
	}

}

/**
 *
 */
function options() {
	if ( ! current_user_can( 'manage_network' ) ) {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}

	$superadmins = new \Pressbooks\Admin\Network_Managers_List_Table();
	$superadmins->prepare_items(); ?>
	<div class="wrap">

		<div id="icon-users" class="icon32"><br/></div>
		<h2><?php _e( 'Pressbooks Network Managers', 'pressbooks' ); ?></h2>

		<p><?php _e( 'Network administrators&rsquo; access to network admininistration menus can be restricted to leave only sites and users visible to them.', 'pressbooks' ); ?></p>
		<?php $superadmins->display(); ?>
	</div>
<?php }


/**
 *
 */
function is_restricted() {
	global $wpdb;

	$val = false;

	$user = wp_get_current_user();

	$restricted = $wpdb->get_results( "SELECT * FROM {$wpdb->sitemeta} WHERE meta_key = 'pressbooks_network_managers'" );
	if ( $restricted ) {
		$restricted = maybe_unserialize( $restricted[0]->meta_value );
	}
	else {
		$restricted = array();
	}

	if ( in_array( $user->ID, $restricted ) ) {
		$val = true;
	}

	return $val;
}

/**
 *
 */
function hide_admin_bar_menus( $wp_admin_bar ) {
	if ( is_restricted() ) {
		$wp_admin_bar->remove_menu( 'updates' );
	}
}

/**
 *
 */
function admin_body_class( $classes ) {
	if ( is_restricted() ) {
		$classes .= ' network-admin-restricted';
	}

    return $classes;
}

/**
 *
 */
function hide_menus() {
	if ( is_restricted() ) {
		remove_action( 'admin_notices', 'site_admin_notice' );
	}
}

/**
 *
 */
function hide_network_menus() {
	if ( is_restricted() ) {
		remove_menu_page( "themes.php" );
		remove_menu_page( "plugins.php" );
		remove_menu_page( "settings.php" );
		remove_submenu_page( "index.php", "update-core.php" );
		remove_submenu_page( "index.php", "upgrade.php" );
		remove_menu_page( "admin.php?page=pb_stats" );
		remove_action( 'network_admin_notices', 'update_nag', 3 );
		remove_action( 'network_admin_notices', 'site_admin_notice' );
	}
}

/**
 *
 */
function restrict_access() {
	global $wpdb;

	$user = wp_get_current_user();

	$restricted = $wpdb->get_results( "SELECT * FROM {$wpdb->sitemeta} WHERE meta_key = 'pressbooks_network_managers'" );
	if ( $restricted ) {
		$restricted = maybe_unserialize( $restricted[0]->meta_value );
	}
	else {
		$restricted = array();
	}

	$check_against_url = parse_url( ( is_ssl() ? 'http://' : 'https://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], PHP_URL_PATH );
	$redirect_url = get_site_url() . '/wp-admin/network/';

	// ---------------------------------------------------------------------------------------------------------------
	// Don't let user go to any of these pages, under any circumstances

	$restricted_urls = array(
		'themes',
		'theme-(install|editor)',
		'plugins',
		'plugin-(install|editor)',
		'settings',
		'update-core',
		'upgrade',
	);

	$expr = '~/wp-admin/network/(' . implode( '|', $restricted_urls ) . ')\.php$~';
	if ( in_array( $user->ID, $restricted ) && preg_match( $expr, $check_against_url ) ) {
		\Pressbooks\Redirect\location( $redirect_url );
	}

}
