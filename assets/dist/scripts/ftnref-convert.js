/******/ (() => { // webpackBootstrap
var __webpack_exports__ = {};
/*!**********************************************!*\
  !*** ./assets/src/scripts/ftnref-convert.js ***!
  \**********************************************/
/* global PB_FootnotesToken */
(function () {
  tinymce.create('tinymce.plugins.ftnref_convert', {
    /**
     * @param ed
     * @param url
     */
    init: function init(ed, url) {
      ed.addButton('ftnref_convert', {
        title: PB_FootnotesToken.ftnref_title,
        icon: 'icon dashicons-screenoptions',

        /**
         *
         */
        onclick: function onclick() {
          jQuery.ajax({
            type: 'post',
            dataType: 'json',
            url: ajaxurl,
            data: {
              action: 'pb_ftnref_convert',
              content: ed.getContent(),
              _ajax_nonce: PB_FootnotesToken.nonce
            },

            /**
             *
             */
            beforeSend: function beforeSend() {
              ed.setProgressState(1); // Show progress
            },

            /**
             * @param data
             * @param textStatus
             * @param transport
             */
            success: function success(data, textStatus, transport) {
              ed.setProgressState(0); // Hide progress

              ed.setContent(data.content, {
                format: 'raw'
              });
            },

            /**
             * @param transport
             */
            error: function error(transport) {
              ed.setProgressState(0); // Hide progress

              if (transport.responseText.trim().length) {
                alert(transport.responseText);
              }
            }
          });
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
  tinymce.PluginManager.add('ftnref_convert', tinymce.plugins.ftnref_convert);
})();
/******/ })()
;