jQuery( function ( $ ) {
	$( '#pb-cloner-form' ).on( 'submit', function ( e ) {
		e.preventDefault();
		$( '#pb-cloner-button' ).attr( 'disabled', true );
		let form = $( '#pb-cloner-form' );
		let eventSourceUrl = PB_ClonerToken.ajaxUrl + (PB_ClonerToken.ajaxUrl.includes( '?' ) ? '&' : '?') + $.param( form.find( ':input' ) );
		let evtSource = new EventSource( eventSourceUrl );
		evtSource.onopen = function () {
			$( '#pb-cloner-button' ).hide();
		};
		evtSource.onmessage = function ( message ) {
			let bar = $( '#pb-sse-progressbar' );
			let info = $( '#pb-sse-info' );
			let data = JSON.parse( message.data );
			switch ( data.action ) {
				case 'updateStatusBar':
					bar.progressbar( { value: parseInt( data.percentage, 10 ) } );
					info.html( data.info );
					break;
				case 'complete':
					evtSource.close();
					if ( data.error ) {
						bar.progressbar( { value: false } );
						info.html( data.error );
					} else {
						window.location = PB_ClonerToken.redirectUrl;
					}
					break;
				default:
					break;
			}
		};
		evtSource.onerror = function () {
			evtSource.close();
			$( '#pb-sse-progressbar' ).progressbar( { value: false } );
			$( '#pb-sse-info' ).html( 'EventStream Connection Error' );
		};
	} );
} );
