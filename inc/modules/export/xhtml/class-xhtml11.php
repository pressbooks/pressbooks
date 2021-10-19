<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 * See templating function for reference: \Pressbooks\Modules\Export\Export loadTemplate()
 */
// TODO: Security audit
// @phpcs:disable Pressbooks.Security.ValidatedSanitizedInput.MissingUnslash
// @phpcs:disable Pressbooks.Security.ValidatedSanitizedInput.InputNotSanitized
// @phpcs:disable Pressbooks.Security.EscapeOutput.OutputNotEscaped

namespace Pressbooks\Modules\Export\Xhtml;

use Jenssegers\Blade\Blade;
use function Pressbooks\Sanitize\clean_filename;
use function Pressbooks\Utility\get_contributors_name_imploded;
use function Pressbooks\Utility\str_starts_with;
use PressbooksMix\Assets;
use Pressbooks\Container;
use Pressbooks\HtmlParser;
use Pressbooks\Modules\Export\ExportGenerator;
use Pressbooks\Sanitize;
use Pressbooks\Utility\PercentageYield;
use Pressbooks\Modules\Export\ExportHelpers;

class Xhtml11 extends ExportGenerator {

	use ExportHelpers;

	const TRANSIENT = 'pressbooks_export_xhtml_buffer_inner_html';

	/**
	 * Prettify HTML
	 *
	 * @var bool
	 */
	public $tidy = false;

	/**
	 * Service URL
	 *
	 * @var string
	 */
	public $url;

	/**
	 * @var string
	 */
	public $transformOutput;

	/**
	 * Endnotes storage container.
	 * Use when overriding the footnote shortcode.
	 *
	 * @var array
	 */
	protected $endnotes = [];

	/**
	 * Footnotes storage container.
	 *
	 * @var array
	 */
	protected $footnotes = [];

	/**
	 * We forcefully reorder some of the front-matter types to respect the Chicago Manual of Style.
	 * Keep track of where we are using this variable.
	 *
	 * @var int
	 */
	protected $frontMatterPos = 1;

	/**
	 * Sometimes the user will omit an introduction so we must inject the style in either the first
	 * part or the first chapter ourselves.
	 *
	 * @var bool
	 */
	protected $hasIntroduction = false;

	/**
	 * Should all header elements be wrapped in a container? Requires a theme based on Buckram.
	 *
	 * @see https://github.com/pressbooks/buckram/
	 *
	 * @var bool
	 */
	protected $wrapHeaderElements = false;

	/**
	 * Should the short title be output in a hidden element? Requires a theme based on Buckram 1.2.0 or greater.
	 *
	 * @see https://github.com/pressbooks/buckram/
	 *
	 * @var bool
	 */
	protected $outputShortTitle = true;

	/**
	 * Main language of document, two letter code
	 *
	 * @var string
	 */
	protected $lang = 'en';

	/**
	 * @var string
	 */
	protected $generatorPrefix;

	/**
	 * @var \Pressbooks\Taxonomy
	 */
	protected $taxonomy;

	/**
	 * @var \Pressbooks\Contributors
	 */
	protected $contributors;

	/**
	 * @var bool
	 */
	protected $displayAboutTheAuthors;

	/**
	 * @var Blade
	 */
	protected $blade;

	/**
	 * @param array $args
	 */
	public function __construct( array $args ) {

		// Some defaults

		$this->taxonomy = \Pressbooks\Taxonomy::init();
		$this->contributors = new \Pressbooks\Contributors();
		$this->blade = Container::get( 'Blade' );

		if ( Container::get( 'Styles' )->hasBuckram( '0.3.0' ) ) {
			$this->wrapHeaderElements = true;
		}

		if ( Container::get( 'Styles' )->hasBuckram( '1.2.0' ) ) {
			$this->outputShortTitle = false;
		}

		if ( ! defined( 'PB_XMLLINT_COMMAND' ) ) {
			define( 'PB_XMLLINT_COMMAND', '/usr/bin/xmllint' );
		}

		$defaults = [
			'endnotes' => false,
		];
		$r = wp_parse_args( $args, $defaults );

		// Set the access protected "format/xhtml" URL with a valid timestamp and NONCE
		$timestamp = time();
		$md5 = $this->nonce( $timestamp );
		$this->url = home_url() . "/format/xhtml?timestamp={$timestamp}&hashkey={$md5}";

		// Append endnotes to URL?
		if ( $r['endnotes'] ) {
			$this->url .= '&endnotes=true';
			$_GET['endnotes'] = true;
		}

		// HtmLawed: id values not allowed in input
		foreach ( $this->reservedIds as $val ) {
			$fixme[ $val ] = 1;
		}
		if ( isset( $fixme ) ) {
			$GLOBALS['hl_Ids'] = $fixme;
		}

		$this->generatorPrefix = __( 'XHTML: ', 'pressbooks' );
	}

	/**
	 * Create $this->outputPath
	 *
	 * @return bool
	 */
	public function convert() {
		try {
			foreach ( $this->convertGenerator() as $percentage => $info ) {
				// Do nothing, this is a compatibility wrapper that makes the generator work like a regular function
			}
		} catch ( \Exception $e ) {
			return false;
		}
		return true;
	}

	/**
	 * Yields an estimated percentage slice of: 1 to 80
	 *
	 * @return \Generator
	 * @throws \Exception
	 */
	public function convertGenerator() : \Generator {
		yield 1 => $this->generatorPrefix . __( 'Initializing', 'pressbooks' );

		yield from $this->transformGenerator();

		if ( ! $this->transformOutput ) {
			throw new \Exception();
		}

		yield 75 => $this->generatorPrefix . __( 'Saving file to exports folder', 'pressbooks' );
		$filename = $this->timestampedFileName( '.html' );
		\Pressbooks\Utility\put_contents( $filename, $this->transformOutput );
		$this->outputPath = $filename;
		yield 80 => $this->generatorPrefix . __( 'Export successful', 'pressbooks' );
	}

	/**
	 * Check the sanity of $this->outputPath
	 *
	 * @return bool
	 */
	public function validate() {
		try {
			foreach ( $this->validateGenerator() as $percentage => $info ) {
				// Do nothing, this is a compatibility wrapper that makes the generator work like a regular function
			}
		} catch ( \Exception $e ) {
			return false;
		}
		return true;
	}

