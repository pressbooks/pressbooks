<?php

/**
 * MCE Superscript & Subscript Buttons for Pressbooks
 *
 * @package			Pressbooks
 * @author			Pressbooks <code@pressbooks.com>
 * @contributors	Brad Payne <brad@bradpayne.ca>
 * @license			GPLv2
 * @copyright		2015 BookOven Inc.
 *
 * @wordpress-plugin
 * Plugin Name:		MCE Superscript & Subscript Buttons for Pressbooks
 * Description:		Adds buttons to TinyMCE for superscript and subscript elements in Pressbooks
 * Version:			1.0.0
 * Author:			BookOven Inc.
 * Author URI:		http://www.pressbooks.com
 * Text Domain:		pressbooks-mce-superscript-subscript-buttons
 * License:			GPLv2
 * License URI:		http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace PBT\Plugins;

class SuperscriptSubscriptButtons {

	function __construct() {
		// Define plugin constants
		
		// Load translations

		// Hook in our bits
		add_action( 'admin_init', array( $this, 'addFilters' ) );
	}

	function addFilters() {
		
		if ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'edit_pages' ) ) {
			return;
		}

		if ( get_user_option( 'rich_editing' ) == 'true' ) {
			add_filter( 'mce_buttons_3', array( $this, 'registerSuperscriptSubscriptButtons' ) );
		}
	}

	/**
	 * Push our buttons onto the buttons stack in the 3rd mce row
	 * 
	 * @param type $buttons
	 */
	function registerSuperscriptSubscriptButtons( $buttons ) {

		array_push( $buttons, 'superscript', 'subscript' );
		return $buttons;
	}
	
	}

$superscript_subscript_buttons = new \PBT\Plugins\SuperscriptSubscriptButtons();

