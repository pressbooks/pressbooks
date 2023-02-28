/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./assets/src/scripts/utils/displayNotice.js":
/*!***************************************************!*\
  !*** ./assets/src/scripts/utils/displayNotice.js ***!
  \***************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (function (type, message, dismissable) {
  var notice = document.createElement('div');
  var p = document.createElement('p');
  var button;
  var h1 = document.getElementsByTagName('h1')[0];
  p.setAttribute('aria-live', 'assertive');
  p.insertAdjacentHTML('beforeend', message);
  notice.classList.add('notice', "notice-".concat(type));
  notice.appendChild(p);

  if (dismissable) {
    button = document.createElement('button');
    var span = document.createElement('span');
    button.classList.add('notice-dismiss');
    span.classList.add('screen-reader-text');
    span.appendChild(document.createTextNode('Dismiss this notice.'));
    button.appendChild(span);
    notice.classList.add('is-dismissible');
    notice.appendChild(button);
  }

  h1.parentNode.insertBefore(notice, h1.nextSibling);

  if (button) {
    button.onclick = function () {
      notice.parentNode.removeChild(notice);
    };
  }
});

/***/ }),

/***/ "./assets/src/scripts/utils/pad.js":
/*!*****************************************!*\
  !*** ./assets/src/scripts/utils/pad.js ***!
  \*****************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/**
 * Pad integer to two digits with leading zero.
 * @param {int} integer Integer.
 * @return {string} String representation of integer with leading zero.
 */
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (function (integer) {
  return integer > 9 ? integer : "0".concat(integer);
});

/***/ }),

/***/ "./assets/src/scripts/utils/resetClock.js":
/*!************************************************!*\
  !*** ./assets/src/scripts/utils/resetClock.js ***!
  \************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (function (clock) {
  var seconds = document.getElementById('pb-sse-seconds');
  var minutes = document.getElementById('pb-sse-minutes');
  minutes.textContent = '';
  seconds.textContent = '';
  clearInterval(clock);
});

/***/ }),

/***/ "./assets/src/scripts/utils/startClock.js":
/*!************************************************!*\
  !*** ./assets/src/scripts/utils/startClock.js ***!
  \************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _pad__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./pad */ "./assets/src/scripts/utils/pad.js");

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (function () {
  // Init clock
  var seconds = document.getElementById('pb-sse-seconds');
  var minutes = document.getElementById('pb-sse-minutes'); // Start clock

  var sec = 0;
  minutes.textContent = '00:';
  seconds.textContent = '00';
  return setInterval(function () {
    seconds.textContent = (0,_pad__WEBPACK_IMPORTED_MODULE_0__["default"])(++sec % 60);
    minutes.textContent = (0,_pad__WEBPACK_IMPORTED_MODULE_0__["default"])(parseInt(sec / 60, 10)) + ':';
  }, 1000);
});

/***/ }),

/***/ "./node_modules/js-cookie/dist/js.cookie.mjs":
/*!***************************************************!*\
  !*** ./node_modules/js-cookie/dist/js.cookie.mjs ***!
  \***************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/*! js-cookie v3.0.1 | MIT */
/* eslint-disable no-var */
function assign (target) {
  for (var i = 1; i < arguments.length; i++) {
    var source = arguments[i];
    for (var key in source) {
      target[key] = source[key];
    }
  }
  return target
}
/* eslint-enable no-var */

/* eslint-disable no-var */
var defaultConverter = {
  read: function (value) {
    if (value[0] === '"') {
      value = value.slice(1, -1);
    }
    return value.replace(/(%[\dA-F]{2})+/gi, decodeURIComponent)
  },
  write: function (value) {
    return encodeURIComponent(value).replace(
      /%(2[346BF]|3[AC-F]|40|5[BDE]|60|7[BCD])/g,
      decodeURIComponent
    )
  }
};
/* eslint-enable no-var */

/* eslint-disable no-var */

