<?php
/**
 * Branding.
 *
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */

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
 */
function custom_color_scheme() {
	\wp_dequeue_style( 'login' );
	$style = get_customizer_colors();
	/**
	 * Print CSS for a custom color scheme for the login page.
	 *
	 * @since 4.3.0
	 */
	$style = apply_filters(
		'pb_login_color_scheme',
		/**
		 * Print <link> to a custom color scheme for the login page.
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
 */
function custom_login_logo() {
	$assets = new Assets( 'pressbooks', 'plugin' );
	if ( has_custom_logo() ) {
		$custom_logo_id = get_theme_mod( 'custom_logo' );
		$logo = sprintf(
			'<style type="text/css">#login h1 a {background-image: url(%s);}</style>',
			wp_get_attachment_image_src( $custom_logo_id, 'logo' )[0]
		);
	} else {
		$logo = sprintf(
			'<style type="text/css">#login h1 a {background-image: url(%s);}</style>',
			$assets->getPath( 'images/PB-logo.svg' )
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
 */
function login_url() {
	return home_url(); // changes the url link from wordpress.org to your blog or website's url
}

/**
 * Changing the login page URL hover text
 */
function login_title() {
	return get_bloginfo( 'title' ); // changing the title from "Powered by WordPress" to whatever you wish
}

/**
 * Replaces 'WordPress' with 'Pressbooks' in titles of admin pages.
 */
function admin_title( $admin_title ) {
	$title = str_replace( 'WordPress', 'Pressbooks', $admin_title );
	return $title;
}
/**
 *
 */
function get_customizer_colors() {
	$colors = [
		'primary',
		'accent',
		'primary_fg',
		'accent_fg',
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

function login_footer() {
	printf(
		'<script type="text/javascript">var loginForm = document.getElementById("loginform"); loginForm.insertAdjacentHTML("beforebegin", "<p class=\'subtitle\'>%s</p>");</script>',
		__( 'Log In', 'pressbooks' )
	);
}
