function moveFootnotes() {
	let footnotes = document.getElementsByClassName( 'footnote-indirect' );
	for ( let i = 0; i < footnotes.length; i++ ) {
		let ref = document.getElementById( footnotes[i].getAttribute( 'data-fnref' ) );
		if ( ref ) footnotes[i].appendChild( ref );
	}
}
if ( typeof Prince != 'undefined' ) {
	moveFootnotes();
}
