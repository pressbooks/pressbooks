jQuery(document).ready(function(t){t("body").on({beforeWpautop:(a,e)=>{(e.unfiltered.indexOf("</math>")!==-1||e.unfiltered.indexOf("</svg>")!==-1)&&(e.data=e.unfiltered.replace(/<(math|svg)[^>]*>[\s\S]*?<\/\1>/gi,r=>r.replace(/(<(pre|script|style|textarea)[^]+?<\/\2)|(^|>)\s+|\s+(?=<|$)/g,"$1$3")))}})});
//# sourceMappingURL=post-mathjax-m_XkW4Tr.js.map