	/**
	 * Yields an estimated percentage slice of: 80 to 100
	 *
	 * @return \Generator
	 * @throws \Exception
	 */
	public function validateGenerator() : \Generator {
		yield 80 => $this->generatorPrefix . __( 'Validating file', 'pressbooks' );

		// Xmllint params
		$command = PB_XMLLINT_COMMAND . ' --html --valid --noout ' . escapeshellcmd( $this->outputPath ) . ' 2>&1';

		// Execute command
		$output = [];
		$return_var = 0;
		exec( $command, $output, $return_var );

		// Is this a valid XHTML?
		if ( is_countable( $output ) && count( $output ) ) {
			$this->logError( implode( "\n", $output ) );
			throw new \Exception();
		}

		yield 100 => $this->generatorPrefix . __( 'Validation successful', 'pressbooks' );
	}

	/**
	 * Procedure for "format/xhtml" rewrite rule.
	 *
	 * Supported http (aka $_GET) params:
	 *
	 *   + timestamp: (int) combines with `hashkey` to allow a 3rd party service temporary access
	 *   + hashkey: (string) combines with `timestamp` to allow a 3rd party service temporary access
	 *   + endnotes: (bool) move all footnotes to end of the book
	 *   + style: (string) name of a user generated stylesheet you want included in the header
	 *   + script: (string) name of javascript file you you want included in the header
	 *   + preview: (bool) Use `Content-Disposition: inline` instead of `Content-Disposition: attachment` when passing through Export::formSubmit
	 *   + optimize-for-print: (bool) Replace images with originals when possible, add class="print" to <body>, and other print specific tweaks
	 *
	 * @see \Pressbooks\Redirect\do_format
	 *
	 * @param bool $return (optional) If you would like to capture the output of transform, use the return parameter. If this parameter is set
	 * to true, transform will return its output, instead of printing it.
	 *
	 * @return mixed
	 */
	public function transform( $return = false ) {

		// Check permissions

		if ( ! current_user_can( 'edit_posts' ) ) {
			$timestamp = ( isset( $_REQUEST['timestamp'] ) ) ? absint( $_REQUEST['timestamp'] ) : 0;
			$hashkey = ( isset( $_REQUEST['hashkey'] ) ) ? $_REQUEST['hashkey'] : '';
			if ( ! $this->verifyNonce( $timestamp, $hashkey ) ) {
				wp_die( __( 'Invalid permission error', 'pressbooks' ) );
			}
		}

		try {
			foreach ( $this->transformGenerator() as $percentage => $info ) {
				// Do nothing, this is a compatibility wrapper that makes the generator work like a regular function
			}
		} catch ( \Exception $e ) {
			return null;
		}

		if ( $return ) {
			return $this->transformOutput;
		} else {
			echo $this->transformOutput;
			return null;
		}
	}

	/**
	 * Yields an estimated percentage slice of: 10 to 75
	 *
	 * @return \Generator
	 * @throws \Exception
	 */
	public function transformGenerator() : \Generator {

		do_action( 'pb_pre_export' );

		// Override footnote shortcode
		if ( ! empty( $_GET['endnotes'] ) ) {
			add_shortcode( 'footnote', [ $this, 'endnoteShortcode' ] );
		} else {
			add_shortcode( 'footnote', [ $this, 'footnoteShortcode' ] );
		}
		// Use SVG for math
		add_filter( 'pb_mathjax_use_svg', '__return_true' );

		// ------------------------------------------------------------------------------------------------------------
		// XHTML, Start!

		$metadata = \Pressbooks\Book::getBookInformation( null, false, false );
		$_unused = [];

		// Set two letter language code
		if ( isset( $metadata['pb_language'] ) ) {
			list( $this->lang ) = explode( '-', $metadata['pb_language'] );
		}

		// ------------------------------------------------------------------------------------------------------------
		// Buffer for Outer XHTML

		ob_start();

		$this->echoDocType( $_unused, $_unused );

		echo "<head>\n";
		echo '<meta content="text/html; charset=UTF-8" http-equiv="content-type" />' . "\n";
		echo '<meta http-equiv="Content-Language" content="' . $this->lang . '" />' . "\n";
		echo '<meta name="generator" content="Pressbooks ' . PB_PLUGIN_VERSION . '" />' . "\n";

		$this->echoMetaData( $_unused, $metadata );

		echo '<title>' . get_bloginfo( 'name' ) . "</title>\n";

		if ( current_user_can( 'edit_posts' ) ) {
			if ( ! empty( $_GET['debug'] ) ) {
				$assets = new Assets( 'pressbooks', 'plugin' );
				$css = ( $_GET['debug'] === 'prince' ) ? $this->getLatestExportStyleUrl( 'prince' ) : false;
				$js = $assets->getPath( 'scripts/paged.polyfill.js' );
				if ( $css ) {
					echo "<link rel='stylesheet' href='$css' type='text/css' />\n";
				}
				echo "<script src='$js'></script>\n";
			}
		}

		if ( ! empty( $_GET['style'] ) ) {
			$url = ( $_GET['style'] === 'prince' ) ? $this->getLatestExportStyleUrl( 'prince' ) : false;
			if ( $url ) {
				echo "<link rel='stylesheet' href='$url' type='text/css' />\n";
			}
		}
		if ( ! empty( $_GET['script'] ) ) {
			$url = $this->getExportScriptUrl( clean_filename( $_GET['script'] ) );
			if ( $url ) {
				echo "<script src='$url' type='text/javascript'></script>\n";
			}
		}
		echo "</head>\n<body lang='{$this->lang}' ";
		if ( ! empty( $_GET['optimize-for-print'] ) ) {
			echo "class='print' ";
		}
		echo ">\n";
		$replace_token = uniqid( 'PB_REPLACE_INNER_HTML_', true );
		echo $replace_token;
		if ( ! empty( $_GET['movefootnotes'] ) ) {
			echo "\n <script>
					function moveFootnotes() {
						var footnotes = document.getElementsByClassName( 'footnote-indirect' );
						for ( var i = 0; i < footnotes.length; i++ ) {
							var ref = document.getElementById( footnotes[i].getAttribute( 'data-fnref' ) );
							if ( ref ) {
								footnotes[i].appendChild( ref );
							}
						}
					}
					if ( typeof Prince != 'undefined' ) {
						moveFootnotes();
					}
				</script> \n";
		}
		echo "\n</body>\n</html>";

		$buffer_outer_html = ob_get_clean();

		// ------------------------------------------------------------------------------------------------------------
		// Buffer for Inner XHTML

		$my_get = $_GET;
		unset( $my_get['timestamp'], $my_get['hashkey'] );
		$cache = get_transient( self::TRANSIENT );
		if ( is_array( $cache ) && isset( $cache[0] ) && $cache[0] === md5( wp_json_encode( $my_get ) ) ) {
			// The $_GET parameters haven't changed since the last request so the output will be the same
			$buffer_inner_html = $cache[1];
		} else {
			$book_contents = $this->preProcessBookContents( \Pressbooks\Book::getBookContents() );
			ob_start();

			$this->displayAboutTheAuthors = ! empty( get_option( 'pressbooks_theme_options_global', [] )['about_the_author'] );

			// Before Title Page
			yield 10 => $this->generatorPrefix . __( 'Creating before title page', 'pressbooks' );
			$this->renderBeforeTitle( $book_contents, $_unused );

			// Half-title
			yield 15 => $this->generatorPrefix . __( 'Creating half title page', 'pressbooks' );
			$this->echoHalfTitle( $_unused, $_unused );

			// Cover
			yield 20 => $this->generatorPrefix . __( 'Creating cover', 'pressbooks' );
			$this->renderCover( $book_contents, $metadata );

			// Title
			yield 25 => $this->generatorPrefix . __( 'Creating title page', 'pressbooks' );
			$this->renderTitle( $book_contents, $metadata );

			// Copyright
			yield 30 => $this->generatorPrefix . __( 'Creating copyright page', 'pressbooks' );
			$this->renderCopyright( $metadata );

			// Dedication and Epigraph (In that order!)
			yield 35 => $this->generatorPrefix . __( 'Creating dedication and epigraph', 'pressbooks' );
			$this->renderDedicationAndEpigraph( $book_contents, $_unused );

			// Table of contents
			yield 40 => $this->generatorPrefix . __( 'Creating table of contents', 'pressbooks' );
			$this->echoToc( $book_contents, $_unused );

			// Front-matter
			yield 50 => $this->generatorPrefix . __( 'Exporting front matter', 'pressbooks' );
			yield from $this->renderFrontMatterGenerator( $book_contents, $metadata );

			// Promo
			$this->createPromo( $_unused, $_unused );

			// Parts, Chapters
			yield 60 => $this->generatorPrefix . __( 'Exporting parts and chapters', 'pressbooks' );
			yield from $this->echoPartsAndChaptersGenerator( $book_contents, $metadata );

			// Back-matter
			yield 70 => $this->generatorPrefix . __( 'Exporting back matter', 'pressbooks' );
			yield from $this->echoBackMatterGenerator( $book_contents, $metadata );

			$buffer_inner_html = ob_get_clean();

			if ( $this->tidy ) {
				$buffer_inner_html = Sanitize\prettify( $buffer_inner_html );
			}

			// Put the $_GET parameters and the buffer in a transient
			set_transient( self::TRANSIENT, [ md5( wp_json_encode( $my_get ) ), $buffer_inner_html ] );
		}

		// Put inner HTML inside outer HTML
		$pos = strpos( $buffer_outer_html, $replace_token );
		$buffer = substr_replace( $buffer_outer_html, $buffer_inner_html, $pos, strlen( $replace_token ) );

		$this->transformOutput = $buffer;
	}

