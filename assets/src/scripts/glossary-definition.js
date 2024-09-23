/* global MathJax */

const glossaryTerms = document.querySelectorAll( '#content .glossary-term' );

Array.prototype.forEach.call( glossaryTerms, glossaryTerm => {
	document.addEventListener( 'click', event => {
		if ( event.target === glossaryTerm ) {
			event.preventDefault();
			event.target.setAttribute( 'data-source', true );
			const template = document.querySelector(
				glossaryTerm.getAttribute( 'href' )
			);

			showDefinition( template );

			if ( typeof MathJax !== 'undefined' ) {
				MathJax.Hub.Queue( [ 'Typeset', MathJax.Hub ] );
			}
		}

		if (
			( ! event.target.closest( '.glossary-term' ) && ! event.target.closest( '.glossary__definition' ) ) || event.target.closest( '.glossary__definition button' )
		) {
			removeDefinition();
		}
	} );

	document.addEventListener( 'keydown', function ( e ) {
		if ( e.key === 'Escape' ) {
			removeDefinition();
		}
	} );

	/**
	 * Show a term definition.
	 *
	 * @param template The definition template to display.
	 */
	function showDefinition( template ) {
		const clone = template.content.cloneNode( true );
		const definition = clone.children[0];
		document.body.classList.add( 'has-dialog' );
		const elems = document.body.children;
		Array.prototype.forEach.call( elems, elem => {
			elem.setAttribute( 'inert', '' );
		} );
		const overlay = document.createElement( 'div' );
		overlay.classList.add( 'overlay' );
		document.body.appendChild( overlay );
		document.body.appendChild( definition );
		definition.querySelector( 'div' ).focus();
	}

	/**
	 * Remove a displayed term definition.
	 */
	function removeDefinition() {
		const definition = document.querySelector( '.glossary__definition' );
		const glossaryTerm = document.querySelector( '.glossary-term[data-source]' );
		const overlay = document.querySelector( '.overlay' );

		if ( definition !== null ) {
			definition.remove();
		}

		if ( overlay !== null ) {
			overlay.remove();
		}

		document.body.classList.remove( 'has-dialog' );
		const elems = document.body.children;
		Array.prototype.forEach.call( elems, elem => {
			elem.removeAttribute( 'inert' );
		} );

		if ( glossaryTerm !== null ) {
			glossaryTerm.focus();
			glossaryTerm.removeAttribute( 'data-source' );
		}
	}
} );
