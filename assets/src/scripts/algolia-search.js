/* global algoliasearch */
/* global instantsearch */
/* global PBAlgolia */

const searchClient = algoliasearch( PBAlgolia.applicationId, PBAlgolia.apiKey );

const search = instantsearch( {
	indexName: PBAlgolia.indexName,
	searchClient,
	/**
	 * @see https://www.algolia.com/doc/api-reference/widgets/instantsearch/js/#widget-param-searchfunction
	 * @param helper
	 */
	searchFunction( helper ) {
		// Ensure we only trigger a search when there's a query
		if ( helper.state.query ) {
			helper.search();
		}
	},
} );

search.addWidgets( [
	instantsearch.widgets.searchBox( {
		container: '#searchbox',
	} ),

	instantsearch.widgets.hits( {
		// cssClasses property -> custom css classes here folks: https://www.algolia.com/doc/api-reference/widgets/hits/js/#widget-param-cssclasses
		container: '#book-cards',
		templates: {
			item: `${ PBAlgolia.hitsTemplate }`,
		},
	} ),
] );

search.start();

