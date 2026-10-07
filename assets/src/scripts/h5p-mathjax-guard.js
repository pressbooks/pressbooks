/**
 * Prevent H5P.MathDisplay from loading MathJax more than once per page.
 * Temporary mitigation pending an upstream fix: h5p/h5p-math-display#49.
 */

// e.g. /wp-content/uploads/h5p/libraries/H5P.MathDisplay-1.0/dist/mathjax.js
const MATHDISPLAY_MATHJAX = /H5P\.MathDisplay[^"']*\/dist\/mathjax\.js(?:[?#]|$)/i;
let injected = false;

/**
 * @param {Node} node Node being inserted.
 * @returns {boolean} Whether the node is a duplicate MathJax script.
 */
function isDuplicateMathJax( node ) {
	if ( ! node || node.nodeType !== 1 || node.tagName !== 'SCRIPT' ) {
		return false;
	}
	const src = node.getAttribute && node.getAttribute( 'src' );
	if ( ! src || ! MATHDISPLAY_MATHJAX.test( src ) ) {
		return false;
	}
	if ( injected ) {
		return true;
	}
	injected = true;
	return false;
}

/**
 * @param {string} name Insertion method name to patch.
 * @returns {void}
 */
function patchInsertion( name ) {
	const original = Node.prototype[ name ];

	/**
	 * @param {Node} node Node being inserted.
	 * @returns {Node} Inserted node, or the skipped node.
	 */
	const patched = function ( node ) {
		if ( isDuplicateMathJax( node ) ) {
			return node;
		}
		return original.apply( this, arguments );
	};

	// eslint-disable-next-line no-extend-native
	Node.prototype[ name ] = patched;
}

if ( ! window.pbH5PMathJaxGuardInstalled ) {
	window.pbH5PMathJaxGuardInstalled = true;
	[ 'appendChild', 'insertBefore' ].forEach( patchInsertion );
}
