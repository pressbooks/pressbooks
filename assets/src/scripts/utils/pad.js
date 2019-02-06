/**
 * Pad integer to two digits with leading zero.
 * @param {int} integer Integer.
 * @return {string} String representation of integer with leading zero.
 */
export default integer => {
	return integer > 9 ? integer : `0${integer}`;
}
