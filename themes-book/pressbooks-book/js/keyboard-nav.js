jQuery( document ).ready( function( $ ) {
	$( document ).keydown( function( e ) {
		var url = false;
		if ( e.which == 37 ) {  // Left arrow key code
			url = $( 'a.previous' ).attr( 'href' );
		}
		else if ( e.which == 39 ) {  // Right arrow key code
			url = $( 'a.next' ).attr( 'href' );
		}
		if ( url && ( !$( 'textarea, input' ).is( ':focus' ) ) ) {
			window.location = url;
		}
	} );
} );