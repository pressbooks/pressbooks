/******/ (() => { // webpackBootstrap
var __webpack_exports__ = {};
/*!*****************************************!*\
  !*** ./assets/src/scripts/textboxes.js ***!
  \*****************************************/
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
  /**
   *
   */
  function showDialog() {
    var selectedNode = editor.selection.getNode();
    editor.windowManager.open({
      title: editor.getLang('strings.customtextbox'),
      body: {
        type: 'textbox',
        name: 'className',
        size: 40,
        label: editor.getLang('strings.classtitle'),
        value: selectedNode.name || selectedNode.id
      },

      /**
       * @param e
       */
      onsubmit: function onsubmit(e) {
        editor.execCommand('mceReplaceContent', false, '<div class="textbox ' + e.data.className + '">{$selection}</div>');
      }
    });
  }
  /**
   * @param type
   * @param title
   * @param selection
   */


  function eduTextboxWithSelection(type, title, selection) {
    return "<div class=\"textbox textbox--".concat(type, "\"><header class=\"textbox__header\"><p class=\"textbox__title\">").concat(title, "</p></header>\n<div class=\"textbox__content\">").concat(selection, "</div></div><p></p>");
  }
  /**
   * @param type
   * @param title
   * @param placeholder
   * @param first
   * @param second
   */


  function eduTextboxWithPlaceholder(type, title, placeholder, first, second) {
    return "<div class=\"textbox textbox--".concat(type, "\"><header class=\"textbox__header\"><p class=\"textbox__title\">").concat(title, "</p></header>\n<div class=\"textbox__content\"><p>").concat(placeholder, "</p><ul><li>").concat(first, "</li><li>").concat(second, "</li></ul></div></div><p></p>");
  }
  /**
   * @param type
   * @param title
   * @param selection
   */


  function eduSidebarTextboxWithSelection(type, title, selection) {
    return "<div class=\"textbox textbox--sidebar textbox--".concat(type, "\"><header class=\"textbox__header\"><p class=\"textbox__title\">").concat(title, "</p></header>\n<div class=\"textbox__content\">").concat(selection, "</div></div><p></p>");
  }
  /**
   * @param type
   * @param title
   * @param placeholder
   * @param first
   * @param second
   */


  function eduSidebarTextboxWithPlaceholder(type, title, placeholder, first, second) {
    return "<div class=\"textbox textbox--sidebar textbox--".concat(type, "\"><header class=\"textbox__header\"><p class=\"textbox__title\">").concat(title, "</p></header>\n<div class=\"textbox__content\"><p>").concat(placeholder, "</p><ul><li>").concat(first, "</li><li>").concat(second, "</li></ul></div></div><p></p>");
  }

  editor.addButton('textboxes', {
    type: 'menubutton',
    text: editor.getLang('strings.textboxes'),
    icon: false,
    menu: [{
      text: editor.getLang('strings.standard'),

      /**
       *
       */
      onclick: function onclick() {
        var selection = editor.selection.getContent();

        if (selection !== '') {
          editor.execCommand('mceReplaceContent', false, '<div class="textbox">' + selection + '</div><p></p>');
        } else {
          editor.execCommand('mceInsertContent', 0, '<div class="textbox">' + editor.getLang('strings.standardplaceholder') + '</div><p></p>');
        }
      }
    }, {
      text: editor.getLang('strings.standardsidebar'),

      /**
       *
       */
      onclick: function onclick() {
        var selection = editor.selection.getContent();

        if (selection !== '') {
          editor.execCommand('mceReplaceContent', false, '<div class="textbox textbox--sidebar">' + selection + '</div><p></p>');
        } else {
          editor.execCommand('mceInsertContent', 0, '<div class="textbox textbox--sidebar">' + editor.getLang('strings.standardplaceholder') + '</div><p></p>');
        }
      }
    }, {
      text: editor.getLang('strings.shaded'),

      /**
       *
       */
      onclick: function onclick() {
        var selection = editor.selection.getContent();

        if (selection !== '') {
          editor.execCommand('mceReplaceContent', false, '<div class="textbox shaded">' + selection + '</div><p></p>');
        } else {
          editor.execCommand('mceInsertContent', 0, '<div class="textbox shaded">' + editor.getLang('strings.standardplaceholder') + '</div><p></p>');
        }
      }
    }, {
      text: editor.getLang('strings.shadedsidebar'),

      /**
       *
       */
      onclick: function onclick() {
        var selection = editor.selection.getContent();

        if (selection !== '') {
          editor.execCommand('mceReplaceContent', false, '<div class="textbox textbox--sidebar shaded">' + selection + '</div><p></p>');
        } else {
          editor.execCommand('mceInsertContent', 0, '<div class="textbox textbox--sidebar shaded">' + editor.getLang('strings.standardplaceholder') + '</div><p></p>');
        }
      }
    }, {
      text: editor.getLang('strings.examples'),

      /**
       *
       */
      onclick: function onclick() {
        var type = 'examples';
        var selection = editor.selection.getContent();
        var title = editor.getLang("strings.".concat(type));
        var placeholder = editor.getLang("strings.".concat(type, "placeholder"));
        var first = editor.getLang('strings.first');
        var second = editor.getLang('strings.second');

        if (selection !== '') {
          editor.execCommand('mceReplaceContent', false, eduTextboxWithSelection(type, title, selection));
        } else {
          editor.execCommand('mceInsertContent', 0, eduTextboxWithPlaceholder(type, title, placeholder, first, second));
        }
      }
    }, {
      text: editor.getLang('strings.examplessidebar'),

      /**
       *
       */
      onclick: function onclick() {
        var type = 'examples';
        var selection = editor.selection.getContent();
        var title = editor.getLang("strings.".concat(type, "sidebar"));
        var placeholder = editor.getLang("strings.".concat(type, "placeholder"));
        var first = editor.getLang('strings.first');
        var second = editor.getLang('strings.second');

        if (selection !== '') {
          editor.execCommand('mceReplaceContent', false, eduSidebarTextboxWithSelection(type, title, selection));
        } else {
          editor.execCommand('mceInsertContent', 0, eduSidebarTextboxWithPlaceholder(type, title, placeholder, first, second));
        }
      }
    }, {
      text: editor.getLang('strings.exercises'),

      /**
       *
       */
      onclick: function onclick() {
        var type = 'exercises';
        var selection = editor.selection.getContent();
        var title = editor.getLang("strings.".concat(type));
        var placeholder = editor.getLang("strings.".concat(type, "placeholder"));
        var first = editor.getLang('strings.first');
        var second = editor.getLang('strings.second');

        if (selection !== '') {
          editor.execCommand('mceReplaceContent', false, eduTextboxWithSelection(type, title, selection));
        } else {
          editor.execCommand('mceInsertContent', 0, eduTextboxWithPlaceholder(type, title, placeholder, first, second));
        }
      }
    }, {
      text: editor.getLang('strings.exercisessidebar'),

      /**
       *
       */
      onclick: function onclick() {
        var type = 'exercises';
        var selection = editor.selection.getContent();
        var title = editor.getLang("strings.".concat(type, "sidebar"));
        var placeholder = editor.getLang("strings.".concat(type, "placeholder"));
        var first = editor.getLang('strings.first');
        var second = editor.getLang('strings.second');

        if (selection !== '') {
          editor.execCommand('mceReplaceContent', false, eduSidebarTextboxWithSelection(type, title, selection));
        } else {
          editor.execCommand('mceInsertContent', 0, eduSidebarTextboxWithPlaceholder(type, title, placeholder, first, second));
        }
      }
    }, {
      text: editor.getLang('strings.keytakeaways'),

      /**
       *
       */
      onclick: function onclick() {
        var type = 'key-takeaways';
        var selection = editor.selection.getContent();
        var title = editor.getLang('strings.keytakeaways');
        var placeholder = editor.getLang('strings.keytakeawaysplaceholder');
        var first = editor.getLang('strings.first');
        var second = editor.getLang('strings.second');

        if (selection !== '') {
          editor.execCommand('mceReplaceContent', false, eduTextboxWithSelection(type, title, selection));
        } else {
          editor.execCommand('mceInsertContent', 0, eduTextboxWithPlaceholder(type, title, placeholder, first, second));
        }
      }
    }, {
      text: editor.getLang('strings.keytakeawayssidebar'),

      /**
       *
       */
      onclick: function onclick() {
        var type = 'key-takeaways';
        var selection = editor.selection.getContent();
        var title = editor.getLang('strings.keytakeawayssidebar');
        var placeholder = editor.getLang('strings.keytakeawaysplaceholder');
        var first = editor.getLang('strings.first');
        var second = editor.getLang('strings.second');

        if (selection !== '') {
          editor.execCommand('mceReplaceContent', false, eduSidebarTextboxWithSelection(type, title, selection));
        } else {
          editor.execCommand('mceInsertContent', 0, eduSidebarTextboxWithPlaceholder(type, title, placeholder, first, second));
        }
      }
    }, {
      text: editor.getLang('strings.learningobjectives'),

      /**
       *
       */
      onclick: function onclick() {
        var type = 'learning-objectives';
        var selection = editor.selection.getContent();
        var title = editor.getLang('strings.learningobjectives');
        var placeholder = editor.getLang('strings.learningobjectivesplaceholder');
        var first = editor.getLang('strings.first');
        var second = editor.getLang('strings.second');

        if (selection !== '') {
          editor.execCommand('mceReplaceContent', false, eduTextboxWithSelection(type, title, selection));
        } else {
          editor.execCommand('mceInsertContent', 0, eduTextboxWithPlaceholder(type, title, placeholder, first, second));
        }
      }
    }, {
      text: editor.getLang('strings.learningobjectivessidebar'),

      /**
       *
       */
      onclick: function onclick() {
        var type = 'learning-objectives';
        var selection = editor.selection.getContent();
        var title = editor.getLang('strings.learningobjectivessidebar');
        var placeholder = editor.getLang('strings.learningobjectivesplaceholder');
        var first = editor.getLang('strings.first');
        var second = editor.getLang('strings.second');

        if (selection !== '') {
          editor.execCommand('mceReplaceContent', false, eduSidebarTextboxWithSelection(type, title, selection));
        } else {
          editor.execCommand('mceInsertContent', 0, eduSidebarTextboxWithPlaceholder(type, title, placeholder, first, second));
        }
      }
    }, {
      text: editor.getLang('strings.customellipses'),

      /**
       *
       */
      onclick: function onclick() {
        showDialog();
      }
    }]
  });
});
/******/ })()
;