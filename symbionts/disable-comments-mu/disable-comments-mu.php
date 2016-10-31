<?php
/*
Plugin Name: Disable Comments (Must Use version)
Plugin URI: https://github.com/solarissmoke/disable-comments-mu
Description: Disables all WordPress comment functionality on the entire network.
Version: 1.1.2
Author: Samir Shah
Author URI: http://rayofsolaris.net/
License: GPL2
GitHub Plugin URI: https://github.com/solarissmoke/disable-comments-mu
*/

if( !defined( 'ABSPATH' ) )
	exit;

class Disable_Comments_MU {
	function __construct() {
		// these need to happen now
		add_action( 'widgets_init', array( $this, 'disable_rc_widget' ) );
		add_filter( 'wp_headers', array( $this, 'filter_wp_headers' ) );
		add_action( 'template_redirect', array( $this, 'filter_query' ), 9 );	// before redirect_canonical

		// Admin bar filtering has to happen here since WP 3.6
		add_action( 'template_redirect', array( $this, 'filter_admin_bar' ) );
		add_action( 'admin_init', array( $this, 'filter_admin_bar' ) );

		// these can happen later
		add_action( 'wp_loaded', array( $this, 'setup_filters' ) );
	}

	function setup_filters(){
		$types = array_keys( get_post_types( array( 'public' => true ), 'objects' ) );
		if( !empty( $types ) ) {
			foreach( $types as $type ) {
				// we need to know what native support was for later
				if( post_type_supports( $type, 'comments' ) ) {
					remove_post_type_support( $type, 'comments' );
					remove_post_type_support( $type, 'trackbacks' );
				}
			}
		}

		// Filters for the admin only
		if( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'filter_admin_menu' ), 9999 );	// do this as late as possible
			add_action( 'admin_head', array( $this, 'hide_dashboard_bits' ) );
			add_action( 'wp_dashboard_setup', array( $this, 'filter_dashboard' ) );
			add_filter( 'pre_option_default_pingback_flag', '__return_zero' );
		}
		// Filters for front end only
		else {
			add_action( 'template_redirect', array( $this, 'check_comment_template' ) );
			add_filter( 'comments_open', array( $this, 'filter_comment_status' ), 20, 2 );
			add_filter( 'pings_open', array( $this, 'filter_comment_status' ), 20, 2 );

			// remove comments links from feed
			add_filter('post_comments_feed_link', '__return_false', 10, 1);
			add_filter('comments_link_feed', '__return_false', 10, 1);
			add_filter('comment_link', '__return_false', 10, 1);

			// remove comment count from feed
			add_filter('get_comments_number', '__return_false', 10, 2);

			// Remove feed link from header
			add_filter( 'feed_links_show_comments_feed', '__return_false' );

			// run when wp_head executes
			add_action('wp_head', array( $this, 'before_wp_head' ), 0 );
		}
	}

	function check_comment_template() {
		if( is_singular() ) {
			// Kill the comments template. This will deal with themes that don't check comment stati properly!
			add_filter( 'comments_template', array( $this, 'dummy_comments_template' ), 20 );
			// Remove comment-reply script for themes that include it indiscriminately
			wp_deregister_script( 'comment-reply' );
			// Remove feed action
			remove_action( 'wp_head', 'feed_links_extra', 3 );
		}
	}

	function dummy_comments_template() {
		return dirname( __FILE__ ) . '/disable-comments-mu/comments-template.php';
	}

	function filter_wp_headers( $headers ) {
		unset( $headers['X-Pingback'] );
		return $headers;
	}

	function filter_query() {
		if( is_comment_feed() ) {
			// we are inside a comment feed
			wp_die( __( 'Comments are closed.' ), '', array( 'response' => 403 ) );
		}
	}

	function filter_admin_bar() {
		if( is_admin_bar_showing() ) {
			// Remove comments links from admin bar
			remove_action( 'admin_bar_menu', 'wp_admin_bar_comments_menu', 50 );	// WP<3.3
			remove_action( 'admin_bar_menu', 'wp_admin_bar_comments_menu', 60 );	// WP 3.3
			if( is_multisite() )
				add_action( 'admin_bar_menu', array( $this, 'remove_network_comment_links' ), 500 );
		}
	}

	function remove_network_comment_links( $wp_admin_bar ) {
		if( is_user_logged_in() ) {
			foreach( (array) $wp_admin_bar->user->blogs as $blog ) {
				$wp_admin_bar->remove_menu( 'blog-' . $blog->userblog_id . '-c' );
			}
		}
	}

	function filter_admin_menu(){
		global $pagenow;

		if ( in_array( $pagenow, array( 'comment.php', 'edit-comments.php', 'options-discussion.php' ) ) ) {
			wp_die( __( 'Comments are closed.' ), '', array( 'response' => 403 ) );
		}

		remove_menu_page( 'edit-comments.php' );
		remove_submenu_page( 'options-general.php', 'options-discussion.php' );
	}

	function filter_dashboard(){
		remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'normal' );
	}

	function hide_dashboard_bits(){
		if( 'dashboard' == get_current_screen()->id )
			add_action( 'admin_print_footer_scripts', array( $this, 'dashboard_js' ) );
	}

	function dashboard_js(){
		if( version_compare( $GLOBALS['wp_version'], '3.8', '<' ) ) {
			// getting hold of the discussion box is tricky. The table_discussion class is used for other things in multisite
			echo '<script> jQuery(function($){ $("#dashboard_right_now .table_discussion").has(\'a[href="edit-comments.php"]\').first().hide(); }); </script>';
		}
		else {
			echo '<script> jQuery(function($){ $("#dashboard_right_now .comment-count, #latest-comments").hide(); }); </script>';
		}
	}

	function filter_comment_status( $open, $post_id ) {
		return false;
	}

	function disable_rc_widget() {
		// This widget has been removed from the Dashboard in WP 3.8 and can be removed in a future version
		unregister_widget( 'WP_Widget_Recent_Comments' );
	}

	function before_wp_head( $args = array() ) {
		// if wp_head feed_links has not been tampered with (WP 4.1.1)
		// In WP > 4.4 the feed_links_show_comments_feed filter is used instead.
		if ( version_compare( $GLOBALS['wp_version'], '4.4', '<' ) && has_action( 'wp_head', 'feed_links' ) == 2 ) {
			// replace it with a modified version
			remove_action( 'wp_head', 'feed_links', 2 );
			add_action( 'wp_head', array( $this, 'feed_links' ) );
		}
	}

	// replaces feed_links function, WP 4.1.1
	// Not required after WP 4.4
	function feed_links( $args = array() ) {
		if ( !current_theme_supports('automatic-feed-links') )
			return;
		$defaults = array(
			/* translators: Separator between blog name and feed type in feed links */
			'separator'	=> _x('&raquo;', 'feed link'),
			/* translators: 1: blog title, 2: separator (raquo) */
			'feedtitle'	=> __('%1$s %2$s Feed'),
		);
		$args = wp_parse_args( $args, $defaults );
		echo '<link rel="alternate" type="' . feed_content_type() . '" title="' . esc_attr( sprintf( $args['feedtitle'], get_bloginfo('name'), $args['separator'] ) ) . '" href="' . esc_url( get_feed_link() ) . "\" />\n";
	}
}

new Disable_Comments_MU();
