function moveFootnotes() {
	var footnotes = document.getElementsByClassName( 'footnote-indirect' );
	for ( var i = 0; i < footnotes.length; i++ ) {
		var ref = document.getElementById(footnotes[i].getAttribute('data-fnref'));
		if (ref) footnotes[i].appendChild(ref);
	}
}
if (typeof Prince != 'undefined') {
	moveFootnotes();
}
