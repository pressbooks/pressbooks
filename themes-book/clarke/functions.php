<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */

function clarke_theme_setup() {
	add_theme_support( 'pressbooks_global_typography', 'grc' );
}

add_action( 'after_setup_theme', 'clarke_theme_setup' );
