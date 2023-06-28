var networkManagerDashboard = {
	handleChange: function ({target}) {

		let data = new FormData();
		data.append('_ajax_nonce', pb_ajax_dashboard.nonce);
		data.append('action', 'pb-dashboard-checklist');
		data.append('item', target.value);

		fetch(pb_ajax_dashboard.ajax_url, {
			method: 'POST',
			body: data,
		})
			.then(function(response) {
				return response.json();
			})
			.then(function(data) {
				// Handle the response
				console.log(data);
			})
			.catch(function(error) {
				// Handle any errors
				console.error(error);
			});
	}
}
