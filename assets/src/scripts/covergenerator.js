jQuery( function ( $ ) {
	// Media
	$( document ).ready( function (){
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

	// Form Submission
	let bar = $( '#pb-sse-progressbar' );
	let info = $( '#pb-sse-info' );
	let makePdfButton = $( '#generate-pdf' );
	let makeEbookButton = $( '#generate-jpg' );
	// Init Clock
	let clock = null;
	let seconds = $( '#pb-sse-seconds' );
	let minutes = $( '#pb-sse-minutes' );
	function pad( val ) {
		return val > 9 ? val : '0' + val;
	}
	let startClock = function () {
		// Start Clock
		let sec = 0;
		seconds.html( '00' );
		minutes.html( '00:' );
		clock = setInterval( function () {
			seconds.html( pad( ++sec % 60 ) );
			minutes.html( pad( parseInt( sec / 60, 10 ) ) + ':' );
		}, 1000 );
	};
	let myEventSource = function ( fileType ) {
		// Init Event Data
		let hiddenForm = $( 'form.' + fileType );
		let eventSourceUrl = PB_CoverGeneratorToken.ajaxUrl + ( PB_CoverGeneratorToken.ajaxUrl.includes( '?' ) ? '&' : '?' ) + $.param( hiddenForm.find( ':input' ) );
		let evtSource = new EventSource( eventSourceUrl );
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
					bar.progressbar( { value: parseInt( data.percentage, 10 ) } );
					info.html( data.info );
					break;
				case 'complete':
					evtSource.close();
					$( window ).unbind( 'beforeunload' );
					if ( data.error ) {
						bar.progressbar( { value: false } );
						info.html( data.error + ' ' + PB_CoverGeneratorToken.reloadSnippet );
						if ( clock ) {
							clearInterval( clock );
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
			bar.progressbar( { value: false } );
			info.html( 'EventStream Connection Error ' + PB_CoverGeneratorToken.reloadSnippet );
			$( window ).unbind( 'beforeunload' );
			if ( clock ) {
				clearInterval( clock );
			}
		};
	};
	$( '.settings-form' ).on( 'saveAndGenerate', function ( event, fileType ) {
		makePdfButton.hide();
		makeEbookButton.hide();
		startClock();
		bar.progressbar( { value: 10 } );
		info.html( PB_CoverGeneratorToken.ajaxSubmitMsg );
		// Save the WP options and WP Media before triggering the generator
		// @see https://github.com/jquery-form/form
		$( this ).ajaxSubmit( {
			done: myEventSource( fileType ),
			timeout: 5000,
		} );
		return false; // return false to prevent normal browser submit and page navigation
	} );
	makePdfButton.click( function () {
		let editor = tinymce.get( 'pb_about_unlimited' );
		if ( editor ) {
			let content = editor.getContent();
			$( '#pb_about_unlimited' ).val( content );
		}
		$( 'form.settings-form' ).trigger( 'saveAndGenerate', [ 'pdf' ] );
	} );
	makeEbookButton.click( function () {
		$( 'form.settings-form' ).trigger( 'saveAndGenerate', [ 'jpg' ] );
	} );
} );
