jQuery( function ( $ ) {
	$( '#pb-cloner-form' ).on( 'submit', function ( e ) {
		e.preventDefault();
		$( '#pb-cloner-button' ).attr( 'disabled', true );
		let form = $( '#pb-cloner-form' );
		let params = {
			'sourceUrl': form.find( 'input[name="source_book_url"]' ).val(),
			'targetUrl': form.find( 'input[name="target_book_url"]' ).val(),
			'targetTitle': form.find( 'input[name="target_book_title"]' ).val(),
		};
		let eventSourceUrl = PB_ClonerToken.url + '&' + jQuery.param( params );
		let evtSource = new EventSource( eventSourceUrl );
		evtSource.onopen = function () {
			$( '#pb-cloner-button' ).hide();
		};
		evtSource.onmessage = function ( message ) {
			let data = JSON.parse( message.data );
			switch ( data.action ) {
				case 'updateStatusBar':
					$( '#pb-sse-progressbar' ).progressbar( { value: parseInt( data.percentage, 10 ) } );
					$( '#pb-sse-info' ).html( data.info );
					break;
				case 'complete':
					evtSource.close();
					if ( data.error ) {
						$( '#pb-sse-progressbar' ).progressbar( { value: false } );
						$( '#pb-sse-info' ).html( data.error );
					} else {
						// TODO: Redirect somewhere?
					}
					break;
				default:
					break;
			}
		};
		evtSource.onerror = function () {
			evtSource.close();
			$( '#pb-sse-progressbar' ).progressbar( { value: false } );
			$( '#pb-sse-info' ).html( '500 Internal Server Error' );
		};
	} );
} );
