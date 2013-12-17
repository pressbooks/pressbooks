<?php

/*
	Plugin Name: Post Type Icons
	Plugin URI: http://boyn.es/category/post-type-icons/
	Description: A simple plugin for setting icons for post types. Requires MP6 (new admin design)
	Version: 0.1
	Author: Matthew Boynes
	Author URI: http://boyn.es/
*/
/*  This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

if ( !class_exists( 'Post_Type_Icons' ) ) :

if ( !defined( 'PTI_PLUGIN_URL' ) )
	define( 'PTI_PLUGIN_URL', plugins_url( '', __FILE__ ) . '/' );

class Post_Type_Icons {

	private static $instance;

	public $has_admin_menu = true;

	private function __construct() {
		/* Don't do anything, needs to be initialized via instance() method */
	}

	public function __clone() { wp_die( "Please don't __clone Post_Type_Icons" ); }

	public function __wakeup() { wp_die( "Please don't __wakeup Post_Type_Icons" ); }

	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new Post_Type_Icons;
			self::$instance->setup();
		}
		return self::$instance;
	}

	public function setup() {
		add_action( 'admin_print_styles', array( $this, 'add_styles' ) );
		add_action( 'init', array( $this, 'safe_mode' ) );
		if ( $this->has_admin_menu = apply_filters( 'pti_plugin_show_admin_menu', true ) )
			add_action( 'admin_menu', array( $this, 'menu' ) );
	}

	public function menu() {
		add_management_page( __('Post Type Icons'), __('Post Type Icons'), 'manage_options', 'pti', array( $this, 'reference' ) );
	}

	public function reference() {
		if ( !current_user_can( 'manage_options' ) ) wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		require_once __DIR__ . '/font-awesome/class-pti-font-awesome.php';
		?>
		<div class="wrap">
			<div id="pti_icons">
				<?php do_action( 'pti_plugin_icon_demos' ) ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Add styles to the site <head> if applicable
	 *
	 * @return void
	 */
	public function add_styles() {
		?>
		<style type="text/css">
			<?php if ( $this->has_admin_menu ) : ?>
			#pti_icons dl { float: left; width: 125px; padding: 10px 5px; overflow:hidden; }
			#pti_icons dt { margin: 0 auto 3px; padding: 0; width: 64px; height: 64px; font-size: 64px; line-height: 64px; text-align: center; }
			#pti_icons dd { margin: 0; padding: 0; white-space: nowrap; text-align: center; }
			<?php endif ?>
			<?php do_action( 'pti_plugin_icon_css' ) ?>
		</style>
		<?php
	}

	public function safe_mode() {
		if ( isset( $GLOBALS['pti_icons'] ) ) {
			pti_set_post_type_icon( $GLOBALS['pti_icons'] );
		}
	}

}

function Post_Type_Icons() {
	return Post_Type_Icons::instance();
}
if ( is_admin() )
	add_action( 'after_setup_theme', 'Post_Type_Icons' );

function pti_set_post_type_icon( $post_type, $icon = false, $library = 'font_awesome' ) {
	if ( is_admin() ) {
		if ( 'font_awesome' == $library ) {
			require_once __DIR__ . '/font-awesome/class-pti-font-awesome.php';
		}
		do_action( 'pti_plugin_set_icon_' . $library, $post_type, $icon );
	}
}

endif;


?>