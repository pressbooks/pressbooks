jQuery( function ( $ ) {
	$( '#pb-cloner-button' ).click( event => {
		event.preventDefault();
		$( '#pb-cloner-button' ).attr( 'disabled', true );
		$( '#loader' ).css( 'display', 'inline-block' );
		const submission = function () {
			$( '#pb-cloner-form' ).submit();
		};
		setTimeout( submission, 0 );
	} );
} );
