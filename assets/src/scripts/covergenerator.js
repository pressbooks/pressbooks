/* global PB_CoverGeneratorToken */

import displayNotice from './utils/displayNotice';
import resetClock from './utils/resetClock';
import startClock from './utils/startClock';

jQuery( function ( $ ) {
	// Media
	$( document ).ready( function () {
		let mediaUploader;
		$( '.front-background-image-upload-button' ).click( function ( e ) {
			e.preventDefault();
			if ( ! mediaUploader ) {
				// Extend the wp.media object
				mediaUploader = wp.media.frames.file_frame = wp.media( {
					multiple: false,
				} );

				// When a file is selected, grab the URL and set it as the text field's value
				mediaUploader.on( 'select', function () {
					let attachment = mediaUploader.state().get( 'selection' ).first().toJSON();
					$( '#front_background_image' ).val( attachment.url );
					$( '.front-background-image' ).attr( 'src', attachment.url );
					$( '.front-background-image-preview-wrap' ).removeClass( 'hidden' );
					$( '.front-background-image-upload-button, .front-background-image-description' ).addClass( 'hidden' );
				} );
			}
			// Open the uploader dialog
			mediaUploader.open();
		} );
	} );
	$( '.delete-front-background-image' ).on( 'click', function () {
		$( '#front_background_image' ).val( '' );
		$( '.front-background-image-preview-wrap' ).addClass( 'hidden' );
		$( '.front-background-image-upload-button, .front-background-image-description' ).removeClass( 'hidden' );
	} );

	// Custom PPI
	let myPpi = $( '#ppi' );
	let myCustomPpi = $( '#custom_ppi' );
	if ( myPpi.val() !== '' ) {
		myCustomPpi.parent().parent().hide();
	}
	myPpi.on( 'change', function () {
		if ( $( this ).val() === '' ) {
			myCustomPpi.parent().parent().show();
		} else {
			myCustomPpi.parent().parent().hide();
			myCustomPpi.val( $( this ).val() );
		}
	} );

	// Color pickers
	$( '.colorpicker' ).wpColorPicker();

	// Set element variables
	const form = $( '.settings-form' );
	const makePdfButton = $( '#generate-pdf' );
	const makeEbookButton = $( '#generate-jpg' );
	const bar = $( '#pb-sse-progressbar' );
	const info = $( '#pb-sse-info' );
	const notices = $( '.notice' );

	// Initialize clock
	let clock = null;

	// Event source handler
	let eventSourceHandler = function ( fileType ) {
		// Initialize event data
		const hiddenForm = $( 'form.' + fileType );
		const eventSourceUrl = PB_CoverGeneratorToken.ajaxUrl + ( PB_CoverGeneratorToken.ajaxUrl.includes( '?' ) ? '&' : '?' ) + $.param( hiddenForm.find( ':input' ) );
		const evtSource = new EventSource( eventSourceUrl );

		// Handle open
		evtSource.onopen = function () {
			// Warn the user if they navigate away
			$( window ).on( 'beforeunload', function () {
				// In some browsers, the return value of the event is displayed in this dialog. Starting with Firefox 44, Chrome 51, Opera 38 and Safari 9.1, a generic string not under the control of the webpage will be shown.
				// @see https://developer.mozilla.org/en-US/docs/Web/API/WindowEventHandlers/onbeforeunload#Notes
				return PB_CoverGeneratorToken.unloadWarning;
			} );
		};
		evtSource.onmessage = function ( message ) {
			let data = JSON.parse( message.data );
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
						makePdfButton.attr( 'disabled', false ).show();
						makeEbookButton.attr( 'disabled', false ).show();
						displayNotice( 'error', data.error, true );
						if ( clock ) {
							resetClock( clock );
						}
					} else {
						window.location = PB_CoverGeneratorToken.redirectUrl;
					}
					break;
				default:
					break;
			}
		};
		evtSource.onerror = function () {
			evtSource.close();
			bar.removeAttr( 'value' );
			info.html( 'EventStream Connection Error ' + PB_CoverGeneratorToken.reloadSnippet );
			$( window ).unbind( 'beforeunload' );
			if ( clock ) {
				resetClock( clock );
			}
		};
	};

	form.on( 'saveAndGenerate', function ( event, fileType ) {
		makePdfButton.hide();
		makeEbookButton.hide();
		bar.val( 0 ).show();
		notices.remove();

		clock = startClock();
		info.html( PB_CoverGeneratorToken.ajaxSubmitMsg );

		// Save the WP options and WP Media before triggering the generator
		// @see https://github.com/jquery-form/form
		$( this ).ajaxSubmit( {
			done: eventSourceHandler( fileType ),
			timeout: 5000,
		} );

		// Return false to prevent normal browser submit and page navigation.
		return false;
	} );

	makePdfButton.click( function () {
		let editor = tinymce.get( 'pb_about_unlimited' );
		if ( editor ) {
			let content = editor.getContent();
			$( '#pb_about_unlimited' ).val( content );
		}
		form.trigger( 'saveAndGenerate', [ 'pdf' ] );
	} );

	makeEbookButton.click( function () {
		form.trigger( 'saveAndGenerate', [ 'jpg' ] );
	} );
} );
