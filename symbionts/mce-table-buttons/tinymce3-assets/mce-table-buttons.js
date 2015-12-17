/**
 * WordPress plugin.
 */

(function() {
	var DOM = tinymce.DOM;

	tinymce.create('tinymce.plugins.mcetablebuttons', {
		init : function(ed, url) {
			var mce_table_toolbar;

			// Hides the specified toolbar and resizes the iframe
			ed.onPostRender.add(function() {
				mce_table_toolbar = ed.controlManager.get('toolbar3');
				if ( ed.getParam('wordpress_adv_hidden', 1) && mce_table_toolbar )
					DOM.hide(mce_table_toolbar.id);
			});

			ed.onExecCommand.add(function(ed, cmd, ui, val) {
				if ( cmd == 'WP_Adv' ) {
					if ( ed.settings.wordpress_adv_hidden == 1 )
						DOM.hide( mce_table_toolbar.id );
					else
						DOM.show( mce_table_toolbar.id );
				}
			});
		}
	});

	// Register plugin
	tinymce.PluginManager.add('mcetablebuttons', tinymce.plugins.mcetablebuttons);
})();
