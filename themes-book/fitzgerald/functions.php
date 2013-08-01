<?php
/**
 * @author  PressBooks <code@pressbooks.org>
 * @license GPLv2 (or any later version)
 */

/* ------------------------------------------------------------------------ *
 * Google Webfonts
 * ------------------------------------------------------------------------ */
 
function uofminn_enqueue_styles() {

   		 wp_enqueue_style( 'uofminn-fonts', 'http://fonts.googleapis.com/css?family=Crimson+Text:400,400italic,700|Roboto+Condensed:400,300,300italic,400italic');  	
   		 	   		   		       		           
}     
add_action('wp_print_styles', 'uofminn_enqueue_styles');	

	
?>