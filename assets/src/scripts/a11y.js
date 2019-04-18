document.addEventListener( 'DOMContentLoaded', function () {

	function addAttribute( selector, att, val ){
		let e = document.querySelectorAll( selector );
		for ( let i=0; i < e.length; i++ ) {
			e[ i ].setAttribute( att, val );
		}
	}

	addAttribute( 'table.wp-list-table th', 'role', 'columnheader' );
	addAttribute( 'table.form-table', 'role', 'none' );

	// Add attributes to make status and alert bars accessible
	addAttribute( 'div.updated', 'role', 'status' );
	addAttribute( 'div.notice', 'role', 'status' );
	addAttribute( 'div.error', 'role', 'alert' );
} )
