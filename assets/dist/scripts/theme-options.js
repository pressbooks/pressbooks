!function(t){var n={};function e(a){if(n[a])return n[a].exports;var r=n[a]={i:a,l:!1,exports:{}};return t[a].call(r.exports,r,r.exports,e),r.l=!0,r.exports}e.m=t,e.c=n,e.d=function(t,n,a){e.o(t,n)||Object.defineProperty(t,n,{enumerable:!0,get:a})},e.r=function(t){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(t,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(t,"__esModule",{value:!0})},e.t=function(t,n){if(1&n&&(t=e(t)),8&n)return t;if(4&n&&"object"==typeof t&&t&&t.__esModule)return t;var a=Object.create(null);if(e.r(a),Object.defineProperty(a,"default",{enumerable:!0,value:t}),2&n&&"string"!=typeof t)for(var r in t)e.d(a,r,function(n){return t[n]}.bind(null,r));return a},e.n=function(t){var n=t&&t.__esModule?function(){return t.default}:function(){return t};return e.d(n,"a",n),n},e.o=function(t,n){return Object.prototype.hasOwnProperty.call(t,n)},e.p="/",e(e.s=25)}({25:function(t,n,e){t.exports=e("wDsX")},wDsX:function(t,n){jQuery(function(t){t(".select2").select2(),t(".color-picker").wpColorPicker();var n=t("#chapter_numbers");t(document).ready(function(){n.is(":checked")?t("#part_label, #chapter_label").parent().parent().show():t("#part_label, #chapter_label").parent().parent().hide(),n.change(function(){this.checked?t("#part_label, #chapter_label").parent().parent().show():t("#part_label, #chapter_label").parent().parent().hide()}),"10"!==t("#pdf_page_size").val()&&t("#pdf_page_width, #pdf_page_height").parent().parent().hide(),t("select#running_content_front_matter_left").change(function(){var n=t(this).val();t("input#running_content_front_matter_left_custom").val(n),""===n&&t("input#running_content_front_matter_left_custom").focus().val("")}),t("select#running_content_front_matter_right").change(function(){var n=t(this).val();t("input#running_content_front_matter_right_custom").val(n),""===n&&t("input#running_content_front_matter_right_custom").focus().val("")}),t("select#running_content_introduction_left").change(function(){var n=t(this).val();t("input#running_content_introduction_left_custom").val(n),""===n&&t("input#running_content_introduction_left_custom").focus().val("")}),t("select#running_content_introduction_right").change(function(){var n=t(this).val();t("input#running_content_introduction_right_custom").val(n),""===n&&t("input#running_content_introduction_right_custom").focus().val("")}),t("select#running_content_part_left").change(function(){var n=t(this).val();t("input#running_content_part_left_custom").val(n),""===n&&t("input#running_content_part_left_custom").focus().val("")}),t("select#running_content_part_right").change(function(){var n=t(this).val();t("input#running_content_part_right_custom").val(n),""===n&&t("input#running_content_part_right_custom").focus().val("")}),t("select#running_content_chapter_left").change(function(){var n=t(this).val();t("input#running_content_chapter_left_custom").val(n),""===n&&t("input#running_content_chapter_left_custom").focus().val("")}),t("select#running_content_chapter_right").change(function(){var n=t(this).val();t("input#running_content_chapter_right_custom").val(n),""===n&&t("input#running_content_chapter_right_custom").focus().val("")}),t("select#running_content_back_matter_left").change(function(){var n=t(this).val();t("input#running_content_back_matter_left_custom").val(n),""===n&&t("input#running_content_back_matter_left_custom").focus().val("")}),t("select#running_content_back_matter_right").change(function(){var n=t(this).val();t("input#running_content_back_matter_right_custom").val(n),""===n&&t("input#running_content_back_matter_right_custom").focus().val("")}),t("#pdf_page_size").change(function(){switch(t("#pdf_page_size").val()){case"1":t("#pdf_page_width").val("5.5in").parent().parent().hide(),t("#pdf_page_height").val("8.5in").parent().parent().hide();break;case"2":t("#pdf_page_width").val("6in").parent().parent().hide(),t("#pdf_page_height").val("9in").parent().parent().hide();break;case"3":t("#pdf_page_width").val("8.5in").parent().parent().hide(),t("#pdf_page_height").val("11in").parent().parent().hide();break;case"4":t("#pdf_page_width").val("8.5in").parent().parent().hide(),t("#pdf_page_height").val("9.25in").parent().parent().hide();break;case"5":t("#pdf_page_width").val("5in").parent().parent().hide(),t("#pdf_page_height").val("7.75in").parent().parent().hide();break;case"6":t("#pdf_page_width").val("4.25in").parent().parent().hide(),t("#pdf_page_height").val("7in").parent().parent().hide();break;case"7":t("#pdf_page_width").val("21cm").parent().parent().hide(),t("#pdf_page_height").val("29.7cm").parent().parent().hide();break;case"8":t("#pdf_page_width").val("14.8cm").parent().parent().hide(),t("#pdf_page_height").val("21cm").parent().parent().hide();break;case"9":t("#pdf_page_width").val("5in").parent().parent().hide(),t("#pdf_page_height").val("8in").parent().parent().hide();break;case"10":t("#pdf_page_width").val("").parent().parent().fadeToggle(),t("#pdf_page_height").val("").parent().parent().fadeToggle();break;default:t("#pdf_page_width").val("5.5in").parent().parent().hide(),t("#pdf_page_height").val("8.5in").parent().parent().hide()}})})})}});