	/**
	 * Add $this->url as additional log info, fallback to parent.
	 *
	 * @param $message
	 * @param array $more_info (unused, overridden)
	 */
	function logError( $message, array $more_info = [] ) {

		$more_info['url'] = $this->url;

		parent::logError( $message, $more_info );
	}

	/**
	 * Wrap footnotes for Prince compatibility
	 *
	 * @see http://www.princexml.com/doc/8.1/footnotes/
	 *
	 * @param       $atts
	 * @param null  $content
	 *
	 * @return string
	 */
	function footnoteShortcode( $atts, $content = null ) {
		global $id; // This is the Post ID, [@see WP_Query::setup_postdata, preProcessBookContents, ...]
		$this->footnotes[ $id ][] = trim( $content );
		$ref_id = count( $this->footnotes[ $id ] );
		$ref_id_dom = $id . '-' . $ref_id;
		return "<span class='footnote'><span class='footnote-indirect' data-fnref='$ref_id_dom'></span></span>";
	}

	/**
	 * Convert footnotes to endnotes by moving them to the end of the_content()
	 *
	 * @see doEndnotes
	 *
	 * @param array $atts
	 * @param null $content
	 *
	 * @return string
	 */
	function endnoteShortcode( $atts, $content = null ) {

		global $id; // This is the Post ID, [@see WP_Query::setup_postdata, preProcessBookContents, ...]

		if ( ! $content ) {
			return '';
		}

		$this->endnotes[ $id ][] = trim( $content );

		return '<sup class="endnote">' . count( $this->endnotes[ $id ] ) . '</sup>';
	}

	/**
	 * Style endnotes.
	 *
	 * @see endnoteShortcode
	 *
	 * @param $id
	 *
	 * @return string
	 */
	function doEndnotes( $id ) {

		if ( ! isset( $this->endnotes[ $id ] ) || ! count( $this->endnotes[ $id ] ) ) {
			return '';
		}

		$e = '<div class="endnotes">';
		$e .= '<hr />';
		$e .= '<h3>' . __( 'Notes', 'pressbooks' ) . '</h3>';
		$e .= '<ol>';
		foreach ( $this->endnotes[ $id ] as $endnote ) {
			$e .= "<li><span>$endnote</span></li>";
		}
		$e .= '</ol></div>';

		return $e;
	}

	/**
	 * Content for footnotes.
	 *
	 * @see footnoteShortCode
	 *
	 * @param $id
	 *
	 * @return string
	 */
	function doFootnotes( $id ) {
		if ( ! isset( $this->footnotes[ $id ] ) || ! count( $this->footnotes[ $id ] ) ) {
			return '';
		}

		$e = '<div class="footnotes">';
		foreach ( $this->footnotes[ $id ] as $k => $footnote ) {
			$key = $k + 1;
			$id_attr = $id . '-' . $key;
			$e .= "<div id='$id_attr'>" . $this->fixInternalLinks( $footnote ) . '</div>';
		}
		$e .= '</div>';

		return $e;
	}

	// ----------------------------------------------------------------------------------------------------------------
	// Sanitize book
	// ----------------------------------------------------------------------------------------------------------------

