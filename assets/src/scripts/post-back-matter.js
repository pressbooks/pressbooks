jQuery( document ).ready( function ( $ ) {
	let notice = $( '#pb-post-type-notice' );
	let dropdown = $( '#back-matter-typedropdown' );
	dropdown.on( 'change', function () {
		if ( parseInt( this.value, 10 ) === parseInt( PB_GlossaryToken.term_id, 10 ) ) {
			notice.html( '<p>' + PB_GlossaryToken.term_notice + '</p>' );
			notice.show();
		} else {
			notice.html( '' );
			notice.hide();
		}
	} );
} );
