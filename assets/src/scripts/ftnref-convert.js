/* global PB_FootnotesToken */

( function () {
	tinymce.create( 'tinymce.plugins.ftnref_convert', {
		init: function ( ed, url ) {
			ed.addButton( 'ftnref_convert', {
				title: PB_FootnotesToken.ftnref_title,
				icon: 'icon dashicons-screenoptions',
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
						beforeSend: function () {
							ed.setProgressState( 1 ); // Show progress
						},
						success: function ( data, textStatus, transport ) {
							ed.setProgressState( 0 ); // Hide progress
							ed.setContent( data.content, { format: 'raw' } );
						},
						error: function ( transport ) {
							ed.setProgressState( 0 ); // Hide progress
							if ( jQuery.trim( transport.responseText ).length ) {
								alert( transport.responseText );
							}
						},
					} );
				},
			} );
		},
		createControl: function ( n, cm ) {
			return null;
		},
	} );
	tinymce.PluginManager.add( 'ftnref_convert', tinymce.plugins.ftnref_convert );
} )();
