/* global PB_ClonerToken */

import startClock from './utils/startClock';

jQuery( function ( $ ) {
	const clonerForm = $( '#pb-cloner-form' );
	clonerForm.on( 'submit', function ( e ) {
		// Stop form from submitting
		e.preventDefault();

		// Set element variables
		const button = $( '#pb-cloner-button' );
		const bar = $( '#pb-sse-progressbar' );
		const info = $( '#pb-sse-info' );

		// Init clock
		let clock = null;

		// Show bar, hide button
		bar.val( 0 ).show();
		button.attr( 'disabled', true ).hide();

		// Initialize event data
		const eventSourceUrl = PB_ClonerToken.ajaxUrl + ( PB_ClonerToken.ajaxUrl.includes( '?' ) ? '&' : '?' ) + $.param( clonerForm.find( ':input' ) );
		const evtSource = new EventSource( eventSourceUrl );

		// Handle open
		evtSource.onopen = function () {
			// Start clock
			startClock( clock );

			// Warn the user if they navigate away
			$( window ).on( 'beforeunload', function () {
				// In some browsers, the return value of the event is displayed in this dialog. Starting with Firefox 44, Chrome 51, Opera 38 and Safari 9.1, a generic string not under the control of the webpage will be shown.
				// @see https://developer.mozilla.org/en-US/docs/Web/API/WindowEventHandlers/onbeforeunload#Notes
				return PB_ClonerToken.unloadWarning;
			} );
		};

		// Handle message
		evtSource.onmessage = function ( message ) {
			const data = JSON.parse( message.data );
			switch ( data.action ) {
				case 'updateStatusBar':
					bar.val( parseInt( data.percentage, 10 ) );
					info.html( data.info );
					break;
				case 'complete':
					evtSource.close();
					$( window ).unbind( 'beforeunload' );
					if ( data.error ) {
						bar.removeAttr( 'value' );
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

		// Handle error
		evtSource.onerror = function () {
			evtSource.close();
			$( '#pb-sse-progressbar' ).removeAttr( 'value' );
			$( '#pb-sse-info' ).html( 'EventStream Connection Error ' + PB_ClonerToken.reloadSnippet );
			$( window ).unbind( 'beforeunload' );
			if ( clock ) {
				clearInterval( clock );
			}
		};
	} );
} );
