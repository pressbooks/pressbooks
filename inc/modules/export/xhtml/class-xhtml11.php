<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Modules\Export\Xhtml;

use function Pressbooks\Sanitize\clean_filename;
use function Pressbooks\Utility\str_starts_with;
use PressbooksMix\Assets;
use Pressbooks\Container;
use Pressbooks\HtmlParser;
use Pressbooks\Modules\Export\Export;
use Pressbooks\Sanitize;

class Xhtml11 extends Export {

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
	 * Endnotes storage container.
	 * Use when overriding the footnote shortcode.
	 *
	 * @var array
	 */
	protected $endnotes = [];


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
	 * Main language of document, two letter code
	 *
	 * @var string
	 */
	protected $lang = 'en';

	/**
	 * Body class for the document
	 *
	 * @var string
	 */
	protected $bodyClass = '';

	/**
	 * @var \Pressbooks\Taxonomy
	 */
	protected $taxonomy;

	/**
	 * @var \Pressbooks\Contributors
	 */
	protected $contributors;

	/**
	 * @param array $args
	 */
	function __construct( array $args ) {

		// Some defaults

		$this->taxonomy = \Pressbooks\Taxonomy::init();
		$this->contributors = new \Pressbooks\Contributors();

		if ( Container::get( 'Styles' )->hasBuckram( '0.3.0' ) ) {
			$this->wrapHeaderElements = true;
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
		if ( ! empty( $_REQUEST['preview'] ) ) {
			$this->url .= '&' . http_build_query(
				[
					'preview' => $_REQUEST['preview'],
				]
			);
		}

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
	}


	/**
	 * Create $this->outputPath
	 *
	 * @return bool
	 */
	function convert() {

		// Get XHTML

		$output = $this->transform( true );

		if ( ! $output ) {
			return false;
		}

		// Save XHTML as file in exports folder

		$filename = $this->timestampedFileName( '.html' );
		\Pressbooks\Utility\put_contents( $filename, $output );
		$this->outputPath = $filename;

		return true;
	}


	/**
	 * Check the sanity of $this->outputPath
	 *
	 * @return bool
	 */
	function validate() {

		// Xmllint params
		$command = PB_XMLLINT_COMMAND . ' --html --valid --noout ' . escapeshellcmd( $this->outputPath ) . ' 2>&1';

		// Execute command
		$output = [];
		$return_var = 0;
		exec( $command, $output, $return_var );

		// Is this a valid XHTML?
		if ( is_countable( $output ) && count( $output ) ) {
			$this->logError( implode( "\n", $output ) );

			return false;
		}

		return true;
	}


	/**
	 * Procedure for "format/xhtml" rewrite rule.
	 *
	 * Supported http params:
	 *
	 *   + timestamp: (int) combines with `hashkey` to allow a 3rd party service temporary access
	 *   + hashkey: (string) combines with `timestamp` to allow a 3rd party service temporary access
	 *   + endnotes: (bool) move all footnotes to end of the book
	 *   + style: (string) name of a user generated stylesheet you want included in the header
	 *   + script: (string) name of javascript file you you want included in the header
	 *   + preview: (bool) Use `Content-Disposition: inline` instead of `Content-Disposition: attachment` when passing through Export::formSubmit
	 *   + fullsize-images: (bool) replace images with originals when possible
	 *
	 * @see \Pressbooks\Redirect\do_format
	 *
	 * @param bool $return (optional)
	 * If you would like to capture the output of transform,
	 * use the return parameter. If this parameter is set
	 * to true, transform will return its output, instead of
	 * printing it.
	 *
	 * @return mixed
	 */
	function transform( $return = false ) {

		// Check permissions

		if ( ! current_user_can( 'edit_posts' ) ) {
			$timestamp = ( isset( $_REQUEST['timestamp'] ) ) ? absint( $_REQUEST['timestamp'] ) : 0;
			$hashkey = ( isset( $_REQUEST['hashkey'] ) ) ? $_REQUEST['hashkey'] : '';
			if ( ! $this->verifyNonce( $timestamp, $hashkey ) ) {
				wp_die( __( 'Invalid permission error', 'pressbooks' ) );
			}
		}

		do_action( 'pb_pre_export' );

		// Override footnote shortcode
		if ( ! empty( $_GET['endnotes'] ) ) {
			add_shortcode( 'footnote', [ $this, 'endnoteShortcode' ] );
		} else {
			add_shortcode( 'footnote', [ $this, 'footnoteShortcode' ] );
		}

		// ------------------------------------------------------------------------------------------------------------
		// XHTML, Start!

		$metadata = \Pressbooks\Book::getBookInformation();
		$book_contents = $this->preProcessBookContents( \Pressbooks\Book::getBookContents() );

		// Set two letter language code
		if ( isset( $metadata['pb_language'] ) ) {
			list( $this->lang ) = explode( '-', $metadata['pb_language'] );
		}

		// ------------------------------------------------------------------------------------------------------------
		// Buffer for Outer XHTML

		ob_start();

		$this->echoDocType( $book_contents, $metadata );

		echo "<head>\n";
		echo '<meta content="text/html; charset=UTF-8" http-equiv="content-type" />' . "\n";
		echo '<meta http-equiv="Content-Language" content="' . $this->lang . '" />' . "\n";
		echo '<meta name="generator" content="Pressbooks ' . PB_PLUGIN_VERSION . '" />' . "\n";

		$this->echoMetaData( $book_contents, $metadata );

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
			$url = $this->getExportScriptUrl( clean_filename( $_GET['script'] ) ) . '/script.js';
			if ( $url ) {
				echo "<script src='$url' type='text/javascript'></script>\n";
			}
		}

		echo "</head>\n<body lang='{$this->lang}'>\n";
		$replace_token = uniqid( 'PB_REPLACE_INNER_HTML_', true );
		echo $replace_token;
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
			ob_start();

			// Before Title Page
			$this->echoBeforeTitle( $book_contents, $metadata );

			// Half-title
			$this->echoHalfTitle( $book_contents, $metadata );

			// Cover
			$this->echoCover( $book_contents, $metadata );

			// Title
			$this->echoTitle( $book_contents, $metadata );

			// Copyright
			$this->echoCopyright( $book_contents, $metadata );

			// Dedication and Epigraph (In that order!)
			$this->echoDedicationAndEpigraph( $book_contents, $metadata );

			// Table of contents
			$this->echoToc( $book_contents, $metadata );

			// Front-matter
			$this->echoFrontMatter( $book_contents, $metadata );

			// Promo
			$this->createPromo( $book_contents, $metadata );

			// Parts, Chapters
			$this->echoPartsAndChapters( $book_contents, $metadata );

			// Back-matter
			$this->echoBackMatter( $book_contents, $metadata );

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

		if ( $return ) {
			return $buffer;
		} else {
			echo $buffer;
			return null;
		}
	}


