/******/ (() => { // webpackBootstrap
var __webpack_exports__ = {};
/*!************************************************!*\
  !*** ./assets/src/scripts/post-back-matter.js ***!
  \************************************************/
/* global PB_GlossaryToken */
jQuery(document).ready(function ($) {
  var notice = $('#pb-post-type-notice');
  var dropdown = $('#back-matter-typedropdown');
  dropdown.on('change', function () {
    if (parseInt(this.value, 10) === parseInt(PB_GlossaryToken.term_id, 10)) {
      notice.html('<p>' + PB_GlossaryToken.term_notice + '</p>');
      notice.show();
    } else {
      notice.html('');
      notice.hide();
    }
  });
});
/******/ })()
;