document.addEventListener( 'DOMContentLoaded', function () {

	function addAttribute( selector, att, val ){
		let e = document.querySelectorAll( selector );
		for ( let i=0; i < e.length; i++ ) {
			e[ i ].setAttribute( att, val );
		}
	}

	// <table> output by do_settings_sections() should have a role="none" attribute
	// https://core.trac.wordpress.org/ticket/46899
	addAttribute( 'table.form-table', 'role', 'none' );

	// WP_List_Table table headers are missing `role=`columnheader` for accessibility
	// https://core.trac.wordpress.org/ticket/46977
	addAttribute( 'table.wp-list-table th', 'role', 'columnheader' );

	// Add attributes to make status and alert bars accessible
	addAttribute( 'div.updated', 'role', 'status' );
	addAttribute( 'div.notice', 'role', 'status' );
	addAttribute( 'div.error', 'role', 'alert' );
} );
