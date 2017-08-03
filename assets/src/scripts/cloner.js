jQuery( function ( $ ) {
    $( '#pb-cloner-button' ).click( function ( e ) {
        e.preventDefault();
        $( '#loader' ).show();
        $( '#pb-cloner-button' ).hide();
        $( '#pb-cloner-form' ).submit();
    } );
} );