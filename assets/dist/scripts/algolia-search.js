/******/ (() => { // webpackBootstrap
var __webpack_exports__ = {};
/*!**********************************************!*\
  !*** ./assets/src/scripts/algolia-search.js ***!
  \**********************************************/
/* global algoliasearch */

/* global instantsearch */

/* global PBAlgolia */
var searchClient = algoliasearch(PBAlgolia.applicationId, PBAlgolia.apiKey);
var search = instantsearch({
  indexName: PBAlgolia.indexName,
  searchClient: searchClient,

  /**
   * @see https://www.algolia.com/doc/api-reference/widgets/instantsearch/js/#widget-param-searchfunction
   * @param helper
   */
  searchFunction: function searchFunction(helper) {
    // Ensure we only trigger a search when there's a query
    if (helper.state.query) {
      helper.search();
    }
  }
});
search.addWidgets([instantsearch.widgets.searchBox({
  container: '#searchbox'
}), instantsearch.widgets.hits({
  // cssClasses property -> custom css classes here folks: https://www.algolia.com/doc/api-reference/widgets/hits/js/#widget-param-cssclasses
  container: '#book-cards',
  templates: {
    item: "".concat(PBAlgolia.hitsTemplate)
  }
})]);
search.start();
/******/ })()
;