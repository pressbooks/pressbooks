/* global pb_sr:true */

function confirmSubmit(form) {
	if ( confirm( pb_sr.warning_text )) {
		var input = document.createElement( 'input' );
		input.setAttribute( 'type', 'hidden' );
		input.setAttribute( 'name', 'replace_and_save' );
		document.getElementById( 'search-form' ).appendChild( input );
		form.submit();
	}
}
