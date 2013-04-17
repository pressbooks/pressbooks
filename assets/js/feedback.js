// This script is used by the Feedback button

jQuery(document).ready(function () {
	jQuery('#myModal').modal('hide');
	jQuery('#myModal').on('hidden', function () {
		jQuery('div.modal-backdrop').remove();
	})
	jQuery('#contextual-help-link-wrap').removeClass('screen-meta-toggle');
	jQuery('#contextual-help-link').attr('href', '#myModal');
	jQuery('#contextual-help-link').attr('data-toggle', 'modal');
});