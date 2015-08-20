<?php
/**
 * Branding.
 *
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */
namespace PressBooks\Admin\Branding;

/**
 * Add Custom Login Graphic
 */
function custom_login_logo() {
    ?>
<style type="text/css">
    .login h1 a {
    	background-image: url(<?php echo PB_PLUGIN_URL; ?>assets/images/PB-logo.svg) !important;
		background-size: 276px 40px;
		width: 276px;
		height: 40px;
    }
    body.login {
    	 background-color: #fff;
    }
    .no-svg .login h1 a {
    	background-image: url(<?php echo PB_PLUGIN_URL; ?>assets/images/PB-logo.png) !important;	    
    }
    .wp-core-ui .button-primary {
	    background-color: #c3c3c3;
	    border: none;
	    box-shadow: none;
	    color: #333;
    }
    .wp-core-ui .button-primary:hover {
	    background-color: #ccc;
	    box-shadow: none;
    }
</style>
<?php }

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