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

	// WP_List_Table table headers are missing `aria-sort` attributes for accessibility
	// https://core.trac.wordpress.org/ticket/47047#ticket
	addAttribute( 'table.wp-list-table th.sortable', 'aria-sort', 'none' );
	addAttribute( 'table.wp-list-table th.sorted.asc', 'aria-sort', 'ascending' );
	addAttribute( 'table.wp-list-table th.sorted.desc', 'aria-sort', 'descending' );

	// Add attributes to make status and alert bars accessible
	// https://core.trac.wordpress.org/ticket/46995
	addAttribute( 'div.updated', 'role', 'status' );
	addAttribute( 'div.notice', 'role', 'status' );
	addAttribute( 'div.error', 'role', 'alert' );

	// Add aria-label attributes to color picker palette boxes
	// https://github.com/Automattic/Iris/issues/69
	 ( function (){
		let colors = document.querySelectorAll( 'a.iris-palette' );

		for ( let i=0; i < colors.length; i++ ) {
			let irisPalette = colors[ i ];
			let rgb = colors[ i ].style.backgroundColor;
			let color = '';

			if ( rgb === 'rgb(0, 0, 0)' ){
				color = 'black'
			}
			if ( rgb === 'rgb(255, 255, 255)' ){
				color = 'white'
			}
			if ( rgb === 'rgb(221, 51, 51)' ){
				color = 'red'
			}
			if ( rgb === 'rgb(221, 153, 51)' ){
				color = 'orange'
			}
			if ( rgb === 'rgb(238, 238, 34)' ){
				color = 'yellow'
			}
			if ( rgb === 'rgb(129, 215, 66)' ){
				color = 'green'
			}
			if ( rgb === 'rgb(30, 115, 190)' ){
				color = 'blue'
			}
			if ( rgb === 'rgb(130, 36, 227)' ){
				color = 'purple'
			}

			irisPalette.setAttribute( 'aria-label', 'Select ' + color );
		}
	} )();
} );