	/**
	 * @param $book_contents
	 *
	 * @return mixed
	 */
	protected function preProcessBookContents( $book_contents ) {

		// We need to change global $id for shortcodes, the_content, ...
		global $id;
		$old_id = $id;

		// Do root level structures first.
		foreach ( $book_contents as $type => $struct ) {

			if ( preg_match( '/^__/', $type ) ) {
				continue; // Skip __magic keys
			}

			foreach ( $struct as $i => $val ) {

				if ( isset( $val['post_content'] ) ) {
					$id = $val['ID'];
					if ( $val['export'] ) {
						$book_contents[ $type ][ $i ]['post_content'] = $this->preProcessPostContent( $val['post_content'], $id );
					} else {
						$book_contents[ $type ][ $i ]['post_content'] = '';
					}
				}
				if ( isset( $val['post_title'] ) ) {
					$book_contents[ $type ][ $i ]['post_title'] = Sanitize\sanitize_xml_attribute( $val['post_title'] );
				}
				if ( isset( $val['post_name'] ) ) {
					$book_contents[ $type ][ $i ]['post_name'] = $this->preProcessPostName( $val['post_name'] );
				}

				if ( 'part' === $type ) {

					// Do chapters, which are embedded in part structure
					foreach ( $book_contents[ $type ][ $i ]['chapters'] as $j => $val2 ) {

						if ( isset( $val2['post_content'] ) ) {
							$id = $val2['ID'];
							$book_contents[ $type ][ $i ]['chapters'][ $j ]['post_content'] = $this->preProcessPostContent( $val2['post_content'], $id );
						}
						if ( isset( $val2['post_title'] ) ) {
							$book_contents[ $type ][ $i ]['chapters'][ $j ]['post_title'] = Sanitize\sanitize_xml_attribute( $val2['post_title'] );
						}
						if ( isset( $val2['post_name'] ) ) {
							$book_contents[ $type ][ $i ]['chapters'][ $j ]['post_name'] = $this->preProcessPostName( $val2['post_name'] );
						}
					}
				}
			}
		}

		$id = $old_id;
		return $book_contents;
	}

	/**
	 * @param string $content
	 * @param int    $id
	 *
	 * @return string
	 */
	protected function preProcessPostContent( $content, $id = null ) {
		$content = apply_filters( 'the_export_content', $content );
		$content = str_ireplace( [ '<b></b>', '<i></i>', '<strong></strong>', '<em></em>' ], '', $content );
		$content = $this->fixInternalLinks( $content, $id );
		$content = $this->switchLaTexFormat( $content );
		$content = $this->fixImageAttributes( $content );
		if ( ! empty( $_GET['optimize-for-print'] ) ) {
			$content = $this->fixImages( $content );
		}
		$content = $this->tidy( $content );

		return $content;
	}

	protected function fixImageAttributes( $content ) {
		$html5 = new HtmlParser();
		$dom = $html5->loadHTML( $content );
		$images = $dom->getElementsByTagName( 'img' );
		foreach ( $images as $image ) {
			$alt = $image->getAttribute( 'alt' );
			$alt = htmlspecialchars( $alt );
			$image->setAttribute( 'alt', $alt );
			$title = $image->getAttribute( 'title' );
			$title = htmlspecialchars( $title );
			$image->setAttribute( 'title', $title );
		}
		$content = $html5->saveHTML( $dom );
		return $content;
	}

	/**
	 * Replace links to QuickLaTex PNG files with links to the corresponding SVG files.
	 *
	 * @param string $content The section content.
	 *
	 * @return string
	 */
	protected function switchLaTexFormat( $content ) {
		$content = preg_replace( '/(quicklatex.com-[a-f0-9]{32}_l3.)(png)/i', '$1svg', $content );

		return $content;
	}

	/**
	 * @param string $source_content
	 * @param int    $id
	 *
	 * @return string
	 */
	protected function fixInternalLinks( $source_content, $id = null ) {

		if ( stripos( $source_content, '<a' ) === false ) {
			// There are no <a> tags to look at, skip this
			return $source_content;
		}

		$home_url = rtrim( home_url(), '/' );
		$html5 = new HtmlParser();
		$dom = $html5->loadHTML( $source_content );
		$links = $dom->getElementsByTagName( 'a' );

		foreach ( $links as $link ) {
			/** @var \DOMElement $link */
			$href = $link->getAttribute( 'href' );

			if ( str_starts_with( $href, '#' ) && ! empty( $id ) ) {
				$link->setAttribute( 'data-url', get_permalink( $id ) . $href );
			} else {
				$link->setAttribute( 'data-url', $href );
			}

			if ( str_starts_with( $href, '/' ) || str_starts_with( $href, $home_url ) ) {
				$pos = strpos( $href, '#' );
				if ( $pos !== false ) {
					// Use the #fragment
					$fragment = substr( $href, strpos( $href, '#' ) + 1 );
				} elseif ( preg_match( '%(front\-matter|chapter|back\-matter|part)/([a-z0-9\-]*)([/]?)%', $href, $matches ) ) {
					// Convert type + slug to #fragment
					$fragment = "{$matches[1]}-{$matches[2]}";
				} else {
					$fragment = false;
				}
				if ( $fragment ) {
					// Check if a fragment is considered external, don't change the URL if we find a match
					$external_anchors = [ \Pressbooks\Interactive\Content::ANCHOR ];
					if ( in_array( "#{$fragment}", $external_anchors, true ) || str_starts_with( $fragment, 'h5p' ) ) {
						continue;
					} else {
						$link->setAttribute( 'href', "#{$fragment}" );
					}
				}
			}
		}

		$content = $html5->saveHTML( $dom );
		return $content;
	}

	/**
	 * Removes the CC attribution link. Returns valid xhtml.
	 *
	 * @since 4.1
	 *
	 * @param string $content
	 *
	 * @return string
	 */
	protected function removeAttributionLink( $content ) {
		if ( stripos( $content, '<a' ) === false ) {
			// There are no <a> tags to look at, skip this
			return $content;
		}

		$changed = false;
		$html5 = new HtmlParser();
		$dom = $html5->loadHTML( $content );

		$urls = $dom->getElementsByTagName( 'a' );
		foreach ( $urls as $url ) {
			/** @var \DOMElement $url */
			// Is this the the attributionUrl?
			if ( $url->getAttribute( 'rel' ) === 'cc:attributionURL' ) {
				$url->parentNode->replaceChild(
					$dom->createTextNode( $url->nodeValue ),
					$url
				);
				$changed = true;
			}
		}

		if ( ! $changed ) {
			return $content;
		} else {
			$content = $html5->saveHTML( $dom );
			$content = $this->html5ToXhtml( $content );
			return $content;
		}
	}

	/**
	 * Replace every image with the bigger original image
	 *
	 * @param $content
	 *
	 * @return string
	 */
	protected function fixImages( $content ) {

		// Cheap cache
		static $already_done = [];

		$changed = false;
		$html5 = new HtmlParser();
		$dom = $html5->loadHTML( $content );

		$images = $dom->getElementsByTagName( 'img' );
		foreach ( $images as $image ) {
			/** @var \DOMElement $image */
			$old_src = $image->getAttribute( 'src' );
			if ( isset( $already_done[ $old_src ] ) ) {
				$new_src = $already_done[ $old_src ];
			} else {
				$new_src = \Pressbooks\Image\maybe_swap_with_bigger( $old_src );
			}
			if ( $old_src !== $new_src ) {
				$image->setAttribute( 'src', $new_src );
				$image->removeAttribute( 'srcset' );
				$changed = true;
			}
			$already_done[ $old_src ] = $new_src;
		}

		if ( $changed ) {
			$content = $html5->saveHTML( $dom );
		}

		return $content;
	}

