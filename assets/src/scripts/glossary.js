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

                for (let i of keys) {
                    termlist = {};
                    termlist['text'] = i;
                    termlist['value'] = i;
                    terms.push(termlist);
                }

                return terms;
            }

            // checks if the term exists, returns value or false if not found in glossary
            function termMatch(mySelection) {

                const matchingKeys = keys.filter(item => item.toLowerCase().indexOf(mySelection.toLowerCase()) !== -1);

                if (typeof matchingKeys[0] === 'undefined') {
                    return false;
                } else {
                    return matchingKeys[0];
                }
            }

            // gets the ID of a term in the glossary
            function termID(mySelection) {

                const matchingKeys = keys.filter(item => item.toLowerCase().indexOf(mySelection.toLowerCase()) !== -1);

                // get the id for the match, returns an empty array if none found
                matchingID = matchingKeys.map(function (key) {
                    return terms[key]['id']
                });

                // check if matchingID array does not exist, is not an array, or is empty
                if (Array.isArray(matchingID) || matchingID.length) {
                    return matchingID[0];
                }
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
                    // placeholder for our default listbox value
                    let listValue = '';
                    // placeholder for our term doesn't exist message
                    let termExists = '';

                    // if the selection matches an existing term, let's set it so we can use it as our default listbox value
                    if (termMatch(mySelection) !== false) {
                        listValue = termMatch(mySelection);
                    } else {
                        termExists = 'Glossary term <b>"' + mySelection + '"</b> not found.<br />Please create it, or select a term from the list below:';
                    }

                    // display the UI
                    tinymce.activeEditor.windowManager.open({

                        title: 'Glossary terms',
                        width: 500,
                        height: 100,

                        buttons: [{
                            text: 'Insert',
                            subtype: 'primary',
                            onclick: 'submit',
                        },
                            {
                                text: 'Close',
                                onclick: 'close'
                            }
                        ],

                        body: [
                            {
                                type: 'container',
                                name: 'container',
                                html: termExists
                            },
                            {
                                type: 'listbox',
                                name: 'terms',
                                label: 'Select a Term',
                                values: getListTerms(),
                                value: listValue
                            },
                        ],
                        // insert the short-code with the associated term ID
                        onsubmit: function (e) {
                            // if term exists, replace their selection with the short-code
                            if (termMatch(mySelection) !== false) {
                                ed.selection.setContent('[pb_glossary' + ' id="' + termID(e.data.terms) + '"]' + e.data.terms + '[/pb_glossary]');
                            } else {
                                // prepend the short-code with their selection to avoid over-writing it
                                ed.selection.setContent(mySelection + ' [pb_glossary' + ' id="' + termID(e.data.terms) + '"]' + e.data.terms + '[/pb_glossary]');
                            }
                        },
                    },);

                    // insert the short-code with the id as an attribute
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
