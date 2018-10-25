// This script is loaded when a user is on a books web view
( function () {

// Show the tooltip
	jQuery( document ).on( 'click', '.tooltip', function () {
		jQuery( '.tooltip.on' ).tooltip( 'close' ).removeClass( 'on' );
		jQuery( this ).addClass( 'on' );
		jQuery( this ).tooltip( {
			items: '.tooltip.on',
			show: false,
			hide: false,
			position: {
				my: 'center bottom',
				at: 'center top',
			},
		} );
		jQuery( this ).trigger( 'mouseenter' );
	} );
	// Hide the tooltip
	jQuery( document ).on( 'click', '.tooltip.on', function () {
		jQuery( this ).tooltip( 'close' );
		jQuery( this ).removeClass( 'on' );
	} );
	//prevent mouseout and other related events from firing their handlers
	jQuery( '.tooltip' ).on( 'mouseout', function ( e ) {
		e.stopImmediatePropagation();
	} );

} )();
