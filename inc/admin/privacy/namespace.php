<?php
/**
 * Privacy administration (export, erasure, and policy generation).
 *
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Admin\Privacy;

/**
 * Suggest text for the Privacy Policy.
 *
 * @see https://developer.wordpress.org/plugins/privacy/suggesting-text-for-the-site-privacy-policy/
 *
 * @since 5.4.0
 */
function add_privacy_policy_content() {
	if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
		return;
	}

	$content = __( 'TODO.', 'pressbooks' );

	wp_add_privacy_policy_content( 'Pressbooks', wp_kses_post( wpautop( $content, false ) ) );
}
