tinymce.create("tinymce.plugins.latex",{init:function(t){t.addButton("latex",{title:PB_LaTeXToken.fn_title,text:!1,icon:"icon dashicons-calculator",onclick:function(){var e,n=t.selection.getContent();""!==n?(e=n,t.selection.setContent("[latex]"+e+"[/latex]")):""!==(e=prompt("LaTeX Content","Enter your LaTeX content here."))&&t.execCommand("mceInsertContent",!1,"[latex]"+e+"[/latex]")}})},createControl:function(t,e){return null}}),tinymce.PluginManager.add("latex",tinymce.plugins.latex);