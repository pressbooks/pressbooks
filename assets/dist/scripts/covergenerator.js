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
/*!**********************************************!*\
  !*** ./assets/src/scripts/covergenerator.js ***!
  \**********************************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _utils_displayNotice__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./utils/displayNotice */ "./assets/src/scripts/utils/displayNotice.js");
/* harmony import */ var _utils_resetClock__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./utils/resetClock */ "./assets/src/scripts/utils/resetClock.js");
/* harmony import */ var _utils_startClock__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./utils/startClock */ "./assets/src/scripts/utils/startClock.js");
/* global PB_CoverGeneratorToken */



jQuery(function ($) {
  // Media
  $(document).ready(function () {
    var mediaUploader;
    $('.front-background-image-upload-button').on('click', function (e) {
      e.preventDefault();

      if (!mediaUploader) {
        // Extend the wp.media object
        mediaUploader = wp.media.frames.file_frame = wp.media({
          multiple: false
        }); // When a file is selected, grab the URL and set it as the text field's value

        mediaUploader.on('select', function () {
          var attachment = mediaUploader.state().get('selection').first().toJSON();
          $('#front_background_image').val(attachment.url);
          $('.front-background-image').attr('src', attachment.url);
          $('.front-background-image-preview-wrap').removeClass('hidden');
          $('.front-background-image-upload-button, .front-background-image-description').addClass('hidden');
        });
      } // Open the uploader dialog


      mediaUploader.open();
    });
  });
  $('.delete-front-background-image').on('click', function () {
    $('#front_background_image').val('');
    $('.front-background-image-preview-wrap').addClass('hidden');
    $('.front-background-image-upload-button, .front-background-image-description').removeClass('hidden');
  }); // Custom PPI

  var myPpi = $('#ppi');
  var myCustomPpi = $('#custom_ppi');

  if (myPpi.val() !== '') {
    myCustomPpi.parent().parent().hide();
  }

  myPpi.on('change', function () {
    if ($(this).val() === '') {
      myCustomPpi.parent().parent().show();
    } else {
      myCustomPpi.parent().parent().hide();
      myCustomPpi.val($(this).val());
    }
  }); // Color pickers

  $('.colorpicker').wpColorPicker(); // Set element variables

  var form = $('.settings-form');
  var makePdfButton = $('#generate-pdf');
  var makeEbookButton = $('#generate-jpg');
  var bar = $('#pb-sse-progressbar');
  var info = $('#pb-sse-info');
  var notices = $('.notice'); // Initialize clock

  var clock = null; // Event source handler

  /**
   * @param fileType
   */

  var eventSourceHandler = function eventSourceHandler(fileType) {
    // Initialize event data
    var hiddenForm = $('form.' + fileType);
    var eventSourceUrl = PB_CoverGeneratorToken.ajaxUrl + (PB_CoverGeneratorToken.ajaxUrl.includes('?') ? '&' : '?') + $.param(hiddenForm.find(':input'));
    var evtSource = new EventSource(eventSourceUrl); // Handle open

    /**
     *
     */

    evtSource.onopen = function () {
      // Warn the user if they navigate away
      $(window).on('beforeunload', function () {
        // In some browsers, the return value of the event is displayed in this dialog. Starting with Firefox 44, Chrome 51, Opera 38 and Safari 9.1, a generic string not under the control of the webpage will be shown.
        // @see https://developer.mozilla.org/en-US/docs/Web/API/WindowEventHandlers/onbeforeunload#Notes
        return PB_CoverGeneratorToken.unloadWarning;
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
            makePdfButton.attr('disabled', false).show();
            makeEbookButton.attr('disabled', false).show();
            (0,_utils_displayNotice__WEBPACK_IMPORTED_MODULE_0__["default"])('error', data.error, true);

            if (clock) {
              (0,_utils_resetClock__WEBPACK_IMPORTED_MODULE_1__["default"])(clock);
            }
          } else {
            window.location = PB_CoverGeneratorToken.redirectUrl;
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
      info.html('EventStream Connection Error ' + PB_CoverGeneratorToken.reloadSnippet);
      $(window).unbind('beforeunload');

      if (clock) {
        (0,_utils_resetClock__WEBPACK_IMPORTED_MODULE_1__["default"])(clock);
      }
    };
  };

  form.on('saveAndGenerate', function (event, fileType) {
    makePdfButton.hide();
    makeEbookButton.hide();
    bar.val(0).show();
    notices.remove();
    clock = (0,_utils_startClock__WEBPACK_IMPORTED_MODULE_2__["default"])();
    info.html(PB_CoverGeneratorToken.ajaxSubmitMsg); // Save the WP options and WP Media before triggering the generator
    // @see https://github.com/jquery-form/form

    $(this).ajaxSubmit({
      done: eventSourceHandler(fileType),
      timeout: 0 // A value of 0 means there will be no timeout.

    }); // Return false to prevent normal browser submit and page navigation.

    return false;
  });
  makePdfButton.on('click', function () {
    var editor = tinymce.get('pb_about_unlimited');

    if (editor) {
      var content = editor.getContent();
      $('#pb_about_unlimited').val(content);
    }

    form.trigger('saveAndGenerate', ['pdf']);
  });
  makeEbookButton.on('click', function () {
    form.trigger('saveAndGenerate', ['jpg']);
  });
});
})();

/******/ })()
;