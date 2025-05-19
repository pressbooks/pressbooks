const Coloris = require( '@melloware/coloris' );

jQuery( function ( $ ) {
	Coloris.init();
	Coloris( {
		el: '.pb_catalog_color',
	} );

	$.when( $.ready ).then( function () {
		$( '.clr-field > button' ).each( function () {
			$( this ).removeAttr( 'aria-labelledby' );
			const label = $( this ).parents( 'td' ).prev( 'th' ).text();
			$( this ).attr( 'aria-label', label );
		} );
	} );
} );
