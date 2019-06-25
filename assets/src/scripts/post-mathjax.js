jQuery( document ).ready( function ( $ ) {
	$( 'body' ).bind( {
		// Switching between the visual and text editors breaks MathML tags
		// We can stop this from happening by forcing the MathML into one line without any returns
		beforeWpautop: ( e, o ) => {
			if ( o.unfiltered.indexOf( '</math>' ) !== -1 || o.unfiltered.indexOf( '</svg>' ) !== -1 ) {
				o.data = o.unfiltered.replace( /<(math|svg)[^>]*>[\s\S]*?<\/\1>/gi, match => {
					// Remove every white space between tags using JavaScript
					return match.replace( /(<(pre|script|style|textarea)[^]+?<\/\2)|(^|>)\s+|\s+(?=<|$)/g, '$1$3' );
				}
				);
			}
		},
	} );
} );
