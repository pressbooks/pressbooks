/* global algoliasearch */
/* global instantsearch */
/* global PBAlgolia */

const searchClient = algoliasearch( PBAlgolia.applicationId, PBAlgolia.apiKey );

const searchWrapper = document.getElementById( 'book-cards' );

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
		window.algoliaHelper = helper;
		console.log(window.algoliaHelper);
	},
} );

window.selectBookToClone = function ( url ) {
	const cloneBook = document.getElementById( 'source_book_url' );
	const newBook = document.getElementById( 'target_book_url' );
	cloneBook.value = url;
	const path = url.split('/');
	newBook.value = path.length > 2 ? path[3] : '';
	// scroll to top
	window.scrollTo( 0, 0 );
	searchWrapper.innerHTML = '';
	document.querySelector( '#searchbox input' ).value = '';
}
document.querySelector( '#searchbox').addEventListener( 'input', ( event ) => {
	if( event.target.value.length === 0 ) {
		event.target.value = '';
		searchWrapper.innerHTML = '';
	}
});

search.addWidgets( [
	instantsearch.widgets.searchBox( {
		container: '#searchbox',
		showSubmit: false,
	} ),

	instantsearch.widgets.hits( {
		// cssClasses property -> custom css classes here folks: https://www.algolia.com/doc/api-reference/widgets/hits/js/#widget-param-cssclasses
		escapeHTML: true,
		container: '#book-cards',
		templates: {
			item: `${ PBAlgolia.hitsTemplate }`,
		},
	} ),
] );

search.start();

