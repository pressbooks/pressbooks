jQuery(document).ready(function(){

	// Sit footer at bottom of page 			
		if(i="msie" && jQuery.browser.version.substr(0,3)=="7.0") {
			} else {
				var height = jQuery(window).height() - jQuery(".sticky").height() - jQuery(".footer").height();
				jQuery(".wrapper").css("min-height", height - 190);
				jQuery(".wrapper #wrap").css("min-height", height - 140);
			}

});