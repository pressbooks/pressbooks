jQuery((function(e){var t=pictureSize.min,i=jQuery("#contributor-picture"),r=jQuery("#contributor-picture-thumbnail"),a=function(e,i){var r,a,o,n,s,d,u,c,l,p=e.get("width"),m=e.get("height"),g=t,h=t,v=g,w=h;i.set("canSkipCrop",(s=!1,d=g,u=h,c=p,l=m,!!(!0==(n=!1)&&!0===s||!0===n&&u===l||!0===s&&d===c||d===c&&u===l||c<=d))),p/m>1?g=1*(h=m):h=(g=p)/1,r=(p-g)/2,a=(m-h)/2;var y=m>t||p>t;return o={handles:!0,keys:!0,instance:!0,persistent:!0,imageWidth:p,imageHeight:m,minWidth:v>g?g:v,minHeight:w>h?h:w,maxHeight:2*t,maxWidth:2*t,x1:y?r-1:r,y1:y?a-1:a,x2:y?g+r-1:g+r,y2:y?h+a-1:h+a},o.aspectRatio=g+":"+h,o};jQuery(document).ajaxComplete((function(e,t,a){a.data.indexOf("action=add-tag")>=0&&(window.tinyMCE.activeEditor.setContent(""),r.attr("src","").hide(),i.val(""))})),jQuery(document).ajaxSend((function(e,t,i){if(i.data.indexOf("action=add-tag")>=0){window.tinyMCE.triggerSave();var r=new URLSearchParams(i.data);r.set("contributor_description",window.tinyMCE.activeEditor.getContent()),i.data=r.toString()}})),jQuery(".term-description-wrap").remove(),jQuery("#contributor-media-picture").hide(),jQuery("#wpbody-content > div.wrap.nosubsub > form").css("margin",0),jQuery("#btn-media").on("click",(function(e){e.preventDefault(),jQuery("#plupload-browse-button").click()})),jQuery("#plupload-browse-button").on("click",(function(e){var o=wp.media.controller.CustomizeImageCropper.extend({doCrop:function(e){var i=e.get("cropDetails");return i.dstWidth=t,i.dstHeight=t,wp.ajax.post("crop-image",{nonce:e.get("nonces").edit,id:e.get("id"),cropDetails:i})}}),n=wp.media({button:{text:"Done",close:!1},states:[new wp.media.controller.Library({title:"Select a picture",library:wp.media.query({type:"image"}),multiple:!1,date:!1,priority:20,suggestedWidth:t,suggestedHeight:t}),new o({imgSelectOptions:a})]});e.preventDefault(),n.open(),n.on("cropped",(function(e){i.val(e.url),r.attr("src",e.url).show()})),n.on("insert",(function(){var e=n.state().get("selection").first().toJSON();i.val(e.url),r.attr("src",e.url).show()})),n.on("select",(function(){var e=n.state().get("selection").first().toJSON();if(e.width<t||e.height<t){var a='<div class="media-uploader-status errors"><div class="upload-errors"><div class="upload-error">\n<span class="upload-error-filename">Your image is too small.</span><span class="upload-error-message">The image must be '+t+" by "+t+" pixels. Your image is "+e.width+" by "+e.height+" pixels.</span></div></div></div>";jQuery(".media-sidebar").html(a)}else e.width!==t||e.height!==t?n.setState("cropper"):(i.val(e.url),r.attr("src",e.url).show(),n.close())}))}))}));