	/**
	 * Tidy HTML
	 *
	 * @param string $html
	 *
	 * @return string
	 */
	protected function tidy( $html ) {

		// Make XHTML 1.1 strict using htmlLawed

		$html = \Pressbooks\Interactive\Content::init()->replaceInteractiveTags( $html );

		$config = [
			'valid_xhtml' => 1,
			'no_deprecated_attr' => 2,
			'unique_ids' => 'fixme-',
			'hook' => '\Pressbooks\Sanitize\html5_to_xhtml11',
			'tidy' => -1,
		];

		$spec = '';
		$spec .= 'table=-border;';
		$spec .= 'div=title;';

		return \Pressbooks\HtmLawed::filter( $html, $config, $spec );
	}

	/**
	 * Clean up content processed by HTML5 Parser, change it back into XHTML
	 *
	 * @param $html
	 *
	 * @return string
	 */
	protected function html5ToXhtml( $html ) {
		$config = [
			'valid_xhtml' => 1,
			'unique_ids' => 0,
		];
		$html = \Pressbooks\HtmLawed::filter( $html, $config );
		return $html;
	}

	// ----------------------------------------------------------------------------------------------------------------
	// Echo Functions
	// ----------------------------------------------------------------------------------------------------------------

	/**
	 * @param array $book_contents
	 * @param array $metadata
	 */
	protected function echoDocType( $book_contents, $metadata ) {

		echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">' . "\n";
		echo '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="' . $this->lang . '">' . "\n";
	}

	/**
	 * @param array $book_contents
	 * @param array $metadata
	 */
	protected function echoMetaData( $book_contents, $metadata ) {
		foreach ( $metadata as $name => $content ) {
			$name = Sanitize\sanitize_xml_id( str_replace( '_', '-', $name ) );
			if ( is_array( $content ) ) {
				foreach ( $content as $content_item ) {
					if ( isset( $content_item['name'] ) ) {
						$this->echoMetaDataItem( $name, $content_item['name'] );
					}
				}
				continue;
			}
			$this->echoMetaDataItem( $name, $content );
		}
	}

	protected function echoMetaDataItem( $name, $content_item ) {
		$content_item = trim( wp_strip_all_tags( html_entity_decode( $content_item ) ) ); // Plain text
		$content_item = preg_replace( '/\s+/', ' ', preg_replace( '/\n+/', ' ', $content_item ) ); // Normalize whitespaces
		$content_item = Sanitize\sanitize_xml_attribute( $content_item );
		printf( '<meta name="%s" content="%s" />', $name, $content_item );
		echo "\n";
	}

	/**
	 * @param array $book_contents
	 * @param array $metadata
	 */
	protected function renderCover( $book_contents, $metadata ) {
		// Does nothing.
		// Is here for child classes to override if ever needed.
	}

	/**
	 * @param array $book_contents
	 * @param array $metadata
	 */
	protected function renderBeforeTitle( $book_contents, $metadata ) {
		$i = $this->frontMatterPos;
		foreach ( [ 'before-title' ] as $compare ) {
			foreach ( $book_contents['front-matter'] as $front_matter ) {

				if ( ! $front_matter['export'] ) {
					continue; // Skip
				}

				$front_matter_id = $front_matter['ID'];
				$subclass = $this->taxonomy->getFrontMatterType( $front_matter_id );

				if ( $compare !== $subclass ) {
					continue; //Skip
				}

				$slug = "front-matter-{$front_matter['post_name']}";
				$title = ( get_post_meta( $front_matter_id, 'pb_show_title', true ) ? $front_matter['post_title'] : '<span class="display-none">' . $front_matter['post_title'] . '</span>' ); // Preserve auto-indexing in Prince using hidden span
				$content = $front_matter['post_content'];

				echo $this->blade->render(
					'export/generic-post-type',
					[
						'post_type_class' => 'front-matter',
						'subclass' => $subclass,
						'slug' => $slug,
						'post_number' => $i,
						'title' => Sanitize\decode( $title ),
						'content' => $content,
						'endnotes' => $this->doEndnotes( $front_matter_id ),
						'footnotes' => $this->doFootnotes( $front_matter_id ),
					]
				);

				++$i;
			}
		}
		$this->frontMatterPos = $i;
	}

	/**
	 * @param array $book_contents
	 * @param array $metadata
	 */
	protected function echoHalfTitle( $book_contents, $metadata ) {

		echo '<div id="half-title-page">';
		echo '<h1 class="title">' . get_bloginfo( 'name' ) . '</h1>';
		echo '</div>' . "\n";
	}

	/**
	 * @param array $book_contents
	 * @param array $metadata
	 */
	protected function renderTitle( $book_contents, $metadata ) {
		// Look for custom title-page

		$content = '';
		foreach ( $book_contents['front-matter'] as $front_matter ) {

			if ( ! $front_matter['export'] ) {
				continue; // Skip
			}

			$front_matter_id = $front_matter['ID'];
			$subclass = $this->taxonomy->getFrontMatterType( $front_matter_id );

			if ( 'title-page' !== $subclass ) {
				continue; // Skip
			}

			$content = $front_matter['post_content'];
			break;
		}

		// HTML
		if ( $content ) {
			echo $this->blade->render(
			'export/title', [
					'content' => $content,
				]
			);
		} else {
			$authors = null;
			$contributors = null;

			if ( isset( $metadata['pb_authors'] ) && ! empty( $metadata['pb_authors'] ) ) {
				$authors = is_array( $metadata['pb_authors'] ) ? get_contributors_name_imploded( $metadata['pb_authors'] ) : $metadata['pb_authors'];
			}

			if ( isset( $metadata['pb_contributors'] ) && ! empty( $metadata['pb_contributors'] ) ) {
				$contributors = is_array( $metadata['pb_contributors'] ) ? get_contributors_name_imploded( $metadata['pb_contributors'] ) : $metadata['pb_contributors'];
			}

			echo $this->blade->render(
				'export/title', [
					'title' => get_bloginfo( 'name' ),
					'subtitle' => $metadata['pb_subtitle'] ?? '',
					'authors' => $authors,
					'contributors' => $contributors,
					'logo' => current_theme_supports( 'pressbooks_publisher_logo' ) ? get_theme_support( 'pressbooks_publisher_logo' )[0]['logo_uri'] : null,
					'publisher' => $metadata['pb_publisher'] ?? '',
					'publisher_city' => $metadata['pb_publisher_city'] ?? '',
				]
			);
		}
	}

