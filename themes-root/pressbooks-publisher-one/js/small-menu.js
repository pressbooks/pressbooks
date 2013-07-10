/**
 * Handles toggling the sidebar menu for small screens.
 */
jQuery(document).ready(function ($) {
	var masthead = $('#catalog-sidebar');
	var timeout = false;

	$.fn.smallMenu = function () {
		masthead.find('.sidebar-inner-wrap').removeClass('main-navigation').addClass('main-small-navigation');
		masthead.find('.tag-menu').removeClass('assistive-text').addClass('menu-toggle');

		$('.menu-toggle').unbind('click').click(function () {
			masthead.find('.main-small-navigation').slideToggle();
			$(this).toggleClass('toggled-on');
		});
	};

	// Check viewport width on first load.
	if ($(window).width() < 600)
		$.fn.smallMenu();

	// Check viewport width when user resizes the browser window.
	$(window).resize(function () {
		var browserWidth = $(window).width();

		if (false !== timeout)
			clearTimeout(timeout);

		timeout = setTimeout(function () {
			if (browserWidth < 600) {
				$.fn.smallMenu();
			} else {
				masthead.find('.sidebar-inner-wrap').removeClass('main-small-navigation').addClass('main-navigation');
				masthead.find('.tag-menu').removeClass('menu-toggle').addClass('assistive-text');
				masthead.find('.main-navigation').removeAttr('style');
			}
		}, 200);
	});
});