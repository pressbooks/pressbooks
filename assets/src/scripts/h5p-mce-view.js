/* global PB_H5PViewToken */

/**
 * H5P MCE View - Renders H5P shortcodes as iframe previews in TinyMCE
 *
 * Uses WordPress's wp.mce.views API to convert [h5p id="X"] shortcodes
 * into visual iframe previews within the editor.
 *
 * @param {object} window - The window object.
 * @param {object} wp - The WordPress object.
 * @param {object} $ - jQuery.
 */
( function ( window, wp, $ ) {
	'use strict';

	if ( ! wp || ! wp.mce || ! wp.mce.views ) {
		return;
	}

	/**
	 * Register the H5P view type
	 */
	wp.mce.views.register( 'h5p', {
		/**
		 * Match [h5p id="X"] or [h5p slug="X"] shortcodes
		 *
		 * @param {string} content - Editor content to scan
		 * @returns {object|boolean} Match object or false
		 */
		match: function ( content ) {
			const match = wp.shortcode.next( 'h5p', content );
			if ( match ) {
				return {
					index: match.index,
					content: match.content,
					options: {
						shortcode: match.shortcode,
					},
				};
			}
			return false;
		},

		/**
		 * Fetch H5P content data and render the preview
		 */
		initialize: function () {
			const self = this;
			const attrs = this.shortcode.attrs.named;
			const id = attrs.id || '';
			const slug = attrs.slug || '';

			if ( ! id && ! slug ) {
				self.setError( PB_H5PViewToken.error_no_id );
				return;
			}

			// Show loading state
			self.render( self.loadingTemplate() );

			// Fetch H5P content data via AJAX
			wp.ajax.post( 'pb_h5p_preview', {
				id: id,
				slug: slug,
				nonce: PB_H5PViewToken.nonce,
			} )
				.done( function ( response ) {
					// wp.ajax.post returns the data directly on success
					if ( response && response.id ) {
						const html = self.template( response );
						self.setContent( html );
						wp.mce.views.render();
					} else {
						self.setError( PB_H5PViewToken.error_loading );
					}
				} )
				.fail( function () {
					self.setError( PB_H5PViewToken.error_loading );
				} );
		},

		/**
		 * Loading state template
		 *
		 * @returns {string} HTML string
		 */
		loadingTemplate: function () {
			return (
				'<div class="h5p-mce-preview h5p-mce-loading">' +
				'<div class="h5p-mce-header">' +
				'<span class="h5p-mce-icon dashicons dashicons-welcome-learn-more"></span>' +
				'<span class="h5p-mce-title">' + PB_H5PViewToken.loading + '</span>' +
				'</div>' +
				'</div>'
			);
		},

		/**
		 * Main preview template with iframe
		 *
		 * @param {object} data - H5P content data
		 * @returns {string} HTML string
		 */
		template: function ( data ) {
			const escapedTitle = $( '<div>' ).text( data.title ).html();
			const escapedType = $( '<div>' ).text( data.contentType ).html();
			const escapedUrl = $( '<div>' ).text( data.embedUrl ).html();

			return (
				'<div class="h5p-mce-preview" data-h5p-id="' + data.id + '">' +
				'<div class="h5p-mce-header">' +
				'<span class="h5p-mce-icon dashicons dashicons-welcome-learn-more"></span>' +
				'<span class="h5p-mce-title">' + escapedTitle + '</span>' +
				'<span class="h5p-mce-type">' + escapedType + '</span>' +
				'</div>' +
				'<div class="h5p-mce-iframe-wrapper">' +
				'<iframe ' +
				'src="' + escapedUrl + '" ' +
				'class="h5p-mce-iframe" ' +
				'frameborder="0" ' +
				'allowfullscreen="allowfullscreen" ' +
				'loading="lazy"' +
				'></iframe>' +
				'</div>' +
				'</div>'
			);
		},

		/**
		 * Handle edit action - reopen H5P selector dialog
		 *
		 * @param {string} text - Current shortcode text
		 * @param {Function} update - Callback to update content
		 */
		edit: function ( text, update ) {
			// Check if H5P insert dialog exists
			const $addH5pButton = $( '#add-h5p' );
			if ( $addH5pButton.length ) {
				/**
				 * Store the update callback for later use
				 *
				 * @param {string} newShortcode - The new shortcode to insert
				 */
				window.pbH5pViewUpdate = function ( newShortcode ) {
					update( newShortcode );
					delete window.pbH5pViewUpdate;
				};
				// Trigger the H5P button click to open dialog
				$addH5pButton.trigger( 'click' );
			}
		},
	} );

	/**
	 * Handle iframe resizing via H5P's postMessage API
	 *
	 * @param {object} event - The jQuery wrapped message event
	 */
	$( window ).on( 'message', function ( event ) {
		const originalEvent = event.originalEvent;
		if ( ! originalEvent.data ) {
			return;
		}

		let data;
		try {
			data = typeof originalEvent.data === 'string'
				? JSON.parse( originalEvent.data )
				: originalEvent.data;
		} catch ( e ) {
			return;
		}

		// Handle H5P resize messages
		if ( data.context === 'h5p' && data.action === 'resize' ) {
			// Find the iframe that sent this message and resize it
			$( '.h5p-mce-iframe' ).each( function () {
				if ( this.contentWindow === originalEvent.source ) {
					$( this ).css( 'height', data.scrollHeight + 'px' );
				}
			} );
		}
	} );
}( window, window.wp, window.jQuery ) );
