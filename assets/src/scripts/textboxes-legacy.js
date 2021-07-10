/**
 * textboxes.js
 *
 * Copyright, Moxiecode Systems AB
 * Released under LGPL License.
 *
 * License: http://www.tinymce.com/license
 * Contributing: http://www.tinymce.com/contributing
 */

tinymce.PluginManager.add( 'textboxes', function ( editor ) {
	/**
	 *
	 */
	function showDialog() {
		let selectedNode = editor.selection.getNode();

		editor.windowManager.open( {
			title: editor.getLang( 'strings.customtextbox' ),
			body: {
				type: 'textbox',
				name: 'className',
				size: 40,
				label: editor.getLang( 'strings.classtitle' ),
				value: selectedNode.name || selectedNode.id,
			},
			/**
			 * @param e
			 */
			onsubmit: function ( e ) {
				editor.execCommand(
					'mceReplaceContent',
					false,
					'<div class="textbox ' + e.data.className + '">{$selection}</div>'
				);
			},
		} );
	}

	editor.addButton( 'textboxes', {
		type: 'menubutton',
		text: editor.getLang( 'strings.textboxes' ),
		icon: false,
		menu: [
			{
				text: editor.getLang( 'strings.standard' ),
				/**
				 *
				 */
				onclick: function () {
					let selection = editor.selection.getContent();
					if ( selection !== '' ) {
						editor.execCommand(
							'mceReplaceContent',
							false,
							'<div class="textbox">' + selection + '</div><p></p>'
						);
					} else {
						editor.execCommand(
							'mceInsertContent',
							0,
							'<div class="textbox">' +
								editor.getLang( 'strings.standardplaceholder' ) +
								'</div><p></p>'
						);
					}
				},
			},
			{
				text: editor.getLang( 'strings.shaded' ),
				/**
				 *
				 */
				onclick: function () {
					let selection = editor.selection.getContent();
					if ( selection !== '' ) {
						editor.execCommand(
							'mceReplaceContent',
							false,
							'<div class="textbox shaded">' + selection + '</div><p></p>'
						);
					} else {
						editor.execCommand(
							'mceInsertContent',
							0,
							'<div class="textbox shaded">' +
								editor.getLang( 'strings.standardplaceholder' ) +
								'</div><p></p>'
						);
					}
				},
			},
			{
				text: editor.getLang( 'strings.learningobjectives' ),
				/**
				 *
				 */
				onclick: function () {
					let selection = editor.selection.getContent();
					if ( selection !== '' ) {
						editor.execCommand(
							'mceReplaceContent',
							false,
							'<div class="textbox learning-objectives"><h3 itemprop="educationalUse">' +
								editor.getLang( 'strings.learningobjectives' ) +
								'</h3>\n' +
								selection +
								'</div><p></p>'
						);
					} else {
						editor.execCommand(
							'mceInsertContent',
							0,
							'<div class="textbox learning-objectives"><h3 itemprop="educationalUse">' +
								editor.getLang( 'strings.learningobjectives' ) +
								'</h3>\n<p>' +
								editor.getLang( 'strings.learningobjectivesplaceholder' ) +
								'</p><ul><li>' +
								editor.getLang( 'strings.first' ) +
								'</li><li>' +
								editor.getLang( 'strings.second' ) +
								'</li></ul></div><p></p>'
						);
					}
				},
			},
			{
				text: editor.getLang( 'strings.keytakeaways' ),
				/**
				 *
				 */
				onclick: function () {
					let selection = editor.selection.getContent();
					if ( selection !== '' ) {
						editor.execCommand(
							'mceReplaceContent',
							false,
							'<div class="textbox key-takeaways"><h3 itemprop="educationalUse">' +
								editor.getLang( 'strings.keytakeaways' ) +
								'</h3>\n' +
								selection +
								'</div><p></p>'
						);
					} else {
						editor.execCommand(
							'mceInsertContent',
							0,
							'<div class="textbox key-takeaways"><h3 itemprop="educationalUse">' +
								editor.getLang( 'strings.keytakeaways' ) +
								'</h3>\n<p>' +
								editor.getLang( 'strings.keytakeawaysplaceholder' ) +
								'</p><ul><li>' +
								editor.getLang( 'strings.first' ) +
								'</li><li>' +
								editor.getLang( 'strings.second' ) +
								'</li></ul></div><p></p>'
						);
					}
				},
			},
			{
				text: editor.getLang( 'strings.exercises' ),
				/**
				 *
				 */
				onclick: function () {
					let selection = editor.selection.getContent();
					if ( selection !== '' ) {
						editor.execCommand(
							'mceReplaceContent',
							false,
							'<div class="textbox exercises"><h3 itemprop="educationalUse">' +
								editor.getLang( 'strings.exercises' ) +
								'</h3>\n' +
								selection +
								'</div><p></p>'
						);
					} else {
						editor.execCommand(
							'mceInsertContent',
							0,
							'<div class="textbox exercises"><h3 itemprop="educationalUse">' +
								editor.getLang( 'strings.exercises' ) +
								'</h3>\n<p>' +
								editor.getLang( 'strings.exercisesplaceholder' ) +
								'</p><ul><li>' +
								editor.getLang( 'strings.first' ) +
								'</li><li>' +
								editor.getLang( 'strings.second' ) +
								'</li></ul></div><p></p>'
						);
					}
				},
			},
			{
				text: editor.getLang( 'strings.examples' ),
				/**
				 *
				 */
				onclick: function () {
					let selection = editor.selection.getContent();
					if ( selection !== '' ) {
						editor.execCommand(
							'mceReplaceContent',
							false,
							'<div class="textbox examples"><h3 itemprop="educationalUse">' +
								editor.getLang( 'strings.examples' ) +
								'</h3>\n' +
								selection +
								'</div><p></p>'
						);
					} else {
						editor.execCommand(
							'mceInsertContent',
							0,
							'<div class="textbox examples"><h3 itemprop="educationalUse">' +
								editor.getLang( 'strings.examples' ) +
								'</h3>\n<p>' +
								editor.getLang( 'strings.examplesplaceholder' ) +
								'</p><ul><li>' +
								editor.getLang( 'strings.first' ) +
								'</li><li>' +
								editor.getLang( 'strings.second' ) +
								'</li></ul></div><p></p>'
						);
					}
				},
			},
			{
				text: editor.getLang( 'strings.customellipses' ),
				/**
				 *
				 */
				onclick: function () {
					showDialog();
				},
			},
		],
	} );
} );