	/**
	 * @param array $metadata
	 */
	protected function renderCopyright( $metadata ) {

		if ( empty( $metadata['pb_book_license'] ) ) {
			$all_rights_reserved = true;
		} elseif ( $metadata['pb_book_license'] === 'all-rights-reserved' ) {
			$all_rights_reserved = true;
		} else {
			$all_rights_reserved = false;
		}
		if ( ! empty( $metadata['pb_custom_copyright'] ) ) {
			$has_custom_copyright = true;
		} else {
			$has_custom_copyright = false;
		}


		// Custom Copyright must override All Rights Reserved
		if ( ! $has_custom_copyright || ( $has_custom_copyright && ! $all_rights_reserved ) ) {
			$license = $this->doCopyrightLicense( $metadata );
			if ( $license ) {
				$license_copyright = $this->removeAttributionLink( $license );
			}
		}

		// Custom copyright
		if ( $has_custom_copyright ) {
			$custom_copyright = $this->tidy( $metadata['pb_custom_copyright'] );
		}

		// default, so something is displayed
		$has_default = false;
		if ( empty( $metadata['pb_custom_copyright'] ) && empty( $license ) ) {
			$has_default = true;
			$default_copyright_name = get_bloginfo( 'name' ) . ' ' . __( 'Copyright', 'pressbooks' ) . ' &copy; ';
			if ( ! empty( $meta['pb_copyright_year'] ) ) {
				$default_copyright_date = $meta['pb_copyright_year'] . ' ';
			} elseif ( ! empty( $meta['pb_publication_date'] ) ) {
				$default_copyright_date = strftime( '%Y', $meta['pb_publication_date'] );
			} else {
				$default_copyright_date = date( 'Y' );
			}
			if ( ! empty( $metadata['pb_copyright_holder'] ) ) {
				$default_copyright_holder = ' ' . __( 'by', 'pressbooks' ) . ' ' . $metadata['pb_copyright_holder'] . '. ';
			}
		}
		$blade_vars = [];
		if ( isset( $license_copyright ) ) {
			$blade_vars['license_copyright'] = $license_copyright;
		}
		if ( isset( $custom_copyright ) ) {
			$blade_vars['custom_copyright'] = $custom_copyright;
		}
		if ( $has_default ) {
			$blade_vars['has_default'] = $has_default;
			$blade_vars['default_copyright_name'] = $default_copyright_name;
			$blade_vars['default_copyright_date'] = $default_copyright_date;
			if ( isset( $default_copyright_holder ) ) {
				$blade_vars['default_copyright_holder'] = $default_copyright_holder;
			}
		}
		echo $this->blade->render( 'export/copyright', $blade_vars );
	}

	/**
	 * @param array $book_contents
	 * @param array $metadata
	 */
	protected function renderDedicationAndEpigraph( $book_contents, $metadata ) {

		$i = $this->frontMatterPos;
		foreach ( [ 'dedication', 'epigraph' ] as $compare ) {
			foreach ( $book_contents['front-matter'] as $front_matter ) {

				if ( ! $front_matter['export'] ) {
					continue; // Skip
				}

				$front_matter_id = $front_matter['ID'];
				$subclass = $this->taxonomy->getFrontMatterType( $front_matter_id );

				if ( $compare !== $subclass ) {
					continue; // Skip
				}

				$slug = "front-matter-{$front_matter['post_name']}";
				$title = ( get_post_meta( $front_matter_id, 'pb_show_title', true ) ? $front_matter['post_title'] : '<span class="display-none">' . $front_matter['post_title'] . '</span>' ); // Preserve auto-indexing in Prince using hidden span
				$content = $front_matter['post_content'];

				echo $this->blade->render(
					'export/dedication-epigraph',//TODO: Review if it could be consolidated in a single file
					[
						'subclass' => $subclass,
						'slug' => $slug,
						'front-matter-number' => $i,
						'title' => Sanitize\decode( $title ),
						'content' => $content,
						'endnotes' => $this->doEndnotes( $front_matter_id ),
						'footnotes' => $this->doFootnotes( $front_matter_id )
					]
				);
				echo "\n";
				++$i;
			}
		}
		$this->frontMatterPos = $i;
	}

