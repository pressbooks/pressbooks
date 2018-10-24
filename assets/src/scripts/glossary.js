( function () {

	// TODO: Language localisation, we can use PB_GlossaryToken

	tinymce.create( 'tinymce.plugins.glossary', {
		init: function ( ed, url ) {
			let glossaryTermValues = jQuery.parseJSON( PB_GlossaryToken.glossary_terms );

			function termValue( name ) {
				for ( let key in glossaryTermValues ) {
					if ( glossaryTermValues.hasOwnProperty( key ) ) {
						if ( glossaryTermValues[ key ].text.toLowerCase().trim() === name.toLowerCase().trim() ) {
							return glossaryTermValues[ key ].value;
						}
					}
				}
				return '';
			}

			function termName( value ) {
				for ( let key in glossaryTermValues ) {
					if ( glossaryTermValues.hasOwnProperty( key ) ) {
						if ( glossaryTermValues[ key ].value === value ) {
							return glossaryTermValues[ key ].text;
						}
					}
				}
				return '';
			}

			// This button adds the glossary short-code that generates a list of all terms
			ed.addButton( 'glossary_all', {
				title: PB_GlossaryToken.glossary_all_title,
				text: 'Glossary',
				icon: false,
				onclick: function () {
					ed.selection.setContent( '[pb_glossary]' );
				},
			} );

			// This button adds the single glossary term short-code with the corresponding term id as an attribute
			ed.addButton( 'glossary', {
				title: PB_GlossaryToken.glossary_title,
				text: 'GL',
				icon: false,
				onclick: function () {
					// get the user highlighted selection from the TinyMCE editor
					let mySelection = ed.selection.getContent();
					// placeholder for our default listbox value
					let listValue = termValue( mySelection );
					// placeholder for our term doesn't exist message
					let termExists = '';

					// if the selection matches an existing term, let's set it so we can use it as our default listbox value
					let myActiveTab;
					if ( listValue ) {
						myActiveTab = 1;
					} else {
						myActiveTab = 0;
						if ( mySelection ) {
							termExists = 'Glossary term <b>"' + mySelection + '"</b> not found. Please create it.';
						}
					}

					// display the UI
					let myWindow = tinymce.activeEditor.windowManager.open( {

						title: 'Glossary Terms',
						bodyType: 'tabpanel',

						body: [
							{
								title: 'Create and Insert Term',
								type: 'form',
								items: [
									{
										type: 'container',
										name: 'container',
										html: termExists,
									},
									{
										name: 'title',
										type: 'textbox',
										label: 'Title',
									},
									{
										name: 'body',
										type: 'textbox',
										label: 'Description',
										multiline: true,
										minHeight: 100,
									},
								],
							},
							{
								title: 'Choose Existing Term',
								type: 'form',
								items: [
									{
										type: 'listbox',
										name: 'term',
										label: 'Select a Term',
										values: glossaryTermValues,
										value: listValue,
									},
								],
							},
						],

						buttons: [
							{
								text: 'Cancel',
								onclick: 'close',
							},
							{
								text: 'Insert',
								subtype: 'primary',
								onclick: 'submit',
							},
						],

						onsubmit: function ( event ) {
							let mySubmittedTabId = this.find( 'tabpanel' )[ 0 ].activeTabId;
							if ( mySubmittedTabId === 't0' ) {
								// Create and Insert Term
								alert( 'TODO: Create and Insert Term' );
							} else {
								// Choose Existing Term
								if ( ! event.data.term || event.data.term.length === 0 ) {
									alert( 'A term was not selected?' );
									return false;
								} else if ( mySelection !== '' ) {
									// if there's a highlighted selection, use that as the text
									ed.selection.setContent( '[pb_glossary id="' + event.data.term + '"]' + mySelection + '[/pb_glossary]' );
								} else {
									// otherwise, use the value of the listbox as the text
									ed.selection.setContent( '[pb_glossary id="' + event.data.term + '"]' + termName( event.data.term ) + '[/pb_glossary]' );
								}
							}
						},
					}, );
					myWindow.find( 'tabpanel' )[ 0 ].activateTab( myActiveTab );
				},
			} );
		},
		createControl: function ( n, cm ) {
			return null;
		},
	} );
	tinymce.PluginManager.add( 'glossary_all', tinymce.plugins.glossary.all );
	tinymce.PluginManager.add( 'glossary', tinymce.plugins.glossary );
} )();
