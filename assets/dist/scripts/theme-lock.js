!function(e){function t(o){if(n[o])return n[o].exports;var r=n[o]={i:o,l:!1,exports:{}};return e[o].call(r.exports,r,r.exports,t),r.l=!0,r.exports}var n={};t.m=e,t.c=n,t.d=function(e,n,o){t.o(e,n)||Object.defineProperty(e,n,{configurable:!1,enumerable:!0,get:o})},t.n=function(e){var n=e&&e.__esModule?function(){return e.default}:function(){return e};return t.d(n,"a",n),n},t.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},t.p="",t(t.s=15)}({15:function(e,t,n){e.exports=n("CKUc")},CKUc:function(e,t){jQuery(function(e){e("#theme_lock").change(function(){this.checked||(window.confirm(PB_ThemeLockToken.confirmation)?e("#theme_lock").attr("checked",!1):e("#theme_lock").attr("checked",!0))})})}});