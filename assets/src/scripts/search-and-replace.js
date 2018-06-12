jQuery( function ( $ ) {
	const form = $( '#search-form' );
	$( '.replace-and-save' ).click( function ( e ) {
		/* eslint-disable no-restricted-globals */
		if ( confirm( pb_sr.warning_text ) ) {
			/* eslint-enable no-restricted-globals */
			let input = document.createElement( 'input' );
			input.setAttribute( 'type', 'hidden' );
			input.setAttribute( 'name', 'replace_and_save' );
			document.getElementById( 'search-form' ).appendChild( input );
			form.submit();
		}
	} );
} );
