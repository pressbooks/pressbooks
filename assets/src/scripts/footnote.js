/* global PB_FootnotesToken */

( function () {
	tinymce.create( 'tinymce.plugins.footnote', {
		/**
		 * @param ed
		 * @param url
		 */
		init: function ( ed, url ) {
			ed.addButton( 'footnote', {
				title: PB_FootnotesToken.fn_title,
				text: 'FN',
				icon: false,
				/**
				 *
				 */
				onclick: function () {
					let mySelection = ed.selection.getContent();
					let footNote;
					if ( mySelection !== '' ) {
						footNote = mySelection;
						ed.selection.setContent( '[footnote]' + footNote + '[/footnote]' );
					} else {
						footNote = prompt(
							'Footnote Content',
							'Enter your footnote content here.'
						);
						if ( footNote !== '' ) {
							ed.execCommand(
								'mceInsertContent',
								false,
								'[footnote]' + footNote + '[/footnote]'
							);
						}
					}
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
	tinymce.PluginManager.add( 'footnote', tinymce.plugins.footnote );
} )();
