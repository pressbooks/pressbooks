jQuery( function ( $ ) {
	let myClonerForm = $( '#pb-cloner-form' );
	myClonerForm.on( 'submit', function ( e ) {
		// Stop form from submitting
		e.preventDefault();
		$( '#pb-cloner-button' ).attr( 'disabled', true );
		// Init Clock
		let clock = null;
		let seconds = $( '#pb-sse-seconds' );
		let minutes = $( '#pb-sse-minutes' );
		function pad( val ) {
			return val > 9 ? val : '0' + val;
		}
		// Init Event Data
		let eventSourceUrl = PB_ClonerToken.ajaxUrl + ( PB_ClonerToken.ajaxUrl.includes( '?' ) ? '&' : '?' ) + $.param( myClonerForm.find( ':input' ) );
		let evtSource = new EventSource( eventSourceUrl );
		evtSource.onopen = function () {
			// Hide button
			$( '#pb-cloner-button' ).hide();
			// Start Clock
			let sec = 0;
			seconds.html( '00' );
			minutes.html( '00:' );
			clock = setInterval( function () {
				seconds.html( pad( ++sec % 60 ) );
				minutes.html( pad( parseInt( sec / 60, 10 ) ) + ':' );
			}, 1000 );
			// Warn the user if they navigate away
			$( window ).on( 'beforeunload', function () {
				// In some browsers, the return value of the event is displayed in this dialog. Starting with Firefox 44, Chrome 51, Opera 38 and Safari 9.1, a generic string not under the control of the webpage will be shown.
				// @see https://developer.mozilla.org/en-US/docs/Web/API/WindowEventHandlers/onbeforeunload#Notes
				return PB_ClonerToken.unloadWarning;
			} );
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
					$( window ).unbind( 'beforeunload' );
					if ( data.error ) {
						bar.progressbar( { value: false } );
						info.html( data.error + ' ' + PB_ClonerToken.reloadSnippet );
						if ( clock ) {
							clearInterval( clock );
						}
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
			$( window ).unbind( 'beforeunload' );
			if ( clock ) {
				clearInterval( clock );
			}
		};
	} );
} );
