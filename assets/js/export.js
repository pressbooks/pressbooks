// This script is loaded when a user is on the [ Export ] page

jQuery(function ($) {
	/* Swap out and animate the "Export Your Book" button */
	$('#pb-export-button').click(function () {
		$('.export-file-container').unbind('mouseenter mouseleave'); // Disable Download & Delete Buttons
		$('#loader').show();
		$('#pb-export-button').hide();
		$('#pb-export-form').submit();
	});
	/* Show and hide download & delete button */
	$(".export-file-container").hover(
			function () { $(this).children(".file-actions").css('visibility', 'visible'); },
			function () { $(this).children(".file-actions").css('visibility', 'hidden'); }
	);
});