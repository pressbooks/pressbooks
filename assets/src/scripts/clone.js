process.env.NODE_TLS_REJECT_UNAUTHORIZED = '0';

let WPAPI = require( 'wpapi' );

let network = new WPAPI( { endpoint: 'https://pressbooks.dev/wp-json' } );
network.books = network.registerRoute( 'pressbooks/v2', '/books' );
network.books().then( function ( data ) {
	let books = new Map();
	data.forEach( function ( book ) {
		books.set( book.id, book.metadata.name );
	} );
	for ( let [ key, value ] of books ) {
		document.querySelector( '.books option' ).insertAdjacentHTML( 'afterend', `<option value="${key}">${value}</option>` );
	}
	document.querySelector( '.books-wrapper .spinner' ).classList.remove( 'is-active' );
} ).catch( function ( err ) {
	// Handle errors here.
} );
