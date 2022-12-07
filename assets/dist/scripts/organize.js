/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./node_modules/countup.js/dist/countUp.min.js":
/*!*****************************************************!*\
  !*** ./node_modules/countup.js/dist/countUp.min.js ***!
  \*****************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "CountUp": () => (/* binding */ CountUp)
/* harmony export */ });
var __assign=undefined&&undefined.__assign||function(){return(__assign=Object.assign||function(t){for(var i,n=1,s=arguments.length;n<s;n++)for(var a in i=arguments[n])Object.prototype.hasOwnProperty.call(i,a)&&(t[a]=i[a]);return t}).apply(this,arguments)},CountUp=function(){function t(t,i,n){var s=this;this.endVal=i,this.options=n,this.version="2.3.2",this.defaults={startVal:0,decimalPlaces:0,duration:2,useEasing:!0,useGrouping:!0,smartEasingThreshold:999,smartEasingAmount:333,separator:",",decimal:".",prefix:"",suffix:"",enableScrollSpy:!1,scrollSpyDelay:200,scrollSpyOnce:!1},this.finalEndVal=null,this.useEasing=!0,this.countDown=!1,this.error="",this.startVal=0,this.paused=!0,this.once=!1,this.count=function(t){s.startTime||(s.startTime=t);var i=t-s.startTime;s.remaining=s.duration-i,s.useEasing?s.countDown?s.frameVal=s.startVal-s.easingFn(i,0,s.startVal-s.endVal,s.duration):s.frameVal=s.easingFn(i,s.startVal,s.endVal-s.startVal,s.duration):s.frameVal=s.startVal+(s.endVal-s.startVal)*(i/s.duration);var n=s.countDown?s.frameVal<s.endVal:s.frameVal>s.endVal;s.frameVal=n?s.endVal:s.frameVal,s.frameVal=Number(s.frameVal.toFixed(s.options.decimalPlaces)),s.printValue(s.frameVal),i<s.duration?s.rAF=requestAnimationFrame(s.count):null!==s.finalEndVal?s.update(s.finalEndVal):s.callback&&s.callback()},this.formatNumber=function(t){var i,n,a,e,r=t<0?"-":"";i=Math.abs(t).toFixed(s.options.decimalPlaces);var o=(i+="").split(".");if(n=o[0],a=o.length>1?s.options.decimal+o[1]:"",s.options.useGrouping){e="";for(var l=0,h=n.length;l<h;++l)0!==l&&l%3==0&&(e=s.options.separator+e),e=n[h-l-1]+e;n=e}return s.options.numerals&&s.options.numerals.length&&(n=n.replace(/[0-9]/g,function(t){return s.options.numerals[+t]}),a=a.replace(/[0-9]/g,function(t){return s.options.numerals[+t]})),r+s.options.prefix+n+a+s.options.suffix},this.easeOutExpo=function(t,i,n,s){return n*(1-Math.pow(2,-10*t/s))*1024/1023+i},this.options=__assign(__assign({},this.defaults),n),this.formattingFn=this.options.formattingFn?this.options.formattingFn:this.formatNumber,this.easingFn=this.options.easingFn?this.options.easingFn:this.easeOutExpo,this.startVal=this.validateValue(this.options.startVal),this.frameVal=this.startVal,this.endVal=this.validateValue(i),this.options.decimalPlaces=Math.max(this.options.decimalPlaces),this.resetDuration(),this.options.separator=String(this.options.separator),this.useEasing=this.options.useEasing,""===this.options.separator&&(this.options.useGrouping=!1),this.el="string"==typeof t?document.getElementById(t):t,this.el?this.printValue(this.startVal):this.error="[CountUp] target is null or undefined","undefined"!=typeof window&&this.options.enableScrollSpy&&(this.error?console.error(this.error,t):(window.onScrollFns=window.onScrollFns||[],window.onScrollFns.push(function(){return s.handleScroll(s)}),window.onscroll=function(){window.onScrollFns.forEach(function(t){return t()})},this.handleScroll(this)))}return t.prototype.handleScroll=function(t){if(t&&window&&!t.once){var i=window.innerHeight+window.scrollY,n=t.el.getBoundingClientRect(),s=n.top+n.height+window.pageYOffset;s<i&&s>window.scrollY&&t.paused?(t.paused=!1,setTimeout(function(){return t.start()},t.options.scrollSpyDelay),t.options.scrollSpyOnce&&(t.once=!0)):window.scrollY>s&&!t.paused&&t.reset()}},t.prototype.determineDirectionAndSmartEasing=function(){var t=this.finalEndVal?this.finalEndVal:this.endVal;this.countDown=this.startVal>t;var i=t-this.startVal;if(Math.abs(i)>this.options.smartEasingThreshold&&this.options.useEasing){this.finalEndVal=t;var n=this.countDown?1:-1;this.endVal=t+n*this.options.smartEasingAmount,this.duration=this.duration/2}else this.endVal=t,this.finalEndVal=null;null!==this.finalEndVal?this.useEasing=!1:this.useEasing=this.options.useEasing},t.prototype.start=function(t){this.error||(this.callback=t,this.duration>0?(this.determineDirectionAndSmartEasing(),this.paused=!1,this.rAF=requestAnimationFrame(this.count)):this.printValue(this.endVal))},t.prototype.pauseResume=function(){this.paused?(this.startTime=null,this.duration=this.remaining,this.startVal=this.frameVal,this.determineDirectionAndSmartEasing(),this.rAF=requestAnimationFrame(this.count)):cancelAnimationFrame(this.rAF),this.paused=!this.paused},t.prototype.reset=function(){cancelAnimationFrame(this.rAF),this.paused=!0,this.resetDuration(),this.startVal=this.validateValue(this.options.startVal),this.frameVal=this.startVal,this.printValue(this.startVal)},t.prototype.update=function(t){cancelAnimationFrame(this.rAF),this.startTime=null,this.endVal=this.validateValue(t),this.endVal!==this.frameVal&&(this.startVal=this.frameVal,null==this.finalEndVal&&this.resetDuration(),this.finalEndVal=null,this.determineDirectionAndSmartEasing(),this.rAF=requestAnimationFrame(this.count))},t.prototype.printValue=function(t){var i=this.formattingFn(t);"INPUT"===this.el.tagName?this.el.value=i:"text"===this.el.tagName||"tspan"===this.el.tagName?this.el.textContent=i:this.el.innerHTML=i},t.prototype.ensureNumber=function(t){return"number"==typeof t&&!isNaN(t)},t.prototype.validateValue=function(t){var i=Number(t);return this.ensureNumber(i)?i:(this.error="[CountUp] invalid start or end value: ".concat(t),null)},t.prototype.resetDuration=function(){this.startTime=null,this.duration=1e3*Number(this.options.duration),this.remaining=this.duration},t}();

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
/*!****************************************!*\
  !*** ./assets/src/scripts/organize.js ***!
  \****************************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var countup_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! countup.js */ "./node_modules/countup.js/dist/countUp.min.js");
