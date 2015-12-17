<?php
/**
 Plugin Name: MCE Table Buttons
 Plugin URI: http://10up.com/plugins-modules/wordpress-mce-table-buttons/
 Description: Add <strong>controls for table editing</strong> to the visual content editor with this <strong>light weight</strong> plug-in.
 Version: 3.2
 Author: Jake Goldman, 10up, Oomph
 Author URI: http://10up.com
 License: GPLv2 or later
*/

class MCE_Table_Buttons {

	/**
	 * Handles initializing this class and returning the singleton instance after it's been cached.
	 *
	 * @return null|MCE_Table_Buttons
	 */
	public static function get_instance() {
		// Store the instance locally to avoid private static replication
		static $instance = null;

		if ( null === $instance ) {
			$instance = new self();
			self::_setup_plugin();
		}

		return $instance;
	}

	/**
	 * An empty constructor
	 */
	public function __construct() { /* Purposely do nothing here */ }

	/**
	 * Handles registering hooks that initialize this plugin.
	 */
	public static function _setup_plugin() {
		add_filter( 'mce_external_plugins', array( __CLASS__, 'mce_external_plugins' ) );
		add_filter( 'mce_buttons_3', array( __CLASS__, 'mce_buttons_2' ) );
		add_action( 'content_save_pre', array( __CLASS__, 'content_save_pre'), 20 );
	}

	/**
	 * Initialize TinyMCE table plugin and custom TinyMCE plugin
	 *
	 * @param array $plugin_array Array of TinyMCE plugins
	 * @return array Array of TinyMCE plugins
	 */
	public static function mce_external_plugins( $plugin_array ) {
		global $tinymce_version;
		$variant = ( defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ) ? '' : '.min';

		if ( version_compare( $tinymce_version, '400', '<' ) ) {

			wp_register_style( 'mce-table-buttons', plugin_dir_url( __FILE__ ) . 'tinymce3-assets/mce-table-buttons.css' );
			wp_print_styles( 'mce-table-buttons' );

			$plugin_dir_url = plugin_dir_url( __FILE__ );
			$plugin_array['table'] = $plugin_dir_url . 'tinymce3-table/editor_plugin.js';
			$plugin_array['mcetablebuttons'] = $plugin_dir_url . 'tinymce3-assets/mce-table-buttons.js';

		} elseif ( version_compare( $tinymce_version, '4100', '<' ) ) {

			$plugin_array['table'] = plugin_dir_url( __FILE__ ) . 'tinymce4-table/plugin' . $variant . '.js';

		} else {

			$plugin_array['table'] = plugin_dir_url( __FILE__ ) . 'tinymce41-table/plugin' . $variant . '.js';

		}

		return $plugin_array;
	}

	/**
	 * Add TinyMCE table control buttons
	 *
	 * @param array $buttons Buttons for the second row
	 * @return array Buttons for the second row
	 */
	public static function mce_buttons_2( $buttons ) {
		global $tinymce_version;

		if ( version_compare( $tinymce_version, '400', '<' ) ) {

			add_filter( 'mce_buttons_3', array( __CLASS__, 'mce_buttons_3' ) );

		} else {

			// in case someone is manipulating other buttons, drop table controls at the end of the row
			if ( ! $pos = array_search( 'undo', $buttons ) ) {
				array_push( $buttons, 'table' );
				return $buttons;
			}

			$buttons = array_merge( array_slice( $buttons, 0, $pos ), array( 'table' ), array_slice( $buttons, $pos ) );

		}

		return $buttons;
	}

	/**
	 * Add TinyMCE 3.x table control to the second row, after other formatting controls
	 *
	 * @param array $buttons Buttons for the second row
	 * @return array Buttons for the second row
	 */
	public static function mce_buttons_3( $buttons ) {
		array_push( $buttons, 'tablecontrols' );
		return $buttons;
	}

	/**
	 * Fixes weirdness resulting from wpautop and formatting clean up not built for tables
	 *
	 * @param string $content Editor content before WordPress massaging
	 * @return string Editor content before WordPress massaging
	 */
	public static function content_save_pre( $content ) {
		if ( false !== strpos( $content, '<table' ) ) {
			// paragraphed content inside of a td requires first paragraph to have extra line breaks (or else autop breaks)
			$content  = preg_replace( "/<td([^>]*)>(.+\r?\n\r?\n)/m", "<td$1>\n\n$2", $content );

			// make sure there's space around the table
			if ( substr( $content, -8 ) == '</table>' ) {
				$content .= "\n<br />";
			}
		}
		
		return $content;
	}
}

MCE_Table_Buttons::get_instance();