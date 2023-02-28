/******/ (() => { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

/***/ "./assets/src/scripts/anchor.js":
/*!**************************************!*\
  !*** ./assets/src/scripts/anchor.js ***!
  \**************************************/
/***/ (() => {

/**
 * plugin.js
 *
 * Copyright, Moxiecode Systems AB
 * Released under LGPL License.
 *
 * License: http://www.tinymce.com/license
 * Contributing: http://www.tinymce.com/contributing
 */
tinymce.PluginManager.add('anchor', function (editor) {
  /**
   *
   */
  function showDialog() {
    var selectedNode = editor.selection.getNode();
    editor.windowManager.open({
      title: 'Anchor',
      body: {
        type: 'textbox',
        name: 'name',
        size: 40,
        label: 'Name',
        value: selectedNode.name || selectedNode.id
      },

      /**
       * @param e
       */
      onsubmit: function onsubmit(e) {
        editor.execCommand('mceInsertContent', false, editor.dom.createHTML('a', {
          id: e.data.name
        }));
      }
    });
  }

  editor.addButton('anchor', {
    icon: 'anchor',
    tooltip: 'Anchor',
    onclick: showDialog,
    stateSelector: 'a:not([href])'
  });
  editor.addMenuItem('anchor', {
    icon: 'anchor',
    text: 'Anchor',
    context: 'insert',
    onclick: showDialog
  });
});

/***/ }),

/***/ "./assets/src/styles/glossary-tooltip.scss":
/*!*************************************************!*\
  !*** ./assets/src/styles/glossary-tooltip.scss ***!
  \*************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "./assets/src/styles/login.scss":
/*!**************************************!*\
  !*** ./assets/src/styles/login.scss ***!
  \**************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "./assets/src/styles/metadata.scss":
/*!*****************************************!*\
  !*** ./assets/src/styles/metadata.scss ***!
  \*****************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "./assets/src/styles/network-managers.scss":
/*!*************************************************!*\
  !*** ./assets/src/styles/network-managers.scss ***!
  \*************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "./assets/src/styles/organize.scss":
/*!*****************************************!*\
  !*** ./assets/src/styles/organize.scss ***!
  \*****************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "./assets/src/styles/pressbooks.scss":
/*!*******************************************!*\
  !*** ./assets/src/styles/pressbooks.scss ***!
  \*******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "./assets/src/styles/pressbooks-dashboard.scss":
/*!*****************************************************!*\
  !*** ./assets/src/styles/pressbooks-dashboard.scss ***!
  \*****************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "./assets/src/styles/search-and-replace.scss":
/*!***************************************************!*\
  !*** ./assets/src/styles/search-and-replace.scss ***!
  \***************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "./assets/src/styles/select2.scss":
/*!****************************************!*\
  !*** ./assets/src/styles/select2.scss ***!
  \****************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "./assets/src/styles/style-catalog.scss":
/*!**********************************************!*\
  !*** ./assets/src/styles/style-catalog.scss ***!
  \**********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "./assets/src/styles/theme-options.scss":
/*!**********************************************!*\
  !*** ./assets/src/styles/theme-options.scss ***!
  \**********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "./assets/src/styles/cloner.scss":
/*!***************************************!*\
  !*** ./assets/src/styles/cloner.scss ***!
  \***************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "./assets/src/styles/catalog.scss":
/*!****************************************!*\
  !*** ./assets/src/styles/catalog.scss ***!
  \****************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "./assets/src/styles/colors-pb.scss":
/*!******************************************!*\
  !*** ./assets/src/styles/colors-pb.scss ***!
  \******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "./assets/src/styles/colors-pb-a11y.scss":
/*!***********************************************!*\
  !*** ./assets/src/styles/colors-pb-a11y.scss ***!
  \***********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "./assets/src/styles/covergenerator.scss":
/*!***********************************************!*\
  !*** ./assets/src/styles/covergenerator.scss ***!
  \***********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "./assets/src/styles/export.scss":
/*!***************************************!*\
  !*** ./assets/src/styles/export.scss ***!
  \***************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = __webpack_modules__;
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/chunk loaded */
/******/ 	(() => {
/******/ 		var deferred = [];
/******/ 		__webpack_require__.O = (result, chunkIds, fn, priority) => {
/******/ 			if(chunkIds) {
/******/ 				priority = priority || 0;
/******/ 				for(var i = deferred.length; i > 0 && deferred[i - 1][2] > priority; i--) deferred[i] = deferred[i - 1];
/******/ 				deferred[i] = [chunkIds, fn, priority];
/******/ 				return;
/******/ 			}
/******/ 			var notFulfilled = Infinity;
/******/ 			for (var i = 0; i < deferred.length; i++) {
/******/ 				var [chunkIds, fn, priority] = deferred[i];
/******/ 				var fulfilled = true;
/******/ 				for (var j = 0; j < chunkIds.length; j++) {
/******/ 					if ((priority & 1 === 0 || notFulfilled >= priority) && Object.keys(__webpack_require__.O).every((key) => (__webpack_require__.O[key](chunkIds[j])))) {
/******/ 						chunkIds.splice(j--, 1);
/******/ 					} else {
/******/ 						fulfilled = false;
/******/ 						if(priority < notFulfilled) notFulfilled = priority;
/******/ 					}
/******/ 				}
/******/ 				if(fulfilled) {
/******/ 					deferred.splice(i--, 1)
/******/ 					var r = fn();
/******/ 					if (r !== undefined) result = r;
/******/ 				}
/******/ 			}
/******/ 			return result;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/jsonp chunk loading */
/******/ 	(() => {
/******/ 		// no baseURI
/******/ 		
/******/ 		// object to store loaded and loading chunks
/******/ 		// undefined = chunk not loaded, null = chunk preloaded/prefetched
/******/ 		// [resolve, reject, Promise] = chunk loading, 0 = chunk loaded
/******/ 		var installedChunks = {
/******/ 			"/scripts/anchor": 0,
/******/ 			"styles/pressbooks": 0,
/******/ 			"styles/export": 0,
/******/ 			"styles/covergenerator": 0,
/******/ 			"styles/colors-pb-a11y": 0,
/******/ 			"styles/colors-pb": 0,
/******/ 			"styles/catalog": 0,
/******/ 			"styles/cloner": 0,
/******/ 			"styles/theme-options": 0,
/******/ 			"styles/style-catalog": 0,
/******/ 			"styles/select2": 0,
/******/ 			"styles/search-and-replace": 0,
/******/ 			"styles/pressbooks-dashboard": 0,
/******/ 			"styles/organize": 0,
/******/ 			"styles/network-managers": 0,
/******/ 			"styles/metadata": 0,
/******/ 			"styles/login": 0,
/******/ 			"styles/glossary-tooltip": 0
/******/ 		};
/******/ 		
/******/ 		// no chunk on demand loading
/******/ 		
/******/ 		// no prefetching
/******/ 		
/******/ 		// no preloaded
/******/ 		
/******/ 		// no HMR
/******/ 		
/******/ 		// no HMR manifest
/******/ 		
/******/ 		__webpack_require__.O.j = (chunkId) => (installedChunks[chunkId] === 0);
/******/ 		
/******/ 		// install a JSONP callback for chunk loading
/******/ 		var webpackJsonpCallback = (parentChunkLoadingFunction, data) => {
/******/ 			var [chunkIds, moreModules, runtime] = data;
/******/ 			// add "moreModules" to the modules object,
/******/ 			// then flag all "chunkIds" as loaded and fire callback
/******/ 			var moduleId, chunkId, i = 0;
/******/ 			if(chunkIds.some((id) => (installedChunks[id] !== 0))) {
/******/ 				for(moduleId in moreModules) {
/******/ 					if(__webpack_require__.o(moreModules, moduleId)) {
/******/ 						__webpack_require__.m[moduleId] = moreModules[moduleId];
/******/ 					}
/******/ 				}
/******/ 				if(runtime) var result = runtime(__webpack_require__);
/******/ 			}
/******/ 			if(parentChunkLoadingFunction) parentChunkLoadingFunction(data);
/******/ 			for(;i < chunkIds.length; i++) {
/******/ 				chunkId = chunkIds[i];
/******/ 				if(__webpack_require__.o(installedChunks, chunkId) && installedChunks[chunkId]) {
/******/ 					installedChunks[chunkId][0]();
/******/ 				}
/******/ 				installedChunks[chunkId] = 0;
/******/ 			}
/******/ 			return __webpack_require__.O(result);
/******/ 		}
/******/ 		
/******/ 		var chunkLoadingGlobal = self["webpackChunk_pressbooks_pressbooks"] = self["webpackChunk_pressbooks_pressbooks"] || [];
/******/ 		chunkLoadingGlobal.forEach(webpackJsonpCallback.bind(null, 0));
/******/ 		chunkLoadingGlobal.push = webpackJsonpCallback.bind(null, chunkLoadingGlobal.push.bind(chunkLoadingGlobal));
/******/ 	})();
/******/ 	
/************************************************************************/
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module depends on other loaded chunks and execution need to be delayed
/******/ 	__webpack_require__.O(undefined, ["styles/pressbooks","styles/export","styles/covergenerator","styles/colors-pb-a11y","styles/colors-pb","styles/catalog","styles/cloner","styles/theme-options","styles/style-catalog","styles/select2","styles/search-and-replace","styles/pressbooks-dashboard","styles/organize","styles/network-managers","styles/metadata","styles/login","styles/glossary-tooltip"], () => (__webpack_require__("./assets/src/scripts/anchor.js")))
/******/ 	__webpack_require__.O(undefined, ["styles/pressbooks","styles/export","styles/covergenerator","styles/colors-pb-a11y","styles/colors-pb","styles/catalog","styles/cloner","styles/theme-options","styles/style-catalog","styles/select2","styles/search-and-replace","styles/pressbooks-dashboard","styles/organize","styles/network-managers","styles/metadata","styles/login","styles/glossary-tooltip"], () => (__webpack_require__("./assets/src/styles/catalog.scss")))
/******/ 	__webpack_require__.O(undefined, ["styles/pressbooks","styles/export","styles/covergenerator","styles/colors-pb-a11y","styles/colors-pb","styles/catalog","styles/cloner","styles/theme-options","styles/style-catalog","styles/select2","styles/search-and-replace","styles/pressbooks-dashboard","styles/organize","styles/network-managers","styles/metadata","styles/login","styles/glossary-tooltip"], () => (__webpack_require__("./assets/src/styles/colors-pb.scss")))
/******/ 	__webpack_require__.O(undefined, ["styles/pressbooks","styles/export","styles/covergenerator","styles/colors-pb-a11y","styles/colors-pb","styles/catalog","styles/cloner","styles/theme-options","styles/style-catalog","styles/select2","styles/search-and-replace","styles/pressbooks-dashboard","styles/organize","styles/network-managers","styles/metadata","styles/login","styles/glossary-tooltip"], () => (__webpack_require__("./assets/src/styles/colors-pb-a11y.scss")))
/******/ 	__webpack_require__.O(undefined, ["styles/pressbooks","styles/export","styles/covergenerator","styles/colors-pb-a11y","styles/colors-pb","styles/catalog","styles/cloner","styles/theme-options","styles/style-catalog","styles/select2","styles/search-and-replace","styles/pressbooks-dashboard","styles/organize","styles/network-managers","styles/metadata","styles/login","styles/glossary-tooltip"], () => (__webpack_require__("./assets/src/styles/covergenerator.scss")))
/******/ 	__webpack_require__.O(undefined, ["styles/pressbooks","styles/export","styles/covergenerator","styles/colors-pb-a11y","styles/colors-pb","styles/catalog","styles/cloner","styles/theme-options","styles/style-catalog","styles/select2","styles/search-and-replace","styles/pressbooks-dashboard","styles/organize","styles/network-managers","styles/metadata","styles/login","styles/glossary-tooltip"], () => (__webpack_require__("./assets/src/styles/export.scss")))
/******/ 	__webpack_require__.O(undefined, ["styles/pressbooks","styles/export","styles/covergenerator","styles/colors-pb-a11y","styles/colors-pb","styles/catalog","styles/cloner","styles/theme-options","styles/style-catalog","styles/select2","styles/search-and-replace","styles/pressbooks-dashboard","styles/organize","styles/network-managers","styles/metadata","styles/login","styles/glossary-tooltip"], () => (__webpack_require__("./assets/src/styles/glossary-tooltip.scss")))
/******/ 	__webpack_require__.O(undefined, ["styles/pressbooks","styles/export","styles/covergenerator","styles/colors-pb-a11y","styles/colors-pb","styles/catalog","styles/cloner","styles/theme-options","styles/style-catalog","styles/select2","styles/search-and-replace","styles/pressbooks-dashboard","styles/organize","styles/network-managers","styles/metadata","styles/login","styles/glossary-tooltip"], () => (__webpack_require__("./assets/src/styles/login.scss")))
/******/ 	__webpack_require__.O(undefined, ["styles/pressbooks","styles/export","styles/covergenerator","styles/colors-pb-a11y","styles/colors-pb","styles/catalog","styles/cloner","styles/theme-options","styles/style-catalog","styles/select2","styles/search-and-replace","styles/pressbooks-dashboard","styles/organize","styles/network-managers","styles/metadata","styles/login","styles/glossary-tooltip"], () => (__webpack_require__("./assets/src/styles/metadata.scss")))
/******/ 	__webpack_require__.O(undefined, ["styles/pressbooks","styles/export","styles/covergenerator","styles/colors-pb-a11y","styles/colors-pb","styles/catalog","styles/cloner","styles/theme-options","styles/style-catalog","styles/select2","styles/search-and-replace","styles/pressbooks-dashboard","styles/organize","styles/network-managers","styles/metadata","styles/login","styles/glossary-tooltip"], () => (__webpack_require__("./assets/src/styles/network-managers.scss")))
/******/ 	__webpack_require__.O(undefined, ["styles/pressbooks","styles/export","styles/covergenerator","styles/colors-pb-a11y","styles/colors-pb","styles/catalog","styles/cloner","styles/theme-options","styles/style-catalog","styles/select2","styles/search-and-replace","styles/pressbooks-dashboard","styles/organize","styles/network-managers","styles/metadata","styles/login","styles/glossary-tooltip"], () => (__webpack_require__("./assets/src/styles/organize.scss")))
/******/ 	__webpack_require__.O(undefined, ["styles/pressbooks","styles/export","styles/covergenerator","styles/colors-pb-a11y","styles/colors-pb","styles/catalog","styles/cloner","styles/theme-options","styles/style-catalog","styles/select2","styles/search-and-replace","styles/pressbooks-dashboard","styles/organize","styles/network-managers","styles/metadata","styles/login","styles/glossary-tooltip"], () => (__webpack_require__("./assets/src/styles/pressbooks.scss")))
/******/ 	__webpack_require__.O(undefined, ["styles/pressbooks","styles/export","styles/covergenerator","styles/colors-pb-a11y","styles/colors-pb","styles/catalog","styles/cloner","styles/theme-options","styles/style-catalog","styles/select2","styles/search-and-replace","styles/pressbooks-dashboard","styles/organize","styles/network-managers","styles/metadata","styles/login","styles/glossary-tooltip"], () => (__webpack_require__("./assets/src/styles/pressbooks-dashboard.scss")))
/******/ 	__webpack_require__.O(undefined, ["styles/pressbooks","styles/export","styles/covergenerator","styles/colors-pb-a11y","styles/colors-pb","styles/catalog","styles/cloner","styles/theme-options","styles/style-catalog","styles/select2","styles/search-and-replace","styles/pressbooks-dashboard","styles/organize","styles/network-managers","styles/metadata","styles/login","styles/glossary-tooltip"], () => (__webpack_require__("./assets/src/styles/search-and-replace.scss")))
/******/ 	__webpack_require__.O(undefined, ["styles/pressbooks","styles/export","styles/covergenerator","styles/colors-pb-a11y","styles/colors-pb","styles/catalog","styles/cloner","styles/theme-options","styles/style-catalog","styles/select2","styles/search-and-replace","styles/pressbooks-dashboard","styles/organize","styles/network-managers","styles/metadata","styles/login","styles/glossary-tooltip"], () => (__webpack_require__("./assets/src/styles/select2.scss")))
/******/ 	__webpack_require__.O(undefined, ["styles/pressbooks","styles/export","styles/covergenerator","styles/colors-pb-a11y","styles/colors-pb","styles/catalog","styles/cloner","styles/theme-options","styles/style-catalog","styles/select2","styles/search-and-replace","styles/pressbooks-dashboard","styles/organize","styles/network-managers","styles/metadata","styles/login","styles/glossary-tooltip"], () => (__webpack_require__("./assets/src/styles/style-catalog.scss")))
/******/ 	__webpack_require__.O(undefined, ["styles/pressbooks","styles/export","styles/covergenerator","styles/colors-pb-a11y","styles/colors-pb","styles/catalog","styles/cloner","styles/theme-options","styles/style-catalog","styles/select2","styles/search-and-replace","styles/pressbooks-dashboard","styles/organize","styles/network-managers","styles/metadata","styles/login","styles/glossary-tooltip"], () => (__webpack_require__("./assets/src/styles/theme-options.scss")))
/******/ 	var __webpack_exports__ = __webpack_require__.O(undefined, ["styles/pressbooks","styles/export","styles/covergenerator","styles/colors-pb-a11y","styles/colors-pb","styles/catalog","styles/cloner","styles/theme-options","styles/style-catalog","styles/select2","styles/search-and-replace","styles/pressbooks-dashboard","styles/organize","styles/network-managers","styles/metadata","styles/login","styles/glossary-tooltip"], () => (__webpack_require__("./assets/src/styles/cloner.scss")))
/******/ 	__webpack_exports__ = __webpack_require__.O(__webpack_exports__);
/******/ 	
/******/ })()
;