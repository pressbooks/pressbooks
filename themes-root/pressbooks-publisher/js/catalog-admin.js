/**
 * catalog-admin.js
 */
 
(function($) {
	$(document).ready(function() {
		$('input.in-catalog').on('change', function() {
			var book_id = $(this).parent('td').siblings('th').children('input').val();
			var in_catalog = $(this).prop('checked');
			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'pressbooks_publisher_update_catalog',
					book_id: book_id,
					in_catalog: in_catalog,
					_ajax_nonce: PB_Publisher_Admin.publisherAdminNonce
				},
				success: function(){
                },
				error: function(jqXHR, textStatus, errorThrown) {
                    alert(jqXHR + " :: " + textStatus + " :: " + errorThrown);
                }
			});
		});
	});
}) (jQuery);
