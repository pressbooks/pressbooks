!function(t){var e={};function a(n){if(e[n])return e[n].exports;var i=e[n]={i:n,l:!1,exports:{}};return t[n].call(i.exports,i,i.exports,a),i.l=!0,i.exports}a.m=t,a.c=e,a.d=function(t,e,n){a.o(t,e)||Object.defineProperty(t,e,{enumerable:!0,get:n})},a.r=function(t){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(t,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(t,"__esModule",{value:!0})},a.t=function(t,e){if(1&e&&(t=a(t)),8&e)return t;if(4&e&&"object"==typeof t&&t&&t.__esModule)return t;var n=Object.create(null);if(a.r(n),Object.defineProperty(n,"default",{enumerable:!0,value:t}),2&e&&"string"!=typeof t)for(var i in t)a.d(n,i,function(e){return t[e]}.bind(null,i));return n},a.n=function(t){var e=t&&t.__esModule?function(){return t.default}:function(){return t};return a.d(e,"a",e),e},a.o=function(t,e){return Object.prototype.hasOwnProperty.call(t,e)},a.p="/",a(a.s=16)}({16:function(t,e,a){t.exports=a("Lm/I")},"Lm/I":function(t,e,a){"use strict";a.r(e);var n=function(){return(n=Object.assign||function(t){for(var e,a=1,n=arguments.length;a<n;a++)for(var i in e=arguments[a])Object.prototype.hasOwnProperty.call(e,i)&&(t[i]=e[i]);return t}).apply(this,arguments)},i=function(){function t(t,e,a){var i=this;this.target=t,this.endVal=e,this.options=a,this.version="2.0.5",this.defaults={startVal:0,decimalPlaces:0,duration:2,useEasing:!0,useGrouping:!0,smartEasingThreshold:999,smartEasingAmount:333,separator:",",decimal:".",prefix:"",suffix:""},this.finalEndVal=null,this.useEasing=!0,this.countDown=!1,this.error="",this.startVal=0,this.paused=!0,this.count=function(t){i.startTime||(i.startTime=t);var e=t-i.startTime;i.remaining=i.duration-e,i.useEasing?i.countDown?i.frameVal=i.startVal-i.easingFn(e,0,i.startVal-i.endVal,i.duration):i.frameVal=i.easingFn(e,i.startVal,i.endVal-i.startVal,i.duration):i.countDown?i.frameVal=i.startVal-(i.startVal-i.endVal)*(e/i.duration):i.frameVal=i.startVal+(i.endVal-i.startVal)*(e/i.duration),i.countDown?i.frameVal=i.frameVal<i.endVal?i.endVal:i.frameVal:i.frameVal=i.frameVal>i.endVal?i.endVal:i.frameVal,i.frameVal=Math.round(i.frameVal*i.decimalMult)/i.decimalMult,i.printValue(i.frameVal),e<i.duration?i.rAF=requestAnimationFrame(i.count):null!==i.finalEndVal?i.update(i.finalEndVal):i.callback&&i.callback()},this.formatNumber=function(t){var e,a,n,r,o,s=t<0?"-":"";if(e=Math.abs(t).toFixed(i.options.decimalPlaces),n=(a=(e+="").split("."))[0],r=a.length>1?i.options.decimal+a[1]:"",i.options.useGrouping){o="";for(var l=0,c=n.length;l<c;++l)0!==l&&l%3==0&&(o=i.options.separator+o),o=n[c-l-1]+o;n=o}return i.options.numerals&&i.options.numerals.length&&(n=n.replace(/[0-9]/g,(function(t){return i.options.numerals[+t]})),r=r.replace(/[0-9]/g,(function(t){return i.options.numerals[+t]}))),s+i.options.prefix+n+r+i.options.suffix},this.easeOutExpo=function(t,e,a,n){return a*(1-Math.pow(2,-10*t/n))*1024/1023+e},this.options=n(n({},this.defaults),a),this.formattingFn=this.options.formattingFn?this.options.formattingFn:this.formatNumber,this.easingFn=this.options.easingFn?this.options.easingFn:this.easeOutExpo,this.startVal=this.validateValue(this.options.startVal),this.frameVal=this.startVal,this.endVal=this.validateValue(e),this.options.decimalPlaces=Math.max(this.options.decimalPlaces),this.decimalMult=Math.pow(10,this.options.decimalPlaces),this.resetDuration(),this.options.separator=String(this.options.separator),this.useEasing=this.options.useEasing,""===this.options.separator&&(this.options.useGrouping=!1),this.el="string"==typeof t?document.getElementById(t):t,this.el?this.printValue(this.startVal):this.error="[CountUp] target is null or undefined"}return t.prototype.determineDirectionAndSmartEasing=function(){var t=this.finalEndVal?this.finalEndVal:this.endVal;this.countDown=this.startVal>t;var e=t-this.startVal;if(Math.abs(e)>this.options.smartEasingThreshold){this.finalEndVal=t;var a=this.countDown?1:-1;this.endVal=t+a*this.options.smartEasingAmount,this.duration=this.duration/2}else this.endVal=t,this.finalEndVal=null;this.finalEndVal?this.useEasing=!1:this.useEasing=this.options.useEasing},t.prototype.start=function(t){this.error||(this.callback=t,this.duration>0?(this.determineDirectionAndSmartEasing(),this.paused=!1,this.rAF=requestAnimationFrame(this.count)):this.printValue(this.endVal))},t.prototype.pauseResume=function(){this.paused?(this.startTime=null,this.duration=this.remaining,this.startVal=this.frameVal,this.determineDirectionAndSmartEasing(),this.rAF=requestAnimationFrame(this.count)):cancelAnimationFrame(this.rAF),this.paused=!this.paused},t.prototype.reset=function(){cancelAnimationFrame(this.rAF),this.paused=!0,this.resetDuration(),this.startVal=this.validateValue(this.options.startVal),this.frameVal=this.startVal,this.printValue(this.startVal)},t.prototype.update=function(t){cancelAnimationFrame(this.rAF),this.startTime=null,this.endVal=this.validateValue(t),this.endVal!==this.frameVal&&(this.startVal=this.frameVal,this.finalEndVal||this.resetDuration(),this.determineDirectionAndSmartEasing(),this.rAF=requestAnimationFrame(this.count))},t.prototype.printValue=function(t){var e=this.formattingFn(t);"INPUT"===this.el.tagName?this.el.value=e:"text"===this.el.tagName||"tspan"===this.el.tagName?this.el.textContent=e:this.el.innerHTML=e},t.prototype.ensureNumber=function(t){return"number"==typeof t&&!isNaN(t)},t.prototype.validateValue=function(t){var e=Number(t);return this.ensureNumber(e)?e:(this.error="[CountUp] invalid start or end value: "+t,null)},t.prototype.resetDuration=function(){this.startTime=null,this.duration=1e3*Number(this.options.duration),this.remaining=this.duration},t}();var r=window.jQuery,o={organize:{bulkToggle:[],oldParent:null,newParent:null,oldOrder:null,newOrder:null,sortableOptions:{revert:!0,helper:"clone",zIndex:2700,distance:3,opacity:.6,placeholder:"ui-state-highlight",dropOnEmpty:!0,cursor:"crosshair",items:"tbody > tr",start:function(t,e){o.organize.oldParent=r(e.item).parents("table").attr("id")},stop:function(t,e){o.organize.newParent=r(e.item).parents("table").attr("id"),h(r(e.item))}}}};function s(t){r.blockUI.defaults.applyPlatformOpacityRules=!1;var e,a=r('[role="alert"]');if("book"===t)e=PB_OrganizeToken.updating.book;else{var n=t.post_type.replace("-","");e=PB_OrganizeToken.updating[n]}a.children("p").text(e),a.addClass("loading-content").removeClass("visually-hidden"),r.blockUI({message:r(a),baseZ:1e5})}function l(t,e){var a,n=r('[role="alert"]');if("book"===t)a=PB_OrganizeToken[e].book;else{var i=t.post_type.replace("-","");a=PB_OrganizeToken[e][i]}r.unblockUI({onUnblock:function(){n.removeClass("loading-content").addClass("visually-hidden"),n.children("p").text(a)}})}function c(t,e){return"prev"===e?r(t).prev("[id^=part]"):"next"===e?r(t).next("[id^=part]"):void 0}function u(t){return{id:(t=r(t).attr("id").split("_"))[t.length-1],post_type:t[0]}}function p(t){var e=[];return t.children("tbody").children("tr").each((function(t,a){var n=u(r(a));e.push(n.id)})),e}function d(t){t.children("tbody").children("tr").each((function(e,a){var n="",i='<button class="move-up">Move Up</button>',o='<button class="move-down">Move Down</button>';r(a).is("tr:only-of-type")?t.is("[id^=part]")&&t.prev("[id^=part]").length&&t.next("[id^=part]").length?n=" | ".concat(i," | ").concat(o):t.is("[id^=part]")&&t.next("[id^=part]").length?n=" | ".concat(o):t.is("[id^=part]")&&t.prev("[id^=part]").length&&(n=" | ".concat(i)):n=r(a).is("tr:first-of-type")?t.is("[id^=part]")&&t.prev("[id^=part]").length?" | ".concat(i," | ").concat(o):" | ".concat(o):r(a).is("tr:last-of-type")?t.is("[id^=part]")&&t.next("[id^=part]").length?" | ".concat(i," | ").concat(o):" | ".concat(i):" | ".concat(i," | ").concat(o),r(a).children(".has-row-actions").children(".row-title").children(".row-actions").children(".reorder").html(n)}))}function h(t){var e=u(t);r.ajax({url:ajaxurl,type:"POST",data:{action:"pb_reorder",id:e.id,old_order:r("#".concat(o.organize.oldParent)).sortable("serialize"),new_order:r("#".concat(o.organize.newParent)).sortable("serialize"),old_parent:o.organize.oldParent.replace(/^part_([0-9]+)$/i,"$1"),new_parent:o.organize.newParent.replace(/^part_([0-9]+)$/i,"$1"),_ajax_nonce:PB_OrganizeToken.reorderNonce},beforeSend:function(){s(e),o.organize.oldParent!==o.organize.newParent&&d(r("#".concat(o.organize.oldParent))),d(r("#".concat(o.organize.newParent)))},success:function(){l(e,"success")},error:function(){l(e,"failure")}})}function f(t,e,a,n){var o,c,u,p={action:"pb_update_post_visibility",post_ids:t,_ajax_nonce:PB_OrganizeToken.postVisibilityNonce};r.ajax({url:ajaxurl,type:"POST",data:Object.assign(p,(o={},c=a,u=n,c in o?Object.defineProperty(o,c,{value:u,enumerable:!0,configurable:!0,writable:!0}):o[c]=u,o)),beforeSend:function(){s({post_type:e})},success:function(t){l({post_type:e},"success"),function(){var t={action:"pb_update_word_count_for_export",_ajax_nonce:PB_OrganizeToken.wordCountNonce};r.post(ajaxurl,t,(function(t){var e=parseInt(r("#wc-selected-for-export").text(),10);new i("wc-selected-for-export",t,{startVal:e,separator:""}).start()}))}()},error:function(){l({post_type:e},"failure")}})}function g(t,e,a){r.ajax({url:ajaxurl,type:"POST",data:{action:"pb_update_post_title_visibility",post_ids:t,show_title:a,_ajax_nonce:PB_OrganizeToken.showTitleNonce},beforeSend:function(){s({post_type:e})},success:function(t){l({post_type:e},"success")},error:function(){l({post_type:e},"failure")}})}r(document).ready((function(){r(".allow-bulk-operations #front-matter").sortable(o.organize.sortableOptions).disableSelection(),r(".allow-bulk-operations table#back-matter").sortable(o.organize.sortableOptions).disableSelection(),r(".allow-bulk-operations table.chapters").sortable(Object.assign(o.organize.sortableOptions,{connectWith:".chapters"})).disableSelection(),r("input[name=blog_public]").change((function(t){var e,a=r(".publicize-alert"),n=r(".publicize-alert > span");e=1===parseInt(t.currentTarget.value,10)?1:0,r.ajax({url:ajaxurl,type:"POST",data:{action:"pb_update_global_privacy_options",blog_public:e,_ajax_nonce:PB_OrganizeToken.privacyNonce},beforeSend:function(){s("book")},success:function(){0===e?(a.removeClass("public").addClass("private"),n.text(PB_OrganizeToken.bookPrivate)):1===e&&(a.removeClass("private").addClass("public"),n.text(PB_OrganizeToken.bookPublic)),l("book","success")},error:function(){l("book","failure")}})})),r(".web_visibility, .export_visibility").change((function(){var t,e=u(r(this).parents("tr")),a=0;r(this).is(":checked")&&(a=1),r(this).is('[id^="export_visibility"]')?t="export":r(this).is('[id^="web_visibility"]')&&(t="web"),f(e.id,e.post_type,t,a)})),r(".show_title").change((function(t){var e=u(r(t.target).parents("tr")),a="";r(t.currentTarget).is(":checked")&&(a="on"),g(e.id,e.post_type,a)})),r(document).on("click",".move-up",(function(t){var e=r(t.target).parents("tr"),a=r(t.target).parents("table");if(o.organize.oldParent=a.attr("id"),e.is("tr:first-of-type")&&a.is("[id^=part]")&&a.prev("[id^=part]").length){var n=c(a,"prev");o.organize.newParent=n.attr("id"),n.append(e),h(e)}else o.organize.newParent=a.attr("id"),e.prev().before(e),h(e)})),r(document).on("click",".move-down",(function(t){var e=r(t.target).parents("tr"),a=r(t.target).parents("table");if(o.organize.oldParent=a.attr("id"),e.is("tr:last-of-type")&&a.is("[id^=part]")&&a.next("[id^=part]").length){var n=c(a,"next");o.organize.newParent=n.attr("id"),n.prepend(e),h(e)}else o.organize.newParent=a.attr("id"),e.next().after(e),h(e)})),r('.allow-bulk-operations table thead th span[id$="show_title"]').on("click",(function(t){var e=r(t.target).attr("id");e=e.replace("-","");var a=r(t.target).parents("table"),n=a.attr("id").split("_")[0];"part"===n&&(n="chapter");var i=p(a);o.organize.bulkToggle[e]?(a.find('tr td.column-showtitle input[type="checkbox"]').prop("checked",!1),o.organize.bulkToggle[e]=!1,g(i.join(),n,"")):(a.find('tr td.column-showtitle input[type="checkbox"]').prop("checked",!0),o.organize.bulkToggle[e]=!0,g(i.join(),n,"on"))})),r('.allow-bulk-operations table thead th span[id$="visibility"]').on("click",(function(t){var e=r(t.target).attr("id"),a=(e=e.replace("-","")).split("_");a=a[a.length-2];var n=r(t.target).parents("table"),i=n.attr("id").split("_")[0];"part"===i&&(i="chapter");var s=p(n);o.organize.bulkToggle[e]?(n.find("tr td.column-".concat(a," input[type=checkbox]")).prop("checked",!1),o.organize.bulkToggle[e]=!1,f(s.join(),i,a,0)):(n.find("tr td.column-".concat(a,' input[type="checkbox"]')).prop("checked",!0),o.organize.bulkToggle[e]=!0,f(s.join(),i,a,1))})),r(window).on("beforeunload",(function(){if(r.active>0)return"Changes you made may not be saved..."}))}))}});