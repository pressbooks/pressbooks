<?php
/**
 * Ensures compatibility between Pressbooks and the server environment
 *
 * @package Pressbooks
 * @author  Book Oven Inc. <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */
// TODO: Security audit
// @phpcs:disable Pressbooks.Security.EscapeOutput.OutputNotEscaped
// @phpcs:disable WordPress.PHP.NoSilencedErrors.Discouraged
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

	// Register activation hook *before* is_plugin_active() test, because pressbooks is not active while we are activating it (cue yakety sax...)
	register_activation_hook( __DIR__ . '/pressbooks.php', 'pb_register_activation_hook' );

	// PHP Version
	global $pb_minimum_php;
	$pb_minimum_php = '7.4.0';

	// WordPress Version
	global $pb_minimum_wp;
	$pb_minimum_wp = '6.0.2';

	include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	$is_compatible = true;

	if ( ! version_compare( PHP_VERSION, $pb_minimum_php, '>=' ) ) {
		add_action( 'admin_notices', '_pb_minimum_php' );
		add_action( 'network_admin_notices', '_pb_minimum_php' );
		$is_compatible = false;
	}

	$wp_version = get_bloginfo( 'version' );
	if ( substr_count( $wp_version, '.' ) === 1 ) {
		// Semantic versioning fail?
		$wp_version .= '.0';
	}

	if ( ! is_multisite() || ! version_compare( $wp_version, $pb_minimum_wp, '>=' ) ) {
		add_action( 'admin_notices', '_pb_minimum_wp' );
		add_action( 'network_admin_notices', '_pb_minimum_wp' );
		$is_compatible = false;
	}

	// Is Pressbooks active?
	if ( ! defined( 'WP_TESTS_MULTISITE' ) ) {
		if ( ! is_plugin_active( 'pressbooks/pressbooks.php' ) ) {
			add_action( 'admin_notices', '_pb_disabled' );
			add_action( 'network_admin_notices', '_pb_disabled' );
			$is_compatible = false;
		}
	}

	if ( $is_compatible ) {
		// Init autoloader
		pb_init_autoloader();
		// Set current version
		if ( ! defined( 'PB_PLUGIN_VERSION' ) ) {
			$info = get_plugin_data( __DIR__ . '/pressbooks.php', false, false );
			define( 'PB_PLUGIN_VERSION', $info['Version'] );
		}
	}

	return $is_compatible;
}

/**
 * Activation hook
 *
 * You can't call register_activation_hook() inside a function hooked to the 'plugins_loaded' or 'init' hooks (or any other hook).
 *
 * @see register_activation_hook()
 */
function pb_register_activation_hook() {

	// Apply Pressbooks color scheme
	update_user_option( get_current_user_id(), 'admin_color', 'pb_colors', true );

	// Prevent overwriting customizations if Pressbooks has been disabled
	if ( ! get_site_option( 'pressbooks-activated' ) ) {

		/**
		 * Allow the default description of the root blog to be customized.
		 *
		 * @since 3.9.7
		 *
		 * @param string $value Default description ('Simple Book Publishing').
		 */
		update_blog_option( 1, 'blogdescription', apply_filters( 'pb_root_description', __( 'Simple Book Publishing', 'pressbooks' ) ) );

		if ( defined( 'PB_ROOT_THEME' ) ) {
			$activate = PB_ROOT_THEME;
		} else {
			$theme = wp_get_theme( 'pressbooks-aldine' );
			if ( $theme->exists() ) {
				$activate = 'pressbooks-aldine';
			}
		}
		if ( ! empty( $activate ) ) {
			switch_to_blog( 1 );
			// Configure root blog theme (PB_ROOT_THEME, usually 'pressbooks-aldine').
			switch_theme( $activate );
			// Remove widgets from root blog.
			delete_option( 'sidebars_widgets' );
			restore_current_blog();
		}

		// Add "activated" key to enable check above
		add_site_option( 'pressbooks-activated', true );

	}
}

/**
 * Plugins are loaded in random order. Other plugins that depend on pressbooks (before pressbooks is loaded) should init the autoloader.
 */
function pb_init_autoloader() {
	static $registered = false;
	if ( ! $registered ) {
		_pb_copy_autoloader();
		require_once( __DIR__ . '/requires.php' );
		\HM\Autoloader\register_class_path( 'Pressbooks', __DIR__ . '/inc' );
		$registered = true;
	}
}

/**
 * Copy Pressbooks’ autoloader file
 */
function _pb_copy_autoloader() {
	$mu_plugin_dir = defined( 'WPMU_PLUGIN_DIR' ) ? WPMU_PLUGIN_DIR : trailingslashit( WP_CONTENT_DIR ) . 'mu-plugins';
	if ( ! file_exists( $mu_plugin_dir ) ) {
		if ( ! wp_mkdir_p( $mu_plugin_dir ) ) {
			die( sprintf( __( 'Pressbooks could not create the mu-plugins folder. Please create the following directory: %s', 'pressbooks' ), $mu_plugin_dir ) );
		}
	}
	$dest = $mu_plugin_dir . '/hm-autoloader.php';
	if ( ! file_exists( $dest ) ) {
		$source = __DIR__ . '/hm-autoloader.php';
		if ( ! @copy( $source, $dest ) ) { // @codingStandardsIgnoreLine
			die( sprintf( __( 'Pressbooks could not copy the autoloader from %1$s to %2$s. Please copy the file manually.', 'pressbooks' ), $source, $dest ) );
		}
		if ( ! function_exists( '\HM\Autoloader\register_class_path' ) ) {
			require_once( $dest );
		}
	}
}

/**
 * Echo message about minimum PHP Version
 */
function _pb_minimum_php() {
	global $pb_minimum_php;
	echo '<div id="message" role="alert" class="error fade"><p>';
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
	echo '<div id="message" role="alert" class="error fade"><p>';
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
	echo '<div id="message" role="alert" class="error fade"><p>';
	_e( 'The Pressbooks plugin is inactive, but you have active plugins that require Pressbooks. This is causing errors. Please go to the Plugins page, and activate Pressbooks.', 'pressbooks' );
	echo '</p></div>';
}
