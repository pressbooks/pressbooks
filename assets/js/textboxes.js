/**
 * textboxes.js
 *
 * Copyright, Moxiecode Systems AB
 * Released under LGPL License.
 *
 * License: http://www.tinymce.com/license
 * Contributing: http://www.tinymce.com/contributing
 */

tinymce.PluginManager.add('textboxes', function (editor) {
    'use strict';
    function showDialog() {
        var selectedNode = editor.selection.getNode();

        editor.windowManager.open({
            title: editor.getLang('strings.customtextbox'),
            body: {type: 'textbox', name: 'className', size: 40, label: editor.getLang('strings.classtitle'), value: selectedNode.name || selectedNode.id},
            onsubmit: function (e) {
                editor.execCommand('mceReplaceContent', false, '<div class="textbox ' + e.data.className + '">{$selection}</div>');
            }
        });
    }

    editor.addButton('textboxes', {
        type: 'menubutton',
        text: editor.getLang('strings.textboxes'),
        icon: false,
        menu: [
            { text: editor.getLang('strings.standard'), onclick: function () {
                editor.execCommand('mceReplaceContent', false, '<div class="textbox">{$selection}</div>');
            } },
            { text: editor.getLang('strings.shaded'), onclick: function () {
                editor.execCommand('mceReplaceContent', false, '<div class="textbox shaded">{$selection}</div>');
            } },
            { text: editor.getLang('strings.learningobjective'), onclick: function () {
                editor.execCommand('mceReplaceContent', false, '<div class="textbox learning-objective">{$selection}</div>');
            } },
            { text: editor.getLang('strings.keytakeaway'), onclick: function () {
                editor.execCommand('mceReplaceContent', false, '<div class="textbox key-takeaway">{$selection}</div>');
            } },
            { text: editor.getLang('strings.exercise'), onclick: function () {
                editor.execCommand('mceReplaceContent', false, '<div class="textbox exercise">{$selection}</div>');
            } },
            { text: editor.getLang('strings.example'), onclick: function () {
                editor.execCommand('mceReplaceContent', false, '<div class="textbox example">{$selection}</div>');
            } },
            { text: editor.getLang('strings.customellipses'), onclick: function () { showDialog(); } }
        ]
    });

});
