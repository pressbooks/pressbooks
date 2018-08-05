jQuery( function ( $ ) {
	$( document ).ready( function (){
		let mediaUploader;

		$( '.front-background-image-upload-button' ).click( function ( e ) {
			e.preventDefault();
			// If the uploader object has already been created, reopen the dialog
			if ( mediaUploader ) {
				mediaUploader.open();
				return;
			}
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

			// Open the uploader dialog
			mediaUploader.open();
		} );
	} );
	$( '.colorpicker' ).wpColorPicker();
	if ( $( '#ppi' ).val() !== '' ) {
		$( '#custom_ppi' ).parent().parent().hide();
	}
	$( '#ppi' ).on( 'change', function () {
		if ( $( this ).val() === '' ) {
			$( '#custom_ppi' ).parent().parent().show();
		} else {
			$( '#custom_ppi' ).parent().parent().hide();
			$( '#custom_ppi' ).val( $( this ).val() );
		}
	} );
	$( '.delete-front-background-image' ).on( 'click', function () {
		$( '#front_background_image' ).val( '' );
		$( '.front-background-image-preview-wrap' ).addClass( 'hidden' );
		$( '.front-background-image-upload-button, .front-background-image-description' ).removeClass( 'hidden' );
	} );

	$( '.settings-form' ).on( 'saveAndGenerate', function ( event, fileType ) {
		$( this ).ajaxSubmit( {
			success: function () {
				$( 'form.'+fileType ).trigger( 'submit' );
			},
			timeout: 5000,
		} );
		return false;
	} );

	$( '#generate-pdf' ).click( function () {
		let editor = tinymce.get( 'pb_about_unlimited' );
		if ( editor ) {
			let content = editor.getContent();
			$( '#pb_about_unlimited' ).val( content );
		}
		$( 'form.settings-form' ).trigger( 'saveAndGenerate', [ 'pdf' ] );
	} );

	$( '#generate-jpg' ).click( function () {
		$( 'form.settings-form' ).trigger( 'saveAndGenerate', [ 'jpg' ] );
	} );
} );
