!function(e){function t(s){if(n[s])return n[s].exports;var a=n[s]={i:s,l:!1,exports:{}};return e[s].call(a.exports,a,a.exports,t),a.l=!0,a.exports}var n={};t.m=e,t.c=n,t.d=function(e,n,s){t.o(e,n)||Object.defineProperty(e,n,{configurable:!1,enumerable:!0,get:s})},t.n=function(e){var n=e&&e.__esModule?function(){return e.default}:function(){return e};return t.d(n,"a",n),n},t.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},t.p="",t(t.s=16)}({16:function(e,t,n){e.exports=n("p1xW")},p1xW:function(e,t){tinymce.PluginManager.add("textboxes",function(e){function t(){var t=e.selection.getNode();e.windowManager.open({title:e.getLang("strings.customtextbox"),body:{type:"textbox",name:"className",size:40,label:e.getLang("strings.classtitle"),value:t.name||t.id},onsubmit:function(t){e.execCommand("mceReplaceContent",!1,'<div class="textbox '+t.data.className+'">{$selection}</div>')}})}e.addButton("textboxes",{type:"menubutton",text:e.getLang("strings.textboxes"),icon:!1,menu:[{text:e.getLang("strings.standard"),onclick:function(){var t=e.selection.getContent();""!==t?e.execCommand("mceReplaceContent",!1,'<div class="textbox">'+t+"</div><p></p>"):e.execCommand("mceInsertContent",0,'<div class="textbox">'+e.getLang("strings.standardplaceholder")+"</div><p></p>")}},{text:e.getLang("strings.shaded"),onclick:function(){var t=e.selection.getContent();""!==t?e.execCommand("mceReplaceContent",!1,'<div class="textbox shaded">'+t+"</div><p></p>"):e.execCommand("mceInsertContent",0,'<div class="textbox shaded">'+e.getLang("strings.standardplaceholder")+"</div><p></p>")}},{text:e.getLang("strings.learningobjectives"),onclick:function(){var t=e.selection.getContent();""!==t?e.execCommand("mceReplaceContent",!1,'<div class="textbox learning-objectives"><h3 itemprop="educationalUse">'+e.getLang("strings.learningobjectives")+"</h3>\n"+t+"</div><p></p>"):e.execCommand("mceInsertContent",0,'<div class="textbox learning-objectives"><h3 itemprop="educationalUse">'+e.getLang("strings.learningobjectives")+"</h3>\n<p>"+e.getLang("strings.learningobjectivesplaceholder")+"</p><ul><li>"+e.getLang("strings.first")+"</li><li>"+e.getLang("strings.second")+"</li></ul></div><p></p>")}},{text:e.getLang("strings.keytakeaways"),onclick:function(){var t=e.selection.getContent();""!==t?e.execCommand("mceReplaceContent",!1,'<div class="textbox key-takeaways"><h3 itemprop="educationalUse">'+e.getLang("strings.keytakeaways")+"</h3>\n"+t+"</div><p></p>"):e.execCommand("mceInsertContent",0,'<div class="textbox key-takeaways"><h3 itemprop="educationalUse">'+e.getLang("strings.keytakeaways")+"</h3>\n<p>"+e.getLang("strings.keytakeawaysplaceholder")+"</p><ul><li>"+e.getLang("strings.first")+"</li><li>"+e.getLang("strings.second")+"</li></ul></div><p></p>")}},{text:e.getLang("strings.exercises"),onclick:function(){var t=e.selection.getContent();""!==t?e.execCommand("mceReplaceContent",!1,'<div class="textbox exercises"><h3 itemprop="educationalUse">'+e.getLang("strings.exercises")+"</h3>\n"+t+"</div><p></p>"):e.execCommand("mceInsertContent",0,'<div class="textbox exercises"><h3 itemprop="educationalUse">'+e.getLang("strings.exercises")+"</h3>\n<p>"+e.getLang("strings.exercisesplaceholder")+"</p><ul><li>"+e.getLang("strings.first")+"</li><li>"+e.getLang("strings.second")+"</li></ul></div><p></p>")}},{text:e.getLang("strings.examples"),onclick:function(){var t=e.selection.getContent();""!==t?e.execCommand("mceReplaceContent",!1,'<div class="textbox examples"><h3 itemprop="educationalUse">'+e.getLang("strings.examples")+"</h3>\n"+t+"</div><p></p>"):e.execCommand("mceInsertContent",0,'<div class="textbox examples"><h3 itemprop="educationalUse">'+e.getLang("strings.examples")+"</h3>\n<p>"+e.getLang("strings.examplesplaceholder")+"</p><ul><li>"+e.getLang("strings.first")+"</li><li>"+e.getLang("strings.second")+"</li></ul></div><p></p>")}},{text:e.getLang("strings.customellipses"),onclick:function(){t()}}]})})}});