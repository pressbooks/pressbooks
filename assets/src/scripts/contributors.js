/* global pictureSize */
jQuery( function ( $ ) {
	/**
	 * @param selector
	 * @param id
	 */
	const addAriaDescribedBy = function ( selector, id ) {
		const input = jQuery( selector );

		input.attr( 'aria-describedby', id );
		input.parent().find( 'p' ).attr( 'id', id );
	};

	const minPictureSize = parseInt( pictureSize.min ),
		contributorPictureElement = jQuery( '#contributor-picture' ),
		contributorPictureThumbnailElement = jQuery( '#contributor-picture-thumbnail' );

	/**
	 * Add read only property for slug input
	 */
	jQuery( '#slug' ).attr( 'readonly', true );

	addAriaDescribedBy( '#tag-name', 'name-description' );
	addAriaDescribedBy( '#name', 'name-description' );
	addAriaDescribedBy( '#tag-slug', 'slug-description' );
	addAriaDescribedBy( '#slug', 'slug-description' );
	addAriaDescribedBy( '#btn-media', 'media-description' );

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

	/**
	 * Returns a set of options, computed from the attached image data and
	 * control-specific data, to be fed to the imgAreaSelect plugin in
	 * wp.media.view.Cropper.
	 *
	 * @param {wp.media.model.Attachment} attachment
	 * @param {wp.media.controller.Cropper} controller
	 * @returns {object} Options
	 */
	let imgSelectOptions = function ( attachment, controller ) {
		let realWidth  = attachment.get( 'width' ),
			realHeight = attachment.get( 'height' ),
			xInit = minPictureSize,
			yInit = minPictureSize,
			ratio = 1,
			xImg  = xInit,
			yImg  = yInit,
			maxSize = minPictureSize * 2,
			x1, y1, imgSelectOptions;
		controller.set( 'canSkipCrop', ! mustBeCropped( false, false, xInit, yInit, realWidth, realHeight ) );
		if ( realWidth / realHeight > ratio ) {
			yInit = realHeight > maxSize ? maxSize : realHeight;
			xInit = yInit * ratio;
		} else {
			xInit = realWidth > maxSize ? maxSize : realWidth;
			yInit = xInit / ratio;
		}

		x1 = ( realWidth - xInit ) / 2;
		y1 = ( realHeight - yInit ) / 2;

		const isBiggerThanMinimum = realHeight > minPictureSize || realWidth > minPictureSize;
		imgSelectOptions = {
			handles: true,
			keys: true,
			instance: true,
			persistent: true,
			imageWidth: realWidth,
			imageHeight: realHeight,
			minWidth: xImg > xInit ? xInit : xImg,
			minHeight: yImg > yInit ? yInit : yImg,
			x1: isBiggerThanMinimum ? x1 - 1 : x1,
			y1: isBiggerThanMinimum ? y1 - 1 : y1,
			x2: isBiggerThanMinimum ? xInit + x1 - 1 : xInit + x1,
			y2: isBiggerThanMinimum ? yInit + y1 - 1 : yInit + y1,
		};
		imgSelectOptions.aspectRatio = xInit + ':' + yInit;

		return imgSelectOptions;
	};

	// Add form
	jQuery( document ).ajaxComplete( function ( event, xhr, settings ) {
		if ( settings.data.indexOf( 'action=add-tag' ) >= 0 ) {
			window.tinyMCE.activeEditor.setContent( '' );
			contributorPictureThumbnailElement.attr( 'src', '' ).hide();
			contributorPictureElement.val( '' );
		}
	} );

	jQuery( document ).ajaxSend( function ( event, xhr, settings ) {
		if ( settings.data.indexOf( 'action=add-tag' ) >= 0 ) {
			window.tinyMCE.triggerSave();
			let dataEncoded = new URLSearchParams( settings.data );
			dataEncoded.set( 'contributor_description', window.tinyMCE.activeEditor.getContent() );
			settings.data = dataEncoded.toString();
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
		let Cropp = wp.media.controller.CustomizeImageCropper.extend( {
			/**
			 * Creates an object with the image attachment and crop properties.
			 *
			 * @param attachment
			 * @returns {$.promise} A jQuery promise with the custom header crop details.
			 */
			doCrop: function ( attachment ) {
				const cropDetails = attachment.get( 'cropDetails' );
				const minSize = parseInt( minPictureSize );
				cropDetails.dst_width  = minSize;
				cropDetails.dst_height = minSize;

				return wp.ajax.post( 'crop-image', {
					nonce: attachment.get( 'nonces' ).edit,
					id: attachment.get( 'id' ),
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
					suggestedWidth: minPictureSize,
					suggestedHeight: minPictureSize,
				} ),
				new Cropp( { imgSelectOptions } ),
			],
		} );
		e.preventDefault();
		pictureLibrary.open();
		pictureLibrary.on( 'cropped', function ( croppedImage ) {
			contributorPictureElement.val( croppedImage.url );
			contributorPictureThumbnailElement.attr( 'src', croppedImage.url ).show();
		} );
		pictureLibrary.on( 'insert', function () {
			const attachment = pictureLibrary.state().get( 'selection' ).first().toJSON();
			contributorPictureElement.val( attachment.url );
			contributorPictureThumbnailElement.attr( 'src', attachment.url ).show();
		} );
		pictureLibrary.on( 'select', function () {
			const attachment = pictureLibrary.state().get( 'selection' ).first().toJSON();
			if ( attachment.width < minPictureSize || attachment.height < minPictureSize ) {
				const htmlError = '<div class="media-uploader-status errors">' +
					'<div class="upload-errors"><div class="upload-error">\n' +
					'<span class="upload-error-filename">Your image is too small.</span>' +
					'<span class="upload-error-message">' +
					'The image must be ' + minPictureSize +
					' by ' + minPictureSize + ' pixels. Your image is ' + attachment.width + ' by ' +
					attachment.height + ' pixels.</span></div></div></div>';

				jQuery( '.media-sidebar' )
					.html( htmlError );
				return;
			}
			if ( ! ( attachment.width === minPictureSize && attachment.height === minPictureSize ) ) {
				pictureLibrary.setState( 'cropper' );
			} else {
				contributorPictureElement.val( attachment.url );
				contributorPictureThumbnailElement.attr( 'src', attachment.url ).show();
				pictureLibrary.close();
			}
		} );
	} );
} );
