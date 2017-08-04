jQuery( function ( $ ) {
	$( '#pb-cloner-button' ).click( function ( e ) {
		e.preventDefault();
		$( '#pb-cloner-button' ).hide( 0, function() {
			$( '#loader' ).show( 0,  function() {
				$( '#pb-cloner-form' ).submit();
			});
		});
	});
});