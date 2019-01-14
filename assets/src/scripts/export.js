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

	/* Export Formats */
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
				// Initialize checkboxes from cookie
				let was_checked = 0;
				if ( json_cookie.hasOwnProperty( shorter_name ) ) {
					was_checked = json_cookie[ shorter_name ];
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
			let shorter_name = name.replace( 'export_formats[', 'ef[' );
			if ( $( this ).prop( 'checked' ) ) {
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
				// Initialize checkboxes from cookie
				let was_checked = 0;
				if ( json_cookie.hasOwnProperty( shorter_name ) ) {
					was_checked = json_cookie[ shorter_name ];
				}
				if ( was_checked ) {
					let tr = $( this ).closest( 'tr' );
					let id = tr.attr( 'data-id' );
					let cb = $( `input[name='ID[]'][value='${id}']` );
					$( this ).prop( 'checked', true );
					cb.prop( 'checked', false );
					cb.prop( 'disabled', true );
				}
			}
		} )
		.change( function () {
			let name = $( this ).attr( 'name' );
			let shorter_name = name.replace( 'pin[', 'p[' );
			let tr = $( this ).closest( 'tr' );
			let id = tr.attr( 'data-id' );
			let format = tr.attr( 'data-format' );
			let cb = $( `input[name='ID[]'][value='${id}']` );
			if ( $( this ).prop( 'checked' ) ) {
				// Up to five files can be pinned at once.
				if ( $( 'td.column-pin input[type="checkbox"]:checked' ).length > 5 ) {
					alert( PB_ExportToken.maximumFilesWarning );
					$( this ).prop( 'checked', false );
					return false;
				}
				json_cookie[ shorter_name ] = format;
				// If the user has pinned three files of a given export type and they then try to pin an additional file of that type,
				// an error should be displayed instructing them to deselect one of the pinned files before attempting to pin another.
				if ( Object.entries( json_cookie ).filter( function ( arr ) {
					return arr[ 0 ].indexOf( 'p[' ) === 0 && arr[ 1 ] === format;
				} ).length > 3 ) {
					alert( PB_ExportToken.maximumFileTypeWarning );
					delete json_cookie[ shorter_name ];
					$( this ).prop( 'checked', false );
					return false;
				}
				cb.prop( 'checked', false );
				cb.prop( 'disabled', true );
			} else {
				cb.prop( 'disabled', false );
				delete json_cookie[ shorter_name ];
			}
			update_json_cookie();
		} );
} );
