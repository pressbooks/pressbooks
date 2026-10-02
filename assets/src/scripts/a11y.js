const { __ } = wp.i18n;

document.addEventListener( 'DOMContentLoaded', function () {

	/**
	 * @param selector
	 * @param att
	 * @param val
	 */
	function addAttribute( selector, att, val ){
		let e = document.querySelectorAll( selector );
		for ( let i=0; i < e.length; i++ ) {
			e[ i ].setAttribute( att, val );
		}
	}

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

	// Add aria-labels to Code/Text editor buttons where missing.
	/**
	 *
	 * @param btn
	 */
	function setFnButtonLabel( btn ) {
		const value = btn.getAttribute( 'value' );
		if ( value === 'fn' ) {
			btn.setAttribute( 'aria-label', __( 'Create footnote shortcode', 'pressbooks' ) );
		} else if ( value === '/fn' ) {
			btn.setAttribute( 'aria-label', __( 'Close footnote shortcode', 'pressbooks' ) );
		}
	}

	/**
	 *
	 */
	function applyQuicktagsLabels() {
		const qtButtons = document.querySelectorAll( '.quicktags-toolbar .ed_button:not([aria-label])' );
		if ( ! qtButtons || qtButtons.length === 0 ) {
			return false;
		}

		for ( let i = 0; i < qtButtons.length; i++ ) {
			const btn = qtButtons[ i ];
			const id = btn.getAttribute( 'id' );
			const value = btn.getAttribute( 'value' );
			if ( id === 'qt_content_ed_fn' ) {
				setFnButtonLabel( btn );
				// observe future changes to its value attribute
				const observer = new MutationObserver( muts => {
					muts.forEach( m => {
						if ( m.type === 'attributes' && m.attributeName === 'value' ) {
							setFnButtonLabel( btn );
						}
					} );
				} );
				observer.observe( btn, {
					attributes: true,
					attributeFilter: [ 'value' ],
				} );
				continue;
			}
			if ( id === 'qt_content_close' ) {
				btn.setAttribute( 'aria-label', __( 'Close all open tags', 'pressbooks' ) );
				continue;
			}
			if ( id === 'qt_content_dfw' ) {
				btn.setAttribute( 'aria-label', __( 'Distraction-free writing mode', 'pressbooks' ) );
				continue;
			}
		}
		return true;
	}

	if ( ! applyQuicktagsLabels() ) {
		const observer = new MutationObserver( ( mutations, obs ) => {
			if ( document.querySelector( '.quicktags-toolbar' ) ) {
				if ( applyQuicktagsLabels() ) obs.disconnect();
			}
		} );
		observer.observe( document.body, {
			childList: true,
			subtree: true,
		} );
	}

	// Add aria-describedby attribute to date picker inputs
	const datePickers = document.querySelectorAll( 'duet-date-picker' );
	datePickers.forEach( datePicker => {
		datePicker.addEventListener( 'duetFocus', () => {
			const input = datePicker.querySelector( 'input.duet-date__input' );
			const ariaDescribedBy = datePicker.getAttribute( 'aria-describedby' );
			if ( input ) {
				input.setAttribute( 'aria-describedby', ariaDescribedBy );
			}
		} );
	} );
} );

const pbLogo = document.querySelector( '#wp-admin-bar-pb-logo > .ab-item' );
if ( pbLogo ) {
	pbLogo.removeAttribute( 'role' );
}

const observer = new MutationObserver( () => {
	const logo = document.querySelector( '#wp-admin-bar-pb-logo > .ab-item' );
	if ( logo && logo.hasAttribute( 'role' ) ) {
		logo.removeAttribute( 'role' );
	}
} );
observer.observe( document.body, {
	childList: true,
	subtree: true,
} );
