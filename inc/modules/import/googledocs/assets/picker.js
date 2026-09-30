/**
 * Google Picker integration for the Google Docs importer.
 *
 * Replaces the URL field with a picker button and stores the picked document
 * id in a hidden input (#import_gdoc_id) for the server to fetch.
 *
 * @package Pressbooks
 * @license GPLv3 (or any later version)
 */

( function () {
	'use strict';

	var cfg = window.pbGdocsPicker || {};
	var strings = cfg.strings || {};
	var pickerReady = false;

	function findIdField() {
		return document.querySelector( cfg.fieldSelector || '#import_gdoc_id' );
	}

	function init() {
		var idField = findIdField();
		if ( ! idField ) {
			return;
		}
		var typeSelect = document.querySelector( cfg.typeSelector || '#type_of' );

		var container = document.createElement( 'span' );
		container.className = 'pb-gdocs-picker';

		var btn = document.createElement( 'button' );
		btn.type = 'button';
		btn.className = 'button pb-gdocs-picker-button';
		btn.textContent = strings.buttonLabel || 'Select from Google Drive';
		container.appendChild( btn );

		var docLink = document.createElement( 'a' );
		docLink.className = 'pb-gdocs-picker-doc';
		docLink.target = '_blank';
		docLink.rel = 'noopener';
		docLink.style.marginLeft = '10px';
		docLink.style.display = 'none';
		container.appendChild( docLink );

		idField.insertAdjacentElement( 'afterend', container );

		function toggle() {
			var show = ! typeSelect || typeSelect.value === 'google-docs';
			container.style.display = show ? '' : 'none';
		}
		toggle();
		if ( typeSelect ) {
			typeSelect.addEventListener( 'change', toggle );
		}

		btn.addEventListener( 'click', function () {
			openPicker( idField, btn, docLink );
		} );
	}

	function loadPickerApi() {
		return new Promise( function ( resolve, reject ) {
			if ( pickerReady ) {
				resolve();
				return;
			}
			if ( ! window.gapi ) {
				reject( new Error( strings.gapiError || 'Could not load the Google Picker.' ) );
				return;
			}
			window.gapi.load( 'picker', {
				callback: function () {
					pickerReady = true;
					resolve();
				},
				onerror: function () {
					reject( new Error( strings.gapiError || 'Could not load the Google Picker.' ) );
				},
			} );
		} );
	}

	function fetchToken() {
		var body = new URLSearchParams( {
			action: 'pb_gdocs_picker_token',
			_ajax_nonce: cfg.nonce,
		} );
		return fetch( cfg.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: body,
		} )
			.then( function ( response ) {
				return response.json();
			} )
			.then( function ( json ) {
				if ( ! json.success ) {
					if ( json.data && json.data.reauthorize && json.data.authorize_url ) {
						// Not connected (or refresh failed): send the user through OAuth.
						window.location.href = json.data.authorize_url;
						return new Promise( function () {} ); // Navigation in progress; never settle.
					}
					throw new Error( ( json.data && json.data.message ) || strings.tokenError || 'Authentication failed.' );
				}
				return json.data.access_token;
			} );
	}

	function openPicker( idField, btn, docLink ) {
		btn.disabled = true;
		Promise.all( [ loadPickerApi(), fetchToken() ] )
			.then( function ( results ) {
				var token = results[ 1 ];
				var google = window.google;

				var view = new google.picker.DocsView( google.picker.ViewId.DOCUMENTS )
					.setMode( google.picker.DocsViewMode.LIST );

				var picker = new google.picker.PickerBuilder()
					.setAppId( cfg.appId )
					.setDeveloperKey( cfg.apiKey )
					.setOAuthToken( token )
					.setOrigin( window.location.protocol + '//' + window.location.host )
					.addView( view )
					.setCallback( function ( data ) {
						if ( data[ google.picker.Response.ACTION ] === google.picker.Action.PICKED ) {
							var doc = data[ google.picker.Response.DOCUMENTS ][ 0 ];
							var docId = doc[ google.picker.Document.ID ];

							idField.value = docId;
							idField.dispatchEvent( new Event( 'change', { bubbles: true } ) );

							docLink.href =
								doc[ google.picker.Document.URL ] ||
								'https://docs.google.com/document/d/' + docId + '/edit';
							docLink.textContent = doc[ google.picker.Document.NAME ] || docId;
							docLink.style.display = '';
							btn.textContent = strings.changeLabel || 'Change document';
						}
					} )
					.build();

				picker.setVisible( true );
			} )
			.catch( function ( err ) {
				window.alert( err.message || strings.tokenError || 'Authentication failed.' );
			} )
			.finally( function () {
				btn.disabled = false;
			} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
