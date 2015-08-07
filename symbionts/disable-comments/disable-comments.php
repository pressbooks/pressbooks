<?php
/**
 * @author    Brad Payne <brad@bradpayne.ca>
 * @license   GPL-2.0+
 * @copyright 2014 Brad Payne
 * 
 * Plugin Name: Disable Comments for Pressbooks
 * Description:  Comments can be disabled according to post type.
 * Version: 1.0.0
 * Author: Brad Payne 
 * Original Author: Samir Shah
 * License: GPL-2.0+
 * Text Domain: disable-comments
 * Domain Path: /languages/
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */
/**
 *
 * This plugin is forked from the original Disable Comments v1.1.1 http://wordpress.org/extend/plugins/disable-comments/ (c) Samir Shah 
 * http://rayofsolaris.net/
 * https://github.com/solarissmoke/disable-comments
 * 
 * This fork modifies the plugin to work with PressBooks; strips unwanted features, adds others.
 * 
 * Designed to be activated only at the site level for site autonomy. Also gets rid of the atomic/permanently destructive 
 * comment disabling that the original plugin currently has. 
 * The default state is to disable the comments for the custom post types that PressBooks defines. 
 * Requires the user to actively turn comments on if they want comments.
 *
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class Disable_Comments {

	/**
	 * 
	 */
	const DB_VERSION = 5;

	/**
	 *
	 * @var type 
	 */
	private $options;
	
	/**
	 *
	 * @var type 
	 */
	private $modified_types = array();

	/**
	 * 
	 */
	function __construct() {

		// load options
		$this->options = get_option( 'disable_comments_options', array() );
		

		// load language files
		load_plugin_textdomain( 'disable-comments', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

		// If it looks like first run, check compat
		if ( empty( $this->options ) && version_compare( $GLOBALS['wp_version'], '3.4', '<' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			deactivate_plugins( __FILE__ );
			if ( isset( $_GET['action'] ) && ( $_GET['action'] == 'activate' || $_GET['action'] == 'error_scrape' ) )
					exit( sprintf( __( 'Disable Comments requires WordPress version %s or greater.', 'disable-comments' ), '3.3' ) );
		}

		$old_ver = isset( $this->options['db_version'] ) ? $this->options['db_version'] : 0;
		
		if ( $old_ver < self::DB_VERSION ) {
			if ( $old_ver < 2 ) {
				// upgrade options from version 0.2.1 or earlier to 0.3
				$this->options['disabled_post_types'] = get_option( 'disable_comments_post_types', array() );
				delete_option( 'disable_comments_post_types' );
			}
			if ( $old_ver < 5 ) {
				// simple is beautiful - remove multiple settings in favour of one
				$this->options['remove_everywhere'] = isset( $this->options['remove_admin_menu_comments'] ) ? $this->options['remove_admin_menu_comments'] : false;
				foreach ( array( 'remove_admin_menu_comments', 'remove_admin_bar_comments', 'remove_recent_comments', 'remove_discussion', 'remove_rc_widget' ) as $v )
					unset( $this->options[$v] );
			}

			foreach ( array( 'remove_everywhere', 'permanent' ) as $v )
				if ( ! isset( $this->options[$v] ) ) $this->options[$v] = false;

			$this->options['db_version'] = self::DB_VERSION;
			$this->update_options();
		}
		
		// Not part of the original plugin
		// Added for integration with PressBooks Textbook plugin
		// Sets defaults, disables comments on custom post types
		$this->pbtModifyDisableComments();
		
		// these need to happen now
		if ( $this->options['remove_everywhere'] ) {
			add_action( 'widgets_init', array( $this, 'disable_rc_widget' ) );
			add_filter( 'wp_headers', array( $this, 'filter_wp_headers' ) );
			add_action( 'template_redirect', array( $this, 'filter_query' ), 9 ); // before redirect_canonical
			// Admin bar filtering has to happen here since WP 3.6
			add_action( 'template_redirect', array( $this, 'filter_admin_bar' ) );
			add_action( 'admin_init', array( $this, 'filter_admin_bar' ) );
		}

		// these can happen later
		add_action( 'wp_loaded', array( $this, 'setup_filters' ) );
	}

	/**
	 * 
	 */
	private function update_options() {
		update_option( 'disable_comments_options', $this->options );
	}

	/**
	 * 
	 */
	function setup_filters() {
		if ( ! empty( $this->options['disabled_post_types'] ) ) {
			foreach ( $this->options['disabled_post_types'] as $type ) {
				// we need to know what native support was for later
				if ( post_type_supports( $type, 'comments' ) ) {
					$this->modified_types[] = $type;
					remove_post_type_support( $type, 'comments' );
					remove_post_type_support( $type, 'trackbacks' );
				}
			}
			add_filter( 'comments_open', array( $this, 'filter_comment_status' ), 20, 2 );
			add_filter( 'pings_open', array( $this, 'filter_comment_status' ), 20, 2 );
		} elseif ( is_admin() ) {
			add_action( 'all_admin_notices', array( $this, 'setup_notice' ) );
		}

		// Filters for the admin only
		if ( is_admin() ) {

			add_action( 'admin_menu', array( $this, 'settings_menu' ) );
			add_filter( 'plugin_action_links', array( $this, 'plugin_actions_links' ), 10, 2 );
			if ( is_multisite() ) // We're on a multisite setup, but the plugin isn't network activated.
					register_deactivation_hook( __FILE__, array( $this, 'single_site_deactivate' ) );

			add_action( 'admin_print_footer_scripts', array( $this, 'discussion_notice' ) );
			add_filter( 'plugin_row_meta', array( $this, 'set_plugin_meta' ), 10, 2 );

			// if only certain types are disabled, remember the original post status
			if ( ! ( $this->persistent_mode_allowed() && $this->options['permanent'] ) && ! $this->options['remove_everywhere'] ) {
				add_action( 'edit_form_advanced', array( $this, 'edit_form_inputs' ) );
				add_action( 'edit_page_form', array( $this, 'edit_form_inputs' ) );
			}

			if ( $this->options['remove_everywhere'] ) {
				add_action( 'admin_menu', array( $this, 'filter_admin_menu' ), 9999 ); // do this as late as possible
				add_action( 'admin_head', array( $this, 'hide_dashboard_bits' ) );
				add_action( 'wp_dashboard_setup', array( $this, 'filter_dashboard' ) );
				add_filter( 'pre_option_default_pingback_flag', '__return_zero' );
			}
		}
		// Filters for front end only
		else {
			add_action( 'template_redirect', array( $this, 'check_comment_template' ) );
		}
	}

	/**
	 * 
	 */
	function check_comment_template() {
		if ( is_singular() && ( $this->options['remove_everywhere'] || in_array( get_post_type(), $this->options['disabled_post_types'] ) ) ) {
			// Kill the comments template. This will deal with themes that don't check comment stati properly!
			add_filter( 'comments_template', array( $this, 'dummy_comments_template' ), 20 );
			// Remove comment-reply script for themes that include it indiscriminately
			wp_deregister_script( 'comment-reply' );
		}
	}

	/**
	 * 
	 * @return type
	 */
	function dummy_comments_template() {
		return dirname( __FILE__ ) . '/comments-template.php';
	}

	/**
	 * 
	 * @param type $headers
	 * @return type
	 */
	function filter_wp_headers( $headers ) {
		unset( $headers['X-Pingback'] );
		return $headers;
	}

	/**
	 * 
	 */
	function filter_query() {
		if ( is_comment_feed() ) {
			if ( isset( $_GET['feed'] ) ) {
				// remove possible XSS
				$url = esc_url_raw( remove_query_arg( 'feed', 301 ) );
				wp_redirect( $url );
				exit;
			}

			set_query_var( 'feed', '' ); // redirect_canonical will do the rest
			redirect_canonical();
		}
	}

	/**
	 * 
	 */
	function filter_admin_bar() {
		if ( is_admin_bar_showing() ) {
			// Remove comments links from admin bar
			remove_action( 'admin_bar_menu', 'wp_admin_bar_comments_menu', 50 ); // WP<3.3
			remove_action( 'admin_bar_menu', 'wp_admin_bar_comments_menu', 60 ); // WP 3.3
			if ( is_multisite() )
					add_action( 'admin_bar_menu', array( $this, 'remove_network_comment_links' ), 500 );
		}
	}

	/**
	 * 
	 * @param type $wp_admin_bar
	 */
	function remove_network_comment_links( $wp_admin_bar ) {
		// We have no way to know whether the plugin is active on other sites, so only remove this one
		$wp_admin_bar->remove_menu( 'blog-' . get_current_blog_id() . '-c' );
	}

	/**
	 * 
	 * @global type $post
	 */
	function edit_form_inputs() {
		global $post;
		// Without a dicussion meta box, comment_status will be set to closed on new/updated posts
		if ( in_array( $post->post_type, $this->modified_types ) ) {
			echo '<input type="hidden" name="comment_status" value="' . $post->comment_status . '" /><input type="hidden" name="ping_status" value="' . $post->ping_status . '" />';
		}
	}

	/**
	 * 
	 */
	function discussion_notice() {
		if ( get_current_screen()->id == 'options-discussion' && ! empty( $this->options['disabled_post_types'] ) ) {
			$names = array();
			foreach ( $this->options['disabled_post_types'] as $type )
				$names[$type] = get_post_type_object( $type )->labels->name;
			?>
			<script>
				jQuery(document).ready(function ($) {
					$(".wrap h2").first().after(<?php echo json_encode( '<div style="color: #900"><p>' . sprintf( __( 'Note: The <em>Disable Comments</em> plugin is currently active, and comments are completely disabled on: %s. Many of the settings below will not be applicable for those post types.', 'disable-comments' ), implode( __( ', ' ), $names ) ) . '</p></div>' ); ?>);
				});
			</script>
			<?php
		}
	}

	/**
	 * Return context-aware settings page URL
	 */
	private function settings_page_url() {
		$base = admin_url( 'options-general.php' );
		// Prevent XSS 
		$url = esc_url( add_query_arg( 'page', 'disable_comments_settings', $base ) );
		return $url;
	}

	/**
	 * 
	 * @return type
	 */
	function setup_notice() {
		if ( strpos( get_current_screen()->id, 'settings_page_disable_comments_settings' ) === 0 )
				return;
		$hascaps = current_user_can( 'manage_options' );
		if ( $hascaps )
				echo '<div class="updated fade"><p>' . sprintf( __( 'The <em>Disable Comments</em> plugin is active and configured to disable comments on custom post types for PressBooks. Visit the <a href="%s">configuration page</a> to choose which post types to <b>enable</b> comments on.', 'disable-comments' ), esc_attr( $this->settings_page_url() ) ) . '</p></div>';
	}

	/**
	 * 
	 * @global type $pagenow
	 */
	function filter_admin_menu() {
		global $pagenow;

		if ( $pagenow == 'comment.php' || $pagenow == 'edit-comments.php' || $pagenow == 'options-discussion.php' )
				wp_die( __( 'Comments are closed.' ), '', array( 'response' => 403 ) );

		remove_menu_page( 'edit-comments.php' );
		remove_submenu_page( 'options-general.php', 'options-discussion.php' );
	}

	/**
	 * 
	 */
	function filter_dashboard() {
		remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'normal' );
	}

	/**
	 * 
	 */
	function hide_dashboard_bits() {
		if ( 'dashboard' == get_current_screen()->id )
				add_action( 'admin_print_footer_scripts', array( $this, 'dashboard_js' ) );
	}

	/**
	 * 
	 */
	function dashboard_js() {
		if ( version_compare( $GLOBALS['wp_version'], '3.8', '<' ) ) {
			// getting hold of the discussion box is tricky. The table_discussion class is used for other things in multisite
			echo '<script> jQuery(function($){ $("#dashboard_right_now .table_discussion").has(\'a[href="edit-comments.php"]\').first().hide(); }); </script>';
		} else {
			echo '<script> jQuery(function($){ $("#dashboard_right_now .comment-count, #latest-comments").hide(); }); </script>';
		}
	}

	/**
	 * 
	 * @param type $open
	 * @param type $post_id
	 * @return type
	 */
	function filter_comment_status( $open, $post_id ) {
		$post = get_post( $post_id );
		return ( $this->options['remove_everywhere'] || in_array( $post->post_type, $this->options['disabled_post_types'] ) ) ? false : $open;
	}

	/**
	 * 
	 */
	function disable_rc_widget() {
		// This widget has been removed from the Dashboard in WP 3.8 and can be removed in a future version
		unregister_widget( 'WP_Widget_Recent_Comments' );
	}

	/**
	 * 
	 * @staticvar type $plugin
	 * @param string $links
	 * @param type $file
	 * @return string
	 */
	function set_plugin_meta( $links, $file ) {
		static $plugin;
		$plugin = plugin_basename( __FILE__ );
		if ( $file == $plugin ) {
			$links[] = '<a href="https://github.com/solarissmoke/disable-comments">GitHub</a>';
		}
		return $links;
	}

	/**
	 * Add links to Settings page
	 */
	function plugin_actions_links( $links, $file ) {
		static $plugin;
		$plugin = plugin_basename( __FILE__ );
		if ( $file == $plugin && current_user_can( 'manage_options' ) ) {
			array_unshift(
				$links, sprintf( '<a href="%s">%s</a>', esc_attr( $this->settings_page_url() ), __( 'Settings' ) )
			);
		}

		return $links;
	}

	/**
	 * 
	 */
	function settings_menu() {
		$title = __( 'Disable Comments', 'disable-comments' );
		add_submenu_page( 'options-general.php', $title, $title, 'manage_options', 'disable_comments_settings', array( $this, 'settings_page' ) );
	}

	/**
	 * 
	 */
	function settings_page() {
		$typeargs = array( 'public' => true );

		$types = get_post_types( $typeargs, 'objects' );
		foreach ( array_keys( $types ) as $type ) {
			if ( ! in_array( $type, $this->modified_types ) && ! post_type_supports( $type, 'comments' ) ) // the type doesn't support comments anyway
					unset( $types[$type] );
		}

		$persistent_allowed = $this->persistent_mode_allowed();

		if ( isset( $_POST['submit'] ) ) {
			check_admin_referer( 'disable-comments-admin' );
			$this->options['remove_everywhere'] = ( $_POST['mode'] == 'remove_everywhere' );

			if ( $this->options['remove_everywhere'] )
					$disabled_post_types = array_keys( $types );
			else
					$disabled_post_types = empty( $_POST['disabled_types'] ) ? array() : ( array ) $_POST['disabled_types'];

			$disabled_post_types = array_intersect( $disabled_post_types, array_keys( $types ) );

			// entering permanent mode, or post types have changed
			if ( $persistent_allowed && ! empty( $_POST['permanent'] ) && ( ! $this->options['permanent'] || $disabled_post_types != $this->options['disabled_post_types'] ) )
					$this->enter_permanent_mode();

			$this->options['disabled_post_types'] = $disabled_post_types;
			$this->options['permanent'] = $persistent_allowed && isset( $_POST['permanent'] );

			$this->update_options();
			$cache_message = WP_CACHE ? ' <strong>' . __( 'If a caching/performance plugin is active, please invalidate its cache to ensure that changes are reflected immediately.' ) . '</strong>' : '';
			echo '<div id="message" class="updated"><p>' . __( 'Options updated. Changes to the Admin Menu and Admin Bar will not appear until you leave or reload this page.', 'disable-comments' ) . $cache_message . '</p></div>';
		}
		?>
		<style> .indent {padding-left: 2em} </style>
		<div class="wrap">
			<?php screen_icon( 'plugins' ); ?>
			<h2><?php _e( 'Disable Comments', 'disable-comments' ) ?></h2>
			<?php
			if ( WP_CACHE )
					echo '<div class="updated"><p>' . __( "It seems that a caching/performance plugin is active on this site. Please manually invalidate that plugin's cache after making any changes to the settings below.", 'disable-comments' ) . '</p></div>';
			?>
			<form action="" method="post" id="disable-comments">
				<ul>

					<li><label for="selected_types"><input type="radio" id="selected_types" name="mode" value="selected_types" <?php checked(  ! $this->options['remove_everywhere'] ); ?> /> <strong><?php _e( 'On certain post types', 'disable-comments' ) ?></strong></label>:
						<p></p>
						<ul class="indent" id="listoftypes">
							<?php foreach ( $types as $k => $v )
								echo "<li><label for='post-type-$k'><input type='checkbox' name='disabled_types[]' value='$k' " . checked( in_array( $k, $this->options['disabled_post_types'] ), true, false ) . " id='post-type-$k'> {$v->labels->name}</label></li>";
							?>
						</ul>
						<p class="indent"><?php _e( 'Disabling comments will also disable trackbacks and pingbacks. All comment-related fields will also be hidden from the edit/quick-edit screens of the affected posts. These settings cannot be overridden for individual posts.', 'disable-comments' ) ?></p>
					</li>
				</ul>

				<?php wp_nonce_field( 'disable-comments-admin' ); ?>
				<p class="submit"><input class="button-primary" type="submit" name="submit" value="<?php _e( 'Save Changes' ) ?>"></p>
			</form>
		</div>

		<?php
	}

	/**
	 * 
	 * @return type
	 */
	private function enter_permanent_mode() {
		$types = $this->options['disabled_post_types'];
		if ( empty( $types ) ) return;

		$this->close_comments_in_db( $types );
	}

	/**
	 * 
	 * @global type $wpdb
	 * @param type $types
	 */
	private function close_comments_in_db( $types ) {
		global $wpdb;
		$bits = implode( ', ', array_pad( array(), count( $types ), '%s' ) );
		$wpdb->query( $wpdb->prepare( "UPDATE `$wpdb->posts` SET `comment_status` = 'closed', ping_status = 'closed' WHERE `post_type` IN ( $bits )", $types ) );
	}

	/**
	 * 
	 * @return type
	 */
	private function persistent_mode_allowed() {
		return apply_filters( 'disable_comments_allow_persistent_mode', true );
	}

	/**
	 * 
	 */
	function single_site_deactivate() {
		// for single sites, delete the options upon deactivation, not uninstall
		delete_option( 'disable_comments_options' );
	}

	/**
	 * Not part of the original plugin. 
	 * Added for integration with PressBooks Textbook plugin
	 */
	private function pbtModifyDisableComments() {
		$post_types = array( 'chapter', 'front-matter', 'back-matter', 'attachment' );

		// if it looks like the first run, we set up a default 
		// to disable comments on PB custom post types
		if ( empty( $this->options['disabled_post_types'] ) && ! isset( $this->options['pbt_first_run'] ) ) {
			$this->options['disabled_post_types'] = $post_types;
			$this->options['pbt_first_run'] = 0; // set a flag
		}
		$this->update_options();
	}

}

new Disable_Comments();
