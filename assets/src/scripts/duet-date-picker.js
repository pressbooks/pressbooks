import { DuetDatePicker } from '@duetds/date-picker/custom-element';

if ( ! customElements.get( 'duet-date-picker' ) ) {
	customElements.define( 'duet-date-picker', DuetDatePicker );
}
