<?php
/**
 * @author  PressBooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */
namespace PressBooks\Redirect;


/**
 * Fail-safe Location: redirection
 *
 * @param string $href a uniform resource locator (URL)
 */
function location( $href ) {

	$href = filter_var( $href, FILTER_SANITIZE_URL );

	if ( ! headers_sent() ) {
		header( "Location: $href" );
	} else {
		// Javascript hack
		echo "
			<script type='text/javascript'>
			// <![CDATA[
			window.location = '{$href}';
			// ]]>
			</script>
			";
	}

	exit; // Quit script
}


/**
 * Change redirect upon login to user's My Catalog page
 *
 * @param string $redirect_to
 * @param string $request_redirect_to
 * @param \WP_User $user
 *
 * @return string
 */
function login( $redirect_to, $request_redirect_to, $user ) {

	if ( false === is_a( $user, 'WP_User' ) ) {
		// Unknown user, bail with default
		return $redirect_to;
	}

	if ( is_super_admin( $user->ID ) ) {
		// This is an admin, don't mess
		return $redirect_to;
	}

	$blogs = get_blogs_of_user( $user->ID );
	if ( array_key_exists( get_current_blog_id(), $blogs ) ) {
		// Yes, user has access to this blog
		return $redirect_to;
	}

	if ( $user->primary_blog ) {
		// Force redirect the user to their catalog, bypass wp_safe_redirect()
		$redirect = get_blogaddress_by_id( $user->primary_blog ) . 'wp-admin/index.php?page=pb_catalog';
		location( $redirect );
	}

	// User has no primary_blog? Make them sign-up for one
	return network_site_url( '/wp-signup.php' );
}


/**
 * Centralize flush_rewrite_rules() in one single function so that rule does not kill the other
 */
function flusher() {

	$pull_the_lever = false;

	// @see \PressBooks\PostType\register_post_types
	$set = get_option( 'pressbooks_flushed_post_type' );
	if ( ! $set ) {
		$pull_the_lever = true;
		update_option( 'pressbooks_flushed_post_type', true );
	}

	// @see rewrite_rules_for_format()
	$set = get_option( 'pressbooks_flushed_format' );
	if ( ! $set ) {
		$pull_the_lever = true;
		update_option( 'pressbooks_flushed_format', true );
	}

	// @see rewrite_rules_for_catalog()
	$set = get_option( 'pressbooks_flushed_catalog' );
	if ( ! $set ) {
		$pull_the_lever = true;
		update_option( 'pressbooks_flushed_catalog', true );
	}

	// @see rewrite_rules_for_sitemap()
	$set = get_option( 'pressbooks_flushed_sitemap' );
	if ( ! $set ) {
		$pull_the_lever = true;
		update_option( 'pressbooks_flushed_sitemap', true );
	}

	// @see \PressBooks\VIP\Upgrade\rewrite_rules_for_upgrade()
	$set = get_option( 'pressbooks-vip_flushed_upgrade' );
	if ( ! $set ) {
		$pull_the_lever = true;
		update_option( 'pressbooks-vip_flushed_upgrade', true );
	}

	if ( $pull_the_lever ) {
		flush_rewrite_rules( false );
	}
}


/**
 * Add a rewrite rule for the keyword "format"
 *
 * @see flusher()
 */
function rewrite_rules_for_format() {

	add_rewrite_endpoint( 'format', EP_ROOT );
	add_filter( 'template_redirect', __NAMESPACE__ . '\do_format', 0 );
}


/**
 * Display book in a custom format.
 */
function do_format() {

	if ( ! array_key_exists( 'format', $GLOBALS['wp_query']->query_vars ) ) {
		// Don't do anything and return
		return;
	}

	$format = get_query_var( 'format' );

	if ( 'xhtml' == $format ) {

		$args = array();
		$foo = new \PressBooks\Export\Xhtml\Xhtml11( $args );
		$foo->transform();
		exit;
	}

	if ( 'wxr' == $format ) {

		$args = array();
		$foo = new \PressBooks\Export\WordPress\Wxr( $args );
		$foo->transform();
		exit;
	}

	wp_die( __( 'Error: Unknown export format.', 'pressbooks' ) );
}


/**
 * Add a rewrite rule for the keyword "catalog"
 *
 * @see flusher()
 */
function rewrite_rules_for_catalog() {

	add_rewrite_endpoint( 'catalog', EP_ROOT );
	add_filter( 'template_redirect', __NAMESPACE__ . '\do_catalog', 0 );
}


/**
 * Display catalog
 */
function do_catalog() {

	if ( ! array_key_exists( 'catalog', $GLOBALS['wp_query']->query_vars ) ) {
		// Don't do anything and return
		return;
	}

	$user_login = get_query_var( 'catalog' );
	if ( ! is_main_site() ) {
		// Hard redirect
		location( network_site_url( "/catalog/$user_login" ) );
	}

	$user = get_user_by( 'login', $user_login );
	if ( false == $user ) {
		$msg = __( 'No catalog was found for user', 'pressbooks' ) . ": $user_login";
		$args = array( 'response' => '404' );
		wp_die( $msg, '', $args );
	}

	\PressBooks\Catalog::loadTemplate( $user->ID );
	exit;
}


/**
 * Add a rewrite rule for sitemap xml
 *
 * @see flusher()
 */
function rewrite_rules_for_sitemap() {

	add_feed( 'sitemap.xml', '\PressBooks\Utility\do_sitemap' );
}