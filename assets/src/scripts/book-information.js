// This script is loaded when a user is on the [ Book Information ] page

/* global jQuery:true */
/* global PB_BookInfoToken:true */

jQuery(function ($) {
	// Hack to get menu item highlighted
	$( '#' + PB_BookInfoToken.bookInfoMenuId ).removeClass( 'wp-not-current-submenu' ).addClass( 'current' );
});