	/**
	 * Add $this->url as additional log info, fallback to parent.
	 *
	 * @param $message
	 * @param array $more_info (unused, overridden)
	 */
	function logError( $message, array $more_info = [] ) {

		$more_info = [
			'url' => $this->url,
		];

		parent::logError( $message, $more_info );
	}


	/**
	 * Wrap footnotes for Prince compatibility
	 *
	 * @see http://www.princexml.com/doc/8.1/footnotes/
	 *
	 * @param      $atts
	 * @param null $content
	 *
	 * @return string
	 */
	function footnoteShortcode( $atts, $content = null ) {

		return '<span class="footnote">' . trim( $content ) . '</span>';
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
						$book_contents[ $type ][ $i ]['post_content'] = $this->preProcessPostContent( $val['post_content'] );
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
							$book_contents[ $type ][ $i ]['chapters'][ $j ]['post_content'] = $this->preProcessPostContent( $val2['post_content'] );
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
	 *
	 * @return string
	 */
	protected function preProcessPostContent( $content ) {

		$content = apply_filters( 'the_content', $content );
		$content = str_ireplace( [ '<b></b>', '<i></i>', '<strong></strong>', '<em></em>' ], '', $content );
		$content = $this->fixAnnoyingCharacters( $content ); // is this used?
		$content = $this->fixInternalLinks( $content );
		$content = $this->switchLaTexFormat( $content );
		if ( ! empty( $_GET['fullsize-images'] ) ) {
			$content = $this->fixImages( $content );
		}
		$content = $this->tidy( $content );

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
	 *
	 * @return string
	 */
	protected function fixInternalLinks( $source_content ) {

		if ( stripos( $source_content, '<a' ) === false ) {
			// There are no <a> tags to look at, skip this
			return $source_content;
		}

		$home_url = rtrim( home_url(), '/' );
		$html5 = new HtmlParser();
		$dom = $html5->loadHTML( $source_content );
		$links = $dom->getElementsByTagName( 'a' );

		$changed = false;
		foreach ( $links as $link ) {
			/** @var \DOMElement $link */
			$href = $link->getAttribute( 'href' );
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
					$link->setAttribute( 'href', "#{$fragment}" );
					$changed = true;
				}
			}
		}

		if ( ! $changed ) {
			return $source_content;
		} else {
			$content = $html5->saveHTML( $dom );
			return $content;
		}
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
			$content = \Pressbooks\HtmLawed::filter( $content, [ 'valid_xhtml' => 1 ] );
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
			'deny_attribute' => 'border',
			'unique_ids' => 'fixme-',
			'hook' => '\Pressbooks\Sanitize\html5_to_xhtml11',
			'tidy' => -1,
		];

