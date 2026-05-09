/**
 *
 * @param type
 * @param message
 * @param dismissable
 */
export default ( type, message, dismissable ) => {
	const notice = document.createElement( 'div' );
	const p = document.createElement( 'p' );
	let button;
	const h1 = document.getElementsByTagName( 'h1' )[0];
	p.setAttribute( 'aria-live', 'assertive' );
	p.insertAdjacentHTML( 'beforeend', message );
	notice.classList.add( 'notice', `notice-${ type }` );
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

	if ( h1 && h1.parentNode ) {
		h1.parentNode.insertBefore( notice, h1.nextSibling );
	} else {
		console.warn( 'displayNotice: Could not find h1 element or its parent to insert notice. Appending to body as a fallback.' );
		document.body.appendChild( notice );
	}

	if ( button ) {
		/**
		 *
		 */
		button.onclick = () => {
			notice.parentNode.removeChild( notice );
		};
	}
};