function init (converter, defaultAttributes) {
  function set (key, value, attributes) {
    if (typeof document === 'undefined') {
      return
    }

    attributes = assign({}, defaultAttributes, attributes);

    if (typeof attributes.expires === 'number') {
      attributes.expires = new Date(Date.now() + attributes.expires * 864e5);
    }
    if (attributes.expires) {
      attributes.expires = attributes.expires.toUTCString();
    }

    key = encodeURIComponent(key)
      .replace(/%(2[346B]|5E|60|7C)/g, decodeURIComponent)
      .replace(/[()]/g, escape);

    var stringifiedAttributes = '';
    for (var attributeName in attributes) {
      if (!attributes[attributeName]) {
        continue
      }

      stringifiedAttributes += '; ' + attributeName;

      if (attributes[attributeName] === true) {
        continue
      }

      // Considers RFC 6265 section 5.2:
      // ...
      // 3.  If the remaining unparsed-attributes contains a %x3B (";")
      //     character:
      // Consume the characters of the unparsed-attributes up to,
      // not including, the first %x3B (";") character.
      // ...
      stringifiedAttributes += '=' + attributes[attributeName].split(';')[0];
    }

    return (document.cookie =
      key + '=' + converter.write(value, key) + stringifiedAttributes)
  }

  function get (key) {
    if (typeof document === 'undefined' || (arguments.length && !key)) {
      return
    }

    // To prevent the for loop in the first place assign an empty array
    // in case there are no cookies at all.
    var cookies = document.cookie ? document.cookie.split('; ') : [];
    var jar = {};
    for (var i = 0; i < cookies.length; i++) {
      var parts = cookies[i].split('=');
      var value = parts.slice(1).join('=');

      try {
        var foundKey = decodeURIComponent(parts[0]);
        jar[foundKey] = converter.read(value, foundKey);

        if (key === foundKey) {
          break
        }
      } catch (e) {}
    }

    return key ? jar[key] : jar
  }

  return Object.create(
    {
      set: set,
      get: get,
      remove: function (key, attributes) {
        set(
          key,
          '',
          assign({}, attributes, {
            expires: -1
          })
        );
      },
      withAttributes: function (attributes) {
        return init(this.converter, assign({}, this.attributes, attributes))
      },
      withConverter: function (converter) {
        return init(assign({}, this.converter, converter), this.attributes)
      }
    },
    {
      attributes: { value: Object.freeze(defaultAttributes) },
      converter: { value: Object.freeze(converter) }
    }
  )
}

var api = init(defaultConverter, { path: '/' });
/* eslint-enable no-var */

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (api);


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
/************************************************************************/
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
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
/************************************************************************/
var __webpack_exports__ = {};
// This entry need to be wrapped in an IIFE because it need to be isolated against other modules in the chunk.
(() => {
/*!**************************************!*\
  !*** ./assets/src/scripts/export.js ***!
  \**************************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var js_cookie__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! js-cookie */ "./node_modules/js-cookie/dist/js.cookie.mjs");
/* harmony import */ var _utils_displayNotice__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./utils/displayNotice */ "./assets/src/scripts/utils/displayNotice.js");
/* harmony import */ var _utils_resetClock__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./utils/resetClock */ "./assets/src/scripts/utils/resetClock.js");
/* harmony import */ var _utils_startClock__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./utils/startClock */ "./assets/src/scripts/utils/startClock.js");
/* global PB_ExportToken */

/* global _pb_export_formats_map */

/* global _pb_export_pins_inventory */




