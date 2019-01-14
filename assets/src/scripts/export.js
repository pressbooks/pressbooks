// This script is loaded when a user is on the [ Export ] page

import Cookies from 'js-cookie';

jQuery( function ( $ ) {

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

	/* Collapsible Export Formats form */
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

	/* Export Formats: Remember User Checkboxes */
	$( '#pb-export-form' )
		.find( 'input' )
		.each( function () {
			let name = $( this ).attr( 'name' );
			let shorter_name = name.replace( 'export_formats[', 'ef[' );
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
				let val = 0;
				if ( json_cookie.hasOwnProperty( shorter_name ) ) {
					val = json_cookie[ shorter_name ];
				}
				$( this ).prop( 'checked', !! val );
			}
			// If there's a dependency error, then don't let the user check the box
			if ( $( this ).attr( 'disabled' ) ) {
				$( this ).prop( 'checked', false );
			}
		} )
		.change( function () {
			let name = $( this ).attr( 'name' );
			let shorter_name = name.replace( 'export_formats[', 'ef[' );
			let my_json_value = $( this ).prop( 'checked' );
			if ( my_json_value ) {
				json_cookie[ shorter_name ] = 1;
			} else {
				delete json_cookie[ shorter_name ];
			}
			update_json_cookie();
		} );

	/* Pins */
	$( 'td.column-pin' )
		.find( 'input' )
		.each( function () {
			let name = $( this ).attr( 'name' );
			let shorter_name = name.replace( 'pin[', 'p[' );
			if ( ! jQuery.isEmptyObject( json_cookie ) ) {
				let val = 0;
				if ( json_cookie.hasOwnProperty( shorter_name ) ) {
					val = json_cookie[ shorter_name ];
				}
				$( this ).prop( 'checked', !! val );
			}
		} )
		.change( function () {
			let name = $( this ).attr( 'name' );
			let shorter_name = name.replace( 'pin[', 'p[' );
			let tr = $( this ).closest( 'tr' );
			let format = tr.attr( 'data-format' );
			let my_json_value = $( this ).prop( 'checked' );
			if ( my_json_value ) {
				if ( Object.entries( json_cookie ).filter( function ( arr ) {
					return ( arr[0].indexOf( 'p[' ) === 0 && arr[1] === format )
				} ).length >= 3 ) {
					alert( 'Cannot pin more than 3 of the same file type' );
					$( this ).prop( 'checked', false );
					return false;
				}
				json_cookie[ shorter_name ] = format;
			} else {
				delete json_cookie[ shorter_name ];
			}
			update_json_cookie();
		} );
} );
