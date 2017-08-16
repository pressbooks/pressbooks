jQuery( function ( $ ) {
	$( '#pb-cloner-button' ).click( function ( e ) {
		e.preventDefault();
		$( '#pb-cloner-button' ).hide();
		$( '#loader' ).show();
		const submission = function () {
			$( '#pb-cloner-form' ).submit();
		};
		setTimeout( submission, 0 );
	} );
} );
