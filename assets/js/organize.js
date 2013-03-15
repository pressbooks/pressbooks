// This script is loaded when a user is on the [ Text → Organize ] page

var PressBooks = {
	"oldPart": null,
	"newPart": null,
	"defaultOptions": {
		revert: true,
		helper: 'clone',
		zIndex: 2700,
		distance: 3,
		opacity: 0.6,
		placeholder: "ui-state-highlight",
		connectWith: '.chapters',
		dropOnEmpty: true,
		cursor: 'crosshair',
		items: 'tbody > tr',
		start: function (index, el) {
			PressBooks.oldPart = el.item.parents('table').attr("id");
		},
		stop: function (index, el) {
			PressBooks.newPart = el.item.parents('table').attr("id");
			PressBooks.update(el.item);
		}
	},
	"frontMatterOptions": {
		revert: true,
		helper: 'clone',
		zIndex: 2700,
		distance: 3,
		opacity: 0.6,
		placeholder: "ui-state-highlight",
		dropOnEmpty: true,
		cursor: 'crosshair',
		items: 'tbody > tr',
		start: function (index, el) {
			//alert(el);
		},
		stop: function (index, el) {
			PressBooks.fmupdate(el.item);
		}
	},
	"backMatterOptions": {
		revert: true,
		helper: 'clone',
		zIndex: 2700,
		distance: 3,
		opacity: 0.6,
		placeholder: "ui-state-highlight",
		dropOnEmpty: true,
		cursor: 'crosshair',
		items: 'tbody > tr',
		start: function (index, el) {
			//alert(el);
		},
		stop: function (index, el) {
			PressBooks.bmupdate(el.item);
		}
	},
	update: function (el) {
		jQuery.ajax({
			beforeSend: function () {
				jQuery.blockUI.defaults.applyPlatformOpacityRules = false;
				jQuery.blockUI({message: jQuery('#loader')});
			},
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'pb_update_chapter',
				// see http://forum.jquery.com/topic/sortable-serialize-not-changing-sort-order-over-3-div-cols
				new_part_order: jQuery("#" + PressBooks.newPart).sortable("serialize"),
				old_part_order: jQuery("#" + PressBooks.oldPart).sortable("serialize"),
				new_part: PressBooks.newPart.replace(/^part\-([0-9]+)$/i, '$1'),
				old_part: PressBooks.oldPart.replace(/^part\-([0-9]+)$/i, '$1'),
				id: jQuery(el).attr('id').replace(/^chapter\-([0-9]+)$/i, '$1'),
				_ajax_nonce: PB_OrganizeToken.orderNonce
			},
			cache: false,
			dataType: 'html',
			error: function (obj, status, thrown) {
				jQuery('#message').html('<p><strong>There has been an error updating your chapter data Usually, <a href="' + window.location.href + '">refreshing the page</a> helps.</strong></p>').addClass('error');
				//window.setTimeout(function(){window.location.replace(window.location.href)}, 5000, true);
			},
			success: function (htmlStr) {
				if (htmlStr == 'NOCHANGE') {
					jQuery('#message').html('<p><strong>No changes were registered.</strong></p>').addClass('error');
				}
				else {
					jQuery('#pb-container').load(location.href + " #pb-container>*", "", function () {
						jQuery('.ui-sortable').sortable(PressBooks.defaultOptions);
						jQuery('.ui-sortable').disableSelection();
						jQuery('#message').html('<p><strong>Chapters have been reordered.</strong></p>').addClass('updated');
					});
				}
			},
			complete: function () {
				jQuery.unblockUI();
			}
		});
	},

	fmupdate: function (el) {
		jQuery.ajax({
			beforeSend: function () {
				jQuery.blockUI.defaults.applyPlatformOpacityRules = false;
				jQuery.blockUI({message: jQuery('#loader')});
			},
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'pb_update_front_matter',
				front_matter_order: jQuery('#front-matter').sortable("serialize"),
				_ajax_nonce: PB_OrganizeToken.orderNonce
			},
			cache: false,
			dataType: 'html',
			error: function (obj, status, thrown) {
				jQuery('#message').html('<p><strong>There has been an error updating your front matter data Usually, <a href="' + window.location.href + '">refreshing the page</a> helps.</strong></p>').addClass('error');
				//window.setTimeout(function(){window.location.replace(window.location.href)}, 5000, true);
			},
			success: function (htmlStr) {
				if (htmlStr == 'NOCHANGE') {
					jQuery('#message').html('<p><strong>No changes were registered.</strong></p>').addClass('error');
				}
				else {
					jQuery('#pb-container').load(location.href + " #pb-container>*", "", function () {
						jQuery('.ui-sortable').sortable(PressBooks.defaultOptions);
						jQuery('.ui-sortable').disableSelection();
						jQuery('#message').html('<p><strong>Front Matter has been reordered.</strong></p>').addClass('updated');
					});
				}
			},
			complete: function () {
				jQuery.unblockUI();
			}
		});
	},

	bmupdate: function (el) {
		jQuery.ajax({
			beforeSend: function () {
				jQuery.blockUI.defaults.applyPlatformOpacityRules = false;
				jQuery.blockUI({message: jQuery('#loader')});
			},
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'pb_update_back_matter',
				back_matter_order: jQuery('#back-matter').sortable("serialize"),
				_ajax_nonce: PB_OrganizeToken.orderNonce
			},
			cache: false,
			dataType: 'html',
			error: function (obj, status, thrown) {
				jQuery('#message').html('<p><strong>There has been an error updating your back matter data. Usually, <a href="' + window.location.href + '">refreshing the page</a> helps.</strong></p>').addClass('error');
				//window.setTimeout(function(){window.location.replace(window.location.href)}, 5000, true);
			},
			success: function (htmlStr) {
				if (htmlStr == 'NOCHANGE') {
					jQuery('#message').html('<p><strong>No changes were registered.</strong></p>').addClass('error');
				}
				else {
					jQuery('#pb-container').load(location.href + " #pb-container>*", "", function () {
						jQuery('.ui-sortable').sortable(PressBooks.defaultOptions);
						jQuery('.ui-sortable').disableSelection();
						jQuery('#message').html('<p><strong>Back Matter has been reordered.</strong></p>').addClass('updated');
					});
				}
			},
			complete: function () {
				jQuery.unblockUI();
			}
		});
	}
};

