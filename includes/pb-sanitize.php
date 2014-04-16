<?php
/**
 * @author  PressBooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */
namespace PressBooks\Sanitize;


/**
 * Convert HTML5 tags to XHTML11 divs.
 * Will give a converted tag two classes.
 * Example: <aside>Howdy</aside> will become <div class="bc-aside aside">Howdy</div> (bc = Backward Compatible)
 * This function is used by htmLawed's hook.
 *
 * @param $t
 * @param $C (optional) unused
 * @param $S (optional) unused
 *
 * @return string
 */
function html5_to_xhtml11( $t, $C = array(), $S = array() ) {

	$html5 = array(
		'article', 'aside', 'audio', 'bdi', 'canvas', 'command', 'data', 'datalist', 'details', 'embed', 'figcaption',
		'figure', 'footer', 'header', 'hgroup', 'keygen', 'mark', 'meter', 'nav', 'output', 'progress', 'rp', 'rt',
		'ruby', 'section', 'source', 'summary', 'time', 'track', 'video', 'wbr',
	);

	$search_open = $replace_open = $search_closed = $replace_closed = array();

	foreach ( $html5 as $tag ) {
		$search_open[] = '`(<' . $tag . ')([^\w])`i';
		$replace_open[] = "<div class='bc-$tag $tag' $2";
		$search_closed[] = "</$tag>";
		$replace_closed[] = '</div>';
	}

	$t = preg_replace( $search_open, $replace_open, str_replace( $search_closed, $replace_closed, $t ) );

	return $t;
}

/**
 * Convert HTML5 tags to XHTML5 divs
 * 
 * @param type $t
 * @param type $C
 * @param type $S
 * @return string
 */
function html5_to_xhtml5( $t, $C = array(), $S = array() ) {

	// HTML5 elements we don't want to deal with just yet
	$html5 = array(
	    'bdi', 'canvas', 'command', 'data', 'datalist', 'embed', 
	    'keygen', 'mark', 'meter', 'nav', 'output', 'progress', 'rp', 'rt',
	    'ruby', 'time', 'track', 'wbr',
	);

	$search_open = $replace_open = $search_closed = $replace_closed = array();

	foreach ( $html5 as $tag ) {
		$search_open[] = '`(<' . $tag . ')([^\w])`i';
		$replace_open[] = "<div class='bc-$tag $tag' $2";
		$search_closed[] = "</$tag>";
		$replace_closed[] = '</div>';
	}

	$t = preg_replace( $search_open, $replace_open, str_replace( $search_closed, $replace_closed, $t ) );

	return $t;
}

/**
 * Sanitize XML attribute
 *
 * Here's what is allowed in an XML attribute value:
 *     '"' ([^<&"] | Reference)* '"'  |  "'" ([^<&'] | Reference)* "'"
 *
 * So, you can't have:
 *  + the same character that opens/closes the attribute value (either ' or ")
 *  + a naked ampersand (& must be &amp;)
 *  + a left angle bracket (< must be &lt;)
 *
 * You should also not being using any characters that are outright not legal anywhere in an XML document (such as
 * form feeds, etc).
 *
 * @param string $slug
 *
 * @return string
 */
function sanitize_xml_attribute( $slug ) {

	$slug = trim( $slug );
	$slug = html_entity_decode( $slug, ENT_COMPAT | ENT_XHTML, 'UTF-8' );
	$slug = htmlspecialchars( $slug, ENT_COMPAT | ENT_XHTML, 'UTF-8', false );

	return $slug;
}


/**
 * Sanitize XML id
 *
 * @param string $slug
 *
 * @return string
 */
function sanitize_xml_id( $slug ) {

	$slug = trim( $slug );
	$slug = html_entity_decode( $slug, ENT_COMPAT | ENT_XHTML, 'UTF-8' );
	$slug = remove_accents( $slug );
	$slug = preg_replace( "([^a-zA-Z0-9-])", '', $slug );

	if ( empty( $slug ) ) {
		$slug = uniqid( 'slug-' );
	} else if ( ! preg_match( '/^[a-z]/i', $slug ) ) {
		$slug = 'slug-' . $slug;
	}

	return $slug;
}


/**
 * Get rid of control characters. Strange hidden characters that mess things up.
 *
 * @param string $slug
 *
 * @return string
 */
function remove_control_characters( $slug ) {

	$slug = preg_replace( '/[\x00-\x1F\x7F]/', '', $slug );

	return $slug;
}


/**
 * Force ASCII
 *
 * @param $slug
 *
 * @return string
 */
function force_ascii( $slug ) {

	$slug = preg_replace( '/[^(\x20-\x7F)]*/', '', $slug );

	return $slug;
}


/**
 * Reverse htmlspecialchars() except ampersands.
 *
 * @param $slug
 *
 * @return mixed
 */
function decode( $slug ) {

	$slug = html_entity_decode( $slug, ENT_NOQUOTES | ENT_XHTML, 'UTF-8' );
	$slug = preg_replace( '/&([^#])(?![a-z1-4]{1,8};)/i', '&#038;$1', $slug );

	return $slug;
}


/**
 * Strip <br /> tags.
 *
 * @param $slug
 *
 * @return string
 */
function strip_br( $slug ) {
	
	$slug = str_replace( '&lt;br /&gt;', ' ', $slug );
	$slug = str_replace( '<br />', ' ', $slug );
	
	return $slug;

}

/**
 * Filter post_title according to our specifications.
 *
 * @param $title
 *
 * @return string
 */
function filter_title( $title ) {
	$allowed = array(
		'br' => array(),
		'span' => array( 'class' => array() ),
		'em' => array(),
		'strong' => array()
	);
	return wp_kses( $title, $allowed );
}

/**
 * Canonicalize URL
 *
 * @param $url
 *
 * @return string
 */
function canonicalize_url( $url ) {

	// remove trailing slash
	$url = rtrim( trim( $url ), '/' );

	// Add http:// if it's missing
	if ( ! preg_match( '#^https?://#i', $url ) ) {
		// Remove ftp://, gopher://, fake://, etc
		if ( mb_strpos( $url, '://' ) ) list( $garbage, $url ) = mb_split( '://', $url );
		// Prepend http
		$url = 'http://' . $url;
		if ( preg_match( '#^http:///#', $url ) ) {
			return ''; // This is wrong...
		}
	}

	// protocol and domain to lowercase (but NOT the rest of the URL),
	$scheme = @parse_url( $url, PHP_URL_SCHEME );
	$url = preg_replace( '/' . preg_quote( $scheme ) . '/', mb_strtolower( $scheme ), $url, 1 );
	$host = @parse_url( $url, PHP_URL_HOST );
	$url = preg_replace( '/' . preg_quote( $host ) . '/', mb_strtolower( $host ), $url, 1 );

	// Sanitize for good measure
	$url = filter_var( $url, FILTER_SANITIZE_URL );

	return $url;

}


/**
 * Maybe change http:// to https://, depending on server state.
 *
 * @param $url
 *
 * @return string
 */
function maybe_https( $url ) {
	if ( empty( $_SERVER['HTTPS'] ) ) {
		return $url;
	} else {
		return preg_replace( '/^http:/', 'https:', $url );
	}
}
