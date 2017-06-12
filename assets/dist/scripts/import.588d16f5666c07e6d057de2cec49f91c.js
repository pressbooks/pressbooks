// This script is loaded when a user is on the [ Import ] page

jQuery(function ($) {
	// Disable submit button on click, prevent multiple clicks
	$('form').submit(function () {
		$('input[type=submit]', this).attr('disabled', 'disabled');
	});
});

