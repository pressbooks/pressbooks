<?php
/**
 * Branding.
 *
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */
namespace Pressbooks\Admin\Branding;

/**
 * Apply Color Scheme to Login Page
 * To customize this, add a filter to the 'pressbooks_login_color_scheme' hook
 * that returns a string containing a link tag for your own admin color scheme.
 */
function custom_color_scheme() {
	$html = '<link rel="stylesheet" type="text/css" href="' . \Pressbooks\Utility\asset_path( 'styles/colors-pb.css' ) . '" media="screen" />';
	$html = apply_filters( 'pressbooks_login_color_scheme', $html );
	echo $html;
}

/**
 * Add Custom Login Graphic.
 * To customize this, add a filter to the 'pressbooks_login_logo' hook that
 * returns a string containing a style tag comparable to the one below.
 */
function custom_login_logo() {
 $html = '<style type="text/css">
	.login h1 a {
  	background-image: url(' . \Pressbooks\Utility\asset_path( 'images/PB-logo.svg' ) . ');
  	background-size: 276px 40px;
  	width: 276px;
  	height: 40px; }
	.login .message {
  	border-left: 4px solid #0077cc; }
	.login #backtoblog a:hover, .login #backtoblog a:active, .login #backtoblog a:focus, .login #nav a:hover, .login #nav a:active, .login #nav a:focus {
  	color: #d4002d; }
	.no-svg .login h1 a {
  	background-image: url(' . \Pressbooks\Utility\asset_path( 'images/PB-logo.png' ) . '; }
	</style>';
	$html = apply_filters( 'pressbooks_login_logo', $html );
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
