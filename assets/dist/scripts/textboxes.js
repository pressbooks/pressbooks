tinymce.PluginManager.add("textboxes",(function(e){function t(e,t,n){return'<div class="textbox textbox--'.concat(e,'"><header class="textbox__header"><p class="textbox__title">').concat(t,'</p></header>\n<div class="textbox__content">').concat(n,"</div></div><p></p>")}function n(e,t,n,a,s){return'<div class="textbox textbox--'.concat(e,'"><header class="textbox__header"><p class="textbox__title">').concat(t,'</p></header>\n<div class="textbox__content"><p>').concat(n,"</p><ul><li>").concat(a,"</li><li>").concat(s,"</li></ul></div></div><p></p>")}function a(e,t,n){return'<div class="textbox textbox--sidebar textbox--'.concat(e,'"><header class="textbox__header"><p class="textbox__title">').concat(t,'</p></header>\n<div class="textbox__content">').concat(n,"</div></div><p></p>")}function s(e,t,n,a,s){return'<div class="textbox textbox--sidebar textbox--'.concat(e,'"><header class="textbox__header"><p class="textbox__title">').concat(t,'</p></header>\n<div class="textbox__content"><p>').concat(n,"</p><ul><li>").concat(a,"</li><li>").concat(s,"</li></ul></div></div><p></p>")}e.addButton("textboxes",{type:"menubutton",text:e.getLang("strings.textboxes"),icon:!1,menu:[{text:e.getLang("strings.standard"),onclick:function(){var t=e.selection.getContent();""!==t?e.execCommand("mceReplaceContent",!1,'<div class="textbox"><p>'.concat(t,"</p></div><p></p>")):e.execCommand("mceInsertContent",0,'<div class="textbox"><p>'.concat(e.getLang("strings.standardplaceholder"),"</p></div><p></p>"))}},{text:e.getLang("strings.standardsidebar"),onclick:function(){var t=e.selection.getContent();""!==t?e.execCommand("mceReplaceContent",!1,'<div class="textbox textbox--sidebar"><p>'.concat(t,"</p></div><p></p>")):e.execCommand("mceInsertContent",0,'<div class="textbox textbox--sidebar"><p>'.concat(e.getLang("strings.standardplaceholder"),"</p></div><p></p>"))}},{text:e.getLang("strings.shaded"),onclick:function(){var t=e.selection.getContent();""!==t?e.execCommand("mceReplaceContent",!1,'<div class="textbox shaded"><p>'.concat(t,"</p></div><p></p>")):e.execCommand("mceInsertContent",0,'<div class="textbox shaded"><p>'.concat(e.getLang("strings.standardplaceholder"),"</p></div><p></p>"))}},{text:e.getLang("strings.shadedsidebar"),onclick:function(){var t=e.selection.getContent();""!==t?e.execCommand("mceReplaceContent",!1,'<div class="textbox textbox--sidebar shaded"><p>'.concat(t,"</p></div><p></p>")):e.execCommand("mceInsertContent",0,'<div class="textbox textbox--sidebar shaded"><p>'.concat(e.getLang("strings.standardplaceholder"),"</p></div><p></p>"))}},{text:e.getLang("strings.examples"),onclick:function(){var a="examples",s=e.selection.getContent(),c=e.getLang("strings.".concat(a)),o=e.getLang("strings.".concat(a,"placeholder")),i=e.getLang("strings.first"),g=e.getLang("strings.second");""!==s?e.execCommand("mceReplaceContent",!1,t(a,c,s)):e.execCommand("mceInsertContent",0,n(a,c,o,i,g))}},{text:e.getLang("strings.examplessidebar"),onclick:function(){var t="examples",n=e.selection.getContent(),c=e.getLang("strings.".concat(t,"sidebar")),o=e.getLang("strings.".concat(t,"placeholder")),i=e.getLang("strings.first"),g=e.getLang("strings.second");""!==n?e.execCommand("mceReplaceContent",!1,a(t,c,n)):e.execCommand("mceInsertContent",0,s(t,c,o,i,g))}},{text:e.getLang("strings.exercises"),onclick:function(){var a="exercises",s=e.selection.getContent(),c=e.getLang("strings.".concat(a)),o=e.getLang("strings.".concat(a,"placeholder")),i=e.getLang("strings.first"),g=e.getLang("strings.second");""!==s?e.execCommand("mceReplaceContent",!1,t(a,c,s)):e.execCommand("mceInsertContent",0,n(a,c,o,i,g))}},{text:e.getLang("strings.exercisessidebar"),onclick:function(){var t="exercises",n=e.selection.getContent(),c=e.getLang("strings.".concat(t,"sidebar")),o=e.getLang("strings.".concat(t,"placeholder")),i=e.getLang("strings.first"),g=e.getLang("strings.second");""!==n?e.execCommand("mceReplaceContent",!1,a(t,c,n)):e.execCommand("mceInsertContent",0,s(t,c,o,i,g))}},{text:e.getLang("strings.keytakeaways"),onclick:function(){var a="key-takeaways",s=e.selection.getContent(),c=e.getLang("strings.keytakeaways"),o=e.getLang("strings.keytakeawaysplaceholder"),i=e.getLang("strings.first"),g=e.getLang("strings.second");""!==s?e.execCommand("mceReplaceContent",!1,t(a,c,s)):e.execCommand("mceInsertContent",0,n(a,c,o,i,g))}},{text:e.getLang("strings.keytakeawayssidebar"),onclick:function(){var t="key-takeaways",n=e.selection.getContent(),c=e.getLang("strings.keytakeawayssidebar"),o=e.getLang("strings.keytakeawaysplaceholder"),i=e.getLang("strings.first"),g=e.getLang("strings.second");""!==n?e.execCommand("mceReplaceContent",!1,a(t,c,n)):e.execCommand("mceInsertContent",0,s(t,c,o,i,g))}},{text:e.getLang("strings.learningobjectives"),onclick:function(){var a="learning-objectives",s=e.selection.getContent(),c=e.getLang("strings.learningobjectives"),o=e.getLang("strings.learningobjectivesplaceholder"),i=e.getLang("strings.first"),g=e.getLang("strings.second");""!==s?e.execCommand("mceReplaceContent",!1,t(a,c,s)):e.execCommand("mceInsertContent",0,n(a,c,o,i,g))}},{text:e.getLang("strings.learningobjectivessidebar"),onclick:function(){var t="learning-objectives",n=e.selection.getContent(),c=e.getLang("strings.learningobjectivessidebar"),o=e.getLang("strings.learningobjectivesplaceholder"),i=e.getLang("strings.first"),g=e.getLang("strings.second");""!==n?e.execCommand("mceReplaceContent",!1,a(t,c,n)):e.execCommand("mceInsertContent",0,s(t,c,o,i,g))}},{text:e.getLang("strings.customellipses"),onclick:function(){var t,n;t=e.selection.getNode(),n=e.selection.getContent(),e.windowManager.open({title:e.getLang("strings.customtextbox"),body:{type:"textbox",name:"className",size:40,label:e.getLang("strings.classtitle"),value:t.name||t.id},onsubmit:function(t){e.execCommand("mceReplaceContent",!1,'<div class="textbox '.concat(t.data.className,'"><p>').concat(n,"</p></div>"))}})}}]})}));