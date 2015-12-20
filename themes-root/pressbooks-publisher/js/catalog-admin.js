/**
 * catalog-admin.js
 */
 
(function($) {
	$(document).ready(function() {
		$('.wrap').on('click', '.notice-dismiss', function() {
			$(this).parent('#message').fadeOut( 500, function() {
				$(this).remove();
			});
		});
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
					if ( $('#message').length < 1 ) {
						$('<div id="message" class="updated notice is-dismissible">').html('<p><strong>'+PB_Publisher_Admin.catalog_updated+'</strong></p><button type="button" class="notice-dismiss"><span class="screen-reader-text">'+PB_Publisher_Admin.dismiss_notice+'</span></button>').hide().insertAfter('.wrap h1').fadeIn( 500 );
					} else {
						$('#message').fadeOut( 500, function() {
							$(this).remove();
							$('<div id="message" class="updated notice is-dismissible">').html('<p><strong>'+PB_Publisher_Admin.catalog_updated+'</strong></p><button type="button" class="notice-dismiss"><span class="screen-reader-text">'+PB_Publisher_Admin.dismiss_notice+'</span></button>').hide().insertAfter('.wrap h1').fadeIn( 500 );
						});
					}
					
                },
				error: function(jqXHR, textStatus, errorThrown) {
					if ( $('#message').length < 1 ) {
						$('<div id="message" class="error notice is-dismissible">').html('<p><strong>'+PB_Publisher_Admin.catalog_not_updated+'</strong></p><button type="button" class="notice-dismiss"><span class="screen-reader-text">'+PB_Publisher_Admin.dismiss_notice+'</span></button>').hide().insertAfter('.wrap h1').fadeIn( 500 );
					} else {
						$('#message').fadeOut( 500, function() {
							$(this).remove();
							$('<div id="message" class="error notice is-dismissible">').html('<p><strong>'+PB_Publisher_Admin.catalog_not_updated+'</strong></p><button type="button" class="notice-dismiss"><span class="screen-reader-text">'+PB_Publisher_Admin.dismiss_notice+'</span></button>').hide().insertAfter('.wrap h1').fadeIn( 500 );
						});
					}
                }
			});
		});
	});
}) (jQuery);
