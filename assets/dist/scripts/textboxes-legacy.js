!function(e){var t={};function n(s){if(t[s])return t[s].exports;var i=t[s]={i:s,l:!1,exports:{}};return e[s].call(i.exports,i,i.exports,n),i.l=!0,i.exports}n.m=e,n.c=t,n.d=function(e,t,s){n.o(e,t)||Object.defineProperty(e,t,{enumerable:!0,get:s})},n.r=function(e){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},n.t=function(e,t){if(1&t&&(e=n(e)),8&t)return e;if(4&t&&"object"==typeof e&&e&&e.__esModule)return e;var s=Object.create(null);if(n.r(s),Object.defineProperty(s,"default",{enumerable:!0,value:e}),2&t&&"string"!=typeof e)for(var i in e)n.d(s,i,function(t){return e[t]}.bind(null,i));return s},n.n=function(e){var t=e&&e.__esModule?function(){return e.default}:function(){return e};return n.d(t,"a",t),t},n.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},n.p="/",n(n.s=23)}({23:function(e,t,n){e.exports=n("zghh")},zghh:function(e,t){tinymce.PluginManager.add("textboxes",function(e){e.addButton("textboxes",{type:"menubutton",text:e.getLang("strings.textboxes"),icon:!1,menu:[{text:e.getLang("strings.standard"),onclick:function(){var t=e.selection.getContent();""!==t?e.execCommand("mceReplaceContent",!1,'<div class="textbox">'+t+"</div><p></p>"):e.execCommand("mceInsertContent",0,'<div class="textbox">'+e.getLang("strings.standardplaceholder")+"</div><p></p>")}},{text:e.getLang("strings.shaded"),onclick:function(){var t=e.selection.getContent();""!==t?e.execCommand("mceReplaceContent",!1,'<div class="textbox shaded">'+t+"</div><p></p>"):e.execCommand("mceInsertContent",0,'<div class="textbox shaded">'+e.getLang("strings.standardplaceholder")+"</div><p></p>")}},{text:e.getLang("strings.learningobjectives"),onclick:function(){var t=e.selection.getContent();""!==t?e.execCommand("mceReplaceContent",!1,'<div class="textbox learning-objectives"><h3 itemprop="educationalUse">'+e.getLang("strings.learningobjectives")+"</h3>\n"+t+"</div><p></p>"):e.execCommand("mceInsertContent",0,'<div class="textbox learning-objectives"><h3 itemprop="educationalUse">'+e.getLang("strings.learningobjectives")+"</h3>\n<p>"+e.getLang("strings.learningobjectivesplaceholder")+"</p><ul><li>"+e.getLang("strings.first")+"</li><li>"+e.getLang("strings.second")+"</li></ul></div><p></p>")}},{text:e.getLang("strings.keytakeaways"),onclick:function(){var t=e.selection.getContent();""!==t?e.execCommand("mceReplaceContent",!1,'<div class="textbox key-takeaways"><h3 itemprop="educationalUse">'+e.getLang("strings.keytakeaways")+"</h3>\n"+t+"</div><p></p>"):e.execCommand("mceInsertContent",0,'<div class="textbox key-takeaways"><h3 itemprop="educationalUse">'+e.getLang("strings.keytakeaways")+"</h3>\n<p>"+e.getLang("strings.keytakeawaysplaceholder")+"</p><ul><li>"+e.getLang("strings.first")+"</li><li>"+e.getLang("strings.second")+"</li></ul></div><p></p>")}},{text:e.getLang("strings.exercises"),onclick:function(){var t=e.selection.getContent();""!==t?e.execCommand("mceReplaceContent",!1,'<div class="textbox exercises"><h3 itemprop="educationalUse">'+e.getLang("strings.exercises")+"</h3>\n"+t+"</div><p></p>"):e.execCommand("mceInsertContent",0,'<div class="textbox exercises"><h3 itemprop="educationalUse">'+e.getLang("strings.exercises")+"</h3>\n<p>"+e.getLang("strings.exercisesplaceholder")+"</p><ul><li>"+e.getLang("strings.first")+"</li><li>"+e.getLang("strings.second")+"</li></ul></div><p></p>")}},{text:e.getLang("strings.examples"),onclick:function(){var t=e.selection.getContent();""!==t?e.execCommand("mceReplaceContent",!1,'<div class="textbox examples"><h3 itemprop="educationalUse">'+e.getLang("strings.examples")+"</h3>\n"+t+"</div><p></p>"):e.execCommand("mceInsertContent",0,'<div class="textbox examples"><h3 itemprop="educationalUse">'+e.getLang("strings.examples")+"</h3>\n<p>"+e.getLang("strings.examplesplaceholder")+"</p><ul><li>"+e.getLang("strings.first")+"</li><li>"+e.getLang("strings.second")+"</li></ul></div><p></p>")}},{text:e.getLang("strings.customellipses"),onclick:function(){var t;t=e.selection.getNode(),e.windowManager.open({title:e.getLang("strings.customtextbox"),body:{type:"textbox",name:"className",size:40,label:e.getLang("strings.classtitle"),value:t.name||t.id},onsubmit:function(t){e.execCommand("mceReplaceContent",!1,'<div class="textbox '+t.data.className+'">{$selection}</div>')}})}}]})})}});