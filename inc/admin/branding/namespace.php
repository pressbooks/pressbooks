<?php
/**
 * Branding.
 *
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */
// TODO: Security audit
// @phpcs:disable Pressbooks.Security.EscapeOutput.OutputNotEscaped

namespace Pressbooks\Admin\Branding;

use PressbooksMix\Assets;

/**
 * Add `pressbooks` to login body class.
 */
function login_body_class( $classes ) {
	$classes[] = 'pressbooks';
	return $classes;
}

/**
 * Apply Color Scheme to Login Page
 * To customize this, add a filter to the 'pb_login_color_scheme' hook
 * that returns a string containing a link tag for your own admin color scheme.
 *
 * TODO: Deprecate & rename. More than just the color scheme now.
 */
function custom_color_scheme() {
	$style = get_customizer_colors();
	/**
	 * Print CSS for a custom color scheme for the login page.
	 *
	 * @since 4.3.0
	 */
	$style = apply_filters(
		'pb_login_color_scheme',
		/**
		 * Print CSS for a custom color scheme for the login page.
		 *
		 * @since 3.5.2
		 * @deprecated 4.3.0 Use pb_login_color_scheme instead.
		 *
		 * @param string $html
		 */
		apply_filters( 'pressbooks_login_color_scheme', $style )
	);
	echo $style;
}

/**
 * Add Custom Login Graphic.
 * To customize this, add a filter to the 'pb_login_logo' hook that
 * returns a string containing a <link> or <style> tag that supplies your custom logo.
 *
 * TODO: Deprecate & rename. More than just the login logo now.
 */
function custom_login_logo() {
	$assets = new Assets( 'pressbooks', 'plugin' );
	if ( has_custom_logo() ) {
		$custom_logo_id = get_theme_mod( 'custom_logo' );
		$logo = sprintf(
			'<style type="text/css">.login h1 a {background-image: url(%s);}</style>',
			wp_get_attachment_image_src( $custom_logo_id, 'logo' )[0]
		);
	} else {
		$logo = sprintf(
			'<style type="text/css">.login h1 a {background-image: url(%s);}</style>',
			PB_PLUGIN_URL . 'assets/dist/images/PB-logo.svg'
		);
	}
	$style = '<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Karla:400,400i,700|Spectral:400,400i,600" />';
	$style .= '<link rel="stylesheet" href="' . $assets->getPath( 'styles/login.css' ) . '" />';
	$style .= $logo;

	/**
	 * Print <link> or <style> tag to add a custom logo for the login page.
	 *
	 * @since 4.3.0
	 */
	$style = apply_filters(
		'pb_login_logo',
		/**
		 * Print <link> or <style> tag to add a custom logo for the login page.
		 *
		 * @since 3.5.2
		 * @deprecated 4.3.0 Use pb_login_logo instead.
		 *
		 * @param string $html
		 */
		apply_filters( 'pressbooks_login_logo', $style )
	);
	echo $style;
}

/**
 * Changing the login page URL
 *
 * @return string
 */
function login_url() {
	return home_url(); // Changes the url link from wordpress.org to your blog or website's url
}

/**
 * Changing the login page URL hover text
 */
function login_title() {
	return get_bloginfo( 'title' ); // changing the title from "Powered by WordPress" to whatever you wish
}

/**
 * Replaces 'WordPress' with 'Pressbooks' in titles of admin pages.
 *
 * @since 5.0.0
 *
 * @param string $admin_title
 * @return string
 */
function admin_title( $admin_title ) {
	$title = str_replace( 'WordPress', 'Pressbooks', $admin_title );
	return $title;
}

/**
 * @since 5.0.0
 *
 * @return string
 */
function get_customizer_colors() {
	$colors = [
		'primary',
		'accent',
		'primary_fg',
		'accent_fg',
		'primary_dark',
		'accent_dark',
		'primary_alpha',
		'accent_alpha',
		'header_text',
	];
	$values = [];
	$root_id = get_network()->site_id;
	$need_to_switch = ( get_current_blog_id() !== $root_id ) ? true : true;
	if ( $need_to_switch ) {
		switch_to_blog( $root_id );
	}
	foreach ( $colors as $k ) {
		$v = get_option( "pb_network_color_$k" );
		if ( $v ) {
			$values[ $k ] = $v;
		}
	}
	if ( $need_to_switch ) {
		restore_current_blog();
	}
	$output = '';
	if ( ! empty( $values ) ) {
		$output .= '<style type="text/css">:root{';
		foreach ( $values as $k => $v ) {
			$k = str_replace( '_', '-', $k );
			$output .= "--$k:$v;";
		}
		$output .= '}</style>';
	}
	return $output;
}

/**
 * Print JavaScript to the login footer to handle some things. Good lord this page is hard to customize.
 *
 * @since 5.0.0
 *
 * @return string
 */
function login_scripts() {
	$assets = new Assets( 'pressbooks', 'plugin' );
	wp_enqueue_script( 'pressbooks-login', $assets->getPath( 'scripts/login.js' ), false, null );
	wp_localize_script(
		'pressbooks-login', 'PB_Login', [
			'logInTitle' => __( 'Log In', 'pressbooks' ),
			'lostPasswordTitle' => __( 'Lost Password', 'pressbooks' ),
			'resetPasswordTitle' => __( 'Reset Password', 'pressbooks' ),
			'passwordResetTitle' => __( 'Password Reset', 'pressbooks' ),
		]
	);
}

/**
 * Favicon
 */
function favicon() {
	// Specify 'pressbooks-book' because when in network or main site, WordPress uses Aldine.
	$href = get_theme_root_uri( 'pressbooks-book' ) . '/pressbooks-book';
	?>
	<link rel="icon" type="image/png" sizes="32x32" href="<?php echo $href; ?>/dist/images/favicon-32x32.png">
	<link rel="icon" type="image/png" sizes="16x16" href="<?php echo $href; ?>/dist/images/favicon-16x16.png">
	<link rel="shortcut icon" href="<?php echo $href; ?>/dist/images/favicon.ico">
	<?php
}
