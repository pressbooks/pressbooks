!function(e){var t={};function r(o){if(t[o])return t[o].exports;var n=t[o]={i:o,l:!1,exports:{}};return e[o].call(n.exports,n,n.exports,r),n.l=!0,n.exports}r.m=e,r.c=t,r.d=function(e,t,o){r.o(e,t)||Object.defineProperty(e,t,{configurable:!1,enumerable:!0,get:o})},r.n=function(e){var t=e&&e.__esModule?function(){return e.default}:function(){return e};return r.d(t,"a",t),t},r.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},r.p="",r(r.s=8)}({8:function(e,t,r){e.exports=r("msGZ")},lbHh:function(e,t,r){var o,n;!function(i){if(void 0===(n="function"==typeof(o=i)?o.call(t,r,t,e):o)||(e.exports=n),!0,e.exports=i(),!!0){var c=window.Cookies,s=window.Cookies=i();s.noConflict=function(){return window.Cookies=c,s}}}(function(){function e(){for(var e=0,t={};e<arguments.length;e++){var r=arguments[e];for(var o in r)t[o]=r[o]}return t}return function t(r){function o(t,n,i){var c;if("undefined"!=typeof document){if(arguments.length>1){if("number"==typeof(i=e({path:"/"},o.defaults,i)).expires){var s=new Date;s.setMilliseconds(s.getMilliseconds()+864e5*i.expires),i.expires=s}i.expires=i.expires?i.expires.toUTCString():"";try{c=JSON.stringify(n),/^[\{\[]/.test(c)&&(n=c)}catch(e){}n=r.write?r.write(n,t):encodeURIComponent(String(n)).replace(/%(23|24|26|2B|3A|3C|3E|3D|2F|3F|40|5B|5D|5E|60|7B|7D|7C)/g,decodeURIComponent),t=(t=(t=encodeURIComponent(String(t))).replace(/%(23|24|26|2B|5E|60|7C)/g,decodeURIComponent)).replace(/[\(\)]/g,escape);var p="";for(var a in i)i[a]&&(p+="; "+a,!0!==i[a]&&(p+="="+i[a]));return document.cookie=t+"="+n+p}t||(c={});for(var u=document.cookie?document.cookie.split("; "):[],f=/(%[0-9A-Z]{2})+/g,l=0;l<u.length;l++){var d=u[l].split("="),b=d.slice(1).join("=");this.json||'"'!==b.charAt(0)||(b=b.slice(1,-1));try{var h=d[0].replace(f,decodeURIComponent);if(b=r.read?r.read(b,h):r(b,h)||b.replace(f,decodeURIComponent),this.json)try{b=JSON.parse(b)}catch(e){}if(t===h){c=b;break}t||(c[h]=b)}catch(e){}}return c}}return o.set=o,o.get=function(e){return o.call(o,e)},o.getJSON=function(){return o.apply({json:!0},[].slice.call(arguments))},o.defaults={},o.remove=function(t,r){o(t,"",e(r,{expires:-1}))},o.withConverter=t,o}(function(){})})},msGZ:function(e,t,r){"use strict";Object.defineProperty(t,"__esModule",{value:!0});var o=r("lbHh"),n=r.n(o);jQuery(function(e){e("#pb-export-form").on("submit",function(t){t.preventDefault(),e("#pb-export-button").attr("disabled",!0);var r=e("#pb-export-form"),o=PB_ExportToken.ajaxUrl+(PB_ExportToken.ajaxUrl.includes("?")?"&":"?")+e.param(r.find(":checked")),n=new EventSource(o);n.onopen=function(){e("#pb-export-button").hide()},n.onmessage=function(t){var r=e("#pb-sse-progressbar"),o=e("#pb-sse-info"),i=JSON.parse(t.data);switch(i.action){case"updateStatusBar":r.progressbar({value:parseInt(i.percentage,10)}),o.html(i.info);break;case"complete":n.close(),i.error?(r.progressbar({value:!1}),o.html(i.error)):window.location=PB_ExportToken.redirectUrl}},n.onerror=function(){n.close(),e("#pb-sse-progressbar").progressbar({value:!1}),e("#pb-sse-info").html("EventStream Connection Error")}}),e("#pb-export-button").click(function(t){t.preventDefault(),e(".export-file-container").unbind("mouseenter mouseleave"),e(".export-control button").prop("disabled",!0),e("#pb-export-form").submit()}),e(".export-file-container").hover(function(){e(this).children(".file-actions").css("visibility","visible")},function(){e(this).children(".file-actions").css("visibility","hidden")}),e("#pb-export-form").find("input").each(function(){var t=e(this).attr("name"),r=n.a.get("pb_"+t),o=void 0;void 0===r?"export_formats[pdf]"===t||"export_formats[mpdf]"===t||"export_formats[epub]"===t||"export_formats[mobi]"===t?e(this).prop("checked",!0):e(this).prop("checked",!1):(o="boolean"==typeof r?r:"true"===r,e(this).prop("checked",o)),e(this).attr("disabled")&&e(this).prop("checked",!1)}).change(function(){n.a.set("pb_"+e(this).attr("name"),e(this).prop("checked"),{path:"/",expires:365})})})}});