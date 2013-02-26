jQuery(function() {
	// script from http://stackoverflow.com/questions/2907367/have-a-div-cling-to-top-of-screen-if-scrolled-down-past-it
  var jQuerywindow = jQuery(window),
       jQuerystickyEl = jQuery('.nav-container nav');

   var elTop = jQuerystickyEl.offset().top;

   jQuerywindow.scroll(function() {
        var windowTop = jQuerywindow.scrollTop();

        jQuerystickyEl.toggleClass('sticky', windowTop > elTop);
 });
    
});