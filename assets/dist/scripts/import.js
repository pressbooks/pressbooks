!function(e){var t={};function n(r){if(t[r])return t[r].exports;var o=t[r]={i:r,l:!1,exports:{}};return e[r].call(o.exports,o,o.exports,n),o.l=!0,o.exports}n.m=e,n.c=t,n.d=function(e,t,r){n.o(e,t)||Object.defineProperty(e,t,{enumerable:!0,get:r})},n.r=function(e){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},n.t=function(e,t){if(1&t&&(e=n(e)),8&t)return e;if(4&t&&"object"==typeof e&&e&&e.__esModule)return e;var r=Object.create(null);if(n.r(r),Object.defineProperty(r,"default",{enumerable:!0,value:e}),2&t&&"string"!=typeof e)for(var o in e)n.d(r,o,function(t){return e[t]}.bind(null,o));return r},n.n=function(e){var t=e&&e.__esModule?function(){return e.default}:function(){return e};return n.d(t,"a",t),t},n.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},n.p="/",n(n.s=13)}({13:function(e,t,n){e.exports=n("tRoB")},ZDLt:function(e,t,n){"use strict";var r=function(e){return e>9?e:"0".concat(e)};t.a=function(e){var t=document.getElementById("pb-sse-seconds"),n=document.getElementById("pb-sse-minutes"),o=0;n.textContent="00:",t.textContent="00",setInterval(function(){t.textContent=r(++o%60),n.textContent=r(parseInt(o/60,10))+":"},1e3)}},tRoB:function(e,t,n){"use strict";n.r(t);var r=n("ZDLt");jQuery(function(e){e("#pb-import-form-step-1").on("submit",function(){e("input[type=submit]").attr("disabled",!0)});var t=e("#pb-import-form-step-2");t.on("submit",function(n){n.preventDefault();var o=e("input[type=submit]"),u=e("#pb-sse-progressbar"),a=e("#pb-sse-info");u.val(0).show(),o.attr("disabled",!0);var i=PB_ImportToken.ajaxUrl+(PB_ImportToken.ajaxUrl.includes("?")?"&":"?")+e.param(t.find(":checked")),c=new EventSource(i);c.onopen=function(){Object(r.a)(null),e(window).on("beforeunload",function(){return PB_ImportToken.unloadWarning})},c.onmessage=function(t){var n=JSON.parse(t.data);switch(n.action){case"updateStatusBar":u.val(parseInt(n.percentage,10)),a.html(n.info);break;case"complete":c.close(),e(window).unbind("beforeunload"),n.error?(u.removeAttr("value"),a.html(n.error+" "+PB_ImportToken.reloadSnippet)):window.location=PB_ImportToken.redirectUrl}},c.onerror=function(){c.close(),u.removeAttr("value"),a.html("EventStream Connection Error "+PB_ImportToken.reloadSnippet),e(window).unbind("beforeunload")}})})}});