	/**
	 * @param array $book_contents
	 * @param array $metadata
	 */
	protected function echoToc( $book_contents, $metadata ) {

		echo '<div id="toc"><h1>' . __( 'Contents', 'pressbooks' ) . '</h1><ul>';
		foreach ( $book_contents as $type => $struct ) {

			if ( preg_match( '/^__/', $type ) ) {
				continue; // Skip __magic keys
			}

			if ( 'part' === $type ) {
				foreach ( $struct as $part ) {
					$slug = "part-{$part['post_name']}";
					$title = Sanitize\strip_br( $part['post_title'] );
					$part_content = trim( $part['post_content'] );
					if ( get_post_meta( $part['ID'], 'pb_part_invisible', true ) !== 'on' ) { // visible
						if ( count( $book_contents['part'] ) === 1 ) { // only part
							if ( $part_content ) { // has content
								printf( '<li class="part"><a href="#%s">%s</a></li>', $slug, Sanitize\decode( $title ) ); // show in TOC
							} else { // no content
								printf( '<li class="part display-none"><a href="#%s">%s</a></li>', $slug, Sanitize\decode( $title ) ); // hide from TOC
							}
						} elseif ( count( $book_contents['part'] ) > 1 ) { // multiple parts
							if ( $this->atLeastOneExport( $part['chapters'] ) ) { // has chapter
								printf( '<li class="part"><a href="#%s">%s</a></li>', $slug, Sanitize\decode( $title ) ); // show in TOC
							} else { // no chapter
								if ( $part_content ) { // has content
									printf( '<li class="part"><a href="#%s">%s</a></li>', $slug, Sanitize\decode( $title ) ); // show in TOC
								} else { // no content
									printf( '<li class="part display-none"><a href="#%s">%s</a></li>', $slug, Sanitize\decode( $title ) ); // hide from TOC
								}
							}
						}
					} elseif ( get_post_meta( $part['ID'], 'pb_part_invisible', true ) === 'on' ) { // invisible
						printf( '<li class="part display-none"><a href="#%s">%s</a></li>', $slug, Sanitize\decode( $title ) ); // hide from TOC
					}
					foreach ( $part['chapters'] as $j => $chapter ) {

						if ( ! $chapter['export'] ) {
							continue;
						}

						$subclass = $this->taxonomy->getChapterType( $chapter['ID'] );
						$slug = "chapter-{$chapter['post_name']}";
						$title = Sanitize\strip_br( $chapter['post_title'] );
						$subtitle = trim( get_post_meta( $chapter['ID'], 'pb_subtitle', true ) );
						$author = $this->contributors->get( $chapter['ID'], 'pb_authors' );
						$license = $this->doTocLicense( $chapter['ID'] );

						printf( '<li class="chapter %s"><a href="#%s"><span class="toc-chapter-title">%s</span>', $subclass, $slug, Sanitize\decode( $title ) );

						if ( $subtitle ) {
							echo ' <span class="chapter-subtitle">' . Sanitize\decode( $subtitle ) . '</span>';
						}

						if ( $author ) {
							echo ' <span class="chapter-author">' . Sanitize\decode( $author ) . '</span>';
						}

						if ( $license ) {
							echo ' <span class="chapter-license">' . $license . '</span> ';
						}

						echo '</a>';

						if ( \Pressbooks\Modules\Export\Export::shouldParseSubsections() === true ) {
							$sections = \Pressbooks\Book::getSubsections( $chapter['ID'] );
							if ( $sections ) {
								echo '<ul class="sections">';
								foreach ( $sections as $id => $section_title ) {
									echo '<li class="section"><a href="#' . $id . '"><span class="toc-subsection-title">' . Sanitize\decode( $section_title ) . '</span></a></li>';
								}
								echo '</ul>';
							}
						}

						echo '</li>';
					}
				}
			} else {
				$has_intro = false;

				foreach ( $struct as $val ) {

					if ( ! $val['export'] ) {
						continue;
					}

					$typetype = '';
					$subtitle = '';
					$author = '';
					$license = '';
					$slug = "{$type}-{$val['post_name']}";
					$title = Sanitize\strip_br( $val['post_title'] );

					if ( 'front-matter' === $type ) {
						$subclass = $this->taxonomy->getFrontMatterType( $val['ID'] );
						if ( 'dedication' === $subclass || 'epigraph' === $subclass || 'title-page' === $subclass || 'before-title' === $subclass ) {
							continue; // Skip
						} else {
							$typetype = $type . ' ' . $subclass;
							if ( $has_intro ) {
								$typetype .= ' post-introduction';
							}
							if ( $subclass === 'introduction' ) {
								$has_intro = true;
							}
							$subtitle = trim( get_post_meta( $val['ID'], 'pb_subtitle', true ) );
							$author = $this->contributors->get( $val['ID'], 'pb_authors' );
							$license = $this->doTocLicense( $val['ID'] );
						}
					} elseif ( 'back-matter' === $type ) {
						$typetype = $type . ' ' . $this->taxonomy->getBackMatterType( $val['ID'] );
						$subtitle = trim( get_post_meta( $val['ID'], 'pb_subtitle', true ) );
						$author = $this->contributors->get( $val['ID'], 'pb_authors' );
						$license = $this->doTocLicense( $val['ID'] );
					}

					printf( '<li class="%s"><a href="#%s"><span class="toc-chapter-title">%s</span>', $typetype, $slug, Sanitize\decode( $title ) );

					if ( $subtitle ) {
						echo ' <span class="chapter-subtitle">' . Sanitize\decode( $subtitle ) . '</span>';
					}

					if ( $author ) {
						echo ' <span class="chapter-author">' . Sanitize\decode( $author ) . '</span>';
					}

					if ( $license ) {
						echo ' <span class="chapter-license">' . $license . '</span> ';
					}

					echo '</a>';

					if ( \Pressbooks\Modules\Export\Export::shouldParseSubsections() === true ) {
						$sections = \Pressbooks\Book::getSubsections( $val['ID'] );
						if ( $sections ) {
							echo '<ul class="sections">';
							foreach ( $sections as $id => $section_title ) {
								echo '<li class="section"><a href="#' . $id . '"><span class="toc-subsection-title">' . Sanitize\decode( $section_title ) . '</span></a></li>';
							}
							echo '</ul>';
						}
					}

					echo '</li>';
				}
			}
		}
		echo "</ul></div>\n";

	}

	/**
	 * Yields an estimated percentage slice of: 50 to 60
	 *
	 * @param array $book_contents
	 * @param array $metadata
	 * @return \Generator
	 */
	protected function renderFrontMatterGenerator( $book_contents, $metadata ) : \Generator {

		$y = new PercentageYield( 50, 60, count( $book_contents['front-matter'] ) );

		$i = $this->frontMatterPos;
		foreach ( $book_contents['front-matter'] as $front_matter ) {
			yield from $y->tick( $this->generatorPrefix . __( 'Exporting front matter', 'pressbooks' ) );

			if ( ! $front_matter['export'] ) {
				continue; // Skip
			}

			$data = $this->mapBookDataAndContent( $front_matter, $metadata, $i, [
				'type' => 'front_matter',
				'endnotes' => true,
				'footnotes' => true
			] );

			$subclass = $data['subclass'];

			if ( 'dedication' === $subclass || 'epigraph' === $subclass || 'title-page' === $subclass || 'before-title' === $subclass ) {
				continue; // Skip
			}

			if ( $this->hasIntroduction ) {
				$subclass .= ' post-introduction';
			}

			if ( 'introduction' === $subclass ) {
				$this->hasIntroduction = true;
			}

			echo $this->blade->render( 'export/generic-post-type', $data );
			++$i;
		}
		$this->frontMatterPos = $i;
	}

	/**
	 * @param array $book_contents
	 * @param array $metadata
	 */
	protected function createPromo( $book_contents, $metadata ) {

		$promo_html = apply_filters( 'pressbooks_pdf_promo', '' );
		if ( $promo_html ) {
			echo $promo_html;
		}
	}

