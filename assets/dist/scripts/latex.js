/******/ (() => { // webpackBootstrap
var __webpack_exports__ = {};
/*!*************************************!*\
  !*** ./assets/src/scripts/latex.js ***!
  \*************************************/
/* global PB_LaTeXToken */
(function () {
  tinymce.create('tinymce.plugins.latex', {
    /**
     * @param ed
     */
    init: function init(ed) {
      ed.addButton('latex', {
        title: PB_LaTeXToken.fn_title,
        icon: 'icon dashicons-calculator',

        /**
         *
         */
        onclick: function onclick() {
          var mySelection = ed.selection.getContent();
          var latex;

          if (mySelection !== '') {
            latex = mySelection;
            ed.selection.setContent('[latex]' + latex + '[/latex]');
          } else {
            latex = prompt('LaTeX Content', 'Enter your LaTeX content here.');

            if (latex !== '') {
              ed.execCommand('mceInsertContent', false, '[latex]' + latex + '[/latex]');
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
  tinymce.PluginManager.add('latex', tinymce.plugins.latex);
})();
/******/ })()
;