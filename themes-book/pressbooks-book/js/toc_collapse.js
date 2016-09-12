jQuery(function($) {
	var $toc = $("#toc");
	var $tocbutton = $(".toc-btn");
	if ( $toc.length > 0 && $tocbutton.length > 0 && ( $(window).height() - ( $tocbutton.offset().top + $tocbutton.height() + $toc.height() ) ) < 0 ) {
		$("#toc > ul").find("li h4:not(:has(a)):not(:empty)").on('click', function() {
				jQuery(this).parent().next().slideToggle(100);
				var dashicon = $(this).find(".dashicons");
				if (dashicon.hasClass("dashicons-arrow-up")) {
					dashicon.removeClass("dashicons-arrow-up").addClass("dashicons-arrow-down");
				} else {
					dashicon.removeClass("dashicons-arrow-down").addClass("dashicons-arrow-up");
				}
			}).css("cursor","pointer")
			.prepend('<div class="dashicons dashicons-arrow-up" style="float:right"></div>')
			.parent().next().hide();

		// open up current
		$("#toc a[href*='"+window.location.pathname+"']").parent().parent().parent().prev().find("h4:not(:has(a))").trigger('click');//show();
	}
});
