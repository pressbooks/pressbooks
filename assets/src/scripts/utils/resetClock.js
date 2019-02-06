
export default clock => {
	const seconds = document.getElementById( 'pb-sse-seconds' );
	const minutes = document.getElementById( 'pb-sse-minutes' );
	minutes.textContent = '';
	seconds.textContent = '';
	clearInterval( clock );
}
