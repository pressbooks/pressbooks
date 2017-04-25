// const docUrl = 'https://raw.githubusercontent.com/secretpizzaparty/huh/master/README.md';

let huhLauncher = '',
		huhMobileClose = '',
		huhContainer = '',
		huhContent = '',
		huhHeader = '',
		huhBackButton = '',
		huhAccentColor = '',
		huhTocTriggers = '';

// init
function huhInit() {
	huhLauncher = document.querySelector( '#huh-launcher--button' );
	huhMobileClose = document.querySelector( '#huh-mobile-close' );
	huhContainer = document.querySelector( '#huh-container' );
	huhContent = document.querySelector( '#huh-content' );
	huhHeader = document.querySelector( '#huh-header');
	huhBackButton = document.querySelector( '#huh-back-to-toc' );
	huhAccentColor = huhLauncher.getAttribute( 'data-accent-color' );

	// fetch the markdown file (set in huh.php)
	// then load the content into the container
	fetch( huhDocUrl )
		.then( blob => blob.text() )
		.then( data => loadContent( data ) );
}

function loadContent( data ) {
	// first we format the content
	const dataFormat = marked( data );

	// then we create our custom content structure
	const content = createContent( dataFormat );

	// then we insert content into the content box
	huhContent.innerHTML = content;

	// apply accent color
	applyAccentColor( huhAccentColor );

	// bind interaction events after all content is loaded
	huhBindEvents();
}

function createContent( data ) {
	let sections = data.split( '<h1' ); // split at h1
	sections = sections.filter( ( n ) => { return n != '' } ); // remove empty elements

	const content = sections.map( section => {
		const splitIndex = section.indexOf( '</h1>' ); // split into two blocks after <h1>
		const headingSplit = section.slice( 0, splitIndex );
		const heading = headingSplit.slice( headingSplit.indexOf( '>' ) + 1 ); // content after `id="*">``
		const body = section.slice( splitIndex + 5 ); // content after closing `</h1>`

		return {
			heading: heading,
			body: body
		};
	} );

	const contentHtml = formatContent( content );

	return contentHtml;
}

function formatContent( content ) {
	const html = content.map( item => {
		return `
			<a class="huh-toc--trigger">${ item.heading }<span>&rarr;</span></a>
			<div class="huh-toc--content">
				${ item.body }
			</div>
		`;
	} ).join( '' );

	return html;
}

function showHideContainer( e ) {
	huhLauncher.classList.toggle( 'active' );
	huhContainer.classList.toggle( 'open' );
}

function showContent( e ) {
	// hide all triggers
	for ( i = 0; i < huhTocTriggers.length; i++ ) {
		huhTocTriggers[i].classList.add( 'hidden' );
		huhTocTriggers[i].classList.remove( 'show' );
	}

	// add a class to indicate current selection
	e.target.classList.add( 'current' );

	// add a class to content block of the current selection
	// so we can show just that one
	const content = e.target.nextElementSibling;
	content.classList.add( 'open' );

	// show back button
	huhHeader.classList.add( 'with-content' );
}

function backToToc() {
	// show all triggers
	for ( i = 0; i < huhTocTriggers.length; i++ ) {
		huhTocTriggers[i].classList.remove( 'hidden', 'current' );
		huhTocTriggers[i].classList.add( 'show' );
	}

	// hide all content blocks
	const contentBlocks = document.querySelectorAll( '.huh-toc--content' );
	for ( i = 0; i < contentBlocks.length; i++ ) {
		contentBlocks[i].classList.remove( 'open' );
	}

	// show main header
	huhHeader.classList.remove( 'with-content' );
}

function applyAccentColor( color ) {
	huhLauncher.setAttribute( 'style', 'background:' + color );
	huhHeader.setAttribute( 'style', 'background:' + color );
}

function huhBindEvents() {
	huhLauncher.addEventListener( 'click', showHideContainer );
	huhMobileClose.addEventListener( 'click', showHideContainer );
	huhBackButton.addEventListener( 'click', backToToc );

	huhTocTriggers = document.querySelectorAll( '.huh-toc--trigger' );
	for ( i = 0; i < huhTocTriggers.length; i++ ) {
		huhTocTriggers[i].addEventListener( 'click', showContent );
	}
}

// init after page has loaded to make sure
// we can find the DOM nodes to modify
window.addEventListener( 'load', huhInit );
