<?php
/**
 * Plugin Name: Pressbooks
 * Plugin URI: https://pressbooks.org
 * GitHub Plugin URI: pressbooks/pressbooks
 * Release Asset: true
 * Description: Simple Book Production
 * x-release-please-start-version
 * Version: 6.22.1
 * x-release-please-end
 * Requires at least: WordPress 6.6.1
 * Requires PHP: 8.1
 * Author: Pressbooks (Book Oven Inc.)
 * Author URI: https://pressbooks.org
 * License: GPL v3 or later
 * Text Domain: pressbooks
 * Network: True
 *
 * @package Pressbooks
 * @author Pressbooks (Book Oven Inc.)
 * @license GPL-3.0-or-later
 */

use Pressbooks\Pressbooks;

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

// -------------------------------------------------------------------------------------------------------------------
// Initialize
// -------------------------------------------------------------------------------------------------------------------

$GLOBALS['pressbooks'] = new Pressbooks();
