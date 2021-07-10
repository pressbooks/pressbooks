/* global PB_FootnotesToken */

( function () {
	tinymce.create( 'tinymce.plugins.ftnref_convert', {
		/**
		 * @param ed
		 * @param url
		 */
		init: function ( ed, url ) {
			ed.addButton( 'ftnref_convert', {
				title: PB_FootnotesToken.ftnref_title,
				icon: 'icon dashicons-screenoptions',
				/**
				 *
				 */
				onclick: function () {
					jQuery.ajax( {
						type: 'post',
						dataType: 'json',
						url: ajaxurl,
						data: {
							action: 'pb_ftnref_convert',
							content: ed.getContent(),
							_ajax_nonce: PB_FootnotesToken.nonce,
						},
						/**
						 *
						 */
						beforeSend: function () {
							ed.setProgressState( 1 ); // Show progress
						},
						/**
						 * @param data
						 * @param textStatus
						 * @param transport
						 */
						success: function ( data, textStatus, transport ) {
							ed.setProgressState( 0 ); // Hide progress
							ed.setContent( data.content, { format: 'raw' } );
						},
						/**
						 * @param transport
						 */
						error: function ( transport ) {
							ed.setProgressState( 0 ); // Hide progress
							if ( transport.responseText.trim().length ) {
								alert( transport.responseText );
							}
						},
					} );
				},
			} );
		},
		/**
		 * @param n
		 * @param cm
		 */
		createControl: function ( n, cm ) {
			return null;
		},
	} );
	tinymce.PluginManager.add( 'ftnref_convert', tinymce.plugins.ftnref_convert );
} )();
