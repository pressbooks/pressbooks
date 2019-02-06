import pad from './pad';

export default clock => {
	// Init clock
	const seconds = document.getElementById( 'pb-sse-seconds' );
	const minutes = document.getElementById( 'pb-sse-minutes' );

	// Start clock
	let sec = 0;
	minutes.textContent = '00:';
	seconds.textContent = '00';
	clock = setInterval( function () {
		seconds.textContent = pad( ++sec % 60 );
		minutes.textContent = pad( parseInt( sec / 60, 10 ) ) + ':';
	}, 1000 );
}
