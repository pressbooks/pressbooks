/* global PBAlgolia */

import algoliasearch from 'algoliasearch/lite';
import instantsearch from 'instantsearch.js';
import { searchBox, hits, stats } from 'instantsearch.js/es/widgets';
import '../styles/cloner.scss';

const searchClient = algoliasearch( PBAlgolia.applicationId, PBAlgolia.apiKey );

const searchWrapper = document.getElementById( 'book-cards' );
const statsHelper = document.getElementById( 'stats' );

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
			helper
				.setQueryParameter( 'facets', [ 'licenseCode' ] )
				.addFacetExclusion( 'licenseCode', 'All Rights Reserved' )
				.addFacetExclusion( 'licenseCode', 'CC BY-BC-ND' )
				.addFacetExclusion( 'licenseCode', 'CC BY-ND' )
				.search();
		}
		window.algoliaHelper = helper;
	},
} );

/**
 *
 * @param url
 */
window.selectBookToClone = function ( url ) {
	const cloneBook = document.getElementById( 'source-book-url' );
	const newBook = document.getElementById( 'target-book-url' );
	cloneBook.value = url;
	const path = url.split( '/' );
	newBook.value = path.length > 2 ? path[3] : '';
	// scroll to top
	window.scrollTo( 0, 0 );
	searchWrapper.innerHTML = '';
	statsHelper.innerHTML = '';
	document.querySelector( '#searchbox input' ).value = '';
};
document.querySelector( '#searchbox' ).addEventListener( 'input', event => {
	if ( event.target.value.length === 0 ) {
		event.target.value = '';
		searchWrapper.innerHTML = '';
		statsHelper.innerHTML = '';
	}
} );

search.addWidgets( [
	searchBox( {
		container: '#searchbox',
		placeholder: 'Search openly licensed books',
		showSubmit: false,
	} ),

	hits( {
		// cssClasses property -> custom css classes here folks: https://www.algolia.com/doc/api-reference/widgets/hits/js/#widget-param-cssclasses
		escapeHTML: true,
		container: '#book-cards',
		templates: {
			item: `${ PBAlgolia.hitsTemplate }`,
		},
	} ),
	stats( {
		container: '#stats',
		templates: {
			/**
			 *
			 * @param data
			 * @param root0
			 * @param root0.html
			 */
			text( data, { html } ) {
				const resultsShown = data.nbHits <= 20  ? data.nbHits : 20;
				return html `${ PBAlgolia.resultsTemplate.replace( '%resultsShown', resultsShown ).replace( '%totalResults', data.nbHits ) }`;
			},
		},
	} ),
] );

search.start();

