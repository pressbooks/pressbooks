( function () {
	tinymce.create( 'tinymce.plugins.glossary', {
		init: function ( ed, url ) {
			ed.addButton( 'glossary', {
				title: PB_GlossaryToken.glossary_title,
				text: 'GL',
				icon: false,
				onclick: function () {
					let mySelection = ed.selection.getContent();
					let glossaryTerm;
					if ( mySelection !== '' ) {
                        glossaryTerm = prompt(
                            'Glossary Content',
                            mySelection
                        );
						ed.selection.setContent( '[pb_glossary]' + glossaryTerm + '[/pb_glossary]' );
					} else {
						glossaryTerm = prompt(
							'Glossary Content',
							'Enter your glossary content here.'
						);
						if ( glossaryTerm !== '' ) {
							ed.execCommand(
								'mceInsertContent',
								false,
								'[pb_glossary]' + glossaryTerm + '[/pb_glossary]'
							);
						}
					}
				},
			} );
		},
		createControl: function ( n, cm ) {
			return null;
		},
	} );
	tinymce.PluginManager.add( 'glossary', tinymce.plugins.glossary );
} )();
