!function(t){var e={};function r(n){if(e[n])return e[n].exports;var a=e[n]={i:n,l:!1,exports:{}};return t[n].call(a.exports,a,a.exports,r),a.l=!0,a.exports}r.m=t,r.c=e,r.d=function(t,e,n){r.o(t,e)||Object.defineProperty(t,e,{enumerable:!0,get:n})},r.r=function(t){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(t,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(t,"__esModule",{value:!0})},r.t=function(t,e){if(1&e&&(t=r(t)),8&e)return t;if(4&e&&"object"==typeof t&&t&&t.__esModule)return t;var n=Object.create(null);if(r.r(n),Object.defineProperty(n,"default",{enumerable:!0,value:t}),2&e&&"string"!=typeof t)for(var a in t)r.d(n,a,function(e){return t[e]}.bind(null,a));return n},r.n=function(t){var e=t&&t.__esModule?function(){return t.default}:function(){return t};return r.d(e,"a",e),e},r.o=function(t,e){return Object.prototype.hasOwnProperty.call(t,e)},r.p="/",r(r.s=15)}({15:function(t,e,r){t.exports=r("ayGu")},ayGu:function(t,e){jQuery((function(t){t(document).ready((function(){t("div.row-actions .restrict a, div.row-actions .unrestrict a").on("click",(function(){var e=t(this),r=e.parent("span"),n=r.parent("div").parent("td").parent("tr"),a=n.attr("id"),o=e.attr("data-restrict-text"),i=e.attr("data-unrestrict-text"),u=e.attr("data-restrict");t.ajax({url:ajaxurl,type:"POST",data:{action:"pb_update_admin_status",admin_id:a,status:u,_ajax_nonce:PB_NetworkManagerToken.networkManagerNonce},success:function(){n.toggleClass("restricted"),"0"===u?(r.removeClass("unrestrict").addClass("restrict"),e.attr("data-restrict","1"),e.text(o)):"1"===u&&(r.removeClass("restrict").addClass("unrestrict"),e.attr("data-restrict","0"),e.text(i))},error:function(t,e,r){alert(t+" :: "+e+" :: "+r)}})}))}))}))}});
