/* global tinymce:true */
/* global PB_FootnotesToken:true */

(function () {
	tinymce.create('tinymce.plugins.footnote', {
		init: function (ed, url) {
			ed.addButton('footnote', {
				title:   PB_FootnotesToken.fn_title,
				text:    'FN',
				icon:    false,
				onclick: function () {
					var mySelection = ed.selection.getContent();
					var footNote;
					if (mySelection !== '') {
						footNote = mySelection;
						ed.selection.setContent( '[footnote]' + footNote + '[/footnote]' );
					} else {
						footNote = prompt( 'Footnote Content', 'Enter your footnote content here.' );
						if (footNote !== '') {
							ed.execCommand( 'mceInsertContent', false, '[footnote]' + footNote + '[/footnote]' );
						}
					}
				},
			});
		},
		createControl: function (n, cm) {
			return null;
		},
	});
	tinymce.PluginManager.add( 'footnote', tinymce.plugins.footnote );
})();
