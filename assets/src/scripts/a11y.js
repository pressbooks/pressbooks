document.addEventListener( 'DOMContentLoaded', function () {

	function addAttribute( x, y, z ){
		let e = document.querySelectorAll( x );
		for ( let i=0; i < e.length; i++ ) {
			e[ i ].setAttribute( y, z );
		}
	}

	addAttribute( 'table.wp-list-table th', 'role', 'columnheader' );
	addAttribute( 'table.form-table', 'role', 'none' );
} )
