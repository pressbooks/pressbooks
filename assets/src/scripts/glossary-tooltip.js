window.Popper = require( 'popper.js' ).default;

document.addEventListener( 'DOMContentLoaded', function () {
	const glossaryTerms = document.querySelectorAll(
		'#content .glossary-term'
	);

	const glossary = document.querySelector(
		'#content .glossary'
	);

	Array.prototype.forEach.call( glossaryTerms, glossaryTerm => {
		const glossaryTermId = glossaryTerm.getAttribute( 'aria-describedby' );
		const glossaryDefinition = document.getElementById( glossaryTermId );

		glossaryTerm.onfocus = showDefinition;
		glossaryTerm.addEventListener( 'keydown', function ( e ) {
			if ( ( e.keyCode || e.which ) === 27 )
				hideDefinition();
		} );

		document.addEventListener( 'click', event => {
			if (
				event.target !== glossaryTerm
				&& event.target.getAttribute( 'aria-describedby' ) !== glossaryTermId
				&& ! glossaryDefinition.contains( event.target )
			) {
				hideDefinition();
			}
		} );

		function showDefinition() {
			new Popper( glossaryTerm, glossaryDefinition, {} );
			glossaryDefinition.classList.add( 'glossary__tooltip--visible' );
			glossaryDefinition.hidden = false;
			Array.prototype.forEach.call( glossary.childNodes, dfn => {
				if ( dfn.getAttribute( 'id' ) !== glossaryTermId ) {
					dfn.classList.remove( 'glossary__tooltip--visible' );
					dfn.hidden = true;
				}
			} );
		}

		function hideDefinition() {
			glossaryDefinition.hidden = true;
			glossaryDefinition.classList.remove( 'glossary__tooltip--visible' );
		}
	} );
} );