function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

/* global PB_OrganizeToken */

var $ = window.jQuery;
var pb = {
  organize: {
    bulkToggle: [],
    oldParent: null,
    newParent: null,
    oldOrder: null,
    newOrder: null,
    sortableOptions: {
      revert: true,
      helper: 'clone',
      zIndex: 2700,
      distance: 3,
      opacity: 0.6,
      placeholder: 'ui-state-highlight',
      dropOnEmpty: true,
      cursor: 'crosshair',
      items: 'tbody > tr',

      /**
       * @param event
       * @param ui
       */
      start: function start(event, ui) {
        pb.organize.oldParent = $(ui.item).parents('table').attr('id');
      },

      /**
       * @param event
       * @param ui
       */
      stop: function stop(event, ui) {
        pb.organize.newParent = $(ui.item).parents('table').attr('id');
        reorder($(ui.item));
      }
    }
  }
};
/**
 * Clear a modal using jQuery.unBlockUI()
 *
 * @param {string | object} item
 */

function showModal(item) {
  $.blockUI.defaults.applyPlatformOpacityRules = false;
  var alert = $('[role="alert"]');
  var alertMessage;

  if (item === 'book') {
    alertMessage = PB_OrganizeToken.updating.book;
  } else {
    var postType = item.post_type.replace('-', '');
    alertMessage = PB_OrganizeToken.updating[postType];
  }

  alert.children('p').text(alertMessage);
  alert.addClass('loading-content').removeClass('visually-hidden');
  $.blockUI({
    message: $(alert),
    baseZ: 100000
  });
}
/**
 * Clear a modal using jQuery.unBlockUI()
 *
 * @param {string | object} item
 * @param {string} status
 */


