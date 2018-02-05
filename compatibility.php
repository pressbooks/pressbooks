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

	// Cheap cache
	static $is_compatible = null;
	if ( null !== $is_compatible ) {
		return $is_compatible;
	}

	$is_compatible = true;

	// PHP Version
	global $pb_minimum_php;
	$pb_minimum_php = '7.0.0';

	if ( ! version_compare( PHP_VERSION, $pb_minimum_php, '>=' ) ) {
		add_action( 'admin_notices', '_pb_minimum_php' );
		$is_compatible = false;
	}

	// WordPress Version
	global $pb_minimum_wp;
	$pb_minimum_wp = '4.9.2';

	$wp_version = get_bloginfo( 'version' );
	if ( substr_count( $wp_version, '.' ) === 1 ) {
		// Semantic versioning fail?
		$wp_version .= '.0';
	}

	if ( ! is_multisite() || ! version_compare( $wp_version, $pb_minimum_wp, '>=' ) ) {
		add_action( 'admin_notices', '_pb_minimum_wp' );
		$is_compatible = false;
	}

	// Is Pressbooks active?
	if ( ! defined( 'WP_TESTS_MULTISITE' ) ) {
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		if ( ! is_plugin_active( 'pressbooks/pressbooks.php' ) ) {
			add_action( 'admin_notices', '_pb_disabled' );
			$is_compatible = false;
		}
	}

	if ( $is_compatible ) {
		pb_init_autoloader();
	}

	return $is_compatible;
}

/**
 * Plugins are loaded in random order. Other plugins that depend on pressbooks (before pressbooks is loaded) should init the autoloader.
 */
function pb_init_autoloader() {
	static $registered = false;
	if ( ! $registered ) {
		\HM\Autoloader\register_class_path( 'Pressbooks', __DIR__ . '/inc' );
		$registered = true;
	}
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

/**
 * Echo message about Pressbooks not active
 */
function _pb_disabled() {
	echo '<div id="message" class="error fade"><p>';
	_e( 'The Pressbooks plugin is inactive, but you have active plugins that require Pressbooks. This is causing errors. Please go to the Plugins page, and activate Pressbooks.', 'pressbooks' );
	echo '</p></div>';
}
