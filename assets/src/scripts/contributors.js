jQuery( function ( $ ) {
	// Media
	$( document ).ready( function () {
		const min_picture_size = 400;

		// Add form
		jQuery( document ).ajaxComplete( function ( event, xhr, settings ) {
			if ( settings.data.indexOf( 'action=add-tag' ) >= 0 ) {
				window.tinyMCE.activeEditor.setContent( '' );
				jQuery( '#contributor-picture-thumbnail' ).attr( 'src', '' ).hide();
				jQuery( '#contributor-picture' ).val( '' );
			}
		} );

		jQuery( document ).ajaxSend( function ( event, xhr, settings ) {
			if ( settings.data.indexOf( 'action=add-tag' ) >= 0 ) {
				window.tinyMCE.triggerSave();
				let data_encoded = new URLSearchParams( settings.data );
				data_encoded.set( 'contributor_description', window.tinyMCE.activeEditor.getContent() );
				settings.data = data_encoded.toString();
			}
		} );
		jQuery( '.term-description-wrap' ).remove();

		jQuery( '#contributor-media-picture' ).hide();
		jQuery( '#wpbody-content > div.wrap.nosubsub > form' ).css( 'margin', 0 );
		jQuery( '#btn-media' ).on(  'click', function ( e ) {
			e.preventDefault();
			jQuery( '#plupload-browse-button' ).click();
		} );
		jQuery( '#plupload-browse-button' ).on( 'click', function ( e ) {
			// Cropper
			let Cropp = wp.media.controller.SiteIconCropper.extend( {
				/**
				 * Creates an object with the image attachment and crop properties.
				 *
				 * @param attachment
				 * @returns {$.promise} A jQuery promise with the custom header crop details.
				 */
				doCrop: function ( attachment ) {
					const cropDetails = attachment.get( 'cropDetails' );

					cropDetails.dst_width  = min_picture_size;
					cropDetails.dst_height = min_picture_size;

					return wp.ajax.post( 'crop-image', {
						nonce: attachment.get( 'nonces' ).edit,
						id: attachment.get( 'id' ),
						context: 'site-icon',
						cropDetails: cropDetails,
					} );
				},
			} );
			let pictureLibrary = wp.media( {
				button: {
					text: 'Done',
					close: false,
				},
				states: [
					new wp.media.controller.Library( {
						title: 'Select a picture',
						library: wp.media.query( { type: 'image' } ),
						multiple: false,
						date: false,
						priority: 20,
						suggestedWidth: min_picture_size,
						suggestedHeight: min_picture_size,
					} ),
					new Cropp( {
						/**
						 * Returns a set of options, computed from the attached image data and
						 * control-specific data, to be fed to the imgAreaSelect plugin in
						 * wp.media.view.Cropper.
						 *
						 * @param {wp.media.model.Attachment} attachment
						 * @param {wp.media.controller.Cropper} controller
						 * @returns {object} Options
						 */
						imgSelectOptions: function ( attachment, controller ) {
							let flexWidth  = false,
								flexHeight = false,
								realWidth  = attachment.get( 'width' ),
								realHeight = attachment.get( 'height' ),
								xInit = min_picture_size,
								yInit = min_picture_size,
								ratio = 1,
								xImg  = xInit,
								yImg  = yInit,
								x1, y1, imgSelectOptions;
							/**
							 * Return whether the image must be cropped, based on required dimensions.
							 *
							 * @param {boolean} flexW
							 * @param {boolean} flexH
							 * @param {number}  dstW
							 * @param {number}  dstH
							 * @param {number}  imgW
							 * @param {number}  imgH
							 * @returns {boolean}
							 */
							let mustBeCropped = function ( flexW, flexH, dstW, dstH, imgW, imgH ) {
								if ( flexW === true && flexH === true ) {
									return false;
								}

								if ( flexW === true && dstH === imgH ) {
									return false;
								}

								if ( flexH === true && dstW === imgW ) {
									return false;
								}

								if ( dstW === imgW && dstH === imgH ) {
									return false;
								}

								if ( imgW <= dstW ) {
									return false;
								}

								return true;
							};

							controller.set( 'canSkipCrop', ! mustBeCropped( flexWidth, flexHeight, xInit, yInit, realWidth, realHeight ) );

							if ( realWidth / realHeight > ratio ) {
								yInit = realHeight;
								xInit = yInit * ratio;
							} else {
								xInit = realWidth;
								yInit = xInit / ratio;
							}

							x1 = ( realWidth - xInit ) / 2;
							y1 = ( realHeight - yInit ) / 2;

							imgSelectOptions = {
								handles: true,
								keys: true,
								instance: true,
								persistent: true,
								imageWidth: realWidth,
								imageHeight: realHeight,
								minWidth: xImg > xInit ? xInit : xImg,
								minHeight: yImg > yInit ? yInit : yImg,
								x1: x1,
								y1: y1,
								x2: xInit + x1,
								y2: yInit + y1,
							};

							if ( flexHeight === false && flexWidth === false ) {
								imgSelectOptions.aspectRatio = xInit + ':' + yInit;
							}

							if ( flexHeight === true ) {
								delete imgSelectOptions.minHeight;
								imgSelectOptions.maxWidth = realWidth;
							}

							if ( flexWidth === true ) {
								delete imgSelectOptions.minWidth;
								imgSelectOptions.maxHeight = realHeight;
							}

							return imgSelectOptions;
						},
					} ),
				],
			} );
			e.preventDefault();
			pictureLibrary.open();
			pictureLibrary.on( 'cropped', function ( croppedImage ) {
				jQuery( '#contributor-picture' ).val( croppedImage.url );
				jQuery( '#contributor-picture-thumbnail' ).attr( 'src', croppedImage.url ).show();
			} );
			pictureLibrary.on( 'insert', function () {
				const attachment = pictureLibrary.state().get( 'selection' ).first().toJSON();
				jQuery( '#contributor-picture' ).val( attachment.url );
				jQuery( '#contributor-picture-thumbnail' ).attr( 'src', attachment.url ).show();
			} );
			pictureLibrary.on( 'select', function () {
				const attachment = pictureLibrary.state().get( 'selection' ).first().toJSON();
				if ( attachment.width !== min_picture_size || attachment.height !== min_picture_size ) {
					pictureLibrary.setState( 'cropper' );
				} else {
					jQuery( '#contributor-picture' ).val( attachment.url );
					jQuery( '#contributor-picture-thumbnail' ).attr( 'src', attachment.url ).show();
					pictureLibrary.close();
				}
			} );
		} );
	} );
} );