function removeModal(item, status) {
  var alert = $('[role="alert"]');
  var alertMessage;

  if (item === 'book') {
    alertMessage = PB_OrganizeToken[status].book;
  } else {
    var postType = item.post_type.replace('-', '');
    alertMessage = PB_OrganizeToken[status][postType];
  }

  $.unblockUI({
    /**
     *
     */
    onUnblock: function onUnblock() {
      alert.removeClass('loading-content').addClass('visually-hidden');
      alert.children('p').text(alertMessage);
    }
  });
}
/**
 * Update word count for exportable content.
 */


function updateWordCountForExport() {
  var data = {
    action: 'pb_update_word_count_for_export',
    _ajax_nonce: PB_OrganizeToken.wordCountNonce
  };
  $.post(ajaxurl, data, function (response) {
    var current_count = parseInt($('#wc-selected-for-export').text(), 10);
    var count_up_options = {
      startVal: current_count,
      separator: ''
    };
    var count_up = new countup_js__WEBPACK_IMPORTED_MODULE_0__.CountUp('wc-selected-for-export', response, count_up_options);
    count_up.start();
  });
}
/**
 * Get the table before or after the current table.
 *
 * @param {object} table
 * @param {string} relationship
 * @returns {object}
 */


function getAdjacentContainer(table, relationship) {
  if (relationship === 'prev') {
    return $(table).prev('[id^=part]');
  } else if (relationship === 'next') {
    return $(table).next('[id^=part]');
  }
}
/**
 * Get data for a table row.
 *
 * @param {object} row
 * @returns {object}
 */


function getRowData(row) {
  row = $(row).attr('id').split('_');
  var rowData = {
    id: row[row.length - 1],
    post_type: row[0]
  };
  return rowData;
}
/**
 * Get an array object of IDs in a table.
 *
 * @param {object} table
 * @returns {Array} ids
 */


function getIdsInTable(table) {
  var ids = [];
  table.children('tbody').children('tr').each(function (i, el) {
    var row = getRowData($(el));
    ids.push(row.id);
  });
  return ids;
}
/**
 * Adjust the reorder controls throughout a table as part of a reorder operation.
 *
 * @param {object} table
 */


function updateControls(table) {
  table.children('tbody').children('tr').each(function (i, el) {
    var controls = '';
    var up = '<button class="move-up">Move Up</button>';
    var down = '<button class="move-down">Move Down</button>';

    if ($(el).is('tr:only-of-type')) {
      if (table.is('[id^=part]') && table.prev('[id^=part]').length && table.next('[id^=part]').length) {
        controls = " | ".concat(up, " | ").concat(down);
      } else if (table.is('[id^=part]') && table.next('[id^=part]').length) {
        controls = " | ".concat(down);
      } else if (table.is('[id^=part]') && table.prev('[id^=part]').length) {
        controls = " | ".concat(up);
      }
    } else if ($(el).is('tr:first-of-type')) {
      if (table.is('[id^=part]') && table.prev('[id^=part]').length) {
        controls = " | ".concat(up, " | ").concat(down);
      } else {
        controls = " | ".concat(down);
      }
    } else if ($(el).is('tr:last-of-type')) {
      if (table.is('[id^=part]') && table.next('[id^=part]').length) {
        controls = " | ".concat(up, " | ").concat(down);
      } else {
        controls = " | ".concat(up);
      }
    } else {
      controls = " | ".concat(up, " | ").concat(down);
    }

    $(el).children('.has-row-actions').children('.row-title').children('.row-actions').children('.reorder').html(controls);
  });
}
/**
 * Reorder the contents of a table, optionally moving the target row to a new table.
 *
 * @param {object} row
 */


