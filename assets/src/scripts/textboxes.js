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

	/**
	 * @param type
	 * @param title
	 * @param selection
	 */
	function eduTextboxWithSelection( type, title, selection ) {
		return `<div class="textbox textbox--${type}"><header class="textbox__header"><p class="textbox__title">${title}</p></header>\n<div class="textbox__content">${selection}</div></div><p></p>`;
	}

	/**
	 * @param type
	 * @param title
	 * @param placeholder
	 * @param first
	 * @param second
	 */
	function eduTextboxWithPlaceholder( type, title, placeholder, first, second ) {
		return `<div class="textbox textbox--${type}"><header class="textbox__header"><p class="textbox__title">${title}</p></header>\n<div class="textbox__content"><p>${placeholder}</p><ul><li>${first}</li><li>${second}</li></ul></div></div><p></p>`;
	}

	/**
	 * @param type
	 * @param title
	 * @param selection
	 */
	function eduSidebarTextboxWithSelection( type, title, selection ) {
		return `<div class="textbox textbox--sidebar textbox--${type}"><header class="textbox__header"><p class="textbox__title">${title}</p></header>\n<div class="textbox__content">${selection}</div></div><p></p>`;
	}

	/**
	 * @param type
	 * @param title
	 * @param placeholder
	 * @param first
	 * @param second
	 */
	function eduSidebarTextboxWithPlaceholder(
		type,
		title,
		placeholder,
		first,
		second
	) {
		return `<div class="textbox textbox--sidebar textbox--${type}"><header class="textbox__header"><p class="textbox__title">${title}</p></header>\n<div class="textbox__content"><p>${placeholder}</p><ul><li>${first}</li><li>${second}</li></ul></div></div><p></p>`;
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
				text: editor.getLang( 'strings.standardsidebar' ),
				/**
				 *
				 */
				onclick: function () {
					let selection = editor.selection.getContent();
					if ( selection !== '' ) {
						editor.execCommand(
							'mceReplaceContent',
							false,
							'<div class="textbox textbox--sidebar">' +
								selection +
								'</div><p></p>'
						);
					} else {
						editor.execCommand(
							'mceInsertContent',
							0,
							'<div class="textbox textbox--sidebar">' +
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
				text: editor.getLang( 'strings.shadedsidebar' ),
				/**
				 *
				 */
				onclick: function () {
					let selection = editor.selection.getContent();
					if ( selection !== '' ) {
						editor.execCommand(
							'mceReplaceContent',
							false,
							'<div class="textbox textbox--sidebar shaded">' +
								selection +
								'</div><p></p>'
						);
					} else {
						editor.execCommand(
							'mceInsertContent',
							0,
							'<div class="textbox textbox--sidebar shaded">' +
								editor.getLang( 'strings.standardplaceholder' ) +
								'</div><p></p>'
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
					let type = 'examples';
					let selection = editor.selection.getContent();
					let title = editor.getLang( `strings.${type}` );
					let placeholder = editor.getLang( `strings.${type}placeholder` );
					let first = editor.getLang( 'strings.first' );
					let second = editor.getLang( 'strings.second' );
					if ( selection !== '' ) {
						editor.execCommand(
							'mceReplaceContent',
							false,
							eduTextboxWithSelection( type, title, selection )
						);
					} else {
						editor.execCommand(
							'mceInsertContent',
							0,
							eduTextboxWithPlaceholder( type, title, placeholder, first, second )
						);
					}
				},
			},
			{
				text: editor.getLang( 'strings.examplessidebar' ),
				/**
				 *
				 */
				onclick: function () {
					let type = 'examples';
					let selection = editor.selection.getContent();
					let title = editor.getLang( `strings.${type}sidebar` );
					let placeholder = editor.getLang( `strings.${type}placeholder` );
					let first = editor.getLang( 'strings.first' );
					let second = editor.getLang( 'strings.second' );
					if ( selection !== '' ) {
						editor.execCommand(
							'mceReplaceContent',
							false,
							eduSidebarTextboxWithSelection( type, title, selection )
						);
					} else {
						editor.execCommand(
							'mceInsertContent',
							0,
							eduSidebarTextboxWithPlaceholder(
								type,
								title,
								placeholder,
								first,
								second
							)
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
					let type = 'exercises';
					let selection = editor.selection.getContent();
					let title = editor.getLang( `strings.${type}` );
					let placeholder = editor.getLang( `strings.${type}placeholder` );
					let first = editor.getLang( 'strings.first' );
					let second = editor.getLang( 'strings.second' );
					if ( selection !== '' ) {
						editor.execCommand(
							'mceReplaceContent',
							false,
							eduTextboxWithSelection( type, title, selection )
						);
					} else {
						editor.execCommand(
							'mceInsertContent',
							0,
							eduTextboxWithPlaceholder( type, title, placeholder, first, second )
						);
					}
				},
			},
			{
				text: editor.getLang( 'strings.exercisessidebar' ),
				/**
				 *
				 */
				onclick: function () {
					let type = 'exercises';
					let selection = editor.selection.getContent();
					let title = editor.getLang( `strings.${type}sidebar` );
					let placeholder = editor.getLang( `strings.${type}placeholder` );
					let first = editor.getLang( 'strings.first' );
					let second = editor.getLang( 'strings.second' );
					if ( selection !== '' ) {
						editor.execCommand(
							'mceReplaceContent',
							false,
							eduSidebarTextboxWithSelection( type, title, selection )
						);
					} else {
						editor.execCommand(
							'mceInsertContent',
							0,
							eduSidebarTextboxWithPlaceholder(
								type,
								title,
								placeholder,
								first,
								second
							)
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
					let type = 'key-takeaways';
					let selection = editor.selection.getContent();
					let title = editor.getLang( 'strings.keytakeaways' );
					let placeholder = editor.getLang( 'strings.keytakeawaysplaceholder' );
					let first = editor.getLang( 'strings.first' );
					let second = editor.getLang( 'strings.second' );
					if ( selection !== '' ) {
						editor.execCommand(
							'mceReplaceContent',
							false,
							eduTextboxWithSelection( type, title, selection )
						);
					} else {
						editor.execCommand(
							'mceInsertContent',
							0,
							eduTextboxWithPlaceholder( type, title, placeholder, first, second )
						);
					}
				},
			},
			{
				text: editor.getLang( 'strings.keytakeawayssidebar' ),
				/**
				 *
				 */
				onclick: function () {
					let type = 'key-takeaways';
					let selection = editor.selection.getContent();
					let title = editor.getLang( 'strings.keytakeawayssidebar' );
					let placeholder = editor.getLang( 'strings.keytakeawaysplaceholder' );
					let first = editor.getLang( 'strings.first' );
					let second = editor.getLang( 'strings.second' );
					if ( selection !== '' ) {
						editor.execCommand(
							'mceReplaceContent',
							false,
							eduSidebarTextboxWithSelection( type, title, selection )
						);
					} else {
						editor.execCommand(
							'mceInsertContent',
							0,
							eduSidebarTextboxWithPlaceholder(
								type,
								title,
								placeholder,
								first,
								second
							)
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
					let type = 'learning-objectives';
					let selection = editor.selection.getContent();
					let title = editor.getLang( 'strings.learningobjectives' );
					let placeholder = editor.getLang(
						'strings.learningobjectivesplaceholder'
					);
					let first = editor.getLang( 'strings.first' );
					let second = editor.getLang( 'strings.second' );
					if ( selection !== '' ) {
						editor.execCommand(
							'mceReplaceContent',
							false,
							eduTextboxWithSelection( type, title, selection )
						);
					} else {
						editor.execCommand(
							'mceInsertContent',
							0,
							eduTextboxWithPlaceholder( type, title, placeholder, first, second )
						);
					}
				},
			},
			{
				text: editor.getLang( 'strings.learningobjectivessidebar' ),
				/**
				 *
				 */
				onclick: function () {
					let type = 'learning-objectives';
					let selection = editor.selection.getContent();
					let title = editor.getLang( 'strings.learningobjectivessidebar' );
					let placeholder = editor.getLang(
						'strings.learningobjectivesplaceholder'
					);
					let first = editor.getLang( 'strings.first' );
					let second = editor.getLang( 'strings.second' );
					if ( selection !== '' ) {
						editor.execCommand(
							'mceReplaceContent',
							false,
							eduSidebarTextboxWithSelection( type, title, selection )
						);
					} else {
						editor.execCommand(
							'mceInsertContent',
							0,
							eduSidebarTextboxWithPlaceholder(
								type,
								title,
								placeholder,
								first,
								second
							)
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
