(function($) {

	$(document).ready(function(){
	
	    $('.modalLink').modal({
	        trigger: '.modalLink',          // id or class of link or button to trigger modal
	        olay:'div.overlay',             // id or class of overlay
	        modals:'div.modal',             // id or class of modal
	        animationEffect: 'slideDown',   // overlay effect | slideDown or fadeIn | default=fadeIn
	        animationSpeed: 400,            // speed of overlay in milliseconds | default=400
	        moveModalSpeed: 'slow',         // speed of modal movement when window is resized | slow or fast | default=false
	        background: 'fff',           // hexidecimal color code - DONT USE #
	        opacity: 0.7,                   // opacity of modal |  0 - 1 | default = 0.8
	        openOnLoad: false,              // open modal on page load | true or false | default=false
	        docClose: true,                 // click document to close | true or false | default=true    
	        closeByEscape: true,            // close modal by escape key | true or false | default=true
	        moveOnScroll: true,             // move modal when window is scrolled | true or false | default=false
	        resizeWindow: true,             // move modal when window is resized | true or false | default=false
	        close:'.closeBtn'               // id or class of close button
	    });
	});

})(jQuery); //End of ( function( $ ) {