// --------------------------------------------------------------------------------------------------------------------

jQuery(document).ready(function ($) {

	jQuery("table.chapters").sortable(PressBooks.defaultOptions).disableSelection();
	jQuery("table#front-matter").sortable(PressBooks.frontMatterOptions).disableSelection();
	jQuery("table#back-matter").sortable(PressBooks.backMatterOptions).disableSelection();

	$('.chapter_export_check').change(function () {
		var id = $(this).attr('id');
		id = id.split('_');
		id = id[id.length - 1];

		if ($(this).is(':checked')) {
			chapter_export = 1;
		} else {
			chapter_export = 0;
		}

		jQuery.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'pb_update_export_options',
				post_id: id,
				chapter_export: chapter_export,
				type: 'pb_export',
				_ajax_nonce: PB_OrganizeToken.exportNonce
			}
		});
	});

	$('.fm_export_check').change(function () {
		var id = $(this).attr('id');
		id = id.split('_');
		id = id[id.length - 1];

		if ($(this).is(':checked')) {
			chapter_export = 1;
		} else {
			chapter_export = 0;
		}

		jQuery.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'pb_update_export_options',
				post_id: id,
				chapter_export: chapter_export,
				type: 'pb_export',
				_ajax_nonce: PB_OrganizeToken.exportNonce
			}
		});
	});

	$('.bm_export_check').change(function () {
		var id = $(this).attr('id');
		id = id.split('_');
		id = id[id.length - 1];

		if ($(this).is(':checked')) {
			chapter_export = 1;
		} else {
			chapter_export = 0;
		}

		jQuery.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'pb_update_export_options',
				post_id: id,
				chapter_export: chapter_export,
				type: 'pb_export',
				_ajax_nonce: PB_OrganizeToken.exportNonce
			}
		});
	});
});
