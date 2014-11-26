jQuery(function() {
	jQuery("#toc > ul").find("li h4:not(:has(a)):not(:empty)").on('click', function() {
			jQuery(this).parent().next().slideToggle(100);
			if (jQuery(this).find(".dashicons").hasClass("dashicons-arrow-up")) {
				jQuery(this).find(".dashicons").removeClass("dashicons-arrow-up").addClass("dashicons-arrow-down");
			} else {
				jQuery(this).find(".dashicons").removeClass("dashicons-arrow-down").addClass("dashicons-arrow-up");
			}
		}).css("cursor","pointer")
		.prepend('<div class="dashicons dashicons-arrow-up" style="float:right"></div>')
		.parent().next().hide();

	//open up current
	jQuery("#toc a[href*='"+window.location.pathname+"']").parent().parent().parent().prev().find("h4:not(:has(a))").trigger('click');//show();
});
