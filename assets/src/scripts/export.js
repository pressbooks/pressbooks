/* global PB_ExportToken */
/* global _pb_export_formats_map */
/* global _pb_export_pins_inventory */

import Cookies from 'js-cookie';

jQuery( function ( $ ) {
	/* SSE powered progress bar */
	let myExportForm = $( '#pb-export-form' );
	myExportForm.on( 'submit', function ( e ) {
		// Stop form from submitting
		e.preventDefault();
		$( '#pb-export-button' ).attr( 'disabled', true );
		// Init Clock
		let clock = null;
		let seconds = $( '#pb-sse-seconds' );
		let minutes = $( '#pb-sse-minutes' );
		function pad( val ) {
			return val > 9 ? val : '0' + val;
		}
		// Init Event Data
		let eventSourceUrl = PB_ExportToken.ajaxUrl + ( PB_ExportToken.ajaxUrl.includes( '?' ) ? '&' : '?' ) + $.param( myExportForm.find( ':checked' ) );
		let evtSource = new EventSource( eventSourceUrl );
		evtSource.onopen = function () {
			// Hide button
			$( '#pb-export-button' ).hide();
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
				return PB_ExportToken.unloadWarning;
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
						info.html( data.error + ' ' + PB_ExportToken.reloadSnippet );
						if ( clock ) {
							clearInterval( clock );
						}
					} else {
						window.location = PB_ExportToken.redirectUrl;
					}
					break;
				default:
					break;
			}
		};
		evtSource.onerror = function () {
			evtSource.close();
			$( '#pb-sse-progressbar' ).progressbar( { value: false } );
			$( '#pb-sse-info' ).html( 'EventStream Connection Error ' + PB_ExportToken.reloadSnippet );
			$( window ).unbind( 'beforeunload' );
			if ( clock ) {
				clearInterval( clock );
			}
		};
	} );

	/* JSON Cookie. Remember to keep key/values short because a cookie has max 4096 bytes */
	let json_cookie_key = 'pb_export';
	let json_cookie = Cookies.getJSON( json_cookie_key );
	if ( typeof json_cookie === 'undefined' ) {
		json_cookie = {};
	}
	function update_json_cookie() {
		Cookies.set( json_cookie_key, json_cookie, {
			path: '/',
			expires: 365,
		} );
	}

	/* Collapsible form */
	const optionsPanel = document.getElementById( 'export-options' );
	const toggleButton = optionsPanel.querySelector( '.handlediv' );
	toggleButton.onclick = () => {
		let expanded = toggleButton.getAttribute( 'aria-expanded' ) === 'true' || false;
		toggleButton.setAttribute( 'aria-expanded', ! expanded );
		if ( expanded ) {
			optionsPanel.classList.add( 'closed' );
		} else {
			optionsPanel.classList.remove( 'closed' );
		}
	}

	/* Swap out and animate the 'Export Your Book' button */
	$( '#pb-export-button' ).click( function ( e ) {
		e.preventDefault();
		// If the user has pinned three files of a given export type and then tries to export that format,
		// the export job should be stopped and an error should be displayed instructing them to deselect
		// one of the pinned files before attempting to export.
		let tooManyExports = false;
		let myLabel = '';
		$( '#pb-export-form input:checked' ).each( function () {
			myLabel = $( "label[for='" + $( this ).attr( 'id' ) + "']" ).text().trim(); // eslint-disable-line quotes
			let name = $( this ).attr( 'name' );
			let myMatch = _pb_export_formats_map[ name ];
			if ( Object.values( _pb_export_pins_inventory ).filter( function ( value ) {
				// value matches <crc32-format-td>
				return value === myMatch;
			} ).length >= 3 ) {
				tooManyExports = true;
				return false; // Use return false to break out of each() loops early
			}
		} );
		if ( tooManyExports ) {
			alert( myLabel + ': ' + PB_ExportToken.tooManyExportsWarning );
			return false;
		}
		$( '.export-file-container' ).unbind( 'mouseenter mouseleave' ); // Disable Download & Delete Buttons
		$( '.export-control button' ).prop( 'disabled', true );
		$( '#pb-export-button' ).hide();
		$( '#loader' ).show();
		const submission = function () {
			$( '#pb-export-form' ).submit();
		};
		setTimeout( submission, 0 );
	} );

	/* Export Formats */
	$( '#pb-export-form' )
		.find( 'input' )
		.each( function () {
			let name = $( this ).attr( 'name' );
			// Defaults
			if ( jQuery.isEmptyObject( json_cookie ) ) {
				// Defaults
				if (
					name === 'export_formats[pdf]' ||
					name === 'export_formats[mpdf]' ||
					name === 'export_formats[epub]' ||
					name === 'export_formats[mobi]'
				) {
					$( this ).prop( 'checked', true );
				} else {
					$( this ).prop( 'checked', false );
				}
			} else {
				// Initialize checkboxes from cookie
				let was_checked = 0;
				if ( json_cookie.hasOwnProperty( name ) ) {
					was_checked = json_cookie[ name ];
				}
				$( this ).prop( 'checked', !! was_checked );
			}
			// If there's a dependency error, then forcibly uncheck
			if ( $( this ).attr( 'disabled' ) ) {
				$( this ).prop( 'checked', false );
			}
		} )
		.change( function () {
			let name = $( this ).attr( 'name' );
			if ( $( this ).prop( 'checked' ) ) {
				// Cookie syntax: 'ef[<format>]': 1
				// I.e: 'ef[print_pdf]': 1
				json_cookie[ name ] = 1;
			} else {
				delete json_cookie[ name ];
			}
			update_json_cookie();
		} );

	/* Pins */
	$( 'td.column-pin' )
		.find( 'input' )
		.each( function () {
			if ( $( this ).prop( 'checked' ) ) {
				let tr = $( this ).closest( 'tr' );
				let id = tr.attr( 'data-id' );
				let cb = $( `input[name='ID[]'][value='${id}']` );
				$( this ).prop( 'checked', true );
				cb.prop( 'checked', false );
				cb.prop( 'disabled', true );
				tr.find( 'td.column-file span.delete' ).hide();
			}
		} )
		.change( function () {
			let name =  $( this ).attr( 'name' );
			let tr = $( this ).closest( 'tr' );
			let id = tr.attr( 'data-id' );
			let cb = $( `input[name='ID[]'][value='${id}']` );
			let format = tr.attr( 'data-format' );
			let file = tr.attr( 'data-file' );
			let pinned = $( this ).prop( 'checked' ) ? 1 : 0;
			if ( pinned ) {
				_pb_export_pins_inventory[ name ] = format;
				// Up to five files can be pinned at once.
				if ( Object.keys( _pb_export_pins_inventory ).length > 5 ) {
					delete _pb_export_pins_inventory[ name ];
					$( this ).prop( 'checked', false );
					alert( PB_ExportToken.maximumFilesWarning );
					return false;
				}
				// If the user has pinned three files of a given export type and they then try to pin an additional file of that type,
				// an error should be displayed instructing them to deselect one of the pinned files before attempting to pin another.
				if ( Object.values( _pb_export_pins_inventory ).filter( function ( value ) {
					// value matches <crc32-format-td>
					return value === format;
				} ).length > 3 ) {
					delete _pb_export_pins_inventory[ name ];
					$( this ).prop( 'checked', false );
					alert( PB_ExportToken.maximumFileTypeWarning );
					return false;
				}
				// Checked
				cb.prop( 'checked', false );
				cb.prop( 'disabled', true );
				tr.find( 'td.column-file span.delete' ).hide();
			} else {
				// Unchecked
				delete _pb_export_pins_inventory[ name ];
				cb.prop( 'disabled', false );
				tr.find( 'td.column-file span.delete' ).show();
			}
			$.ajax( {
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'pb_update_pins',
					pins: JSON.stringify( _pb_export_pins_inventory ),
					file: file,
					pinned: pinned,
					_ajax_nonce: PB_ExportToken.pinsNonce,
				},
				success: response => {
					let pinNotifications = $( '#pin-notifications' );
					pinNotifications.html( response.data.message );
				},
			} );
		} );
} );