		return \Pressbooks\HtmLawed::filter( $html, $config );
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
			$content = trim( strip_tags( html_entity_decode( $content ) ) ); // Plain text
			$content = preg_replace( '/\s+/', ' ', preg_replace( '/\n+/', ' ', $content ) ); // Normalize whitespaces
			$content = Sanitize\sanitize_xml_attribute( $content );
			printf( '<meta name="%s" content="%s" />', $name, $content );
			echo "\n";
		}
	}


	/**
	 * @param array $book_contents
	 * @param array $metadata
	 */
	protected function echoCover( $book_contents, $metadata ) {
		// Does nothing.
		// Is here for child classes to override if ever needed.
	}


	/**
	 * @param array $book_contents
	 * @param array $metadata
	 */
	protected function echoBeforeTitle( $book_contents, $metadata ) {

		$front_matter_printf = '<div class="front-matter %1$s" id="%2$s">';
		$front_matter_printf .= '<div class="front-matter-title-wrap"><h3 class="front-matter-number">%3$s</h3><h1 class="front-matter-title">%4$s</h1></div>';
		$front_matter_printf .= '<div class="ugc front-matter-ugc">%5$s</div>%6$s';
		$front_matter_printf .= '</div>';

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

				printf(
					$front_matter_printf,
					$subclass,
					$slug,
					$i,
					Sanitize\decode( $title ),
					$content,
					$this->doEndnotes( $front_matter_id )
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
	protected function echoHalfTitle( $book_contents, $metadata ) {

		echo '<div id="half-title-page">';
		echo '<h1 class="title">' . get_bloginfo( 'name' ) . '</h1>';
		echo '</div>' . "\n";
	}


	/**
	 * @param array $book_contents
	 * @param array $metadata
	 */
	protected function echoTitle( $book_contents, $metadata ) {

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

		echo '<div id="title-page">';
		if ( $content ) {
			echo $content;
		} else {
			printf( '<h1 class="title">%s</h1>', get_bloginfo( 'name' ) );
			printf( '<h2 class="subtitle">%s</h2>', ( isset( $metadata['pb_subtitle'] ) ) ? $metadata['pb_subtitle'] : '' );
			if ( isset( $metadata['pb_authors'] ) ) {
				printf( '<h3 class="author">%s</h3>', $metadata['pb_authors'] );
			}
			if ( isset( $metadata['pb_contributors'] ) ) {
				printf( '<h3 class="author">%s</h3>', $metadata['pb_contributors'] );
			}
			if ( current_theme_supports( 'pressbooks_publisher_logo' ) ) {
				printf( '<div class="publisher-logo"><img src="%s" /></div>', get_theme_support( 'pressbooks_publisher_logo' )[0]['logo_uri'] ); // TODO: Support custom publisher logo.
			}
			printf( '<h4 class="publisher">%s</h4>', ( isset( $metadata['pb_publisher'] ) ) ? $metadata['pb_publisher'] : '' );
			printf( '<h5 class="publisher-city">%s</h5>', ( isset( $metadata['pb_publisher_city'] ) ) ? $metadata['pb_publisher_city'] : '' );
		}
		echo "</div>\n";
	}


	/**
	 * @param array $book_contents
	 * @param array $metadata
	 */
	protected function echoCopyright( $book_contents, $metadata ) {

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

		// HTML
		echo '<div id="copyright-page"><div class="ugc">';

		// Custom Copyright must override All Rights Reserved
		if ( ! $has_custom_copyright || ( $has_custom_copyright && ! $all_rights_reserved ) ) {
			$license = $this->doCopyrightLicense( $metadata );
			if ( $license ) {
				echo $this->removeAttributionLink( $license );
			}
		}

		// Custom copyright
		if ( $has_custom_copyright ) {
			echo $this->tidy( $metadata['pb_custom_copyright'] );
		}

		// default, so something is displayed
		if ( empty( $metadata['pb_custom_copyright'] ) && empty( $license ) ) {
			echo '<p>';
			echo get_bloginfo( 'name' ) . ' ' . __( 'Copyright', 'pressbooks' ) . ' &#169; ';
			if ( ! empty( $meta['pb_copyright_year'] ) ) {
				echo $meta['pb_copyright_year'] . ' ';
			} elseif ( ! empty( $meta['pb_publication_date'] ) ) {
				echo strftime( '%Y', $meta['pb_publication_date'] );
			} else {
				echo date( 'Y' );
			}
			if ( ! empty( $metadata['pb_copyright_holder'] ) ) {
				echo ' ' . __( 'by', 'pressbooks' ) . ' ' . $metadata['pb_copyright_holder'] . '. ';
			}
			echo '</p>';
		}

		echo "</div></div>\n";
	}


	/**
	 * @param array $book_contents
	 * @param array $metadata
	 */
	protected function echoDedicationAndEpigraph( $book_contents, $metadata ) {

		$front_matter_printf = '<div class="front-matter %1$s" id="%2$s">';
		$front_matter_printf .= '<div class="front-matter-title-wrap"><h3 class="front-matter-number">%3$s</h3><h1 class="front-matter-title">%4$s</h1></div>';
		$front_matter_printf .= '<div class="ugc front-matter-ugc">%5$s</div>%6$s';
		$front_matter_printf .= '</div>';

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

				printf(
					$front_matter_printf,
					$subclass,
					$slug,
					$i,
					Sanitize\decode( $title ),
					$content,
					$this->doEndnotes( $front_matter_id )
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

						if ( \Pressbooks\Modules\Export\Export::isParsingSubsections() === true ) {
							$sections = \Pressbooks\Book::getSubsections( $chapter['ID'] );
							if ( $sections ) {
								echo '<ul class="sections">';
								foreach ( $sections as $id => $title ) {
									echo '<li class="section"><a href="#' . $id . '"><span class="toc-subsection-title">' . Sanitize\decode( $title ) . '</span></a></li>';
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

					if ( \Pressbooks\Modules\Export\Export::isParsingSubsections() === true ) {
						$sections = \Pressbooks\Book::getSubsections( $val['ID'] );
						if ( $sections ) {
							echo '<ul class="sections">';
							foreach ( $sections as $id => $title ) {
								echo '<li class="section"><a href="#' . $id . '"><span class="toc-subsection-title">' . $title . '</span></a></li>';
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
	 * @param array $book_contents
	 * @param array $metadata
	 */
	protected function echoFrontMatter( $book_contents, $metadata ) {
		$front_matter_printf = '<div class="front-matter %1$s" id="%2$s">';
		$front_matter_printf .= '<div class="front-matter-title-wrap"><h3 class="front-matter-number">%3$s</h3><h1 class="front-matter-title">%4$s</h1>%5$s</div>';
		$front_matter_printf .= '<div class="ugc front-matter-ugc">%6$s</div>%7$s%8$s';
		$front_matter_printf .= '</div>';

		$i = $this->frontMatterPos;
		foreach ( $book_contents['front-matter'] as $front_matter ) {

			if ( ! $front_matter['export'] ) {
				continue; // Skip
			}

			$front_matter_id = $front_matter['ID'];
			$subclass = $this->taxonomy->getFrontMatterType( $front_matter_id );

			if ( 'dedication' === $subclass || 'epigraph' === $subclass || 'title-page' === $subclass || 'before-title' === $subclass ) {
				continue; // Skip
			}

			if ( $this->hasIntroduction ) {
				$subclass .= ' post-introduction';
			}

			if ( 'introduction' === $subclass ) {
				$this->hasIntroduction = true;
			}

			$slug = "front-matter-{$front_matter['post_name']}";
			$title = ( get_post_meta( $front_matter_id, 'pb_show_title', true ) ? $front_matter['post_title'] : '<span class="display-none">' . $front_matter['post_title'] . '</span>' ); // Preserve auto-indexing in Prince using hidden span
			$after_title = '';
			$content = $front_matter['post_content'];
			$append_front_matter_content = apply_filters( 'pb_append_front_matter_content', '', $front_matter_id );
			$short_title = trim( get_post_meta( $front_matter_id, 'pb_short_title', true ) );
			$subtitle = trim( get_post_meta( $front_matter_id, 'pb_subtitle', true ) );
			$author = $this->contributors->get( $front_matter_id, 'pb_authors' );

			if ( \Pressbooks\Modules\Export\Export::isParsingSubsections() === true ) {
				if ( \Pressbooks\Book::getSubsections( $front_matter_id ) !== false ) {
					$content = \Pressbooks\Book::tagSubsections( $content, $front_matter_id );
					$content = \Pressbooks\HtmLawed::filter( $content, [ 'valid_xhtml' => 1 ] );
				}
			}

			if ( $author ) {
				if ( $this->wrapHeaderElements ) {
					$after_title = '<h2 class="chapter-author">' . Sanitize\decode( $author ) . '</h2>' . $after_title;
				} else {
					$content = '<h2 class="chapter-author">' . Sanitize\decode( $author ) . '</h2>' . $content;
				}
			}

			if ( $subtitle ) {
				if ( $this->wrapHeaderElements ) {
					$after_title = '<h2 class="chapter-subtitle">' . Sanitize\decode( $subtitle ) . '</h2>' . $after_title;
				} else {
					$content = '<h2 class="chapter-subtitle">' . Sanitize\decode( $subtitle ) . '</h2>' . $content;
				}
			}

			if ( $short_title ) {
				if ( $this->wrapHeaderElements ) {
					$after_title = '<h6 class="short-title">' . Sanitize\decode( $short_title ) . '</h6>' . $after_title;
				} else {
					$content = '<h6 class="short-title">' . Sanitize\decode( $short_title ) . '</h6>' . $content;
				}
			}

			$append_front_matter_content .= $this->removeAttributionLink( $this->doSectionLevelLicense( $metadata, $front_matter_id ) );

			printf(
				$front_matter_printf,
				$subclass,
				$slug,
				$i,
				Sanitize\decode( $title ),
				$after_title,
				$content,
				$append_front_matter_content,
				$this->doEndnotes( $front_matter_id )
			);

			echo "\n";
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
	 * @param array $book_contents
	 * @param array $metadata
	 */
	protected function echoPartsAndChapters( $book_contents, $metadata ) {
		$part_printf = '<div class="part %1$s" id="%2$s">';
		$part_printf .= '<div class="part-title-wrap"><h3 class="part-number">%3$s</h3><h1 class="part-title">%4$s</h1></div>%5$s';
		$part_printf .= '</div>';

		$chapter_printf = '<div class="chapter %1$s" id="%2$s">';
		$chapter_printf .= '<div class="chapter-title-wrap"><h3 class="chapter-number">%3$s</h3><h2 class="chapter-title">%4$s</h2>%5$s</div>';
		$chapter_printf .= '<div class="ugc chapter-ugc">%6$s</div>%7$s%8$s';
		$chapter_printf .= '</div>';

		$i = 1;
		$j = 1;
		foreach ( $book_contents['part'] as $part ) {

			$invisibility = ( get_post_meta( $part['ID'], 'pb_part_invisible', true ) === 'on' ) ? 'invisible' : '';

			$part_printf_changed = '';
			$slug = "part-{$part['post_name']}";
			$title = $part['post_title'];
			$part_content = trim( $part['post_content'] );

			// Inject introduction class?
			if ( 'invisible' !== $invisibility ) { // visible
				if ( count( $book_contents['part'] ) === 1 ) { // only part
					if ( $part_content ) { // has content
						if ( ! $this->hasIntroduction ) {
							$part_printf_changed = str_replace( '<div class="part %1$s" id="', '<div class="part introduction %1$s" id="', $part_printf );
							$this->hasIntroduction = true;
						}
					}
				} elseif ( count( $book_contents['part'] ) > 1 ) { // multiple parts
					if ( ! $this->hasIntroduction ) {
						$part_printf_changed = str_replace( '<div class="part %1$s" id="', '<div class="part introduction %1$s" id="', $part_printf );
						$this->hasIntroduction = true;
					}
				}
			}

			// Inject part content?
			if ( $part_content ) {
				if ( $part_printf_changed ) {
					$part_printf_changed = str_replace( '</h1></div>%s</div>', '</h1></div><div class="ugc part-ugc">%s</div></div>', $part_printf_changed );
				} else {
					$part_printf_changed = str_replace( '</h1></div>%s</div>', '</h1></div><div class="ugc part-ugc">%s</div></div>', $part_printf );
				}
			}

			$m = ( 'invisible' === $invisibility ) ? '' : $i;
			$my_part = sprintf(
				( $part_printf_changed ? $part_printf_changed : $part_printf ),
				$invisibility,
				$slug,
				\Pressbooks\L10n\romanize( $m ),
				Sanitize\decode( $title ),
				$part_content
			) . "\n";

			$my_chapters = '';

			foreach ( $part['chapters'] as $chapter ) {

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
				$short_title = trim( get_post_meta( $chapter_id, 'pb_short_title', true ) );
				$subtitle = trim( get_post_meta( $chapter_id, 'pb_subtitle', true ) );
				$author = $this->contributors->get( $chapter_id, 'pb_authors' );

				if ( \Pressbooks\Modules\Export\Export::isParsingSubsections() === true ) {
					if ( \Pressbooks\Book::getSubsections( $chapter_id ) !== false ) {
						$content = \Pressbooks\Book::tagSubsections( $content, $chapter_id );
						$content = \Pressbooks\HtmLawed::filter( $content, [ 'valid_xhtml' => 1 ] );
					}
				}

				if ( $author ) {
					if ( $this->wrapHeaderElements ) {
						$after_title = '<h2 class="chapter-author">' . Sanitize\decode( $author ) . '</h2>' . $after_title;
					} else {
						$content = '<h2 class="chapter-author">' . Sanitize\decode( $author ) . '</h2>' . $content;
					}
				}

				if ( $subtitle ) {
					if ( $this->wrapHeaderElements ) {
						$after_title = '<h2 class="chapter-subtitle">' . Sanitize\decode( $subtitle ) . '</h2>' . $after_title;
					} else {
						$content = '<h2 class="chapter-subtitle">' . Sanitize\decode( $subtitle ) . '</h2>' . $content;
					}
				}

				if ( $short_title ) {
					if ( $this->wrapHeaderElements ) {
						$after_title = '<h6 class="short-title">' . Sanitize\decode( $short_title ) . '</h6>' . $after_title;
					} else {
						$content = '<h6 class="short-title">' . Sanitize\decode( $short_title ) . '</h6>' . $content;
					}
				}

				// Inject introduction class?
				if ( ! $this->hasIntroduction ) {
					$subclass .= ' introduction';
					$this->hasIntroduction = true;
				}

				$append_chapter_content .= $this->removeAttributionLink( $this->doSectionLevelLicense( $metadata, $chapter_id ) );

				$n = ( strpos( $subclass, 'numberless' ) === false ) ? $j : '';
				$my_chapters .= sprintf(
					$chapter_printf,
					$subclass,
					$slug,
					$n,
					Sanitize\decode( $title ),
					$after_title,
					$content,
					$append_chapter_content,
					$this->doEndnotes( $chapter_id )
				) . "\n";

				if ( 'numberless' !== $subclass ) {
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
			} elseif ( 'invisible' === $invisibility ) { // invisible
				if ( $my_chapters ) {
					echo $my_chapters;
				}
			}
		}

	}


	/**
	 * @param array $book_contents
	 * @param array $metadata
	 */
	protected function echoBackMatter( $book_contents, $metadata ) {
		$back_matter_printf = '<div class="back-matter %1$s" id="%2$s">';
		$back_matter_printf .= '<div class="back-matter-title-wrap"><h3 class="back-matter-number">%3$s</h3><h1 class="back-matter-title">%4$s</h1>%5$s</div>';
		$back_matter_printf .= '<div class="ugc back-matter-ugc">%6$s</div>%7$s%8$s';
		$back_matter_printf .= '</div>';

		$i = 1;
		foreach ( $book_contents['back-matter'] as $back_matter ) {

			if ( ! $back_matter['export'] ) {
				continue;
			}

			$back_matter_id = $back_matter['ID'];
			$subclass = $this->taxonomy->getBackMatterType( $back_matter_id );
			$slug = "back-matter-{$back_matter['post_name']}";
			$title = ( get_post_meta( $back_matter_id, 'pb_show_title', true ) ? $back_matter['post_title'] : '<span class="display-none">' . $back_matter['post_title'] . '</span>' ); // Preserve auto-indexing in Prince using hidden span
			$after_title = '';
			$content = $back_matter['post_content'];
			$append_back_matter_content = apply_filters( 'pb_append_back_matter_content', '', $back_matter_id );
			$short_title = trim( get_post_meta( $back_matter_id, 'pb_short_title', true ) );
			$subtitle = trim( get_post_meta( $back_matter_id, 'pb_subtitle', true ) );
			$author = $this->contributors->get( $back_matter_id, 'pb_authors' );

			if ( \Pressbooks\Modules\Export\Export::isParsingSubsections() === true ) {
				if ( \Pressbooks\Book::getSubsections( $back_matter_id ) !== false ) {
					$content = \Pressbooks\Book::tagSubsections( $content, $back_matter_id );
					$content = \Pressbooks\HtmLawed::filter( $content, [ 'valid_xhtml' => 1 ] );
				}
			}

			if ( $author ) {
				if ( $this->wrapHeaderElements ) {
					$after_title = '<h2 class="chapter-author">' . Sanitize\decode( $author ) . '</h2>' . $after_title;
				} else {
					$content = '<h2 class="chapter-author">' . Sanitize\decode( $author ) . '</h2>' . $content;
				}
			}

			if ( $subtitle ) {
				if ( $this->wrapHeaderElements ) {
					$after_title = '<h2 class="chapter-subtitle">' . Sanitize\decode( $subtitle ) . '</h2>' . $after_title;
				} else {
					$content = '<h2 class="chapter-subtitle">' . Sanitize\decode( $subtitle ) . '</h2>' . $content;
				}
			}

			if ( $short_title ) {
				if ( $this->wrapHeaderElements ) {
					$after_title = '<h6 class="short-title">' . Sanitize\decode( $short_title ) . '</h6>' . $after_title;
				} else {
					$content = '<h6 class="short-title">' . Sanitize\decode( $short_title ) . '</h6>' . $content;
				}
			}

			$append_back_matter_content .= $this->removeAttributionLink( $this->doSectionLevelLicense( $metadata, $back_matter_id ) );

			printf(
				$back_matter_printf,
				$subclass,
				$slug,
				$i,
				Sanitize\decode( $title ),
				$after_title,
				$content,
				$append_back_matter_content,
				$this->doEndnotes( $back_matter_id )
			);

			echo "\n";
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
