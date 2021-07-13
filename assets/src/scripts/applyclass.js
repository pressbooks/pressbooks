tinymce.PluginManager.add( 'apply_class', function ( editor ) {
	/**
	 *
	 */
	function showDialog() {
		let selectedNode = editor.selection.getNode();
		let selectedContent = editor.selection.getContent();
		editor.windowManager.open( {
			title: editor.getLang( 'strings.applyclass' ),
			body: {
				type: 'textbox',
				name: 'class',
				size: 40,
				label: editor.getLang( 'strings.classtitle' ),
			},
			/**
			 * @param e
			 */
			onsubmit: function ( e ) {
				if ( selectedContent !== '' ) {
					editor.selection.setContent(
						'<span class="' + e.data.class + '">' + selectedContent + '</span>'
					);
				} else {
					editor.dom.addClass( selectedNode, e.data.class );
				}
			},
		} );
	}
	editor.addButton( 'apply_class', {
		icon: 'icon dashicons-art',
		tooltip: editor.getLang( 'strings.applyclass' ),
		onclick: showDialog,
	} );
	editor.addMenuItem( 'apply_class', {
		icon: 'icon dashicons-art',
		text: editor.getLang( 'strings.applyclass' ),
		context: 'insert',
		onclick: showDialog,
	} );
} );
