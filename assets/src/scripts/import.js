/* global PB_ImportToken */

import displayNotice from './utils/displayNotice';
import resetClock from './utils/resetClock';
import startClock from './utils/startClock';

jQuery( function ( $ ) {
	// Set element variables
	const button = $( 'input[type=submit]' );
	const bar = $( '#pb-sse-progressbar' );
	const info = $( '#pb-sse-info' );
	const notices = $( '.notice' );

	// Init clock
	let clock = null;

	/**
	 *
	 */
	let eventSourceHandler = function () {

		// Initialize event data
		const eventSourceUrl = PB_ImportToken.ajaxUrl;
		const evtSource = new EventSource( eventSourceUrl );

		// Handle open
		/**
		 *
		 */
		evtSource.onopen = function () {
			// Warn the user if they navigate away
			$( window ).on( 'beforeunload', function () {
				// In some browsers, the return value of the event is displayed in this dialog. Starting with Firefox 44, Chrome 51, Opera 38 and Safari 9.1, a generic string not under the control of the webpage will be shown.
				// @see https://developer.mozilla.org/en-US/docs/Web/API/WindowEventHandlers/onbeforeunload#Notes
				return PB_ImportToken.unloadWarning;
			} );
		};

		// Handle message
		/**
		 * @param message
		 */
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
		/**
		 *
		 */
		evtSource.onerror = function () {
			evtSource.close();
			bar.removeAttr( 'value' );
			info.html( 'EventStream Connection Error ' + PB_ImportToken.reloadSnippet );
			$( window ).unbind( 'beforeunload' );
			if ( clock ) {
				resetClock( clock );
			}
		};

	};

	// Step 1: Upload or sideload import data prior to selecting content for import.
	$( '#pb-import-form-step-1' ).on( 'submit', function () {
		$( 'input[type=submit]' ).attr( 'disabled', true );
	} );

	// Step 2: Create posts from selected content.
	const importForm = $( '#pb-import-form-step-2' );
	importForm.on( 'submit', function ( e ) {
		// Stop form from submitting
		e.preventDefault();

		// Show bar, hide button
		bar.val( 0 ).show();
		button.attr( 'disabled', true );
		notices.remove();

		clock = startClock();
		info.html( PB_ImportToken.ajaxSubmitMsg );

		// Save the WP options and WP Media before triggering the generator
		// @see https://github.com/jquery-form/form
		$( this ).ajaxSubmit( {
			done: eventSourceHandler(),
			timeout: 0, // A value of 0 means there will be no timeout.
		} );

		// Return false to prevent normal browser submit and page navigation.
		return false;
	} );
} );
