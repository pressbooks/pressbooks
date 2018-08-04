(function () {
    tinymce.create('tinymce.plugins.glossary', {
        init: function (ed, url) {

            // get and clean up the data
            let json_str = PB_GlossaryToken.glossary_terms.replace(/&quot;/g, '"');
            let terms = jQuery.parseJSON(json_str);

            // get the keys
            let keys = Object.keys(terms);

            // get values for the combobox
            function getListTerms() {
                let terms = [];
                let termlist = {};

                for (let i = 0; i < keys.length; i++) {
                    termlist = {};
                    termlist['text'] = keys[i];
                    termlist['value'] = keys[i];
                    terms.push(termlist);
                }

                return terms;
            }

            // This button adds the glossary short-code that generates a list of all terms
            ed.addButton('glossary_all', {
                title: PB_GlossaryToken.glossary_all_title,
                text: 'Glossary',
                icon: false,
                onclick: function () {
                    ed.selection.setContent('[pb_glossary]');
                },
            });

            // This button adds the single glossary term short-code with the corresponding term id as an attribute
            ed.addButton('glossary', {
                title: PB_GlossaryToken.glossary_title,
                text: 'GL',
                icon: false,
                onclick: function () {
                    // get the highlighted selection
                    let mySelection = ed.selection.getContent();
                    // placeholder for our term
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
                        tinymce.activeEditor.windowManager.open({
                            title: 'Glossary terms',
                            buttons: [{
                                text: 'Accept',
                                subtype: 'primary',
                                onclick: 'submit'
                            },
                                {
                                    text: 'Close',
                                    onclick: 'close'
                                }
                            ],

                            body: [
                                {
                                    type: 'combobox',
                                    name: 'Terms',
                                    label: 'Select a Term',
                                    values: getListTerms(),
                                },
                            ],

                            width: 400,
                            height: 80,
                        },);
                        // insert the short-code with the id as an attribute
                        ed.selection.setContent('[pb_glossary' + ' id="' + matchingID[0] + '"]' + glossaryTerm + '[/pb_glossary]');

                        // if there was no highlighted selection, display the UI to lookup and select an existing term
                    } else {
                        glossaryTerm = prompt(
                            'Glossary Content',
                            'Enter your glossary content here.'
                        );
                        if (glossaryTerm !== '') {
                            ed.execCommand(
                                'mceInsertContent',
                                false,
                                '[pb_glossary]' + glossaryTerm + '[/pb_glossary]'
                            );
                        }
                    }
                },
            });
        },
        createControl: function (n, cm) {
            return null;
        },
    });
    tinymce.PluginManager.add('glossary_all', tinymce.plugins.glossary.all);
    tinymce.PluginManager.add('glossary', tinymce.plugins.glossary);
})();