	/**
	 * Yields an estimated percentage slice of: 60 to 70
	 *
	 * @param array $book_contents
	 * @param array $metadata
	 * @return \Generator
	 */
	protected function echoPartsAndChaptersGenerator( $book_contents, $metadata ) : \Generator {
		$ticks = 0;
		foreach ( $book_contents['part'] as $key => $part ) {
			$ticks += 1 + count($book_contents['part'][$key]['chapters']);
		}
		$y = new PercentageYield( 60, 70, $ticks );

		$i = 1;
		$j = 1;
		foreach ( $book_contents['part'] as $part ) {
			yield from $y->tick( $this->generatorPrefix . __( 'Exporting parts and chapters', 'pressbooks' ) );

			$invisibility = ( get_post_meta( $part['ID'], 'pb_part_invisible', true ) === 'on' ) ? 'invisible' : '';

			$slug = "part-{$part['post_name']}";
			$title = $part['post_title'];
			$part_content = trim( $part['post_content'] );

			// Inject introduction class?
			$part_introduction = $this->hasIntroduction;

			if ( 'invisible' !== $invisibility ) { // visible
				if ( count( $book_contents['part'] ) === 1 ) { // only part
					if ( $part_content && ! $this->hasIntroduction ) { // has content
						$part_introduction = true;
						$this->hasIntroduction = true;
					}
				} elseif ( count( $book_contents['part'] ) > 1 ) { // multiple parts
					if ( ! $this->hasIntroduction ) {
						$part_introduction = true;
						$this->hasIntroduction = true;
					}
				}
			}

			$wrap_part = ( bool ) $part_content;

			$m = ( 'invisible' === $invisibility ) ? '' : $i;

			$my_part = $this->blade->render( 'export/part', [
				'invisibility' => $invisibility,
				'id' => $slug,
				'introduction_class' => ! $part_introduction ? 'introduction' : '',
				'number' => \Pressbooks\L10n\romanize( $m ),
				'title' => Sanitize\decode( $title ),
				'content' => $part_content,
				'endnotes' => $this->doEndnotes( $part['ID'] ),
				'footnotes' =>	$this->doFootnotes( $part['ID'] ),
				'wrap_part' => $wrap_part,
			] );

			$my_chapters = '';

			foreach ( $part['chapters'] as $chapter ) {
				yield from $y->tick( $this->generatorPrefix . __( 'Exporting parts and chapters', 'pressbooks' ) );

				if ( ! $chapter['export'] ) {
					continue; // Skip
				}

				$chapter_id = $chapter['ID'];
				$subclass = $this->taxonomy->getChapterType( $chapter_id );
				$slug = "chapter-{$chapter['post_name']}";
				$title = ( get_post_meta( $chapter_id, 'pb_show_title', true ) ? $chapter['post_title'] : '<span class="display-none">' . $chapter['post_title'] . '</span>' ); // Preserve auto-indexing in Prince using hidden span
				$after_title = '';
				$content = $chapter['post_content'];
				$append_chapter_content = apply_filters( 'pb_append_chapter_content', '', $chapter_id );
				$short_title = $this->outputShortTitle ? trim( get_post_meta( $chapter_id, 'pb_short_title', true ) ) : false;
				$subtitle = trim( get_post_meta( $chapter_id, 'pb_subtitle', true ) );
				$author = $this->contributors->get( $chapter_id, 'pb_authors' );

				if ( \Pressbooks\Modules\Export\Export::shouldParseSubsections() === true ) {
					if ( \Pressbooks\Book::getSubsections( $chapter_id ) !== false ) {
						$content = \Pressbooks\Book::tagSubsections( $content, $chapter_id );
						$content = $this->html5ToXhtml( $content );
					}
				}

				// Inject introduction class?
				if ( ! $this->hasIntroduction ) {
					$subclass .= ' introduction';
					$this->hasIntroduction = true;
				}

				$append_chapter_content .= $this->removeAttributionLink( $this->doSectionLevelLicense( $metadata, $chapter_id ) );

				$content .= $this->displayAboutTheAuthors ? \Pressbooks\Modules\Export\get_contributors_section( $chapter_id ) : '';

				$my_chapter_number = ( strpos( $subclass, 'numberless' ) === false ) ? $j : '';
				$my_chapters .= $this->blade->render( 'export/chapter', [
					'subclass' => $subclass,
					'id' => $slug,
					'short_title' => ( $short_title ) ? $short_title : wp_strip_all_tags( Sanitize\decode( $chapter['post_title'] ) ),
					'number' => $my_chapter_number,
					'after_title' => $after_title,
					'title' => Sanitize\decode( $title ),
					'content' => $content,
					'append_content' => $append_chapter_content,
					'is_new_buckram' => $this->wrapHeaderElements,
					'endnotes' => $this->doEndnotes( $chapter_id ),
					'footnotes' =>$this->doFootnotes( $chapter_id ),
					'subtitle' => Sanitize\decode( $subtitle ),
					'authors' => $author,
				] );

				if ( $my_chapter_number !== '' ) {
					++$j;
				}
			}

			// Echo with parts?

			if ( 'invisible' !== $invisibility ) { // visible
				if ( count( $book_contents['part'] ) === 1 ) { // only part
					if ( $part_content ) { // has content
						echo $my_part; // show
						if ( $my_chapters ) {
							echo $my_chapters;
						}
					} else { // no content
						if ( $my_chapters ) {
							echo $my_chapters;
						}
					}
				} elseif ( count( $book_contents['part'] ) > 1 ) { // multiple parts
					if ( $my_chapters ) { // has chapter
						echo $my_part . $my_chapters; // show
					} else { // no chapter
						if ( $part_content ) { // has content
							echo $my_part; // show
						}
					}
				}
				++$i;
			} else { // invisible
				if ( $my_chapters ) {
					echo $my_chapters;
				}
			}
		}

	}

	/**
	 * Yields an estimated percentage slice of: 70 to 80
	 *
	 * @param array $book_contents
	 * @param array $metadata
	 * @return \Generator
	 */
	protected function echoBackMatterGenerator( $book_contents, $metadata ) : \Generator {

		$y = new PercentageYield( 70, 80, count( $book_contents['back-matter'] ) );

		$i = 1;
		foreach ( $book_contents['back-matter'] as $back_matter ) {
			yield from $y->tick( $this->generatorPrefix . __( 'Exporting back matter', 'pressbooks' ) );

			if ( ! $back_matter['export'] ) {
				continue;
			}

			$data = $this->mapBookDataAndContent( $back_matter, $metadata, $i, [
				'type' => 'back_matter',
				'endnotes' => true,
				'footnotes' => true
			] );

			echo $this->blade->render( 'export/generic-post-type', $data );
			++$i;
		}

	}

	/**
	 * Does array of chapters have at least one export? Recursive.
	 *
	 * @param array $chapters
	 *
	 * @return bool
	 */
	protected function atLeastOneExport( array $chapters ) {

		foreach ( $chapters as $key => $val ) {
			if ( is_array( $val ) ) {
				$found = $this->atLeastOneExport( $val );
				if ( $found ) {
					return true;
				} else {
					continue;
				}
			} elseif ( 'export' === (string) $key && $val ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Dependency check.
	 *
	 * @return bool
	 */
	static function hasDependencies() {
		if ( true === \Pressbooks\Utility\check_xmllint_install() ) {
			return true;
		}

		return false;
	}

}
