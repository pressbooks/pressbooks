!function(e){function t(r){if(a[r])return a[r].exports;var n=a[r]={i:r,l:!1,exports:{}};return e[r].call(n.exports,n,n.exports,t),n.l=!0,n.exports}var a={};t.m=e,t.c=a,t.d=function(e,a,r){t.o(e,a)||Object.defineProperty(e,a,{configurable:!1,enumerable:!0,get:r})},t.n=function(e){var a=e&&e.__esModule?function(){return e.default}:function(){return e};return t.d(a,"a",a),a},t.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},t.p="",t(t.s=11)}({11:function(e,t,a){e.exports=a("EO+/")},"EO+/":function(e,t,a){"use strict";Object.defineProperty(t,"__esModule",{value:!0});var r=a("EbL4"),n=a.n(r),o={oldPart:null,newPart:null,defaultOptions:{revert:!0,helper:"clone",zIndex:2700,distance:3,opacity:.6,placeholder:"ui-state-highlight",connectWith:".chapters",dropOnEmpty:!0,cursor:"crosshair",items:"tbody > tr",start:function(e,t){o.oldPart=t.item.parents("table").attr("id")},stop:function(e,t){o.newPart=t.item.parents("table").attr("id"),o.update(t.item)}},frontMatterOptions:{revert:!0,helper:"clone",zIndex:2700,distance:3,opacity:.6,placeholder:"ui-state-highlight",dropOnEmpty:!0,cursor:"crosshair",items:"tbody > tr",start:function(e,t){},stop:function(e,t){o.fmupdate(t.item)}},backMatterOptions:{revert:!0,helper:"clone",zIndex:2700,distance:3,opacity:.6,placeholder:"ui-state-highlight",dropOnEmpty:!0,cursor:"crosshair",items:"tbody > tr",start:function(e,t){},stop:function(e,t){o.bmupdate(t.item)}},update:function(e){jQuery.ajax({beforeSend:function(){jQuery.blockUI.defaults.applyPlatformOpacityRules=!1,jQuery.blockUI({message:jQuery("#loader.chapter")})},url:ajaxurl,type:"POST",data:{action:"pb_update_chapter",new_part_order:jQuery("#"+o.newPart).sortable("serialize"),old_part_order:jQuery("#"+o.oldPart).sortable("serialize"),new_part:o.newPart.replace(/^part-([0-9]+)$/i,"$1"),old_part:o.oldPart.replace(/^part-([0-9]+)$/i,"$1"),id:jQuery(e).attr("id").replace(/^chapter-([0-9]+)$/i,"$1"),_ajax_nonce:PB_OrganizeToken.orderNonce},cache:!1,dataType:"html",error:function(e,t,a){jQuery("#message").html('<p><strong>There has been an error updating your chapter data. Usually, <a href="'+window.location.href+'">refreshing the page</a> helps.</strong></p>').addClass("error")},success:function(e){"NOCHANGE"===e&&jQuery("#message").html("<p><strong>No changes were registered.</strong></p>").addClass("error")},complete:function(){jQuery.unblockUI()}})},fmupdate:function(e){jQuery.ajax({beforeSend:function(){jQuery.blockUI.defaults.applyPlatformOpacityRules=!1,jQuery.blockUI({message:jQuery("#loader.front-matter")})},url:ajaxurl,type:"POST",data:{action:"pb_update_front_matter",front_matter_order:jQuery("#front-matter").sortable("serialize"),_ajax_nonce:PB_OrganizeToken.orderNonce},cache:!1,dataType:"html",error:function(e,t,a){jQuery("#message").html('<p><strong>There has been an error updating your front matter data Usually, <a href="'+window.location.href+'">refreshing the page</a> helps.</strong></p>').addClass("error")},success:function(e){"NOCHANGE"===e&&jQuery("#message").html("<p><strong>No changes were registered.</strong></p>").addClass("error")},complete:function(){jQuery.unblockUI()}})},bmupdate:function(e){jQuery.ajax({beforeSend:function(){jQuery.blockUI.defaults.applyPlatformOpacityRules=!1,jQuery.blockUI({message:jQuery("#loader.back-matter")})},url:ajaxurl,type:"POST",data:{action:"pb_update_back_matter",back_matter_order:jQuery("#back-matter").sortable("serialize"),_ajax_nonce:PB_OrganizeToken.orderNonce},cache:!1,dataType:"html",error:function(e,t,a){jQuery("#message").html('<p><strong>There has been an error updating your back matter data. Usually, <a href="'+window.location.href+'">refreshing the page</a> helps.</strong></p>').addClass("error")},success:function(e){"NOCHANGE"===e&&jQuery("#message").html("<p><strong>No changes were registered.</strong></p>").addClass("error")},complete:function(){jQuery.unblockUI()}})}};jQuery(document).ready(function(e){function t(t){return e(t).parent().parent().parent().parent().parent()}function a(e){var t=void 0;return"chapter"===e.post_type?t=new wp.api.models.Chapters({id:e.id}):"front-matter"===e.post_type?t=new wp.api.models.FrontMatter({id:e.id}):"back-matter"===e.post_type?t=new wp.api.models.BackMatter({id:e.id}):"part"===e.post_type&&(t=new wp.api.models.Parts({id:e.id})),t}function r(e,t){e=(e=e.attr("id").split("_"))[e.length-1],t=(t=t.attr("id").split("_"))[t.length-1];var a=new wp.api.models.Chapters({id:e});a.fetch({success:function(e,r,n){a.save({part:t},{patch:!0})}})}function i(t,a){return"prev"===a?e(t).prev("[id^=part]"):"next"===a?e(t).next("[id^=part]"):void 0}function s(t){t.children("tbody").children("tr").each(function(t,r){var n=e(r).attr("id").split("_"),o=a(n={id:n[n.length-1],post_type:n[0],menu_order:t});o.fetch({success:function(e,t,a){o.save({menu_order:n.menu_order},{patch:!0})}}),t++})}function l(t){t.children("tbody").children("tr").each(function(a,r){var n="",o='<button class="move-up">Move Up</button>',i='<button class="move-down">Move Down</button>';n=e(r).is("tr:first-of-type")?t.prev("[id^=part]").length?" | "+o+" | "+i:" | "+i:e(r).is("tr:last-of-type")?e(t).next("[id^=part]").length?" | "+o+" | "+i:" | "+o:" | "+o+" | "+i,e(r).children(".has-row-actions").children(".row-title").children(".row-actions").children(".reorder").html(n)})}e("table.chapters").sortable(o.defaultOptions).disableSelection(),e("table#front-matter").sortable(o.frontMatterOptions).disableSelection(),e("table#back-matter").sortable(o.backMatterOptions).disableSelection(),e("input[name=blog_public]").change(function(){var t=void 0;t=1===parseInt(this.value,10)?1:0,e.ajax({url:ajaxurl,type:"POST",data:{action:"pb_update_global_privacy_options",blog_public:t,_ajax_nonce:PB_OrganizeToken.privacyNonce},beforeSend:function(){0===t?(e("h4.publicize-alert > span").text(PB_OrganizeToken.private),e(".publicize-alert").removeClass("public").addClass("private")):1===t&&(e("h4.publicize-alert > span").text(PB_OrganizeToken.public),e(".publicize-alert").removeClass("private").addClass("public"))},error:function(e,t,a){}})}),e(".web_visibility, .export_visibility").change(function(t){var r=e(t.target).parent().parent().attr("id").split("_");r={id:r[r.length-1],post_type:r[0]};var o=e("#export_visibility_"+r.id),i=e("#web_visibility_"+r.id),s=void 0;s=i.is(":checked")?o.is(":checked")?"publish":"web-only":o.is(":checked")?"private":"draft";var l=a(r);l.fetch({success:function(t,a,r){l.save({status:s},{patch:!0,success:function(){!function(){var t={action:"pb_update_word_count_for_export",_ajax_nonce:PB_OrganizeToken.wordCountNonce};e.post(ajaxurl,t,function(t){var a=parseInt(e("#wc-selected-for-export").text(),10);new n.a("wc-selected-for-export",a,t,0,2.5,{separator:""}).start()})}()}})}})}),e(".show_title").change(function(t){var r=e(t.target).parent().parent().attr("id");r={id:(r=r.split("_"))[r.length-1],post_type:r[0]};var n="";e(t.target).is(":checked")&&(n="on");var o=a(r);o.fetch({success:function(e,t,a){o.save({meta:{pb_show_title:n}},{patch:!0})}})}),e(document).on("click",".move-up",function(a){jQuery.blockUI.defaults.applyPlatformOpacityRules=!1,jQuery.blockUI({message:jQuery("#loader.chapter")});var n=t(a.target),o=e(n).parent().parent();if(e(n).is("tr:first-of-type")&&o.is("[id^=part]")&&o.prev("[id^=part]").length){var c=i(o,"prev");c.append(n),r(n,c),s(o),s(c),l(o),l(c)}else n.prev().before(n),s(o),l(o);jQuery.unblockUI()}),e(document).on("click",".move-down",function(a){jQuery.blockUI.defaults.applyPlatformOpacityRules=!1,jQuery.blockUI({message:jQuery("#loader.chapter")});var n=t(a.target),o=e(n).parent().parent();if(e(n).is("tr:last-of-type")&&o.is("[id^=part]")&&o.next("[id^=part]").length){var c=i(o,"next");c.prepend(n),r(n,c),s(o),s(c),l(o),l(c)}else n.next().after(n),s(o),l(o);jQuery.unblockUI()});var c=[];e("table thead th").click(function(){var t=e(this).index()+1,a=e(this).parents("table").index()+"_"+t;c[a]?(e(this).parents("table").find("tr td:nth-of-type("+t+")").find("input[type=checkbox]:checked").click(),c[a]=!1):(e(this).parents("table").find("tr td:nth-of-type("+t+")").find("input[type=checkbox]:not(:checked)").click(),c[a]=!0)}),e(window).on("beforeunload",function(){if(e.active>0)return"Changes you made may not be saved..."})})},EbL4:function(e,t,a){var r,n;!function(o,i){void 0===(n="function"==typeof(r=i)?r.call(t,a,t,e):r)||(e.exports=n)}(0,function(e,t,a){return function(e,t,a,r,n,o){function i(e){return"number"==typeof e&&!isNaN(e)}var s=this;if(s.version=function(){return"1.9.3"},s.options={useEasing:!0,useGrouping:!0,separator:",",decimal:".",easingFn:function(e,t,a,r){return a*(1-Math.pow(2,-10*e/r))*1024/1023+t},formattingFn:function(e){var t,a,r,n,o,i,l=e<0;if(e=Math.abs(e).toFixed(s.decimals),e+="",t=e.split("."),a=t[0],r=t.length>1?s.options.decimal+t[1]:"",s.options.useGrouping){for(n="",o=0,i=a.length;o<i;++o)0!==o&&o%3==0&&(n=s.options.separator+n),n=a[i-o-1]+n;a=n}return s.options.numerals.length&&(a=a.replace(/[0-9]/g,function(e){return s.options.numerals[+e]}),r=r.replace(/[0-9]/g,function(e){return s.options.numerals[+e]})),(l?"-":"")+s.options.prefix+a+r+s.options.suffix},prefix:"",suffix:"",numerals:[]},o&&"object"==typeof o)for(var l in s.options)o.hasOwnProperty(l)&&null!==o[l]&&(s.options[l]=o[l]);""===s.options.separator?s.options.useGrouping=!1:s.options.separator=""+s.options.separator;for(var c=0,u=["webkit","moz","ms","o"],p=0;p<u.length&&!window.requestAnimationFrame;++p)window.requestAnimationFrame=window[u[p]+"RequestAnimationFrame"],window.cancelAnimationFrame=window[u[p]+"CancelAnimationFrame"]||window[u[p]+"CancelRequestAnimationFrame"];window.requestAnimationFrame||(window.requestAnimationFrame=function(e,t){var a=(new Date).getTime(),r=Math.max(0,16-(a-c)),n=window.setTimeout(function(){e(a+r)},r);return c=a+r,n}),window.cancelAnimationFrame||(window.cancelAnimationFrame=function(e){clearTimeout(e)}),s.initialize=function(){return!(!s.initialized&&(s.error="",s.d="string"==typeof e?document.getElementById(e):e,s.d?(s.startVal=Number(t),s.endVal=Number(a),i(s.startVal)&&i(s.endVal)?(s.decimals=Math.max(0,r||0),s.dec=Math.pow(10,s.decimals),s.duration=1e3*Number(n)||2e3,s.countDown=s.startVal>s.endVal,s.frameVal=s.startVal,s.initialized=!0,0):(s.error="[CountUp] startVal ("+t+") or endVal ("+a+") is not a number",1)):(s.error="[CountUp] target is null or undefined",1)))},s.printValue=function(e){var t=s.options.formattingFn(e);"INPUT"===s.d.tagName?this.d.value=t:"text"===s.d.tagName||"tspan"===s.d.tagName?this.d.textContent=t:this.d.innerHTML=t},s.count=function(e){s.startTime||(s.startTime=e),s.timestamp=e;var t=e-s.startTime;s.remaining=s.duration-t,s.options.useEasing?s.countDown?s.frameVal=s.startVal-s.options.easingFn(t,0,s.startVal-s.endVal,s.duration):s.frameVal=s.options.easingFn(t,s.startVal,s.endVal-s.startVal,s.duration):s.countDown?s.frameVal=s.startVal-(s.startVal-s.endVal)*(t/s.duration):s.frameVal=s.startVal+(s.endVal-s.startVal)*(t/s.duration),s.countDown?s.frameVal=s.frameVal<s.endVal?s.endVal:s.frameVal:s.frameVal=s.frameVal>s.endVal?s.endVal:s.frameVal,s.frameVal=Math.round(s.frameVal*s.dec)/s.dec,s.printValue(s.frameVal),t<s.duration?s.rAF=requestAnimationFrame(s.count):s.callback&&s.callback()},s.start=function(e){s.initialize()&&(s.callback=e,s.rAF=requestAnimationFrame(s.count))},s.pauseResume=function(){s.paused?(s.paused=!1,delete s.startTime,s.duration=s.remaining,s.startVal=s.frameVal,requestAnimationFrame(s.count)):(s.paused=!0,cancelAnimationFrame(s.rAF))},s.reset=function(){s.paused=!1,delete s.startTime,s.initialized=!1,s.initialize()&&(cancelAnimationFrame(s.rAF),s.printValue(s.startVal))},s.update=function(e){if(s.initialize()){if(e=Number(e),!i(e))return void(s.error="[CountUp] update() - new endVal is not a number: "+e);s.error="",e!==s.frameVal&&(cancelAnimationFrame(s.rAF),s.paused=!1,delete s.startTime,s.startVal=s.frameVal,s.endVal=e,s.countDown=s.startVal>s.endVal,s.rAF=requestAnimationFrame(s.count))}},s.initialize()&&s.printValue(s.startVal)}})}});