/******/ (() => { // webpackBootstrap
var __webpack_exports__ = {};
/*!**********************************************!*\
  !*** ./assets/src/scripts/algolia-search.js ***!
  \**********************************************/
var _templateObject;

function _taggedTemplateLiteral(strings, raw) { if (!raw) { raw = strings.slice(0); } return Object.freeze(Object.defineProperties(strings, { raw: { value: Object.freeze(raw) } })); }

/* global algoliasearch */

/* global instantsearch */

/* global PBAlgolia */
var searchClient = algoliasearch(PBAlgolia.applicationId, PBAlgolia.apiKey);
var searchWrapper = document.getElementById('book-cards');
var statsHelper = document.getElementById('stats');
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
      helper.setQueryParameter('facets', ['licenseCode']).addFacetExclusion('licenseCode', 'All Rights Reserved').addFacetExclusion('licenseCode', 'CC BY-BC-ND').addFacetExclusion('licenseCode', 'CC BY-ND').search();
    }

    window.algoliaHelper = helper;
  }
});
/**
 *
 * @param url
 */

window.selectBookToClone = function (url) {
  var cloneBook = document.getElementById('source-book-url');
  var newBook = document.getElementById('target-book-url');
  cloneBook.value = url;
  var path = url.split('/');
  newBook.value = path.length > 2 ? path[3] : ''; // scroll to top

  window.scrollTo(0, 0);
  searchWrapper.innerHTML = '';
  statsHelper.innerHTML = '';
  document.querySelector('#searchbox input').value = '';
};

document.querySelector('#searchbox').addEventListener('input', function (event) {
  if (event.target.value.length === 0) {
    event.target.value = '';
    searchWrapper.innerHTML = '';
    statsHelper.innerHTML = '';
  }
});
search.addWidgets([instantsearch.widgets.searchBox({
  container: '#searchbox',
  placeholder: 'Search openly licensed books',
  showSubmit: false
}), instantsearch.widgets.hits({
  // cssClasses property -> custom css classes here folks: https://www.algolia.com/doc/api-reference/widgets/hits/js/#widget-param-cssclasses
  escapeHTML: true,
  container: '#book-cards',
  templates: {
    item: "".concat(PBAlgolia.hitsTemplate)
  }
}), instantsearch.widgets.stats({
  container: '#stats',
  templates: {
    /**
     *
     * @param data
     * @param root0
     * @param root0.html
     */
    text: function text(data, _ref) {
      var html = _ref.html;
      var resultsShown = data.nbHits <= 20 ? data.nbHits : 20;
      return html(_templateObject || (_templateObject = _taggedTemplateLiteral(["", ""])), PBAlgolia.resultsTemplate.replace('%resultsShown', resultsShown).replace('%totalResults', data.nbHits));
    }
  }
})]);
search.start();
/******/ })()
;