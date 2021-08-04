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
	});
});
