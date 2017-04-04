jQuery(document).ready(function($){
	// script from http://stackoverflow.com/questions/2907367/have-a-div-cling-to-top-of-screen-if-scrolled-down-past-it
  var stickyEl = $('.nav-container nav');
	var elTop = stickyEl.offset().top;
	$(window).scroll(function() {
  	var windowTop = $(window).scrollTop();
    stickyEl.toggleClass('sticky', windowTop > elTop);
 	});
});
