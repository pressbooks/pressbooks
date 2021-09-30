<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Admin\NetworkManagers;

use function Pressbooks\Sanitize\safer_unserialize;
use PressbooksMix\Assets;

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
	$assets = new Assets( 'pressbooks', 'plugin' );

	wp_enqueue_style( 'pb-network-managers', $assets->getPath( 'styles/network-managers.css' ) );
	wp_enqueue_script( 'pb-network-managers', $assets->getPath( 'scripts/network-managers.js' ), [ 'jquery' ] );
	wp_localize_script(
		'pb-network-managers', 'PB_NetworkManagerToken', [
			'networkManagerNonce' => wp_create_nonce( 'pb-network-managers' ),
		]
	);
}

/**
 * Get a list of restricted super users
 * (A network manager is a restricted super user)
 * Cheap cached in a static variable to improve i/o performance
 *
 * @param bool $reset
 *
 * @return array
 */
function _restricted_users( $reset = false ) {
	// Cheap cache
	static $restricted = false;
	if ( $reset ) {
		$restricted = false;
	}
	if ( $restricted === false ) {
		global $wpdb;
		$restricted = $wpdb->get_results( "SELECT * FROM {$wpdb->sitemeta} WHERE meta_key = 'pressbooks_network_managers'" );
		if ( $restricted ) {
			$restricted = safer_unserialize( $restricted[0]->meta_value );
		}
		if ( empty( $restricted ) ) {
			$restricted = [];
		}
	}
	return $restricted;
}

/**
 *
 */
function update_admin_status() {
	if ( check_ajax_referer( 'pb-network-managers' ) ) {
		$restricted = _restricted_users( true );
		$id = absint( $_POST['admin_id'] );

		if ( 1 === absint( $_POST['status'] ) ) {
			if ( ! in_array( absint( $id ), $restricted, true ) ) {
				$restricted[] = $id;
			}
		} elseif ( 0 === absint( $_POST['status'] ) ) {
			$key = array_search( absint( $id ), $restricted, true );
			if ( $key !== false ) {
				unset( $restricted[ $key ] );
			}
		}

		if ( is_array( $restricted ) && ! empty( $restricted ) ) {
			update_site_option( 'pressbooks_network_managers', $restricted );
			// This meta option will only be used by the BI, but we want to keep that in sync.
			update_site_option( 'pressbooks_network_managers_ids', implode( ',', $restricted ) );
		} else {
			delete_site_option( 'pressbooks_network_managers' );
			delete_site_option( 'pressbooks_network_managers_ids' );
		}
		// Reset the cheap cache after updating the option
		_restricted_users( true );
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

		<p><?php _e( 'Network administrators&rsquo; access to network administration menus can be restricted to leave only sites and users visible to them.', 'pressbooks' ); ?></p>
		<?php $superadmins->display(); ?>
	</div>
	<?php
}

/**
 * Is this a restricted super user?
 * (A network manager is a restricted super user)
 *
 * @return bool
 */
function is_restricted() {
	$user = wp_get_current_user();
	if ( in_array( $user->ID, _restricted_users(), true ) ) {
		return true;
	}
	return false;
}

/**
 * @return array
 */
function permitted_setting_menus() {
	return [
		'pb_analytics',
		'pb_network_analytics_options',
		'pb_whitelabel_settings',
		'pressbooks_sharingandprivacy_options',
	];
}

/**
 * @param \WP_Admin_Bar $wp_admin_bar
 */
function hide_admin_bar_menus( $wp_admin_bar ) {
	if ( is_restricted() ) {
		$wp_admin_bar->remove_menu( 'updates' );
	}
}

/**
 * @param string $classes
 *
 * @return string
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
		remove_menu_page( 'themes.php' );
		remove_menu_page( 'plugins.php' );
		remove_submenu_page( 'index.php', 'update-core.php' );
		remove_submenu_page( 'index.php', 'upgrade.php' );
		remove_menu_page( 'admin.php?page=pb_stats' );
		remove_action( 'network_admin_notices', 'update_nag', 3 );
		remove_action( 'network_admin_notices', 'site_admin_notice' );

		$keepers = [
			'settings.php' => permitted_setting_menus(),
		];
		global $submenu;
		foreach ( $keepers as $menu_slug => $submenu_slugs_to_keep ) {
			$something_was_kept = false;
			if ( isset( $submenu[ $menu_slug ] ) ) {
				foreach ( $submenu[ $menu_slug ] as $i => $item ) {
					if ( ! in_array( $item[2], $submenu_slugs_to_keep, true ) ) {
						unset( $submenu[ $menu_slug ][ $i ] );
					} else {
						$something_was_kept = true;
					}
				}
			}
			if ( ! $something_was_kept ) {
				remove_menu_page( $menu_slug );
			}
		}
	}
}

/**
 *
 */
function restrict_access() {
	$user = wp_get_current_user();
	if ( ! in_array( $user->ID, _restricted_users(), true ) ) {
		// This user doesn't have any restrictions. Bail.
		return;
	}

	$check_against_url = wp_parse_url( ( is_ssl() ? 'http://' : 'https://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], PHP_URL_PATH );
	$redirect_url = get_site_url() . '/wp-admin/network/';

	// ---------------------------------------------------------------------------------------------------------------
	// Don't let user go to any of these pages, under any circumstances

	$restricted_urls = [
		'themes',
		'theme-(install|editor)',
		'plugins',
		'plugin-(install|editor)',
		'update-core',
		'upgrade',
	];

	$expr = '~/wp-admin/network/(' . implode( '|', $restricted_urls ) . ')\.php$~';
	if ( preg_match( $expr, $check_against_url ) ) {
		\Pressbooks\Redirect\location( $redirect_url );
	}

	// ---------------------------------------------------------------------------------------------------------------
	// Settings

	$expr = '~/wp-admin/network/settings.php~';
	if ( preg_match( $expr, $check_against_url ) ) {
		$ok = false;
		foreach ( permitted_setting_menus() as $submenu_slug_to_keep ) {
			if ( isset( $_GET['page'] ) && $_GET['page'] === $submenu_slug_to_keep ) {
				$ok = true;
				break;
			}
		}
		if ( ! $ok ) {
			\Pressbooks\Redirect\location( $redirect_url );
		}
	}
}
