<?php
/**
 * Branding.
 *
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */
namespace PressBooks\Admin\Branding;

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