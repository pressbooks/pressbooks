// This script is loaded when a user is on the [ Export ] page

import Cookies from 'js-cookie';

jQuery( function ( $ ) {

	let cookie_key = 'pb_export';
	let cookie_json = Cookies.getJSON( cookie_key );
	if ( typeof cookie_json === 'undefined' ) {
		cookie_json = {};
	}

	/* Collapsible form */
	$( '#pb-export-hndle' ).click( function ( e ) {
		let hndle = $( '#pb-export-hndle' );
		if ( hndle.hasClass( 'dashicons-arrow-up' ) ) {
			hndle.removeClass( 'dashicons-arrow-up' );
			hndle.addClass( 'dashicons-arrow-down' );
			$( '.wrap .postbox .inside' ).hide();
		} else {
			hndle.removeClass( 'dashicons-arrow-down' );
			hndle.addClass( 'dashicons-arrow-up' );
			$( '.wrap .postbox .inside' ).show();
		}
	} );

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

	/* Remember User Checkboxes */
	$( '#pb-export-form' )
		.find( 'input' )
		.each( function () {
			let name = $( this ).attr( 'name' );
			// Defaults
			if ( jQuery.isEmptyObject( cookie_json ) ) {
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
				let val = 0;
				if ( cookie_json.hasOwnProperty( name ) ) {
					val = cookie_json[ name ];
				}
				$( this ).prop( 'checked', !! val );
			}
			if ( $( this ).attr( 'disabled' ) ) {
				$( this ).prop( 'checked', false );
			}
		} )
		.change( function () {
			let my_json_key = $( this ).attr( 'name' );
			let my_json_value = $( this ).prop( 'checked' );
			if ( my_json_value ) {
				cookie_json[ my_json_key ] = 1;
			} else {
				delete cookie_json[ my_json_key ];
			}
			Cookies.set( cookie_key, cookie_json, {
				path: '/',
				expires: 365,
			} );
		} );
} );
