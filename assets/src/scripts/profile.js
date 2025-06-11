document.addEventListener( 'DOMContentLoaded', () => {
	const elements = {
		first_name: 'given-name',
		last_name: 'family-name',
		email: 'email',
		url: 'url',
		nickname: 'nickname',
	};

	for ( const id in elements ) {
		const element = document.getElementById( id );

		if ( ! element ) {
			continue;
		}

		element.setAttribute( 'autocomplete', elements[id] );
	}
} );
