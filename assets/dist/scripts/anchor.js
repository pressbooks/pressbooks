!function(n){var t={};function o(e){if(t[e])return t[e].exports;var c=t[e]={i:e,l:!1,exports:{}};return n[e].call(c.exports,c,c.exports,o),c.l=!0,c.exports}o.m=n,o.c=t,o.d=function(n,t,e){o.o(n,t)||Object.defineProperty(n,t,{configurable:!1,enumerable:!0,get:e})},o.n=function(n){var t=n&&n.__esModule?function(){return n.default}:function(){return n};return o.d(t,"a",t),t},o.o=function(n,t){return Object.prototype.hasOwnProperty.call(n,t)},o.p="",o(o.s=0)}({"/Qko":function(n,t){},0:function(n,t,o){o("oT4W"),o("RfME"),o("WNlA"),o("64/+"),o("7jP+"),o("/Qko"),o("UHkt"),o("L65F"),o("zq5l"),o("h5qw"),o("hkMH"),o("XCdE"),o("ePI+"),n.exports=o("rwXc")},"64/+":function(n,t){},"7jP+":function(n,t){},L65F:function(n,t){},RfME:function(n,t){},UHkt:function(n,t){},WNlA:function(n,t){},XCdE:function(n,t){},"ePI+":function(n,t){},h5qw:function(n,t){},hkMH:function(n,t){},oT4W:function(n,t){tinymce.PluginManager.add("anchor",function(n){function t(){var t=n.selection.getNode();n.windowManager.open({title:"Anchor",body:{type:"textbox",name:"name",size:40,label:"Name",value:t.name||t.id},onsubmit:function(t){n.execCommand("mceInsertContent",!1,n.dom.createHTML("a",{id:t.data.name}))}})}n.addButton("anchor",{icon:"anchor",tooltip:"Anchor",onclick:t,stateSelector:"a:not([href])"}),n.addMenuItem("anchor",{icon:"anchor",text:"Anchor",context:"insert",onclick:t})})},rwXc:function(n,t){},zq5l:function(n,t){}});