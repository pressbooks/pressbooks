<?php
/**
 * @author  PressBooks <code@pressbooks.org>
 * @license GPLv2 (or any later version)
 */

/* ------------------------------------------------------------------------ *
 * Google Webfonts
 * ------------------------------------------------------------------------ */
 
function harvard_enqueue_styles() {
   		 wp_enqueue_style( 'harvard-fonts', 'http://fonts.googleapis.com/css?family=Galdeano|Tinos:400,400italic,700,700italic');  		   		   		       		           
}     
add_action('wp_print_styles', 'harvard_enqueue_styles');
?>