/* global PB_ColorPicker */

const { __ } = wp.i18n;
import Coloris from '@melloware/coloris';

jQuery( function ( $ ) {
	Coloris.init();

	Coloris( {
		el: '.coloris',
		a11y: {
			open: __( 'Open color picker', 'pressbooks' ),
			close: __( 'Close color picker', 'pressbooks' ),
			clear: __( 'Clear the selected color', 'pressbooks' ),
			marker: __( 'Saturation: {s}. Brightness: {v}.', 'pressbooks' ),
			hueSlider: __( 'Hue slider', 'pressbooks' ),
			alphaSlider: __( 'Opacity slider', 'pressbooks' ),
			input: __( 'Color value field', 'pressbooks' ),
			format: __( 'Color format', 'pressbooks' ),
			swatch: __( 'Color swatch', 'pressbooks' ),
			instruction: __( 'Saturation and brightness selector. Use up, down, left and right arrow keys to select.', 'pressbooks' ),
		},
	} );

	$.when( $.ready ).then( function () {
		$( '.clr-field > button' ).each( function () {
			$( this ).removeAttr( 'aria-labelledby' );
			const label = $( this ).parents( 'td' ).prev( 'th' ).text();
			$( this ).attr( 'aria-label', label );
		} );
	} );
} );
