var formfield;
jQuery(document).ready(function($) {

	// duplicating fields
	if ( $('.add-multiple').length ) {
		$('.add-multiple').live('click', function(e) {
			e.preventDefault();
			var parent = $(this).parent().prev('.cloneable').attr('id');
			var $last = $('#'+parent);
			var $clone = $last.clone();
			var idName = $clone.attr('id');
			var instanceNum = parseInt(idName.split('-')[1])+1;
			idName = idName.split('-')[0]+'-'+instanceNum;
			$clone.attr('id',idName);
			$clone.insertAfter($last).hide().fadeIn().find(':input[type=text]').val('');
		});
	}

	// deleting fields
	if ( $('.del-multiple').length )	 {
		$('.del-multiple').live('click', function(e) {
			e.preventDefault();
			$(this).parent().fadeOut('normal', function(){
				$(this).remove();
			});
		});
	}

	// init the upload fields
	if ( $('.upload_button').length ) {
		$('.upload_button').live('click', function(e) {
			formfield = $(this).parent().attr('id');
			window.send_to_editor=window.send_to_editor_clone;
			tb_show('','media-upload.php?post_id='+numb+'&TB_iframe=true');
			return false;
		});
		window.original_send_to_editor = window.send_to_editor;
		window.send_to_editor_clone = function(html){
				file_url = jQuery('img',html).attr('src');
				if (!file_url) { file_url = jQuery(html).attr('href'); }
				tb_remove();
				jQuery('#'+formfield+' .upload_field').val(file_url);
			}
 	}

 	// init the datepicker fields
	if ( $('.datepicker').length ) {
		$( '.datepicker input' ).datepicker({changeMonth: true, changeYear: true, dateFormat: 'yy-mm-dd'});
	}

	// chosen
	$("select.chosen").each(function(index) { 
		$(this).chosen();
	});

});