jQuery(function ($) {
  /* SSE powered progress bar */
  var exportForm = $('#pb-export-form');
  exportForm.on('submit', function (e) {
    // Stop form from submitting
    e.preventDefault(); // Set element variables

    var button = $('#pb-export-button');
    var bar = $('#pb-sse-progressbar');
    var info = $('#pb-sse-info');
    var notices = $('.notice'); // Init clock

    var clock = null; // Show bar, hide button

    bar.val(0).show();
    button.attr('disabled', true).hide();
    notices.remove(); // Initialize event data

    var eventSourceUrl = PB_ExportToken.ajaxUrl + (PB_ExportToken.ajaxUrl.includes('?') ? '&' : '?') + $.param(exportForm.find(':checked'));
    var evtSource = new EventSource(eventSourceUrl); // Handle open

    /**
     *
     */

    evtSource.onopen = function () {
      // Start clock
      clock = (0,_utils_startClock__WEBPACK_IMPORTED_MODULE_3__["default"])(); // Warn the user if they navigate away

      $(window).on('beforeunload', function () {
        // In some browsers, the return value of the event is displayed in this dialog. Starting with Firefox 44, Chrome 51, Opera 38 and Safari 9.1, a generic string not under the control of the webpage will be shown.
        // @see https://developer.mozilla.org/en-US/docs/Web/API/WindowEventHandlers/onbeforeunload#Notes
        return PB_ExportToken.unloadWarning;
      });
    }; // Handle message

    /**
     * @param message
     */


    evtSource.onmessage = function (message) {
      var data = JSON.parse(message.data);

      switch (data.action) {
        case 'updateStatusBar':
          bar.val(parseInt(data.percentage, 10));
          info.html(data.info);
          break;

        case 'complete':
          evtSource.close();
          $(window).unbind('beforeunload');

          if (data.error) {
            bar.val(0).hide();
            button.attr('disabled', false).show();
            (0,_utils_displayNotice__WEBPACK_IMPORTED_MODULE_1__["default"])('error', data.error, true);

            if (clock) {
              (0,_utils_resetClock__WEBPACK_IMPORTED_MODULE_2__["default"])(clock);
            }
          } else {
            window.location = PB_ExportToken.redirectUrl;
          }

          break;

        default:
          break;
      }
    }; // Handle error

    /**
     *
     */


    evtSource.onerror = function () {
      evtSource.close();
      bar.removeAttr('value');
      info.html('EventStream Connection Error ' + PB_ExportToken.reloadSnippet);
      $(window).unbind('beforeunload');

      if (clock) {
        (0,_utils_resetClock__WEBPACK_IMPORTED_MODULE_2__["default"])(clock);
      }
    };
  });
  /* JSON Cookie. Remember to keep key/values short because a cookie has max 4096 bytes */

  var json_cookie_key = 'pb_export';
  var json_cookie = js_cookie__WEBPACK_IMPORTED_MODULE_0__["default"].get(json_cookie_key);
  json_cookie = typeof json_cookie === 'undefined' ? {} : JSON.parse(js_cookie__WEBPACK_IMPORTED_MODULE_0__["default"].get(json_cookie_key));
  /**
   *
   */

  function update_json_cookie() {
    js_cookie__WEBPACK_IMPORTED_MODULE_0__["default"].set(json_cookie_key, JSON.stringify(json_cookie), {
      path: '/',
      expires: 365
    });
  }
  /* Collapsible form */


  var optionsPanel = document.getElementById('export-options');
  var toggleButton = optionsPanel.querySelector('.handlediv');
  /**
   *
   */

  toggleButton.onclick = function () {
    var expanded = toggleButton.getAttribute('aria-expanded') === 'true' || false;
    toggleButton.setAttribute('aria-expanded', !expanded);

    if (expanded) {
      optionsPanel.classList.add('closed');
    } else {
      optionsPanel.classList.remove('closed');
    }
  };
  /* Bulk Action Handler */


  var bulkActionsTop = document.getElementById('bulk-action-selector-top');
  var bulkActionsBottom = document.getElementById('bulk-action-selector-bottom');
  var bulkForm = document.querySelector('.wp-list-table').parentNode;
  bulkForm.addEventListener('submit', function (event) {
    event.preventDefault();

    if (bulkActionsTop.value === 'delete' || bulkActionsBottom.value === 'delete') {
      if (!confirm(PB_ExportToken.bulkDeleteWarning)) {
        // eslint-disable-line
        return false;
      }
    }
    /**
     *
     */


    var bulkSubmission = function bulkSubmission() {
      bulkForm.submit();
    };

    setTimeout(bulkSubmission, 0);
  });
  /* Swap out and animate the 'Export Your Book' button */

  $('#pb-export-button').on('click', function (e) {
    e.preventDefault(); // If the user has pinned three files of a given export type and then tries to export that format,
    // the export job should be stopped and an error should be displayed instructing them to deselect
    // one of the pinned files before attempting to export.

    var tooManyExports = false;
    var myLabel = '';
    $('#pb-export-form input:checked').each(function () {
      myLabel = $("label[for='" + $(this).attr('id') + "']").text().trim(); // eslint-disable-line quotes

      var name = $(this).attr('name');
      var myMatch = _pb_export_formats_map[name];

      if (Object.values(_pb_export_pins_inventory).filter(function (value) {
        // value matches <crc32-format-td>
        return value === myMatch;
      }).length >= 3) {
        tooManyExports = true;
        return false; // Use return false to break out of each() loops early
      }
    });

    if (tooManyExports) {
      alert(myLabel + ': ' + PB_ExportToken.tooManyExportsWarning);
      return false;
    }

    $('.export-file-container').unbind('mouseenter mouseleave'); // Disable Download & Delete Buttons

    $('.export-control button').prop('disabled', true);
    $('#pb-export-button').hide();
    $('#loader').show();
    /**
     *
     */

    var submission = function submission() {
      $('#pb-export-form').submit();
    };

    setTimeout(submission, 0);
  });
  /* Export Formats */

  $('#pb-export-form').find('input').each(function () {
    var name = $(this).attr('name'); // Defaults

    if (jQuery.isEmptyObject(json_cookie)) {
      // Defaults
      if (name === 'export_formats[pdf]' || name === 'export_formats[mpdf]' || name === 'export_formats[epub]') {
        $(this).prop('checked', true);
      } else {
        $(this).prop('checked', false);
      }
    } else {
      // Initialize checkboxes from cookie
      var was_checked = 0;

      if (Object.prototype.hasOwnProperty.call(json_cookie, name)) {
        was_checked = json_cookie[name];
      }

      $(this).prop('checked', !!was_checked);
    } // If there's a dependency error, then forcibly uncheck


    if ($(this).attr('disabled')) {
      $(this).prop('checked', false);
    }
  }).on('change', function () {
    var name = $(this).attr('name');

    if ($(this).prop('checked')) {
      // Cookie syntax: 'ef[<format>]': 1
      // I.e: 'ef[print_pdf]': 1
      json_cookie[name] = 1;
    } else {
      delete json_cookie[name];
    }

    update_json_cookie();
  });
  /* Pins */

  /**
   *
   */

  var adjustBulkActions = function adjustBulkActions() {
    var totalCount = $('td.column-pin input').length;
    var checkedCount = $('td.column-pin input:checked').length;

    if (checkedCount === totalCount) {
      $('#cb-select-all-1, #cb-select-all-2, #bulk-action-selector-top, #bulk-action-selector-bottom, #doaction, #doaction2').attr('disabled', true);
    } else {
      $('#cb-select-all-1, #cb-select-all-2, #bulk-action-selector-top, #bulk-action-selector-bottom, #doaction, #doaction2').attr('disabled', false);
    }
  };

  adjustBulkActions();
  $('td.column-pin').find('input').each(function () {
    if ($(this).prop('checked')) {
      var tr = $(this).closest('tr');
      var id = tr.attr('data-id');
      var cb = $("input[name='ID[]'][value='".concat(id, "']"));
      $(this).prop('checked', true);
      cb.prop('checked', false);
      cb.prop('disabled', true);
      tr.find('td.column-file span.delete').hide();
    }
  }).on('change', function () {
    adjustBulkActions();
    var name = $(this).attr('name');
    var tr = $(this).closest('tr');
    var id = tr.attr('data-id');
    var cb = $("input[name='ID[]'][value='".concat(id, "']"));
    var format = tr.attr('data-format');
    var file = tr.attr('data-file');
    var pinned = $(this).prop('checked') ? 1 : 0;

    if (pinned) {
      _pb_export_pins_inventory[name] = format; // Up to five files can be pinned at once.

      if (Object.keys(_pb_export_pins_inventory).length > 5) {
        delete _pb_export_pins_inventory[name];
        $(this).prop('checked', false);
        alert(PB_ExportToken.maximumFilesWarning);
        return false;
      } // If the user has pinned three files of a given export type and they then try to pin an additional file of that type,
      // an error should be displayed instructing them to deselect one of the pinned files before attempting to pin another.


      if (Object.values(_pb_export_pins_inventory).filter(function (value) {
        // value matches <crc32-format-td>
        return value === format;
      }).length > 3) {
        delete _pb_export_pins_inventory[name];
        $(this).prop('checked', false);
        alert(PB_ExportToken.maximumFileTypeWarning);
        return false;
      } // Checked


      cb.prop('checked', false);
      cb.prop('disabled', true);
      tr.find('td.column-file span.delete').hide();
    } else {
      // Unchecked
      delete _pb_export_pins_inventory[name];
      cb.prop('disabled', false);
      tr.find('td.column-file span.delete').show();
    }

    $.ajax({
      url: ajaxurl,
      type: 'POST',
      data: {
        action: 'pb_update_pins',
        pins: JSON.stringify(_pb_export_pins_inventory),
        file: file,
        pinned: pinned,
        _ajax_nonce: PB_ExportToken.pinsNonce
      },

      /**
       * @param response
       */
      success: function success(response) {
        var pinNotifications = $('#pin-notifications');
        pinNotifications.html(response.data.message);
      }
    });
  });
});
})();

/******/ })()
;