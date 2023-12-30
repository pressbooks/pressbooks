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
				if ( data.completed ) {
					//notify alpine
					document.dispatchEvent( new CustomEvent( 'updateCompleted', {
						detail: {
							completed: data.completed,
							reset: false,
						},
					} ) );
				}
			} ).catch( function ( error ) {} );
	},
	/**
	 *
	 */
	reset: function () {
		document.dispatchEvent( new CustomEvent( 'updateCompleted', {
			detail: {
				reset: true,
				completed: true,
			},
		} ) );
	},
};

document.addEventListener( 'alpine:init', () => {
	Alpine.store( 'checklist', {
		completed: false,
		reset: false,
		loading: true,
		/**
		 *
		 */
		toggleComplete() {
			this.completed = ! this.completed;
		},
		/**
		 *
		 */
		toggleReset() {
			this.reset = ! this.reset;
		},
		/**
		 *
		 */
		updateCompleted() {
			const checkboxes = document.querySelectorAll( '.network-checklist input[type="checkbox"]' );
			const allSelected = Array.from( checkboxes ).every( checkbox => checkbox.checked );
			this.completed = allSelected;
			this.loading = false;
		},
	} );
} );

//alpine init
document.addEventListener( 'updateCompleted', function ( event ) {
	const { completed, reset } = event.detail;
	if ( reset ) {
		Alpine.store( 'checklist' ).toggleReset();
	}
	if ( completed ) {
		Alpine.store( 'checklist' ).toggleComplete();
	}
} );

document.addEventListener( 'DOMContentLoaded', () => {
	Alpine.store( 'checklist' ).updateCompleted();
} );
