!function(t){var e={};function n(r){if(e[r])return e[r].exports;var a=e[r]={i:r,l:!1,exports:{}};return t[r].call(a.exports,a,a.exports,n),a.l=!0,a.exports}n.m=t,n.c=e,n.d=function(t,e,r){n.o(t,e)||Object.defineProperty(t,e,{configurable:!1,enumerable:!0,get:r})},n.n=function(t){var e=t&&t.__esModule?function(){return t.default}:function(){return t};return n.d(e,"a",e),e},n.o=function(t,e){return Object.prototype.hasOwnProperty.call(t,e)},n.p="",n(n.s=23)}({23:function(t,e,n){t.exports=n("ADpY")},ADpY:function(t,e){tinymce.create("tinymce.plugins.glossary",{init:function(t,e){var n=jQuery.parseJSON(PB_GlossaryToken.glossary_terms);t.addButton("glossary_all",{title:PB_GlossaryToken.glossary_all_title,text:"Glossary",icon:!1,onclick:function(){t.selection.setContent("[pb_glossary]")}}),t.addButton("glossary",{title:PB_GlossaryToken.glossary_title,text:"GL",icon:!1,onclick:function(){var e=t.selection.getContent(),r=function(t){for(var e in n)if(n.hasOwnProperty(e)&&n[e].text.toLowerCase().trim()===t.toLowerCase().trim())return n[e].value;return""}(e),a="",o=void 0;r?o=1:(o=0,e&&(a='Glossary term <b>"'+e+'"</b> not found. Please create it.')),tinymce.activeEditor.windowManager.open({title:"Glossary Terms",bodyType:"tabpanel",body:[{title:"Create and Insert Term",type:"form",items:[{type:"container",name:"container",html:a},{name:"title",type:"textbox",label:"Title"},{name:"body",type:"textbox",label:"Description",multiline:!0,minHeight:100}]},{title:"Choose Existing Term",type:"form",items:[{type:"listbox",name:"term",label:"Select a Term",values:n,value:r}]}],buttons:[{text:"Cancel",onclick:"close"},{text:"Insert",subtype:"primary",onclick:"submit"}],onsubmit:function(r){if("t0"===this.find("tabpanel")[0].activeTabId)alert("TODO: Create and Insert Term");else{if(!r.data.term||0===r.data.term.length)return alert("A term was not selected?"),!1;""!==e?t.selection.setContent('[pb_glossary id="'+r.data.term+'"]'+e+"[/pb_glossary]"):t.selection.setContent('[pb_glossary id="'+r.data.term+'"]'+function(t){for(var e in n)if(n.hasOwnProperty(e)&&n[e].value===t)return n[e].text;return""}(r.data.term)+"[/pb_glossary]")}}}).find("tabpanel")[0].activateTab(o)}})},createControl:function(t,e){return null}}),tinymce.PluginManager.add("glossary_all",tinymce.plugins.glossary.all),tinymce.PluginManager.add("glossary",tinymce.plugins.glossary)}});