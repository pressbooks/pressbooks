/* global pb_ajax_dashboard, Alpine */

let networkManagerDashboard = {
	/**
	 * This function perform the ajax call to update the checklist item.
	 *
	 * @param {object} target - The target element.
	 * @param target.target
	 */
	handleChange: function ( { target } ) {

		let data = new FormData();
		data.append( '_ajax_nonce', pb_ajax_dashboard.nonce );
		data.append( 'action', 'pb-dashboard-checklist' );
		data.append( 'item', target.value );

		fetch( pb_ajax_dashboard.ajax_url, {
			method: 'POST',
			body: data,
		} )
			.then( response => {
				return response.json();
			} )
			.then( ( { data } ) => {
				// Handle the response
				if ( data.completed ) {
					document.dispatchEvent( new CustomEvent( 'updateCompleted', { detail: { completed: data.completed, reset: false } } ) );
				}
			} ).catch( function ( error ) {} );
	},
	/**
	 *
	 */
	reset: function () {
		document.dispatchEvent( new CustomEvent( 'updateCompleted', { detail: { reset: true } } ) );
	},
};

document.addEventListener( 'alpine:init', () => {
	Alpine.store( 'checklist', {
		completed: false,
		reset: false,
		/**
		 *
		 */
		toggle() {
			this.completed = ! this.completed;
		},
	} );
} );

//alpine init
document.addEventListener( 'updateCompleted', function ( event ) {
	const { completed } = event.detail;
	const { reset } = event.detail;
	if ( reset ) {
		Alpine.store( 'checklist' ).reset = true;
	}
	if ( completed ) {
		Alpine.store( 'checklist' ).toggle();
	}
} );
