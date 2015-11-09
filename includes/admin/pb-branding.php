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
  background-image: url(<?php echo PB_PLUGIN_URL; ?>assets/images/PB-logo.svg);
  background-size: 276px 40px;
  width: 276px;
  height: 40px; }
.login .message {
  border-left: 4px solid #0077cc; }
.login #backtoblog a:hover, .login #backtoblog a:active, .login #backtoblog a:focus, .login #nav a:hover, .login #nav a:active, .login #nav a:focus {
  color: #d4002d; }

.no-svg .login h1 a {
  background-image: url(<?php echo PB_PLUGIN_URL; ?>assets/images/PB-logo.png); }
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