/**
 * WordPress CSS Spinner
 * CSS Animations support test
 * From modernizr and MDN: https://developer.mozilla.org/en-US/docs/Web/Guide/CSS/Using_CSS_animations/Detecting_CSS_animation_support
 *
 */
( function ( document ) {
	let animation = false,
		animationstring = 'animation', // eslint-disable-line
		keyframeprefix = '', // eslint-disable-line
		domPrefixes = 'Webkit Moz O ms Khtml'.split( ' ' ),
		pfx = '',
		docElement = document.documentElement,
		modElem = document.createElement( 'modernizr' );

	if ( modElem.style.animationName !== undefined ) {
		animation = true;
	}

	if ( animation === false ) {
		for ( let i = 0; i < domPrefixes.length; i++ ) {
			if ( modElem.style[domPrefixes[i] + 'AnimationName'] !== undefined ) {
				pfx = domPrefixes[i];
				animationstring = pfx + 'Animation';
				keyframeprefix = '-' + pfx.toLowerCase() + '-';
				animation = true;
				break;
			}
		}
	}
	docElement.className += ' ' + ( animation ? '' : 'no-' ) + 'cssanimations';
} )( document );
