(function($) {
	
	//$('catalog-book').matchHeight();
	
    // get test settings
   // var byRow = $('body').hasClass('test-rows');

    // apply matchHeight to each item container's items
    $('.site-main').each(function() {
        $(this).children('.catalog-book').matchHeight();
    });	


})(jQuery); //End of ( function( $ ) {