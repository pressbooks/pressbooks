/******/ (() => { // webpackBootstrap
var __webpack_exports__ = {};
/*!****************************************!*\
  !*** ./assets/src/scripts/footnote.js ***!
  \****************************************/
/* global PB_FootnotesToken */
(function () {
  tinymce.create('tinymce.plugins.footnote', {
    /**
     * @param ed
     * @param url
     */
    init: function init(ed, url) {
      ed.addButton('footnote', {
        title: PB_FootnotesToken.fn_title,
        icon: 'icon dashicons-paperclip',

        /**
         *
         */
        onclick: function onclick() {
          var mySelection = ed.selection.getContent();
          var footNote;

          if (mySelection !== '') {
            footNote = mySelection;
            ed.selection.setContent('[footnote]' + footNote + '[/footnote]');
          } else {
            footNote = prompt('Footnote Content', 'Enter your footnote content here.');

            if (footNote !== '') {
              ed.execCommand('mceInsertContent', false, '[footnote]' + footNote + '[/footnote]');
            }
          }
        }
      });
    },

    /**
     * @param n
     * @param cm
     */
    createControl: function createControl(n, cm) {
      return null;
    }
  });
  tinymce.PluginManager.add('footnote', tinymce.plugins.footnote);
})();
/******/ })()
;