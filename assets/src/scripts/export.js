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
		// If the user has pinned three files of a given export type and then tries to export that format,
		// the export job should be stopped and an error should be displayed instructing them to deselect
		// one of the pinned files before attempting to export.
		let tooManyExports = false;
		let myLabel = '';
		$( '#pb-export-form input:checked' ).each( function () {
			myLabel = $( "label[for='" + $( this ).attr( 'id' ) + "']" ).text().trim(); // eslint-disable-line quotes
			let myMatch = _pb_export_formats_map[ this.name ]; // eslint-disable-line no-undef
			if ( Object.entries( json_cookie ).filter( function ( arr ) {
				// key starts with p[ (for pin) AND value matches <crc32-format-td>
				return arr[ 0 ].indexOf( 'p[' ) === 0 && arr[ 1 ] === myMatch;
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
				// Syntax: 'ef[<format>]': 1
				// I.e: 'ef[print_pdf]': 1
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
				// Syntax: 'p[<crc32-filename-td>]': '<crc32-format-td>'
				// I.e: 'p[579e13ce]': '857408ec'
				json_cookie[ shorter_name ] = format;
				// Up to five files can be pinned at once.
				if ( Object.entries( json_cookie ).filter( function ( arr ) {
					// key starts with p[ (for pin)
					return arr[ 0 ].indexOf( 'p[' ) === 0;
				} ).length > 5 ) {
					delete json_cookie[ shorter_name ];
					$( this ).prop( 'checked', false );
					alert( PB_ExportToken.maximumFilesWarning );
					return false;
				}
				// If the user has pinned three files of a given export type and they then try to pin an additional file of that type,
				// an error should be displayed instructing them to deselect one of the pinned files before attempting to pin another.
				if ( Object.entries( json_cookie ).filter( function ( arr ) {
					// key starts with p[ (for pin) AND value matches <crc32-format-td>
					return arr[ 0 ].indexOf( 'p[' ) === 0 && arr[ 1 ] === format;
				} ).length > 3 ) {
					delete json_cookie[ shorter_name ];
					$( this ).prop( 'checked', false );
					alert( PB_ExportToken.maximumFileTypeWarning );
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
