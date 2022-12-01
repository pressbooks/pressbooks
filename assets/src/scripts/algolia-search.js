const searchClient = algoliasearch(PBAlgolia.applicationId, PBAlgolia.apiKey);

const search = instantsearch({
	indexName: PBAlgolia.indexName,
	searchClient,
	searchFunction(helper) {
		// Ensure we only trigger a search when there's a query
		if (helper.state.query) {
			helper.search();
		}
	},
});

search.addWidgets([
	instantsearch.widgets.searchBox({
		container: "#searchbox",
	}),

	instantsearch.widgets.hits({
		container: "#hits",
		templates: {
			item: `${PBAlgolia.hitsTemplate}`,
		},
	}),
]);

search.start();
