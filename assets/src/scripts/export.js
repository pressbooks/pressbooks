// This script is loaded when a user is on the [ Export ] page

import Cookies from 'js-cookie';

jQuery( function ( $ ) {
	/* Swap out and animate the 'Export Your Book' button */
	$( '#pb-export-form' ).on( 'submit', function ( e ) {
		e.preventDefault();
		$( '#pb-export-button' ).attr( 'disabled', true );
		let form = $( '#pb-export-form' );
		let eventSourceUrl = PB_ExportToken.ajaxUrl + ( PB_ExportToken.ajaxUrl.includes( '?' ) ? '&' : '?' ) + $.param( form.find( ':checked' ) );
		let evtSource = new EventSource( eventSourceUrl );
		evtSource.onopen = function () {
			$( '#pb-export-button' ).hide();
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
						info.html( data.error + ' ' + PB_ExportToken.reloadSnippet );
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
		};
	} );
	$( '#pb-export-button' ).click( function ( e ) {
		e.preventDefault();
		$( '.export-file-container' ).unbind( 'mouseenter mouseleave' ); // Disable Download & Delete Buttons
		$( '.export-control button' ).prop( 'disabled', true );
		$( '#pb-export-form' ).submit();
	} );

	/* Show and hide download & delete button */
	$( '.export-file-container' ).hover(
		function () {
			$( this )
				.children( '.file-actions' )
				.css( 'visibility', 'visible' );
		},
		function () {
			$( this )
				.children( '.file-actions' )
				.css( 'visibility', 'hidden' );
		}
	);

	/* Remember User Checkboxes */
	$( '#pb-export-form' )
		.find( 'input' )
		.each( function () {
			let name = $( this ).attr( 'name' );
			let val = Cookies.get( 'pb_' + name );
			let v;
			// Defaults
			if ( typeof val === 'undefined' ) {
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
				// Toggle based on user's cookie
				if ( typeof val === 'boolean' ) {
					v = val;
				} else {
					v = val === 'true';
				}
				$( this ).prop( 'checked', v );
			}
			if ( $( this ).attr( 'disabled' ) ) {
				$( this ).prop( 'checked', false );
			}
		} )
		.change( function () {
			Cookies.set( 'pb_' + $( this ).attr( 'name' ), $( this ).prop( 'checked' ), {
				path: '/',
				expires: 365,
			} );
		} );
} );
