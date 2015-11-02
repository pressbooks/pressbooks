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
            title: 'Custom Textbox',
            body: {type: 'textbox', name: 'className', size: 40, label: 'Class', value: selectedNode.name || selectedNode.id},
            onsubmit: function (e) {
                editor.execCommand('mceReplaceContent', false, '<div class="' + e.data.className + '">{$selection}</div>');
            }
        });
    }

    editor.addButton('textboxes', {
        type: 'menubutton',
        text: 'Textboxes',
        icon: false,
        menu: [
            { text: 'Standard', onclick: function () {
                editor.execCommand('mceReplaceContent', false, '<div class="textbox">{$selection}</div>');
            } },
            { text: 'Shaded', onclick: function () {
                editor.execCommand('mceReplaceContent', false, '<div class="textbox shaded">{$selection}</div>');
            } },
            { text: 'Learning Objective', onclick: function () {
                editor.execCommand('mceReplaceContent', false, '<div class="textbox learning-objective">{$selection}</div>');
            } },
            { text: 'Key Takeaway', onclick: function () {
                editor.execCommand('mceReplaceContent', false, '<div class="textbox key-takeaway">{$selection}</div>');
            } },
            { text: 'Excercise', onclick: function () {
                editor.execCommand('mceReplaceContent', false, '<div class="textbox exercise">{$selection}</div>');
            } },
            { text: 'Example', onclick: function () {
                editor.execCommand('mceReplaceContent', false, '<div class="textbox example">{$selection}</div>');
            } },
            { text: 'Custom', onclick: function () { showDialog(); } }
        ]
    });

});
