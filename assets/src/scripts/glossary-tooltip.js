window.Popper = require( 'popper.js' ).default;

document.addEventListener( 'DOMContentLoaded', function () {
	const glossTerms = document.querySelectorAll(
		'#content .glossary-term'
	);

	Array.prototype.forEach.call( glossTerms, glossTerm => {
		const glossTermId = glossTerm.getAttribute( 'aria-describedby' );
		const glossDefinition = document.getElementById( glossTermId );
		new Popper( glossTerm, glossDefinition, {} );

		glossTerm.onfocus = showDefinition;

		function showDefinition() {
			glossDefinition.hidden = false;
		}

		function hideDefinition() {
			glossDefinition.hidden = true;
		}

		document.addEventListener( 'click', event => {
			if ( ! glossDefinition.contains( event.target ) && ! glossTerm.contains( event.target ) ) {
				hideDefinition();
			}
		} );
	} );
} );
