!function(e){var n={};function r(t){if(n[t])return n[t].exports;var o=n[t]={i:t,l:!1,exports:{}};return e[t].call(o.exports,o,o.exports,r),o.l=!0,o.exports}r.m=e,r.c=n,r.d=function(e,n,t){r.o(e,n)||Object.defineProperty(e,n,{enumerable:!0,get:t})},r.r=function(e){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},r.t=function(e,n){if(1&n&&(e=r(e)),8&n)return e;if(4&n&&"object"==typeof e&&e&&e.__esModule)return e;var t=Object.create(null);if(r.r(t),Object.defineProperty(t,"default",{enumerable:!0,value:e}),2&n&&"string"!=typeof e)for(var o in e)r.d(t,o,function(n){return e[n]}.bind(null,o));return t},r.n=function(e){var n=e&&e.__esModule?function(){return e.default}:function(){return e};return r.d(n,"a",n),n},r.o=function(e,n){return Object.prototype.hasOwnProperty.call(e,n)},r.p="/",r(r.s=4)}({4:function(e,n,r){e.exports=r("dvui")},dvui:function(e,n){jQuery(function(e){e(document).ready(function(){var n;e(".front-background-image-upload-button").click(function(r){r.preventDefault(),n||(n=wp.media.frames.file_frame=wp.media({multiple:!1})).on("select",function(){var r=n.state().get("selection").first().toJSON();e("#front_background_image").val(r.url),e(".front-background-image").attr("src",r.url),e(".front-background-image-preview-wrap").removeClass("hidden"),e(".front-background-image-upload-button, .front-background-image-description").addClass("hidden")}),n.open()})}),e(".delete-front-background-image").on("click",function(){e("#front_background_image").val(""),e(".front-background-image-preview-wrap").addClass("hidden"),e(".front-background-image-upload-button, .front-background-image-description").removeClass("hidden")});var n=e("#ppi"),r=e("#custom_ppi");""!==n.val()&&r.parent().parent().hide(),n.on("change",function(){""===e(this).val()?r.parent().parent().show():(r.parent().parent().hide(),r.val(e(this).val()))}),e(".colorpicker").wpColorPicker();var t=e("#pb-sse-progressbar"),o=e("#pb-sse-info"),a=e("#generate-pdf"),i=e("#generate-jpg"),u=null,l=e("#pb-sse-seconds"),c=e("#pb-sse-minutes");function d(e){return e>9?e:"0"+e}var s=function(n){var r=e("form."+n),a=PB_CoverGeneratorToken.ajaxUrl+(PB_CoverGeneratorToken.ajaxUrl.includes("?")?"&":"?")+e.param(r.find(":input")),i=new EventSource(a);i.onopen=function(){e(window).on("beforeunload",function(){return PB_CoverGeneratorToken.unloadWarning})},i.onmessage=function(n){var r=JSON.parse(n.data);switch(r.action){case"updateStatusBar":t.progressbar({value:parseInt(r.percentage,10)}),o.html(r.info);break;case"complete":i.close(),e(window).unbind("beforeunload"),r.error?(t.progressbar({value:!1}),o.html(r.error+" "+PB_CoverGeneratorToken.reloadSnippet),u&&clearInterval(u)):window.location=PB_CoverGeneratorToken.redirectUrl}},i.onerror=function(){i.close(),t.progressbar({value:!1}),o.html("EventStream Connection Error "+PB_CoverGeneratorToken.reloadSnippet),e(window).unbind("beforeunload"),u&&clearInterval(u)}};e(".settings-form").on("saveAndGenerate",function(n,r){var f;return a.hide(),i.hide(),f=0,l.html("00"),c.html("00:"),u=setInterval(function(){l.html(d(++f%60)),c.html(d(parseInt(f/60,10))+":")},1e3),t.progressbar({value:10}),o.html(PB_CoverGeneratorToken.ajaxSubmitMsg),e(this).ajaxSubmit({done:s(r),timeout:5e3}),!1}),a.click(function(){var n=tinymce.get("pb_about_unlimited");if(n){var r=n.getContent();e("#pb_about_unlimited").val(r)}e("form.settings-form").trigger("saveAndGenerate",["pdf"])}),i.click(function(){e("form.settings-form").trigger("saveAndGenerate",["jpg"])})})}});