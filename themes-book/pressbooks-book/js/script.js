jQuery(document).ready(function(){
				
	jQuery(".share-header").hide();
	jQuery(".share-btn").click(function() {
	  	jQuery('.share-header').slideToggle( function() {
  			  	jQuery(".share-btn").toggleClass("open");
  		});
		return false;
	});	
				
	// Sit footer at bottom of page 			
		if(i="msie" && jQuery.browser.version.substr(0,3)=="7.0") {
			} else {
				var height = jQuery(window).height() - jQuery(".sticky").height() - jQuery(".footer").height();
				jQuery(".wrapper").css("min-height", height - 190);
				jQuery(".wrapper #wrap").css("min-height", height - 140);
			}
	
});
