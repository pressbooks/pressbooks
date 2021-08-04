jQuery( function ( $ ) {
	// Media
	$( document ).ready( function () {
		jQuery('#addtag > div.form-field.contributor-picture-wrap > p.savebutton.ml-submit > input').hide();
		jQuery('#media-upload-header').hide();
		jQuery('#edittag > table > tbody > tr.form-field.contributor-picture-wrap > td > p.savebutton.ml-submit > input').hide();
		jQuery('#addtag > div.form-field.contributor-picture-wrap > h3').hide();
		jQuery('#edittag > table > tbody > tr.form-field.contributor-picture-wrap > td > h3').hide();
		jQuery('#plupload-upload-ui > p').hide();
		jQuery( '#plupload-browse-button' ).on( 'click', function( e ) {
			let pictureLibrary = wp.media({
				title: "Select a picture",
				frame: 'post',
				multiple: false,
				library: {
					type: 'image'
				},
				button: {
					text: 'Done'
				}
			});
			e.preventDefault();
			pictureLibrary.open();
			pictureLibrary.on( 'insert', function() {
				const selectedImage = pictureLibrary.state().get( 'selection' ).first().toJSON(); console.log(selectedImage);
				jQuery('#contributor-picture').val(selectedImage.url);
			});
		});
	});
});
