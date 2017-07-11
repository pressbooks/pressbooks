jQuery( function ( $ ) {
	const form = $( '#search-form' );
	$( '.replace-and-save' ).click( function ( e ) {
		if ( confirm( pb_sr.warning_text ) ) {
			let input = document.createElement( 'input' );
			input.setAttribute( 'type', 'hidden' );
			input.setAttribute( 'name', 'replace_and_save' );
			document.getElementById( 'search-form' ).appendChild( input );
			form.submit();
		}
	} );
} );
