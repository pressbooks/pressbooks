<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Sanitize;

/**
 * Convert HTML5 tags to XHTML11 divs.
 *
 * Will give a converted tag two classes.
 * Example: <aside>Howdy</aside> will become <div class="bc-aside aside">Howdy</div> (bc = Backward Compatible)
 * This function is used by htmLawed's hook.
 *
 * @param string $html
 * @param array $config (optional)
 * @param array $spec (optional) Extra HTML specifications using the $spec parameter
 *
 * @return string
 */
function html5_to_xhtml11( $html, $config = [], $spec = [] ) {

	$html5 = [
		'article',
		'aside',
		'audio',
		'bdi',
		'canvas',
		'command',
		'data',
		'datalist',
		'details',
		'embed',
		'figcaption',
		'figure',
		'footer',
		'header',
		'hgroup',
		'keygen',
		'mark',
		'meter',
		'nav',
		'output',
		'progress',
		'rp',
		'rt',
		'ruby',
		'section',
		'source',
		'summary',
		'time',
		'track',
		'video',
		'wbr',
	];

	$search_open = [];
	$search_closed = [];
	$replace_open = [];
	$replace_closed = [];

	foreach ( $html5 as $tag ) {
		$search_open[] = '`(<' . $tag . ')([^\w])`i';
		$replace_open[] = "<div class='bc-$tag $tag' $2";
		$search_closed[] = "</$tag>";
		$replace_closed[] = '</div>';
	}

	$html = preg_replace( $search_open, $replace_open, str_replace( $search_closed, $replace_closed, $html ) );

	return $html;
}

/**
 * Convert HTML5 to Epub3 compatible soup
 *
 * @param string $html
 * @param array $config (optional)
 * @param array $spec (optional) Extra HTML specifications using the $spec parameter
 *
 * @return string
 */
function html5_to_epub3( $html, $config = [], $spec = [] ) {

	// HTML5 elements we don't want to deal with just yet
	$html5 = [ 'command', 'embed', 'track' ];

	$search_open = [];
	$search_closed = [];
	$replace_open = [];
	$replace_closed = [];

	foreach ( $html5 as $tag ) {
		$search_open[] = '`(<' . $tag . ')([^\w])`i';
		$replace_open[] = "<div class='bc-$tag $tag' $2";
		$search_closed[] = "</$tag>";
		$replace_closed[] = '</div>';
	}

	$html = preg_replace( $search_open, $replace_open, str_replace( $search_closed, $replace_closed, $html ) );

	return $html;
}

/**
 * Setup a filter that removes style from WP audio shortcode
 *
 * @see \wp_audio_shortcode (in /wp-includes/media.php line ~1658)
 */
function fix_audio_shortcode() {

	add_filter(
		'wp_audio_shortcode', function ( $html, $atts, $audio, $post_id, $library ) {
			$html = preg_replace( '/(id=\"audio[0-9\-]*\")(.*)(style="[^\"]*\")/ui', '$1', $html );
			return $html;
		}, 10, 5
	);

}

/**
 * Sanitize XML attribute
 *
 * Here's what is allowed in an XML attribute value:
 * '"' ([^<&"] | Reference)* '"'  |  "'" ([^<&'] | Reference)* "'"
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
	$slug = str_replace( [ "\f" ], '', $slug );

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
	$slug = preg_replace( '([^a-zA-Z0-9-])', '', $slug );

	if ( empty( $slug ) ) {
		$slug = uniqid( 'slug-' );
	} elseif ( ! preg_match( '/^[a-z]/i', $slug ) ) {
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
 * Force ASCII (no control characters)
 *
 * @param $slug
 *
 * @return string
 */
