
export default ( type, message, dismissable ) => {
	const notice = document.createElement( 'div' );
	const p = document.createElement( 'p' );
	let button;
	const h1 = document.getElementsByTagName( 'h1' )[0];
	p.setAttribute( 'aria-live', 'assertive' );
	p.appendChild( document.createTextNode( message ) );
	notice.classList.add( 'notice', `notice-${type}` );
	notice.appendChild( p );

	if ( dismissable ) {
		button = document.createElement( 'button' );
		const span = document.createElement( 'span' );
		button.classList.add( 'notice-dismiss' );
		span.classList.add( 'screen-reader-text' );
		span.appendChild( document.createTextNode( 'Dismiss this notice.' ) );
		button.appendChild( span );
		notice.classList.add( 'is-dismissible' );
		notice.appendChild( button );
	}

	h1.parentNode.insertBefore( notice, h1.nextSibling );

	if ( button ) {
		button.onclick = () => {
			notice.parentNode.removeChild( notice );
		};
	}
}
