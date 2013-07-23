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

	global $current_site; // Main site
	$blogs = get_blogs_of_user( $user->ID );
	if ( array_key_exists( $current_site->blog_id, $blogs ) ) {
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
 * Add a rewrite rule for the keyword "format"
 */
function rewrite_rules_for_format() {

	add_rewrite_endpoint( 'format', EP_ROOT );
	add_filter( 'template_redirect', __NAMESPACE__ . '\do_format', 0 );

	// Flush rewrite rules
	$set = get_option( 'pressbooks_flushed_format' );
	if ( ! $set ) {
		flush_rewrite_rules( false );
		update_option( 'pressbooks_flushed_format', true );
	}
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
 */
function rewrite_rules_for_catalog() {

	add_rewrite_endpoint( 'catalog', EP_ROOT );
	add_filter( 'template_redirect', __NAMESPACE__ . '\do_catalog', 0 );

	// Flush rewrite rules
	$set = get_option( 'pressbooks_flushed_catalog' );
	if ( ! $set ) {
		flush_rewrite_rules( false );
		update_option( 'pressbooks_flushed_catalog', true );
	}
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
 */
function rewrite_rules_for_sitemap() {

	add_rewrite_rule(
		'.*sitemap.xml$',
		'index.php?feed=sitemap',
		'top'
	);

	// Flush rewrite rules
	$set = get_option( 'pressbooks_flushed_sitemap' );
	if ( ! $set ) {
		flush_rewrite_rules( false );
		update_option( 'pressbooks_flushed_sitemap', true );
	}
}