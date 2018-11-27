jQuery( function ( $ ) {
	$( '#pb-cloner-form' ).on( 'submit', function ( e ) {
		e.preventDefault();
		$( '#pb-cloner-button' ).attr( 'disabled', true );
		let form = $( '#pb-cloner-form' );
		let eventSourceUrl = PB_ClonerToken.ajaxUrl + ( PB_ClonerToken.ajaxUrl.includes( '?' ) ? '&' : '?' ) + $.param( form.find( ':input' ) );
		let evtSource = new EventSource( eventSourceUrl );
		evtSource.onopen = function () {
			$( '#pb-cloner-button' ).hide();
			// count up timer
			let sec = 0;
			let seconds = $( '#pb-sse-seconds' );
			let minutes = $( '#pb-sse-minutes' );
			seconds.html( '00' );
			minutes.html( '00:' );
			function pad( val ) {
				return val > 9 ? val : '0' + val;
			}
			setInterval( function () {
				seconds.html( pad( ++sec % 60 ) );
				minutes.html( pad( parseInt( sec / 60, 10 ) ) + ':' );
			}, 1000 );
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
						info.html( data.error + ' ' + PB_ClonerToken.reloadSnippet );
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
			$( '#pb-sse-info' ).html( 'EventStream Connection Error ' + PB_ClonerToken.reloadSnippet );
		};
	} );
} );
