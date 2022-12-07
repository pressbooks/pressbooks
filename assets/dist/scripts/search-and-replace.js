/******/ (() => { // webpackBootstrap
var __webpack_exports__ = {};
/*!**************************************************!*\
  !*** ./assets/src/scripts/search-and-replace.js ***!
  \**************************************************/
jQuery(function ($) {
  var form = $('#search-form');
  $('.replace-and-save').on('click', function (e) {
    /* eslint-disable no-restricted-globals */
    if (confirm(pb_sr.warning_text)) {
      /* eslint-enable no-restricted-globals */
      var input = document.createElement('input');
      input.setAttribute('type', 'hidden');
      input.setAttribute('name', 'replace_and_save');
      document.getElementById('search-form').appendChild(input);
      form.submit();
    }
  });
});
/******/ })()
;