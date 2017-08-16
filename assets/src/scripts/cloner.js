jQuery( function( $ ) {
	$( '#pb-cloner-button' ).click( function( e ) {
		e.preventDefault();
<<<<<<< ours
		$( '#pb-cloner-button' ).hide( 0, function() {
			$( '#loader' ).show( 0, function() {
				$( '#pb-cloner-form' ).submit();
			} );
		} );
	} );
} );
=======
		$( '#pb-cloner-button' ).hide();
		$( '#loader' ).show();
		const submission = function () {
			$( '#pb-cloner-form' ).submit();
		};
		setTimeout( submission, 0 );
	} );
} );
>>>>>>> theirs