function reorder(row) {
  var item = getRowData(row);
  $.ajax({
    url: ajaxurl,
    type: 'POST',
    data: {
      action: 'pb_reorder',
      id: item.id,
      old_order: $("#".concat(pb.organize.oldParent)).sortable('serialize'),
      new_order: $("#".concat(pb.organize.newParent)).sortable('serialize'),
      old_parent: pb.organize.oldParent.replace(/^part_([0-9]+)$/i, '$1'),
      new_parent: pb.organize.newParent.replace(/^part_([0-9]+)$/i, '$1'),
      _ajax_nonce: PB_OrganizeToken.reorderNonce
    },

    /**
     *
     */
    beforeSend: function beforeSend() {
      showModal(item);

      if (pb.organize.oldParent !== pb.organize.newParent) {
        updateControls($("#".concat(pb.organize.oldParent)));
      }

      updateControls($("#".concat(pb.organize.newParent)));
    },

    /**
     *
     */
    success: function success() {
      removeModal(item, 'success');
    },

    /**
     *
     */
    error: function error() {
      removeModal(item, 'failure');
    }
  });
}
/**
 * Update post status for individual or multiple posts.
 *
 * @param ids
 * @param postType
 * @param output
 * @param visibility
 */


function updateVisibility(ids, postType, output, visibility) {
  var data = {
    action: 'pb_update_post_visibility',
    post_ids: ids,
    _ajax_nonce: PB_OrganizeToken.postVisibilityNonce
  };
  $.ajax({
    url: ajaxurl,
    type: 'POST',
    data: Object.assign(data, _defineProperty({}, output, visibility)),

    /**
     *
     */
    beforeSend: function beforeSend() {
      showModal({
        post_type: postType
      });
    },

    /**
     * @param response
     */
    success: function success(response) {
      removeModal({
        post_type: postType
      }, 'success');
      updateWordCountForExport();
    },

    /**
     *
     */
    error: function error() {
      removeModal({
        post_type: postType
      }, 'failure');
    }
  });
}
/**
 * Update title visibility for individual or multiple posts.
 *
 * @param {string} ids Comma separated post IDs.
 * @param {string} postType
 * @param {bool} showTitle
 */


function updateTitleVisibility(ids, postType, showTitle) {
  $.ajax({
    url: ajaxurl,
    type: 'POST',
    data: {
      action: 'pb_update_post_title_visibility',
      post_ids: ids,
      show_title: showTitle,
      _ajax_nonce: PB_OrganizeToken.showTitleNonce
    },

    /**
     *
     */
    beforeSend: function beforeSend() {
      showModal({
        post_type: postType
      });
    },

    /**
     * @param response
     */
    success: function success(response) {
      removeModal({
        post_type: postType
      }, 'success');
    },

    /**
     *
     */
    error: function error() {
      removeModal({
        post_type: postType
      }, 'failure');
    }
  });
}

