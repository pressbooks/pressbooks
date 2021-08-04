jQuery( function ( $ ) {
	// Media
	$( document ).ready( function () {
		jQuery( '#plupload-browse-button' ).on( 'click', function( e ) {
			let pictureLibrary = wp.media({
				title: "Select picture",
				multiple: false,
				button: {
					text: 'Done'
				}
			});
			e.preventDefault();
			pictureLibrary.open();
			pictureLibrary.on( 'select', function() {
				const selectedImage = pictureLibrary.state().get( 'selection' ).first().toJSON();
				jQuery('#contributor-picture').val(selectedImage.url);
			});
		});

		// hide description field
		jQuery(window).ready(function(){
			jQuery('.term-description-wrap').remove();
		});

		// Clean Tinymce biography field
		jQuery( document ).ajaxComplete(function(event, xhr, settings) {
			if ( settings.data.indexOf('action=add-tag') >= 0 ) {
				window.tinyMCE.activeEditor.setContent('');
			}
		});

		jQuery('.term-description-wrap').remove();
		jQuery('#submit').on('click', function(event) {
			event.preventDefault();
			window.tinyMCE.triggerSave();
			event.target.click();
		});

	});
});
