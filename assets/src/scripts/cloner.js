jQuery( function ( $ ) {
	$( '#pb-cloner-button' ).click( event => {
		event.preventDefault();
		$( '#pb-cloner-button' ).attr( 'disabled', true );
		/*
		$( '#loader' ).css( 'display', 'inline-block' );
		const submission = function () {
			$( '#pb-cloner-form' ).submit();
		};
		setTimeout( submission, 0 );
		*/

		let evtSource = new EventSource( 'https://pressbooks.test/test1/wp-admin/admin-ajax.php?action=clone' );
		evtSource.onmessage = function( message ) {
			let data = JSON.parse( message.data );
			switch ( data.action ) {
				case 'updateStatusBar':
					$( "#pb-cloner-progressbar" ).progressbar( {
						value: parseInt( data.percentage, 10 )
					} );
					$( "#pb-cloner-progressbar-info" ).html( data.info );
					break;
				case 'complete':
					evtSource.close();
					alert('OMG THIS ACTUALLY WORKS?!')
					break;
			}
		}

	} );
} );
