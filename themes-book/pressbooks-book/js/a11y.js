/** 
 * Javascript file contents originally from WP Accessibility Plugin v.1.3.10 which is released under GPL v3 
 * original author Chris Rodriguez 
 * modified by Brad Payne, Ashlee Zhang
 */

// Cookie handler, non-$ style
function createCookie(name, value, days) {
	if (days) {
		var date = new Date();
		date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
		var expires = "; expires=" + date.toGMTString();
	} else
		var expires = "";
	document.cookie = name + "=" + value + expires + "; path=/";
}

function readCookie(name) {
	var nameEQ = name + "=";
	var ca = document.cookie.split(';');
	for (var i = 0; i < ca.length; i++) {
		var c = ca[i];
		while (c.charAt(0) == ' ')
			c = c.substring(1, c.length);
		if (c.indexOf(nameEQ) == 0)
			return c.substring(nameEQ.length, c.length);
	}
	return null;
}

function eraseCookie(name) {
	// createCookie(name, "", -1);
	createCookie(name, "");
}

jQuery(document).ready(function ($) {

	// Fontsize handler
	if (readCookie('a11y-larger-fontsize')) {
		$('body').addClass('fontsize');
		$('#is_normal_fontsize').attr('id', 'is_large_fontsize').attr('aria-checked', true).addClass('active');
	}

	$('.toggle-fontsize').on('click', function () {
		if ($(this).attr('id') == "is_normal_fontsize") {
			$('body').addClass('fontsize');
			$(this).attr('id', 'is_large_fontsize').attr('aria-checked', true).addClass('active');
			createCookie('a11y-larger-fontsize', '1');
			return false;
		} else {
			$('body').removeClass('fontsize');
			$(this).attr('id', 'is_normal_fontsize').removeAttr('aria-checked').removeClass('active');
			eraseCookie('a11y-larger-fontsize');
			return false;
		}
	});

	// Sets a -1 tabindex to ALL sections for .focus()-ing
	var sections = document.getElementsByTagName("section");
	for (var i = 0, max = sections.length; i < max; i++) {
		sections[i].setAttribute('tabindex', -1);
		sections[i].className += ' focusable';
	}

	// If there is a '#' in the URL (someone linking directly to a page with an anchor), go directly to that area and focus is
	// Thanks to WebAIM.org for this idea
	if (document.location.hash && document.location.hash != '#') {
		var anchorUponArrival = document.location.hash;
		setTimeout(function () {
			$(anchorUponArrival).scrollTo({duration: 1500});
			$(anchorUponArrival).focus();
		}, 100);
	}

});
