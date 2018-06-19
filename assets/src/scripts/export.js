// This script is loaded when a user is on the [ Export ] page

import Cookies from 'js-cookie';

jQuery( function ( $ ) {
	/* Swap out and animate the 'Export Your Book' button */
	$( '#pb-export-button' ).click( function ( e ) {
		e.preventDefault();
		$( '.export-file-container' ).unbind( 'mouseenter mouseleave' ); // Disable Download & Delete Buttons
		$( '.export-control button' ).prop( 'disabled', true );
		$( '#pb-export-button' ).hide();
		$( '#loader' ).show();
		const submission = function () {
			$( '#pb-export-form' ).submit();
		};
		setTimeout( submission, 0 );
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
