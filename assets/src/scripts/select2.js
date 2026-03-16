// Select2 library for catalog admin and other pages that need it
// We need to attach select2 to the global jQuery (loaded by WordPress admin)
// so that inline scripts can use $().select2()
import $ from 'jquery';
import 'select2';
import 'select2/dist/css/select2.css';

// Attach select2 to the global jQuery if it exists
if ( window.jQuery && window.jQuery.fn && ! window.jQuery.fn.select2 ) {
	window.jQuery.fn.select2 = $.fn.select2;
}