function force_ascii( $slug ) {

	$slug = preg_replace( '/[^(\x20-\x7E)]*/', '', $slug );

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

	$slug = preg_replace( '/&lt;br\W*?\/&gt;/', ' ', $slug );
	$slug = preg_replace( '/<br\W*?\/>/', ' ', $slug );

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
	$allowed = [
		'br' => [],
		'span' => [
			'class' => [],
		],
		'em' => [],
		'strong' => [],
		'del' => [],
	];
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

	if ( preg_match( '#^mailto:#i', $url ) ) {
		return filter_var( $url, FILTER_SANITIZE_URL );
	}

	// Add http:// if it's missing
	if ( ! preg_match( '#^https?://#i', $url ) ) {
		// Remove ftp://, gopher://, fake://, etc
		if ( mb_strpos( $url, '://' ) ) {
			list( $garbage, $url ) = mb_split( '://', $url );
		}
		// Prepend http
		$url = 'http://' . $url;
	}

	// protocol and domain to lowercase (but NOT the rest of the URL),
	$scheme = wp_parse_url( $url, PHP_URL_SCHEME );
	$url = preg_replace( '/' . preg_quote( $scheme ) . '/', mb_strtolower( $scheme ), $url, 1 );
	$host = wp_parse_url( $url, PHP_URL_HOST );
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

/**
 * Search for all possible permutations of CSS url syntax -- url("*"), url('*'), and url(*) -- and update URLs as needed.
 *
 * @param string $css
 * @param string $url_path
 *
 * @return string
 */
function normalize_css_urls( $css, $url_path = '' ) {

	$url_regex = '/url\(([\s])?([\"|\'])?(.*?)([\"|\'])?([\s])?\)/i';
	$root_theme = get_template_directory_uri(); // If you want the current child theme then use `get_stylesheet_directory_uri()`

	$css = preg_replace_callback(
		$url_regex, function ( $matches ) use ( $url_path, $root_theme ) {

			$buckram_dir = get_theme_root( 'pressbooks-book' ) . '/pressbooks-book/packages/buckram/assets/';
			$typography_dir = get_theme_root( 'pressbooks-book' ) . '/pressbooks-book/assets/book/typography/';

			$url = $matches[3];

			// Look for relative fonts, convert to full http(s) url

			if ( preg_match( '#^themes-book/pressbooks-book/fonts/[a-zA-Z0-9_-]+(\.woff|\.otf|\.ttf)$#i', $url ) ) {
				$url = str_replace( 'themes-book/pressbooks-book/', '', $url );
				$my_asset = realpath( $typography_dir . $url );
				if ( $my_asset ) {
					return 'url(' . $root_theme . '/assets/book/typography/' . $url . ')';
				}
			}
			if ( preg_match( '#^fonts/[a-zA-Z0-9_-]+(\.woff|\.otf|\.ttf)$#i', $url ) ) {
				$my_asset = realpath( $typography_dir . $url );
				if ( $my_asset ) {
					return 'url(' . $root_theme . '/assets/book/typography/' . $url . ')';
				}
			}
			if ( preg_match( '#^uploads/assets/fonts/[a-zA-Z0-9_-]+(\.woff|\.otf|\.ttf)$#i', $url ) ) {
				$my_asset = realpath( WP_CONTENT_DIR . '/' . $url );
				if ( $my_asset ) {
					return 'url(' . set_url_scheme( WP_CONTENT_URL ) . '/' . $url . ')';
				}
			}

			// Look for images in Buckram
			if ( preg_match( '#^pressbooks-book/assets/book/images/[a-zA-Z0-9_-]+(\.svg|\.png)$#i', $url ) ) {
				$url = str_replace( 'pressbooks-book/assets/book/', '', $url );
				$my_asset = realpath( $buckram_dir . $url );
				if ( $my_asset ) {
					return 'url(' . $root_theme . '/packages/buckram/assets/' . $url . ')';
				}
			}

			if ( preg_match( '#^images/[a-zA-Z0-9_-]+(\.svg|\.png)$#i', $url ) ) {
				$my_asset = realpath( $buckram_dir . $url );
				if ( $my_asset ) {
					return 'url(' . $root_theme . '/packages/buckram/assets/' . $url . ')';
				}
			}

			// Look for anything !NOT! prefixed with http(s), convert to $url_path
			if ( $url_path && ! preg_match( '#^https?://#i', $url ) ) {
				if ( filter_var( $url_path, FILTER_VALIDATE_URL ) !== false ) {
					$my_asset = \Pressbooks\Utility\absolute_path( "$url_path/$url" );
				} else {
					$my_asset = realpath( "$url_path/$url" );
				}
				if ( $my_asset ) {
					return "url($my_asset)";
				}
			}

			return $matches[0]; // No change

		},
		$css
	);

	return $css;
}


/**
 * Allow language tagging on more inline elements.
 *
 * @return null
 */
function allow_post_content() {
	global $allowedposttags;

	$allowedposttags['caption']['lang'] = true;
	$allowedposttags['caption']['xml:lang'] = true;

	$allowedposttags['cite']['xml:lang'] = true;

	$allowedposttags['code']['lang'] = true;
	$allowedposttags['code']['xml:lang'] = true;

	$allowedposttags['dd']['lang'] = true;
	$allowedposttags['dd']['xml:lang'] = true;

	$allowedposttags['del']['lang'] = true;
	$allowedposttags['del']['xml:lang'] = true;

	$allowedposttags['dl']['lang'] = true;
	$allowedposttags['dl']['xml:lang'] = true;

	$allowedposttags['dt']['lang'] = true;
	$allowedposttags['dt']['xml:lang'] = true;

	$allowedposttags['em']['lang'] = true;
	$allowedposttags['em']['xml:lang'] = true;

	$allowedposttags['h1']['lang'] = true;
	$allowedposttags['h1']['xml:lang'] = true;

	$allowedposttags['h2']['lang'] = true;
	$allowedposttags['h2']['xml:lang'] = true;

	$allowedposttags['h3']['lang'] = true;
	$allowedposttags['h3']['xml:lang'] = true;

	$allowedposttags['h4']['lang'] = true;
	$allowedposttags['h4']['xml:lang'] = true;

	$allowedposttags['h5']['lang'] = true;
	$allowedposttags['h5']['xml:lang'] = true;

	$allowedposttags['h6']['lang'] = true;
	$allowedposttags['h6']['xml:lang'] = true;

	$allowedposttags['ins']['lang'] = true;
	$allowedposttags['ins']['xml:lang'] = true;

	$allowedposttags['kbd']['lang'] = true;
	$allowedposttags['kbd']['xml:lang'] = true;

	$allowedposttags['label']['lang'] = true;
	$allowedposttags['label']['xml:lang'] = true;

	$allowedposttags['legend']['lang'] = true;
	$allowedposttags['legend']['xml:lang'] = true;

	$allowedposttags['li']['lang'] = true;
	$allowedposttags['li']['xml:lang'] = true;

	$allowedposttags['ol']['lang'] = true;
	$allowedposttags['ol']['xml:lang'] = true;

	$allowedposttags['q']['lang'] = true;
	$allowedposttags['q']['xml:lang'] = true;

	$allowedposttags['strong']['lang'] = true;
	$allowedposttags['strong']['xml:lang'] = true;

	$allowedposttags['q']['lang'] = true;
	$allowedposttags['q']['xml:lang'] = true;

	$allowedposttags['td']['lang'] = true;
	$allowedposttags['td']['xml:lang'] = true;

	$allowedposttags['th']['lang'] = true;
	$allowedposttags['th']['xml:lang'] = true;

	$allowedposttags['ul']['lang'] = true;
	$allowedposttags['ul']['xml:lang'] = true;

	$allowedposttags['var']['lang'] = true;
	$allowedposttags['var']['xml:lang'] = true;

	return;
}


/**
 * Sanitizer for filename
 *
 * @param string $file
 *
 * @return string
 */
function clean_filename( $file ) {
	// Remove anything which isn't a word, whitespace, number or any of the following caracters -_~,;[]().
	$file = mb_ereg_replace( '([^\w\s\d\-_~,;\[\]\(\).])', '', $file );
	// Remove any runs of periods
	$file = mb_ereg_replace( '([\.]{2,})', '', $file );
	return $file;
}

/**
 * Remove auto-created <html> <body> and <!DOCTYPE> tags generated by DomDocument manipulations
 *
 * @param $html
 *
 * @return string
 */
function strip_container_tags( $html ) {
	// Strip all
	$strip_list = [ 'html', 'body' ];
	foreach ( $strip_list as $tag ) {
		$html = preg_replace( '/<\/?' . $tag . '(.|\s)*?>/im', '', $html );
	}
	// Strip <?xml (limit 1)
	$html = preg_replace( '/<\?xml.*>/im', '', $html, 1 );
	// Strip <!DOCTYPE (limit 1)
	$html = preg_replace( '/<!DOCTYPE.*>/im', '', $html, 1 );

	return (string) $html;
}


/**
 * Clean up CSS.
 *
 * Minimal intervention, but prevent users from injecting garbage.
 *
 * @param $css
 *
 * @return string
 */
function cleanup_css( $css ) {

	$css = stripslashes( $css );
	$prev = $css;
	$css = preg_replace( '/\\\\([0-9a-fA-F]{2,4})/', '\\\\\\\\$1', $prev );

	if ( $css !== $prev ) {
		$warnings[] = 'preg_replace() double escaped unicode escape sequences';
	}

	$css = str_replace( '<=', '&lt;=', $css ); // Some people put weird stuff in their CSS, KSES tends to be greedy
	$prev = $css;
	$css = wp_kses_split( $prev, [], [] );
	$css = str_replace( '&gt;', '>', $css ); // kses replaces lone '>' with &gt;
	$css = strip_tags( $css );

	if ( $css !== $prev ) {
		$warnings[] = 'kses() and strip_tags() do not match';
	}

	// TODO: Something with $warnings[]

	return $css;
}

/**
 * Prettify HTML
 *
 * @param $html
 *
 * @return string
 */
function prettify( $html ) {

	// Simplest, allowing all valid HTML markup except uncommon URL schemes like 'whatsapp:', and prettying-up the HTML

	$config = [
		'tidy' => 5,
		'unique_ids' => 0,
	];

	return \Pressbooks\HtmLawed::filter( $html, $config );
}

/**
 * Check whether a variable is a unix timestamp
 *
 * @since 5.0.0
 *
 * @param mixed $timestamp
 *
 * @return bool
 */
function is_valid_timestamp( $timestamp ) {
	if ( is_int( $timestamp ) ) {
		return true;
	} else {
		return ( (string) (int) $timestamp === $timestamp ) && ( $timestamp <= PHP_INT_MAX ) && ( $timestamp >= ~PHP_INT_MAX );
	}
}

/**
 * Reverse wpautop
 *
 * @see wpautop
 * @see shortcode_unautop
 *
 * @param string $pee
 *
 * @return string
 */
function reverse_wpautop( $pee ) {
	// Pre tags shouldn't be touched by autop. Replace pre tags with placeholders and bring them back after autop.
	if ( strpos( $pee, '<pre' ) !== false ) {
		$pee_parts = explode( '</pre>', $pee );
		$last_pee = array_pop( $pee_parts );
		$pee = '';
		$i = 0;

		foreach ( $pee_parts as $pee_part ) {
			$start = strpos( $pee_part, '<pre' );

			// Malformed html?
			if ( $start === false ) {
				$pee .= $pee_part;
				continue;
			}

			$name = "<pre wp-pre-tag-$i></pre>";
			$pre_tags[ $name ] = substr( $pee_part, $start ) . '</pre>';

			$pee .= substr( $pee_part, 0, $start ) . $name;
			$i++;
		}

		$pee .= $last_pee;
	}

	// Code is the same as shortcode_unautop() but instead of ensuring shortcodes are not wrapped in `<p>...</p>`, we keep the trailing </p> for later
	// @codingStandardsIgnoreStart
	global $shortcode_tags;
	if ( is_array( $shortcode_tags ) ) {
		$tagregexp = join( '|', array_map( 'preg_quote', array_keys( $shortcode_tags ) ) );
		$spaces = wp_spaces_regexp();

		$pattern =
			'/'
			. '<p>'                              // Opening paragraph
			. '(?:' . $spaces . ')*+'            // Optional leading whitespace
			. '('                                // 1: The shortcode
			.     '\\['                          // Opening bracket
			.     "($tagregexp)"                 // 2: Shortcode name
			.     '(?![\\w-])'                   // Not followed by word character or hyphen
			// Unroll the loop: Inside the opening shortcode tag
			.     '[^\\]\\/]*'                   // Not a closing bracket or forward slash
			.     '(?:'
			.         '\\/(?!\\])'               // A forward slash not followed by a closing bracket
			.         '[^\\]\\/]*'               // Not a closing bracket or forward slash
			.     ')*?'
			.     '(?:'
			.         '\\/\\]'                   // Self closing tag and closing bracket
			.     '|'
			.         '\\]'                      // Closing bracket
			.         '(?:'                      // Unroll the loop: Optionally, anything between the opening and closing shortcode tags
			.             '[^\\[]*+'             // Not an opening bracket
			.             '(?:'
			.                 '\\[(?!\\/\\2\\])' // An opening bracket not followed by the closing shortcode tag
			.                 '[^\\[]*+'         // Not an opening bracket
			.             ')*+'
			.             '\\[\\/\\2\\]'         // Closing shortcode tag
			.         ')?'
			.     ')'
			. ')'
			. '(?:' . $spaces . ')*+'            // optional trailing whitespace
			. '<\\/p>'                           // closing paragraph
			. '/';

		$pee = preg_replace( $pattern, '$1</p>', $pee );
	}
	// @codingStandardsIgnoreEnd

	// Reverses "If a <blockquote> is wrapped with a <p>, move it inside the <blockquote>."
	$pee = str_replace( '</p></blockquote>', '</blockquote></p>', $pee );

	// Cheap and barely good enough...
	$pee = str_replace( "\n", '', $pee );
	$pee = str_replace( '<p>', '', $pee );
	$pee = str_replace( [ '<br />', '<br>', '<br/>' ], "\n", $pee );
	$pee = str_replace( '</p>', "\n\n", $pee );

	// Replace placeholder <pre> tags with their original content.
	if ( ! empty( $pre_tags ) ) {
		$pee = str_replace( array_keys( $pre_tags ), array_values( $pre_tags ), $pee );
	}

	return $pee;
}

/**
 * Sanitize post content for webbook output.
 *
 * @since 5.6.0
 *
 * @param string $content
 * @return string
 */

function sanitize_webbook_content( $content ) {
	$spec = '';
	$spec .= 'table=-border;';

	return \Pressbooks\HtmLawed::filter( $content, [], $spec );
}

/**
 * Apply the_content filters minus webbook-specific ones.
 *
 * @since 5.6.0
 *
 * @param string $content Input content
 * @return string
 */

function filter_export_content( $content ) {
	remove_filter( 'the_content', '\Pressbooks\Sanitize\sanitize_webbook_content' );
	$content = apply_filters( 'the_content', $content );
	add_filter( 'the_content', '\Pressbooks\Sanitize\sanitize_webbook_content' );
	return $content;
}
