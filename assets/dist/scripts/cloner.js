!function(e){var t={};function n(o){if(t[o])return t[o].exports;var r=t[o]={i:o,l:!1,exports:{}};return e[o].call(r.exports,r,r.exports,n),r.l=!0,r.exports}n.m=e,n.c=t,n.d=function(e,t,o){n.o(e,t)||Object.defineProperty(e,t,{enumerable:!0,get:o})},n.r=function(e){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},n.t=function(e,t){if(1&t&&(e=n(e)),8&t)return e;if(4&t&&"object"==typeof e&&e&&e.__esModule)return e;var o=Object.create(null);if(n.r(o),Object.defineProperty(o,"default",{enumerable:!0,value:e}),2&t&&"string"!=typeof e)for(var r in e)n.d(o,r,function(t){return e[t]}.bind(null,r));return o},n.n=function(e){var t=e&&e.__esModule?function(){return e.default}:function(){return e};return n.d(t,"a",t),t},n.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},n.p="/",n(n.s=5)}({5:function(e,t,n){e.exports=n("BHd/")},"BHd/":function(e,t,n){"use strict";n.r(t);var o=n("EuuU"),r=n("xvUD"),a=n("ZDLt");jQuery(function(e){var t=e("#pb-cloner-form");t.on("submit",function(n){n.preventDefault();var i=e("#pb-cloner-button"),c=e("#pb-sse-progressbar"),u=e("#pb-sse-info"),s=e(".notice"),d=null;c.val(0).show(),i.attr("disabled",!0).hide(),s.remove();var l=PB_ClonerToken.ajaxUrl+(PB_ClonerToken.ajaxUrl.includes("?")?"&":"?")+e.param(t.find(":input")),p=new EventSource(l);p.onopen=function(){d=Object(a.a)(),e(window).on("beforeunload",function(){return PB_ClonerToken.unloadWarning})},p.onmessage=function(t){var n=JSON.parse(t.data);switch(n.action){case"updateStatusBar":c.val(parseInt(n.percentage,10)),u.html(n.info);break;case"complete":p.close(),e(window).unbind("beforeunload"),n.error?(c.val(0).hide(),i.attr("disabled",!1).show(),Object(o.a)("error",n.error,!0),d&&Object(r.a)(d)):window.location=PB_ClonerToken.redirectUrl}},p.onerror=function(){p.close(),e("#pb-sse-progressbar").removeAttr("value"),e("#pb-sse-info").html("EventStream Connection Error "+PB_ClonerToken.reloadSnippet),e(window).unbind("beforeunload"),d&&Object(r.a)(d)}})})},EuuU:function(e,t,n){"use strict";t.a=function(e,t,n){var o,r=document.createElement("div"),a=document.createElement("p"),i=document.getElementsByTagName("h1")[0];if(a.appendChild(document.createTextNode(t)),r.classList.add("notice","notice-".concat(e)),r.appendChild(a),n){o=document.createElement("button");var c=document.createElement("span");o.classList.add("notice-dismiss"),c.classList.add("screen-reader-text"),c.appendChild(document.createTextNode("Dismiss this notice.")),o.appendChild(c),r.classList.add("is-dismissible"),r.appendChild(o)}i.parentNode.insertBefore(r,i.nextSibling),o&&(o.onclick=function(){r.parentNode.removeChild(r)})}},ZDLt:function(e,t,n){"use strict";var o=function(e){return e>9?e:"0".concat(e)};t.a=function(){var e=document.getElementById("pb-sse-seconds"),t=document.getElementById("pb-sse-minutes"),n=0;return t.textContent="00:",e.textContent="00",setInterval(function(){e.textContent=o(++n%60),t.textContent=o(parseInt(n/60,10))+":"},1e3)}},xvUD:function(e,t,n){"use strict";t.a=function(e){var t=document.getElementById("pb-sse-seconds");document.getElementById("pb-sse-minutes").textContent="",t.textContent="",clearInterval(e)}}});