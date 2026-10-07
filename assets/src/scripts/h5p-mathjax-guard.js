/**
 * Prevent H5P.MathDisplay from loading MathJax more than once per page.
 *
 * H5P.MathDisplay injects its bundled MathJax via `document.body.appendChild`
 * with no guard. With two or more math-bearing H5P activities on one page the
 * engine is injected twice, and MathJax v3 crashes with
 * "Cannot set property Package ... which has only a getter" (combineWithMathJax).
 * This drops any MathDisplay `mathjax.js` insertion after the first.
 *
 * Temporary mitigation pending an upstream fix in h5p/h5p-math-display.
 *
 * Enqueued as a deferred module in the head, so it installs before MathDisplay
 * injects MathJax on `H5P.jQuery( document ).ready()` (DOMContentLoaded).
 * @see https://github.com/h5p/h5p-math-display/blob/master/src/scripts/mathdisplay.js
 */

// e.g. /wp-content/uploads/h5p/libraries/H5P.MathDisplay-1.0/dist/mathjax.js
const MATHDISPLAY_MATHJAX = /H5P\.MathDisplay[^"']*\/dist\/mathjax\.js(?:[?#]|$)/i;
let injected = false;

/**
 * Decide whether a DOM node is a duplicate MathDisplay MathJax injection.
 * @param {Node} node Node about to be inserted into the DOM.
 * @returns {boolean} True when the node is a second (or later) MathJax script.
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
		return true; // Block every MathJax injection after the first.
	}
	injected = true;
	return false;
}

/**
 * Wrap a native Node insertion method so duplicate MathJax injections are dropped.
 * @param {string} name Name of the `Node.prototype` insertion method to patch.
 * @returns {void}
 */
function patchInsertion( name ) {
	const original = Node.prototype[ name ];

	/**
	 * Patched insertion method that silently skips duplicate MathJax scripts.
	 * @param {Node} node Node being inserted.
	 * @returns {Node} The inserted node, or the skipped node reported as success.
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