$(document).ready(function () {
  // Initialize jQuery.sortable()
  $('.allow-bulk-operations #front-matter').sortable(pb.organize.sortableOptions).disableSelection();
  $('.allow-bulk-operations table#back-matter').sortable(pb.organize.sortableOptions).disableSelection();
  $('.allow-bulk-operations table.chapters').sortable(Object.assign(pb.organize.sortableOptions, {
    connectWith: '.chapters'
  })).disableSelection(); // Handle Global Privacy form changes.

  $('input[name=blog_public]').on('change', function (event) {
    var publicizeAlert = $('.publicize-alert');
    var publicizeAlertText = $('.publicize-alert > span');
    var blogPublic;

    if (parseInt(event.currentTarget.value, 10) === 1) {
      blogPublic = 1;
    } else {
      blogPublic = 0;
    }

    $.ajax({
      url: ajaxurl,
      type: 'POST',
      data: {
        action: 'pb_update_global_privacy_options',
        blog_public: blogPublic,
        _ajax_nonce: PB_OrganizeToken.privacyNonce
      },

      /**
       *
       */
      beforeSend: function beforeSend() {
        showModal('book');
      },

      /**
       *
       */
      success: function success() {
        if (blogPublic === 0) {
          publicizeAlert.removeClass('public').addClass('private');
          publicizeAlertText.text(PB_OrganizeToken.bookPrivate);
        } else if (blogPublic === 1) {
          publicizeAlert.removeClass('private').addClass('public');
          publicizeAlertText.text(PB_OrganizeToken.bookPublic);
        }

        removeModal('book', 'success');
      },

      /**
       *
       */
      error: function error() {
        removeModal('book', 'failure');
      }
    });
  }); // Handle visibility changes.

  $('.web_visibility, .export_visibility').on('change', function () {
    var row = $(this).parents('tr');
    var item = getRowData(row);
    var output;
    var visibility = 0;

    if ($(this).is(':checked')) {
      visibility = 1;
    }

    if ($(this).is('[id^="export_visibility"]')) {
      output = 'export';
    } else if ($(this).is('[id^="web_visibility"]')) {
      output = 'web';
    }

    updateVisibility(item.id, item.post_type, output, visibility);
  }); // Handle title visibility changes.

  $('.show_title').on('change', function (event) {
    var row = $(event.target).parents('tr');
    var item = getRowData(row);
    var showTitle = '';

    if ($(event.currentTarget).is(':checked')) {
      showTitle = 'on';
    }

    updateTitleVisibility(item.id, item.post_type, showTitle);
  }); // Handle "move up".

  $(document).on('click', '.move-up', function (event) {
    var row = $(event.target).parents('tr');
    var table = $(event.target).parents('table');
    pb.organize.oldParent = table.attr('id');

    if (row.is('tr:first-of-type') && table.is('[id^=part]') && table.prev('[id^=part]').length) {
      var targetTable = getAdjacentContainer(table, 'prev');
      pb.organize.newParent = targetTable.attr('id');
      targetTable.append(row);
      reorder(row);
    } else {
      pb.organize.newParent = table.attr('id');
      row.prev().before(row);
      reorder(row);
    }
  }); // Handle "move down".

  $(document).on('click', '.move-down', function (event) {
    var row = $(event.target).parents('tr');
    var table = $(event.target).parents('table');
    pb.organize.oldParent = table.attr('id');

    if (row.is('tr:last-of-type') && table.is('[id^=part]') && table.next('[id^=part]').length) {
      var targetTable = getAdjacentContainer(table, 'next');
      pb.organize.newParent = targetTable.attr('id');
      targetTable.prepend(row);
      reorder(row);
    } else {
      pb.organize.newParent = table.attr('id');
      row.next().after(row);
      reorder(row);
    }
  });
  $('.allow-bulk-operations table thead th span[id$="show_title"]').on('click', function (event) {
    var id = $(event.target).attr('id');
    id = id.replace('-', '');
    var table = $(event.target).parents('table');
    var postType = table.attr('id').split('_')[0];

    if (postType === 'part') {
      postType = 'chapter';
    }

    var ids = getIdsInTable(table);

    if (pb.organize.bulkToggle[id]) {
      table.find('tr td.column-showtitle input[type="checkbox"]').prop('checked', false);
      pb.organize.bulkToggle[id] = false;
      updateTitleVisibility(ids.join(), postType, '');
    } else {
      table.find('tr td.column-showtitle input[type="checkbox"]').prop('checked', true);
      pb.organize.bulkToggle[id] = true;
      updateTitleVisibility(ids.join(), postType, 'on');
    }
  });
  $('.allow-bulk-operations table thead th span[id$="visibility"]').on('click', function (event) {
    var id = $(event.target).attr('id');
    id = id.replace('-', '');
    var format = id.split('_');
    format = format[format.length - 2];
    var table = $(event.target).parents('table');
    var postType = table.attr('id').split('_')[0];

    if (postType === 'part') {
      postType = 'chapter';
    }

    var ids = getIdsInTable(table);

    if (pb.organize.bulkToggle[id]) {
      table.find("tr td.column-".concat(format, " input[type=checkbox]")).prop('checked', false);
      pb.organize.bulkToggle[id] = false;
      updateVisibility(ids.join(), postType, format, 0);
    } else {
      table.find("tr td.column-".concat(format, " input[type=\"checkbox\"]")).prop('checked', true);
      pb.organize.bulkToggle[id] = true;
      updateVisibility(ids.join(), postType, format, 1);
    }
  }); // Warn of incomplete AJAX

  $(window).on('beforeunload', function () {
    if ($.active > 0) {
      return 'Changes you made may not be saved...';
    }
  });
});
})();

/******/ })()
;