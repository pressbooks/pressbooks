<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */

if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Check if installation environment meets minimum PB requirements.
 * Can be shared by other plugins that depend on Pressbooks. Example usage:
 *
 *     if ( ! @include_once( WP_PLUGIN_DIR . '/pressbooks/compatibility.php' ) ) {
 *         add_action( 'admin_notices', function () {
 *             echo '<div id="message" class="error fade"><p>' . __( 'Cannot find Pressbooks install.', 'pressbooks' ) . '</p></div>';
 *         } );
 *         return;
 *     }
 *     elseif ( ! pb_meets_minimum_requirements() ) {
 *         return;
 *     }
 *
 *
 * @return bool
 */
function pb_meets_minimum_requirements() {

	$is_compatible = true;

	// ---------------------------------------------------------------------------------------------------------------
	// PHP Version
	// ---------------------------------------------------------------------------------------------------------------

	// Override PHP version at your own risk!
	global $pb_minimum_php;
	if ( empty ( $pb_minimum_php ) ) $pb_minimum_php = '5.6.0';

	if ( ! version_compare( PHP_VERSION, $pb_minimum_php, '>=' ) ) {
		add_action( 'admin_notices', '_pb_minimum_php' );
		$is_compatible = false;
	}

	// ---------------------------------------------------------------------------------------------------------------
	// WordPress Version
	// ---------------------------------------------------------------------------------------------------------------

	// Override WP version at your own risk!
	global $pb_minimum_wp;
	if ( empty ( $pb_minimum_wp ) ) $pb_minimum_wp = '4.6.1';

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
	printf( __( 'Pressbooks will not work with your version of PHP. Pressbooks requires PHP version %s or greater. Please upgrade PHP if you would like to use Pressbooks.', 'pressbooks' ), $pb_minimum_php );
	echo '</p></div>';
}

/**
 * Echo message about minimum WordPress Version
 */
function _pb_minimum_wp() {
	global $pb_minimum_wp;
	echo '<div id="message" class="error fade"><p>';
	printf( __( 'Pressbooks will not work with your version of WordPress. Pressbooks requires a dedicated install of WordPress Multi-Site, version %s or greater. Please upgrade WordPress if you would like to use Pressbooks.', 'pressbooks' ), $pb_minimum_wp );
	echo '</p></div>';
}
