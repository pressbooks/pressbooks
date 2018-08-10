// This script is loaded when a user is on a books web view

( function () {
	//todo: modify so it shows on click, not on hover

	// make tooltip faster by disabling the show/hide animations
	jQuery( 'a.tooltip' ).addClass( 'no-hover' ).tooltip( {
		show: false,
		hide: false,
	} ).click( function () {
		jQuery( this ).tooltip();
	} );

} )();
