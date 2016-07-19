// This script is loaded when a user is on the [ Theme Options ] page

jQuery(function ($) {
	$('.select2').select2();

	$(document).ready(function(){
		if ( $("#pdf_page_size").val() !== '10' ) {
			$("#pdf_page_width, #pdf_page_height").parent().parent().hide();
		}
		$("#pdf_page_size").change(function(){
			var size = $("#pdf_page_size").val();
			switch ( size ) {
				case "1":
					$("#pdf_page_width").val("5.5in").parent().parent().hide();
					$("#pdf_page_height").val("8.5in").parent().parent().hide();
					break;
				case "2":
					$("#pdf_page_width").val("6in").parent().parent().hide();
					$("#pdf_page_height").val("9in").parent().parent().hide();
					break;
				case "3":
					$("#pdf_page_width").val("8.5in").parent().parent().hide();
					$("#pdf_page_height").val("11in").parent().parent().hide();
					break;
				case "4":
					$("#pdf_page_width").val("8.5in").parent().parent().hide();
					$("#pdf_page_height").val("9.25in").parent().parent().hide();
					break;
				case "5":
					$("#pdf_page_width").val("5in").parent().parent().hide();
					$("#pdf_page_height").val("7.75in").parent().parent().hide();
					break;
				case "6":
					$("#pdf_page_width").val("4.25in").parent().parent().hide();
					$("#pdf_page_height").val("7in").parent().parent().hide();
					break;
				case "7":
					$("#pdf_page_width").val("21cm").parent().parent().hide();
					$("#pdf_page_height").val("29.7cm").parent().parent().hide();
					break;
				case "8":
					$("#pdf_page_width").val("14.8cm").parent().parent().hide();
					$("#pdf_page_height").val("21cm").parent().parent().hide();
					break;
				case "9":
					$("#pdf_page_width").val("5in").parent().parent().hide();
					$("#pdf_page_height").val("8in").parent().parent().hide();
					break;
				case "10":
					$("#pdf_page_width").val("").parent().parent().fadeToggle();
					$("#pdf_page_height").val("").parent().parent().fadeToggle();
					break;
			}
		});
	});
});
