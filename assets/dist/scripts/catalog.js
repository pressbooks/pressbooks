/******/ (() => { // webpackBootstrap
var __webpack_exports__ = {};
/*!***************************************!*\
  !*** ./assets/src/scripts/catalog.js ***!
  \***************************************/
jQuery(function ($) {
  var $container = $('#catalog-content');
  $container.isotope({
    itemSelector: '.mix',
    layoutMode: 'fitRows'
  });
  $('.mix').matchHeight({});
  /**
   * @param event
   * @param groups
   */

  $.fn.matchHeight._afterUpdate = function (event, groups) {
    $container.isotope('layout');
  };

  $('.filter-group-1').on('click', function () {
    var filter1_id = $(this).attr('data-filter');
    var filter1_name = $(this).text();
    var filter2_id, filter2_name, currentFilterIDs, currentFilters;

    if ($('.filter-group-2.active').length !== 0) {
      filter2_id = $('.filter-group-2.active').attr('data-filter');
      filter2_name = $('.filter-group-2.active').text();
    } else {
      filter2_id = '';
      filter2_name = '';
    }

    if (filter2_id !== '') {
      currentFilterIDs = filter1_id + filter2_id;
    } else {
      currentFilterIDs = filter1_id;
    }

    $container.isotope({
      filter: currentFilterIDs
    });
    $('.filter-group-1.active').removeClass('active');
    $(this).addClass('active');

    if (filter2_name !== '') {
      currentFilters = filter1_name + ', ' + filter2_name;
    } else {
      currentFilters = filter1_name;
    }

    $('.catalog-content-wrap h1 span.current-filters').text(currentFilters).show();
    $('.catalog-content-wrap h1 span.filtered-by').show();
    $('.catalog-content-wrap h1 span.clear-filters').show();
  });
  $('.filter-group-2').on('click', function () {
    var filter2_id = $(this).attr('data-filter');
    var filter2_name = $(this).text();
    var filter1_id, filter1_name, currentFilterIDs, currentFilters;

    if ($('.filter-group-1.active').length !== 0) {
      filter1_id = $('.filter-group-1.active').attr('data-filter');
      filter1_name = $('.filter-group-1.active').text();
    } else {
      filter1_id = '';
      filter1_name = '';
    }

    if (filter1_id !== '') {
      currentFilterIDs = filter1_id + filter2_id;
    } else {
      currentFilterIDs = filter2_id;
    }

    $container.isotope({
      filter: currentFilterIDs
    });
    $('.filter-group-2.active').removeClass('active');
    $(this).addClass('active');

    if (filter1_name !== '') {
      currentFilters = filter1_name + ', ' + filter2_name;
    } else {
      currentFilters = filter2_name;
    }

    $('.catalog-content-wrap h1 span.current-filters').text(currentFilters).show();
    $('.catalog-content-wrap h1 span.filtered-by').show();
    $('.catalog-content-wrap h1 span.clear-filters').show();
  });
  $('a.clear-filters').on('click', function (e) {
    $('.filter-group-1.active').removeClass('active');
    $('.filter-group-2.active').removeClass('active');
    $container.isotope({
      filter: '*'
    });
    $('.catalog-content-wrap h1 span.filtered-by').hide();
    $('.catalog-content-wrap h1 span.clear-filters').hide();
    $('.catalog-content-wrap h1 span.current-filters').text('');
    e.preventDefault();
  });
});
/******/ })()
;