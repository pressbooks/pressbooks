(function () {
	tinymce.create('tinymce.plugins.footnote', {
		init: function (ed, url) {
			ed.addButton('footnote', {
				title: PB_FootnotesToken.fn_title,
				image: url + '/fn.png',
				onclick: function () {
					var mySelection = ed.selection.getContent();
					if (mySelection != '') {
						var footNote = mySelection;
						ed.selection.setContent('[footnote]' + footNote + '[/footnote]');
					} else {
						var footNote = prompt("Footnote Content", "Enter your footnote content here.");
						if (footNote != '') {
							ed.execCommand('mceInsertContent', false, '[footnote]' + footNote + '[/footnote]');
						}
					}
				}
			});
		},
		createControl: function (n, cm) {
			return null;
		}
	});
	tinymce.PluginManager.add('footnote', tinymce.plugins.footnote);
})();