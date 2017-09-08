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
 * Apply Color Scheme to Login Page
 * To customize this, add a filter to the 'pb_login_color_scheme' hook
 * that returns a string containing a link tag for your own admin color scheme.
 */
function custom_color_scheme() {
	$assets = new Assets( 'pressbooks', 'plugin' );
	$html = '<link rel="stylesheet" type="text/css" href="' . $assets->getPath( 'styles/colors-pb.css' ) . '" media="screen" />';
	/**
	 * Print <link> to a custom color scheme for the login page.
	 *
	 * @since 4.3.0
	 */
	$html = apply_filters(
		'pb_login_color_scheme',
		/**
		 * Print <link> to a custom color scheme for the login page.
		 *
		 * @since 3.5.2
		 * @deprecated 4.3.0 Use pb_login_color_scheme instead.
		 */
		apply_filters( 'pressbooks_login_color_scheme', $html )
	);
	echo $html;
}

/**
 * Add Custom Login Graphic.
 * To customize this, add a filter to the 'pb_login_logo' hook that
 * returns a string containing a <link> or <style> tag that supplies your custom logo.
 */
function custom_login_logo() {
	$html = '<style type="text/css">
	.login h1 a {
  	background-image: url(' . PB_PLUGIN_URL . 'assets/dist//images/PB-logo.svg' . ');
  	background-size: 276px 40px;
  	width: 276px;
  	height: 40px; }
	.login .message {
  	border-left: 4px solid #0077cc; }
	.login #backtoblog a:hover, .login #backtoblog a:active, .login #backtoblog a:focus, .login #nav a:hover, .login #nav a:active, .login #nav a:focus {
  	color: #d4002d; }
	.no-svg .login h1 a {
  	background-image: url(' . PB_PLUGIN_URL . 'assets/dist//images/PB-logo.png' . '; }
	</style>';
	/**
	 * Print <link> or <style> tag to add a custom logo for the login page.
	 *
	 * @since 4.3.0
	 */
	$html = apply_filters(
		'pb_login_logo',
		/**
		 * Print <link> or <style> tag to add a custom logo for the login page.
		 *
		 * @since 3.5.2
		 * @deprecated 4.3.0 Use pb_login_logo instead.
		 */
		apply_filters( 'pressbooks_login_logo', $html )
	);
	echo $html;
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
