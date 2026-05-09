import '../styles/duet.css';
import './webcomponents/pressbooks-reorderable-multiselect.js';
import { DuetDatePicker } from '@duetds/date-picker/custom-element';
if ( ! customElements.get( 'duet-date-picker' ) ) {
	customElements.define( 'duet-date-picker', DuetDatePicker );
}
jQuery( document ).ready( function ( $ ) {
	// Set an initial focus to help users of assistive technology
	$( '#pb_title' ).trigger( 'focus' );

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
