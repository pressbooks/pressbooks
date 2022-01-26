/* global PB_LaTeXToken */

( function () {
	tinymce.create( 'tinymce.plugins.latex', {
		/**
		 * @param ed
		 */
		init: function ( ed ) {
			ed.addButton( 'latex', {
				title: PB_LaTeXToken.fn_title,
				text: 'LaTeX',
				icon: false,
				/**
				 *
				 */
				onclick: function () {
					let mySelection = ed.selection.getContent();
					let latex;
					if ( mySelection !== '' ) {
						latex = mySelection;
						ed.selection.setContent( '[latex]' + latex + '[/latex]' );
					} else {
						latex = prompt(
							'LaTeX Content',
							'Enter your LaTeX content here.'
						);
						if ( latex !== '' ) {
							ed.execCommand(
								'mceInsertContent',
								false,
								'[latex]' + latex + '[/latex]'
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
	tinymce.PluginManager.add( 'latex', tinymce.plugins.latex );
} )();
