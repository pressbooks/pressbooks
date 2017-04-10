<?php
/**
 * Ensures compatibility between Pressbooks and the server environment
 *
 * @package Pressbooks
 * @author  Book Oven Inc. <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Check if installation environment meets minimum PB requirements. Can be used by other plugins that depend on Pressbooks. Example usage: https://gist.github.com/greatislander/403f63ae466a166255c65d9e4e3edd20
 *
 * @return bool
 */
function pb_meets_minimum_requirements() {

	$is_compatible = true;

	// PHP Version
	global $pb_minimum_php;
	$pb_minimum_php = '5.6.0';

	if ( ! version_compare( PHP_VERSION, $pb_minimum_php, '>=' ) ) {
		add_action( 'admin_notices', '_pb_minimum_php' );
		$is_compatible = false;
	}

	// WordPress Version
	global $pb_minimum_wp;
	$pb_minimum_wp = '4.7.3';

	if ( ! is_multisite() || ! version_compare( get_bloginfo( 'version' ), $pb_minimum_wp, '>=' ) ) {
		add_action( 'admin_notices', '_pb_minimum_wp' );
		$is_compatible = false;
	}

	return $is_compatible;
}

/**
 * Echo message about minimum PHP Version
 */
function _pb_minimum_php() {
	global $pb_minimum_php;
	echo '<div id="message" class="error fade"><p>';
	printf(
		esc_attr__( 'Pressbooks will not work with your version of PHP. Pressbooks requires PHP version %s or greater. Please upgrade PHP if you would like to use Pressbooks.', 'pressbooks' ),
		esc_attr( $pb_minimum_php )
	);
	echo '</p></div>';
}

/**
 * Echo message about minimum WordPress Version
 */
function _pb_minimum_wp() {
	global $pb_minimum_wp;
	echo '<div id="message" class="error fade"><p>';
	printf(
		esc_attr__( 'Pressbooks will not work with your version of WordPress. Pressbooks requires a dedicated install of WordPress Multisite, version %s or greater. Please upgrade WordPress if you would like to use Pressbooks.', 'pressbooks' ),
		esc_attr( $pb_minimum_wp )
	);
	echo '</p></div>';
}
