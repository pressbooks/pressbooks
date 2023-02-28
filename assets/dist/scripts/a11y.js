/******/ (() => { // webpackBootstrap
var __webpack_exports__ = {};
/*!************************************!*\
  !*** ./assets/src/scripts/a11y.js ***!
  \************************************/
var __ = wp.i18n.__;
document.addEventListener('DOMContentLoaded', function () {
  /**
   * @param selector
   * @param att
   * @param val
   */
  function addAttribute(selector, att, val) {
    var e = document.querySelectorAll(selector);

    for (var i = 0; i < e.length; i++) {
      e[i].setAttribute(att, val);
    }
  } // WP_List_Table table headers are missing `aria-sort` attributes for accessibility
  // https://core.trac.wordpress.org/ticket/47047#ticket


  addAttribute('table.wp-list-table th.sortable', 'aria-sort', 'none');
  addAttribute('table.wp-list-table th.sorted.asc', 'aria-sort', 'ascending');
  addAttribute('table.wp-list-table th.sorted.desc', 'aria-sort', 'descending'); // Add attributes to make status and alert bars accessible
  // https://core.trac.wordpress.org/ticket/46995

  addAttribute('div.updated', 'role', 'status');
  addAttribute('div.notice', 'role', 'status');
  addAttribute('div.error', 'role', 'alert'); // Add aria-label attribute to color picker slider
  // https://github.com/Automattic/Iris/issues/69

  addAttribute('div.iris-slider.iris-strip', 'aria-label', __('Gradient selector', 'pressbooks'));
  addAttribute('.ui-slider-handle', 'aria-label', __('Gradient selector', 'pressbooks')); // Add aria-label attributes to color picker palette boxes
  // https://github.com/Automattic/Iris/issues/69

  (function () {
    var colors = document.querySelectorAll('a.iris-palette');

    for (var i = 0; i < colors.length; i++) {
      var irisPalette = colors[i];
      var rgb = colors[i].style.backgroundColor;
      var color = '';

      if (rgb === 'rgb(0, 0, 0)') {
        color = __('Black', 'pressbooks');
      }

      if (rgb === 'rgb(255, 255, 255)') {
        color = __('White', 'pressbooks');
      }

      if (rgb === 'rgb(221, 51, 51)') {
        color = __('Red', 'pressbooks');
      }

      if (rgb === 'rgb(221, 153, 51)') {
        color = __('Orange', 'pressbooks');
      }

      if (rgb === 'rgb(238, 238, 34)') {
        color = __('Yellow', 'pressbooks');
      }

      if (rgb === 'rgb(129, 215, 66)') {
        color = __('Green', 'pressbooks');
      }

      if (rgb === 'rgb(30, 115, 190)') {
        color = __('Blue', 'pressbooks');
      }

      if (rgb === 'rgb(130, 36, 227)') {
        color = __('Purple', 'pressbooks');
      }

      irisPalette.setAttribute('aria-label', __('Select ' + color, 'pressbooks'));
    }
  })();
});
/******/ })()
;