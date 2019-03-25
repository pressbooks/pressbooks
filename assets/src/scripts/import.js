/* global PB_ImportToken */

import displayNotice from './utils/displayNotice';
import resetClock from './utils/resetClock';
import startClock from './utils/startClock';

jQuery( function ( $ ) {
	// Step 1: Upload or sideload import data prior to selecting content for import.
	$( '#pb-import-form-step-1' ).on( 'submit', function () {
		$( 'input[type=submit]' ).attr( 'disabled', true );
	} );

	// Step 2: Create posts from selected content.
	const importForm = $( '#pb-import-form-step-2' );
	importForm.on( 'submit', function ( e ) {
		// Stop form from submitting
		e.preventDefault();

		// Set element variables
		const button = $( 'input[type=submit]' );
		const bar = $( '#pb-sse-progressbar' );
		const info = $( '#pb-sse-info' );
		const notices = $( '.notice' );

		// Init clock
		let clock = null;

		// Show bar, hide button
		bar.val( 0 ).show();
		button.attr( 'disabled', true );
		notices.remove();

		// Initialize event data
		// TODO: There's a maximum $_GET and we are probably exceeding it
		const eventSourceUrl = PB_ImportToken.ajaxUrl + ( PB_ImportToken.ajaxUrl.includes( '?' ) ? '&' : '?' ) + $.param( importForm.find( ':checked' ) );
		const evtSource = new EventSource( eventSourceUrl );

		// Handle open
		evtSource.onopen = function () {
			// Start clock
			clock = startClock();
			// Warn the user if they navigate away
			$( window ).on( 'beforeunload', function () {
				// In some browsers, the return value of the event is displayed in this dialog. Starting with Firefox 44, Chrome 51, Opera 38 and Safari 9.1, a generic string not under the control of the webpage will be shown.
				// @see https://developer.mozilla.org/en-US/docs/Web/API/WindowEventHandlers/onbeforeunload#Notes
				return PB_ImportToken.unloadWarning;
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
						bar.val( 0 ).hide();
						button.attr( 'disabled', false ).show();
						displayNotice( 'error', data.error, true );
						if ( clock ) {
							resetClock( clock );
						}
					} else {
						window.location = PB_ImportToken.redirectUrl;
					}
					break;
				default:
					break;
			}
		};

		// Handle error
		evtSource.onerror = function () {
			evtSource.close();
			bar.removeAttr( 'value' );
			info.html( 'EventStream Connection Error ' + PB_ImportToken.reloadSnippet );
			$( window ).unbind( 'beforeunload' );
			if ( clock ) {
				resetClock( clock );
			}
		};
	} );
} );
