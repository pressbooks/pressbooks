<?php
/**
 * @author  PressBooks <code@pressbooks.org>
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
 * Change redirect upon login to user's My Sites page
 *
 * @param string   $redirect_to
 * @param string   $request_redirect_to
 * @param \WP_USER $user
 */
function login( $redirect_to, $request_redirect_to, $user ) {
	if ( is_a( $user, 'WP_User' ) ) {
		if ( ! is_user_member_of_blog( $user->ID, intval( 1 ) ) ) {
			$user_info = get_userdata( $user->ID );
			if ( $user_info->primary_blog ) {
				wp_redirect( get_blogaddress_by_id( $user_info->primary_blog ) . 'wp-admin/my-sites.php' );
				exit;
			} else {
				$redirect_to = network_site_url( '/wp-signup.php' );
			}
		}
	}

	return $redirect_to;
}


/**
 * Add a rewrite rule for the keyword "format"
 */
function rewrite_rules_for_format() {

	add_rewrite_endpoint( 'format', EP_ROOT );
	add_filter( 'template_redirect', __NAMESPACE__ . '\do_format', 0 );

	// Flush rewrite rules
	$set = get_option( 'pressbooks_flushed_format' );
	if ( $set !== true ) {
		flush_rewrite_rules( false );
		update_option( 'pressbooks_flushed_format', true );
	}
}


/**
 * Display book in a custom format.
 */
function do_format() {

	$format = get_query_var( 'format' );
	if ( ! $format ) {
		// Don't do anything and return
		return;
	}

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

}