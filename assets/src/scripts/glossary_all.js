( function () {
	tinymce.create( 'tinymce.plugins.glossary.all', {
		init: function ( ed, url ) {
			ed.addButton( 'glossary_all', {
				title: PB_GlossaryToken.glossary_all_title,
				text: 'Glossary',
				icon: false,
				onclick: function () {
						ed.selection.setContent( '[pb_glossary]' );
				},
			} );
		},
		createControl: function ( n, cm ) {
			return null;
		},
	} );
	tinymce.PluginManager.add( 'glossary_all', tinymce.plugins.glossary.all );
} )();
