( function () {
    tinymce.create( 'tinymce.plugins.glossary', {
        init: function ( ed, url ) {
            ed.addButton( 'glossary', {
                title: PB_GlossaryToken.glossary_title,
                text: 'GL',
                icon: false,
                onclick: function () {
                    // make sure we clea
                    let json_str = PB_GlossaryToken.glossary_terms.replace(/&quot;/g, '"');
                    let terms = jQuery.parseJSON(json_str);
                    let mySelection = ed.selection.getContent();
                    let glossaryTerm;

                    // if there was a highlighted selection, check for it's existence and get the value of the ID
                    if (mySelection !== '') {
                        // get the keys
                        let myKeys = Object.keys(terms);
                        // check if mySelection exists
                        let matchingKeys = myKeys.filter(function (key) {
                            return key.indexOf(mySelection) !== -1
                        });
                        // get the value of the id for the match
                        let matchingID = matchingKeys.map(function (key) {
                            return terms[key]['id']
                        });
                        // display the UI to lookup and select an existing term
                        glossaryTerm = prompt(
                            'Glossary Content',
                            mySelection
                        );
                        // insert the short-code with the id as an attribute
                        ed.selection.setContent('[pb_glossary' + ' id="' + matchingID[0] + '"]' + glossaryTerm + '[/pb_glossary]');

                        // if there was no highlighted selection, display the UI to lookup and select an existing term
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
