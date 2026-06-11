/* global PBAlgolia */

import algoliasearch from 'algoliasearch/lite';
import instantsearch from 'instantsearch.js';
import { searchBox, hits, stats } from 'instantsearch.js/es/widgets';
import '../styles/cloner.scss';

const searchClient = algoliasearch( PBAlgolia.applicationId, PBAlgolia.apiKey );
let debounceTimer;

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
		if ( helper.state.query ) {
			searchWrapper.classList.remove( 'is-hidden' );
			statsHelper.classList.remove( 'is-hidden' );
			helper
				.setQueryParameter( 'facets', [ 'licenseCode' ] )
				.addFacetExclusion( 'licenseCode', 'All Rights Reserved' )
				.addFacetExclusion( 'licenseCode', 'CC BY-BC-ND' )
				.addFacetExclusion( 'licenseCode', 'CC BY-ND' )
				.search();
		} else {
			searchWrapper.classList.add( 'is-hidden' );
			statsHelper.classList.add( 'is-hidden' );
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
	window.scrollTo( 0, 0 );
	searchWrapper.classList.add( 'is-hidden' );
	statsHelper.classList.add( 'is-hidden' );
	document.querySelector( '#searchbox input' ).value = '';
};

search.addWidgets( [
	searchBox( {
		container: '#searchbox',
		placeholder: 'Search openly licensed books',
		showSubmit: false,
		/**
		 *
		 * @param query
		 * @param search
		 */
		queryHook( query, search ) {
			clearTimeout( debounceTimer );
			debounceTimer = setTimeout( () => search( query ), 1000 );
		},
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

