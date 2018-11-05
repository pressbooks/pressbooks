( function () {
	tinymce.create( 'tinymce.plugins.glossary', {
		init: function ( ed, url ) {
			let glossaryTermValues = jQuery.parseJSON( PB_GlossaryToken.listbox_values );

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

			// This button adds the single glossary term short-code with the corresponding term id as an attribute
			ed.addButton( 'glossary', {
				title: PB_GlossaryToken.glossary_button_title,
				text: 'GL',
				icon: false,
				onclick: function () {
					// get the user highlighted selection from the TinyMCE editor
					let mySelection = ed.selection.getContent();
					// placeholder for our default listbox value
					let listValue = termValue( mySelection );
					// placeholder for our term doesn't exist message
					let termExistsMessage = '';
					// Does the term exist?
					let termExists = ( listValue !== '' ) ? true : false;
					// Autofill the term name if the term does not exist
					let termAutofillValue = ( termExists ) ? '' : mySelection;

					// if the selection matches an existing term, let's set it so we can use it as our default listbox value
					let myActiveTab;
					if ( listValue ) {
						myActiveTab = 1;
					} else {
						myActiveTab = 0;
						if ( mySelection ) {
							let templateString1 = mySelection.trim(); // eslint-disable-line no-unused-vars
							termExistsMessage = eval( '`' + PB_GlossaryToken.not_found.replace( /`/g, '' ) + '`' ); // eslint-disable-line no-eval
						}
					}

					// display the UI
					let myWindow = tinymce.activeEditor.windowManager.open( {

						title: PB_GlossaryToken.window_title,
						bodyType: 'tabpanel',

						body: [
							{
								title: PB_GlossaryToken.tab0_title,
								type: 'form',
								items: [
									{
										type: 'container',
										name: 'container',
										html: termExistsMessage,
									},
									{
										name: 'title',
										type: 'textbox',
										label: PB_GlossaryToken.term_title,
										value: termAutofillValue,
									},
									{
										name: 'body',
										type: 'textbox',
										label: PB_GlossaryToken.description,
										multiline: true,
										minHeight: 100,
									},
								],
							},
							{
								title: PB_GlossaryToken.tab1_title,
								type: 'form',
								items: [
									{
										type: 'listbox',
										name: 'term',
										label: PB_GlossaryToken.select_a_term,
										values: glossaryTermValues,
										value: listValue,
									},
								],
							},
						],

						buttons: [
							{
								text: PB_GlossaryToken.cancel,
								onclick: 'close',
							},
							{
								text: PB_GlossaryToken.insert,
								subtype: 'primary',
								onclick: 'submit',
							},
						],

						onsubmit: function ( event ) {
							let mySubmittedTabId = this.find( 'tabpanel' )[ 0 ].activeTabId;
							if ( mySubmittedTabId === 't0' ) {
								// Create and Insert Term
								if ( ! event.data.title || event.data.title.length === 0 ) {
									alert( PB_GlossaryToken.term_is_empty );
									return false;
								}
								if ( termValue( event.data.title ) ) {
									alert( PB_GlossaryToken.term_already_exists );
									return false;
								}
								wp.api.loadPromise.done( function () {
									let glossary = new wp.api.models.Glossary( {
										title: event.data.title,
										content: event.data.body,
										status: 'publish',
									} );
									glossary.save().done( function () {
										if ( mySelection ) {
											ed.selection.setContent( '[pb_glossary id="' + glossary.id + '"]' + mySelection + '[/pb_glossary]' );
										} else {
											ed.selection.setContent( '[pb_glossary id="' + glossary.id + '"]' + event.data.title + '[/pb_glossary]' );
										}
										glossaryTermValues.push( {
											text: event.data.title,
											value: glossary.id,
										} );
									} );
								} );
							} else {
								// Choose Existing Term
								if ( ! event.data.term || event.data.term.length === 0 ) {
									alert( PB_GlossaryToken.term_not_selected );
									return false;
								} else if ( mySelection ) {
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
	tinymce.PluginManager.add( 'glossary', tinymce.plugins.glossary );
} )();
