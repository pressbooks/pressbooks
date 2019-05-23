<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Modules\Export\HTMLBook;

use function Pressbooks\Utility\oxford_comma_explode;
use function Pressbooks\Utility\str_starts_with;
use PressbooksMix\Assets;
use Pressbooks\HTMLBook\Block\Blockquote;
use Pressbooks\HTMLBook\Block\OrderedLists;
use Pressbooks\HTMLBook\Block\Paragraph;
use Pressbooks\HTMLBook\Component\Book;
use Pressbooks\HTMLBook\Component\Chapter;
use Pressbooks\HTMLBook\Component\Frontmatter;
use Pressbooks\HTMLBook\Component\Part;
use Pressbooks\HTMLBook\Component\TableOfContents;
use Pressbooks\HTMLBook\Element;
use Pressbooks\HTMLBook\Heading\H1;
use Pressbooks\HTMLBook\Heading\Header;
use Pressbooks\HTMLBook\Inline\Footnote;
use Pressbooks\HTMLBook\Validator;
use Pressbooks\HtmlParser;
use Pressbooks\Modules\Export\Export;
use Pressbooks\Sanitize;

class HTMLBook extends Export {

	/**
	 * Prettify HTML
	 *
	 * @var bool
	 */
	public $tidy = true;

	/**
	 * Service URL
	 *
	 * @var string
	 */
	public $url;

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
	 * Main language of document, two letter code
	 *
	 * @var string
	 */
	protected $lang = 'en';


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
	public function __construct( array $args ) {

		// Some defaults

		$this->taxonomy = \Pressbooks\Taxonomy::init();
		$this->contributors = new \Pressbooks\Contributors();

		$defaults = [
			'endnotes' => false,
		];
		$r = wp_parse_args( $args, $defaults );

		// Set the access protected "format/htmlbook" URL with a valid timestamp and NONCE
		$timestamp = time();
		$md5 = $this->nonce( $timestamp );
		$this->url = home_url() . "/format/htmlbook?timestamp={$timestamp}&hashkey={$md5}";

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
	public function convert() {

		// Get HTMLBook

		$output = $this->transform( true );

		if ( ! $output ) {
			return false;
		}

		// Save HTMLBook as file in exports folder

		$filename = $this->timestampedFileName( '-htmlbook.html' );
		\Pressbooks\Utility\put_contents( $filename, $output );
		$this->outputPath = $filename;

		return true;
	}


	/**
	 * Check the sanity of $this->outputPath
	 *
	 * @return bool
	 */
	public function validate() {
		// Is this a valid HtmlBook?

		// TODO, three point validation:
		// 1) Is this valid HTML5? (https://github.com/svenkreiss/html5validator, https://github.com/mozilla/html5-lint, ...)
		// 2) If you remove all the user generated content, is this structurally valid HTMLBook?
		// 3) Is this valid HTMLBook?

		$v = new Validator();
		if ( ! $v->validate( $this->outputPath ) ) {
			$this->logError( implode( "\n", $v->getErrors() ) );
			return false;
		}

		return true;
	}


	/**
	 * Procedure for "format/htmlbook" rewrite rule.
	 *
	 * Supported http (aka $_GET) params:
	 *
	 *   + timestamp: (int) combines with `hashkey` to allow a 3rd party service temporary access
	 *   + hashkey: (string) combines with `timestamp` to allow a 3rd party service temporary access
	 *   + endnotes: (bool) move all footnotes to end of the book
	 *   + style: (string) name of a user generated stylesheet you want included in the header
	 *   + script: (string) name of javascript file you you want included in the header
	 *   + preview: (bool) Use `Content-Disposition: inline` instead of `Content-Disposition: attachment` when passing through Export::formSubmit
	 *   + optimize-for-print: (bool) replace images with originals when possible, add class="print" to <body>, and other print specific features
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

		do_action( 'pb_pre_export' );

		// Override footnote shortcode
		if ( ! empty( $_GET['endnotes'] ) ) {
			add_shortcode( 'footnote', [ $this, 'endnoteShortcode' ] );
		} else {
			add_shortcode( 'footnote', [ $this, 'footnoteShortcode' ] );
		}

		// ------------------------------------------------------------------------------------------------------------
		// HTMLBook, Start!

		$metadata = \Pressbooks\Book::getBookInformation();
		$book_contents = $this->preProcessBookContents( \Pressbooks\Book::getBookContents() );

		// Set two letter language code
		if ( isset( $metadata['pb_language'] ) ) {
			list( $this->lang ) = explode( '-', $metadata['pb_language'] );
		}

		ob_start();

		echo '<?xml version="1.0" encoding="utf-8"?>' . "\n";

		$this->echoDocType();

		echo "<head>\n";
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

		echo "</head>\n";

		$book = new Book();
		if ( ! empty( $_GET['optimize-for-print'] ) ) {
			$book->setAttributes(
				[
					'class' => 'print',
				]
			);
		}

		// Before Title Page
		$this->beforeTitle( $book, $book_contents );

		// Half-title
		$this->halfTitle( $book );

		// Title
		$this->title( $book, $book_contents, $metadata );

		// Copyright
		$this->copyright( $book, $metadata );

		// Dedication and Epigraph (In that order!)
		$this->dedicationAndEpigraph( $book, $book_contents );

		// Table of contents
		$this->tableOfContents( $book, $book_contents );

		// Front-matter
		$this->frontMatter( $book, $book_contents, $metadata );

		// Promo
		$this->echoPromo();

		// Parts, Chapters
		$this->partsAndChapters( $book, $book_contents, $metadata );

		// Back-matter
		$this->backMatter( $book, $book_contents, $metadata );

		if ( $this->tidy ) {
			$book->setTidy( true );
		}

		echo $book->render();
		echo "\n" . '</html>';

		$buffer = ob_get_clean();
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
	public function logError( $message, array $more_info = [] ) {

		$more_info['url'] = $this->url;

		parent::logError( $message, $more_info );
	}


	/**
	 * Footnotes
	 *
	 * @see http://www.princexml.com/doc/8.1/footnotes/
	 *
	 * @param      $atts
	 * @param null $content
	 *
	 * @return string
	 */
	public function footnoteShortcode( $atts, $content = null ) {

		$fn = new Footnote();
		$fn->setAttributes(
			[
				'class' => 'footnote',
			]
		);
		$fn->setContent( trim( $content ) );

		return $fn;
	}


	/**
	 * Endnotes
	 *
	 * @param array $atts
	 * @param null $content
	 *
	 * @return string
	 */
	public function endnoteShortcode( $atts, $content = null ) {

		$fn = new Footnote();
		$fn->setAttributes(
			[
				'class' => 'endnote',
			]
		);
		$fn->setContent( trim( $content ) );

		// Desired rendering of footnote content (i.e., floating/moving footnotes to the
		// bottom of a page or end of a section, adding appropriate marker symbols/numeration)
		// should be handled by XSL/CSS stylesheet processing.

		return $fn;
	}

	/**
	 * @param $data_type
	 *
	 * @return Element
	 */
	public function guessFrontMatterObj( $data_type ) {
		$objects = [
			'\Pressbooks\HTMLBook\Component\Preface',
			'\Pressbooks\HTMLBook\Component\Frontmatter',
		];

		foreach ( $objects as $obj ) {
			/** @var \Pressbooks\HTMLBook\Element $fm */
			$fm = new $obj();
			if ( in_array( $data_type, $fm->getSupportedDataTypes(), true ) ) {
				$fm->setDataType( $data_type );
				break;
			}
		}
		return $fm;
	}

	/**
	 * @param $data_type
	 *
	 * @return Element
	 */
	public function guessBackMatterObj( $data_type ) {
		$objects = [
			'\Pressbooks\HTMLBook\Component\Appendix',
			'\Pressbooks\HTMLBook\Component\Bibliography',
			'\Pressbooks\HTMLBook\Component\Backmatter',
		];

		foreach ( $objects as $obj ) {
			/** @var \Pressbooks\HTMLBook\Element $bm */
			$bm = new $obj();
			if ( in_array( $data_type, $bm->getSupportedDataTypes(), true ) ) {
				$bm->setDataType( $data_type );
				break;
			}
		}
		return $bm;
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
		$content = apply_filters( 'the_export_content', $content );
		$content = str_ireplace( [ '<b></b>', '<i></i>', '<strong></strong>', '<em></em>' ], '', $content );
		$content = $this->fixAnnoyingCharacters( $content ); // is this used?
		$content = $this->fixInternalLinks( $content );
		$content = $this->switchLaTexFormat( $content );
		if ( ! empty( $_GET['optimize-for-print'] ) ) {
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
					// Check if a fragment is considered external, don't change the URL if we find a match
					$external_anchors = [ \Pressbooks\Interactive\Content::ANCHOR ];
					if ( in_array( "#{$fragment}", $external_anchors, true ) ) {
						continue;
					} else {
						$link->setAttribute( 'href', "#{$fragment}" );
						$changed = true;
					}
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
	 * Removes the CC attribution link.
	 *
	 * @since 4.1
	 *
	 * @param string $content
	 *
	 * @return string
	 */
	protected function removeAttributionLink( $content ) {
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
			}
		}

		$content = $html5->saveHTML( $dom );

		return $content;
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

		// HTML5

		$html = \Pressbooks\Interactive\Content::init()->replaceInteractiveTags( $html );

		$config = [
			'no_deprecated_attr' => 2,
			'unique_ids' => 'fixme-',
			'tidy' => -1,
		];

		$spec = '';
		$spec .= 'table=-border;';

		return \Pressbooks\HtmLawed::filter( $html, $config, $spec );
	}


	// ----------------------------------------------------------------------------------------------------------------
	// Echo Functions
	// ----------------------------------------------------------------------------------------------------------------

	/**
	 */
	protected function echoDocType() {

		echo '<!DOCTYPE html>' . "\n";
		echo '<html xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"' . "\n";
		echo 'xsi:schemaLocation="http://www.w3.org/1999/xhtml https://raw.githubusercontent.com/oreillymedia/HTMLBook/master/schema/htmlbook.xsd"' . "\n";
		echo 'xmlns="http://www.w3.org/1999/xhtml"' . "\n";
		echo 'lang="' . $this->lang . '">' . "\n";
	}


	/**
	 * @param Book $book
	 * @param array $book_contents
	 */
	protected function beforeTitle( $book, $book_contents ) {

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

				$show_title = get_post_meta( $front_matter_id, 'pb_show_title', true );

				// HTML

				$fm = $this->guessFrontMatterObj( $subclass );
				$fm->setAttributes(
					[
						'class' => "front-matter {$subclass}",
						'id' => "front-matter-{$front_matter['post_name']}",
					]
				);

				$header = new Header();

				$h1 = new H1();
				$h1->setAttributes(
					[
						'class' => 'front-matter-title',
					]
				);
				if ( ! $show_title ) {
					$h1->appendAttributes(
						[
							'class' => 'display-none',
						]
					);
				}
				$h1->setContent( Sanitize\decode( $front_matter['post_title'] ) );

				$p = new Paragraph();
				$p->setDataType( 'subtitle' );
				$p->setAttributes(
					[
						'class' => 'front-matter-number',
					]
				);
				if ( ! $show_title ) {
					$p->appendAttributes(
						[
							'class' => 'display-none',
						]
					);
				}
				$p->setContent( $i );

				$header->setContent(
					[
						$h1,
						$p,
					]
				);

				$fm->setContent(
					[
						$header,
						$front_matter['post_content'],
					]
				);

				$book->appendContent( $fm );

				++$i;
			}
		}
		$this->frontMatterPos = $i;
	}


	/**
	 * @param Book $book
	 */
	protected function halfTitle( $book ) {

		$fm = new Frontmatter();
		$fm->setDataType( 'halftitlepage' );
		$fm->setContent( '<h1 class="title">' . get_bloginfo( 'name' ) . '</h1>' );

		$book->appendContent( $fm );
	}


	/**
	 * @param Book $book
	 * @param array $book_contents
	 * @param array $metadata
	 */
	protected function title( $book, $book_contents, $metadata ) {

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

		$fm = new Frontmatter();
		$fm->setDataType( 'titlepage' );

		if ( ! $content ) {
			$content .= sprintf( '<h1 class="title">%s</h1>', get_bloginfo( 'name' ) );
			$content .= sprintf( '<p class="subtitle">%s</p>', ( isset( $metadata['pb_subtitle'] ) ) ? $metadata['pb_subtitle'] : '' );
			if ( isset( $metadata['pb_authors'] ) ) {
				$authors = oxford_comma_explode( $metadata['pb_authors'] );
				foreach ( $authors as $author ) {
					$content .= sprintf( '<p class="author">%s</p>', $author );
				}
			}
			if ( current_theme_supports( 'pressbooks_publisher_logo' ) ) {
				$content .= sprintf( '<p class="publisher-logo"><img src="%s" /></p>', get_theme_support( 'pressbooks_publisher_logo' )[0]['logo_uri'] ); // TODO: Support custom publisher logo.
			}
			$content .= sprintf( '<p class="publisher">%s</p>', ( isset( $metadata['pb_publisher'] ) ) ? $metadata['pb_publisher'] : '' );
			$content .= sprintf( '<p class="publisher-city">%s</p>', ( isset( $metadata['pb_publisher_city'] ) ) ? $metadata['pb_publisher_city'] : '' );
		}

		$fm->setContent( $content );

		$book->appendContent( $fm );
	}


	/**
	 * @param Book $book
	 * @param array $metadata
	 */
	protected function copyright( $book, $metadata ) {

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

		$fm = new Frontmatter();
		$fm->setDataType( 'copyright-page' );
		$fm->appendContent( '<h1>' . get_bloginfo( 'name' ) . '</h1>' );

		// Custom Copyright must override All Rights Reserved
		if ( ! $has_custom_copyright || ( $has_custom_copyright && ! $all_rights_reserved ) ) {
			$license = $this->doCopyrightLicense( $metadata );
			if ( $license ) {
				$fm->appendContent( $this->removeAttributionLink( $license ) );
			}
		}

		// Custom copyright
		if ( $has_custom_copyright ) {
			$fm->appendContent( $this->tidy( $metadata['pb_custom_copyright'] ) );
		}

		// default, so something is displayed
		if ( empty( $metadata['pb_custom_copyright'] ) && empty( $license ) ) {
			$p = new Paragraph();
			$p->appendContent( get_bloginfo( 'name' ) . ' ' . __( 'Copyright', 'pressbooks' ) . ' &#169; ' );
			if ( ! empty( $meta['pb_copyright_year'] ) ) {
				$p->appendContent( $meta['pb_copyright_year'] . ' ' );
			} elseif ( ! empty( $meta['pb_publication_date'] ) ) {
				$p->appendContent( strftime( '%Y', $meta['pb_publication_date'] ) );
			} else {
				$p->appendContent( date( 'Y' ) );
			}
			if ( ! empty( $metadata['pb_copyright_holder'] ) ) {
				$p->appendContent( ' ' . __( 'by', 'pressbooks' ) . ' ' . $metadata['pb_copyright_holder'] . '. ' );
			}

			$fm->appendContent( $p );
		}

		$book->appendContent( $fm );
	}


	/**
	 * @param Book $book
	 * @param array $book_contents
	 */
	protected function dedicationAndEpigraph( $book, $book_contents ) {

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

				$show_title = get_post_meta( $front_matter_id, 'pb_show_title', true );

				// HTML

				$fm = $this->guessFrontMatterObj( $subclass );
				$fm->setAttributes(
					[
						'class' => "front-matter {$subclass}",
						'id' => "front-matter-{$front_matter['post_name']}",
					]
				);

				$header = new Header();

				$h1 = new H1();
				$h1->setAttributes(
					[
						'class' => 'front-matter-title',
					]
				);
				if ( ! $show_title ) {
					$h1->appendAttributes(
						[
							'class' => 'display-none',
						]
					);
				}
				$h1->setContent( Sanitize\decode( $front_matter['post_title'] ) );

				$p = new Paragraph();
				$p->setDataType( 'subtitle' );
				$p->setAttributes(
					[
						'class' => 'front-matter-number',
					]
				);
				if ( ! $show_title ) {
					$p->appendAttributes(
						[
							'class' => 'display-none',
						]
					);
				}
				$p->setContent( $i );

				$header->setContent(
					[
						$h1,
						$p,
					]
				);

				if ( $subclass === 'epigraph' ) {
					$content = new Blockquote();
					$content->setDataType( 'epigraph' );
					$content->appendContent( $front_matter['post_content'] );

				} else {
					$content = $front_matter['post_content'];
				}

				$fm->setContent(
					[
						$header,
						$content,
					]
				);

				$book->appendContent( $fm );

				++$i;
			}
		}
		$this->frontMatterPos = $i;
	}


	/**
	 * @param Book $book
	 * @param array $book_contents
	 */
	protected function tableOfContents( $book, $book_contents ) {

		$toc = new TableOfContents();

		$h1 = new H1;
		$h1->appendContent( __( 'Contents', 'pressbooks' ) );
		$toc->appendContent( $h1 );

		$ordered_lists = new OrderedLists();

		foreach ( $book_contents as $type => $struct ) {

			if ( preg_match( '/^__/', $type ) ) {
				continue; // Skip __magic keys
			}

			if ( 'part' === $type ) {
				foreach ( $struct as $part ) {
					$slug = "part-{$part['post_name']}";
					$title = Sanitize\strip_br( $part['post_title'] );
					$part_content = trim( $part['post_content'] );

					$li = new Element();
					$li->setTag( 'li' );
					$li->setContent( sprintf( '<a href="#%s">%s</a>', $slug, Sanitize\decode( $title ) ) );

					if ( get_post_meta( $part['ID'], 'pb_part_invisible', true ) !== 'on' ) { // visible
						if ( count( $book_contents['part'] ) === 1 ) { // only part
							if ( $part_content ) { // has content
								$li->setAttributes(
									[
										'class' => 'part',
									]
								); // show in TOC
								$ordered_lists->appendContent( $li );
							} else { // no content
								$li->setAttributes(
									[
										'class' => 'part display-none',
									]
								); // hide from TOC
								$ordered_lists->appendContent( $li );
							}
						} elseif ( count( $book_contents['part'] ) > 1 ) { // multiple parts
							if ( $this->atLeastOneExport( $part['chapters'] ) ) { // has chapter
								$li->setAttributes(
									[
										'class' => 'part',
									]
								); // show in TOC
								$ordered_lists->appendContent( $li );
							} else { // no chapter
								if ( $part_content ) { // has content
									$li->setAttributes(
										[
											'class' => 'part',
										]
									); // show in TOC
									$ordered_lists->appendContent( $li );
								} else { // no content
									$li->setAttributes(
										[
											'class' => 'part display-none',
										]
									); // hide from TOC
									$ordered_lists->appendContent( $li );
								}
							}
						}
					} elseif ( get_post_meta( $part['ID'], 'pb_part_invisible', true ) === 'on' ) { // invisible
						$li->setAttributes(
							[
								'class' => 'part display-none',
							]
						); // hide from TOC
						$ordered_lists->appendContent( $li );
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

						$li = new Element();
						$li->setTag( 'li' );
						$li->setAttributes(
							[
								'class' => "chapter $subclass",
							]
						);

						$li_href = sprintf( '<a href="#%s"><span class="toc-chapter-title">%s</span>', $slug, Sanitize\decode( $title ) );
						if ( $subtitle ) {
							$li_href .= ' <span class="chapter-subtitle">' . Sanitize\decode( $subtitle ) . '</span>';
						}
						if ( $author ) {
							$li_href .= ' <span class="chapter-author">' . Sanitize\decode( $author ) . '</span>';
						}
						if ( $license ) {
							$li_href .= ' <span class="chapter-license">' . $license . '</span> ';
						}
						$li_href .= '</a>';

						$li->appendContent( $li_href );

						if ( \Pressbooks\Modules\Export\Export::shouldParseSubsections() === true ) {
							$sections = \Pressbooks\Book::getSubsections( $chapter['ID'] );
							if ( $sections ) {
								$li_sections = '<ol class="sections">';
								foreach ( $sections as $id => $title ) {
									$li_sections .= '<li class="section"><a href="#' . $id . '"><span class="toc-subsection-title">' . strip_shortcodes( Sanitize\decode( $title ) ) . '</span></a></li>';
								}
								$li_sections .= '</ol>';
								$li->appendContent( $li_sections );
							}
						}

						$ordered_lists->appendContent( $li );
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

					$li = new Element();
					$li->setTag( 'li' );
					$li->setAttributes(
						[
							'class' => $typetype,
						]
					);

					$li_href = sprintf( '<a href="#%s"><span class="toc-chapter-title">%s</span>', $slug, Sanitize\decode( $title ) );
					if ( $subtitle ) {
						$li_href .= ' <span class="chapter-subtitle">' . Sanitize\decode( $subtitle ) . '</span>';
					}
					if ( $author ) {
						$li_href .= ' <span class="chapter-author">' . Sanitize\decode( $author ) . '</span>';
					}
					if ( $license ) {
						$li_href .= ' <span class="chapter-license">' . $license . '</span> ';
					}
					$li_href .= '</a>';

					$li->appendContent( $li_href );

					if ( \Pressbooks\Modules\Export\Export::shouldParseSubsections() === true ) {
						$sections = \Pressbooks\Book::getSubsections( $val['ID'] );
						if ( $sections ) {
							$li_sections = '<ol class="sections">';
							foreach ( $sections as $id => $title ) {
								$li_sections .= '<li class="section"><a href="#' . $id . '"><span class="toc-subsection-title">' . strip_shortcodes( Sanitize\decode( $title ) ) . '</span></a></li>';
							}
							$li_sections .= '</ol>';
							$li->appendContent( $li_sections );
						}
					}

					$ordered_lists->appendContent( $li );
				}
			}
		}

		$toc->appendContent( $ordered_lists );
		$book->appendContent( $toc );
	}


	/**
	 * @param Book $book
	 * @param array $book_contents
	 * @param array $metadata
	 */
	protected function frontMatter( $book, $book_contents, $metadata ) {

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

			$content = $front_matter['post_content'];
			$append_front_matter_content = apply_filters( 'pb_append_front_matter_content', '', $front_matter_id );
			$short_title = trim( get_post_meta( $front_matter_id, 'pb_short_title', true ) );
			$subtitle = trim( get_post_meta( $front_matter_id, 'pb_subtitle', true ) );
			$author = trim( get_post_meta( $front_matter_id, 'pb_section_author', true ) );

			if ( \Pressbooks\Modules\Export\Export::shouldParseSubsections() === true ) {
				if ( \Pressbooks\Book::getSubsections( $front_matter_id ) !== false ) {
					$content = \Pressbooks\Book::tagSubsections( $content, $front_matter_id );
				}
			}
			if ( $author ) {
				$content = '<h2 class="chapter-author">' . Sanitize\decode( $author ) . '</h2>' . $content;
			}
			if ( $subtitle ) {
				$content = '<h2 class="chapter-subtitle">' . Sanitize\decode( $subtitle ) . '</h2>' . $content;
			}

			$append_front_matter_content .= $this->removeAttributionLink( $this->doSectionLevelLicense( $metadata, $front_matter_id ) );

			$show_title = get_post_meta( $front_matter_id, 'pb_show_title', true );

			// HTML

			$fm = $this->guessFrontMatterObj( $subclass );
			$fm->setAttributes(
				[
					'class' => "front-matter {$subclass}",
					'id' => "front-matter-{$front_matter['post_name']}",
					'title' => $short_title ? $short_title : wp_strip_all_tags( Sanitize\decode( $front_matter['post_title'] ) ),
				]
			);

			$header = new Header();

			$h1 = new H1();
			$h1->setAttributes(
				[
					'class' => 'front-matter-title',
				]
			);
			if ( ! $show_title ) {
				$h1->appendAttributes(
					[
						'class' => 'display-none',
					]
				);
			}
			$h1->setContent( Sanitize\decode( $front_matter['post_title'] ) );

			$p = new Paragraph();
			$p->setDataType( 'subtitle' );
			$p->setAttributes(
				[
					'class' => 'front-matter-number',
				]
			);
			if ( ! $show_title ) {
				$p->appendAttributes(
					[
						'class' => 'display-none',
					]
				);
			}
			$p->setContent( $i );

			$header->setContent(
				[
					$h1,
					$p,
				]
			);

			$fm->setContent(
				[
					$header,
					$content,
					$append_front_matter_content,
				]
			);

			$book->appendContent( $fm );

			++$i;
		}
		$this->frontMatterPos = $i;
	}


	/**
	 */
	protected function echoPromo() {

		$promo_html = apply_filters( 'pressbooks_pdf_promo', '' );
		if ( $promo_html ) {
			echo $promo_html;
		}
	}


	/**
	 * @param Book $book
	 * @param array $book_contents
	 * @param array $metadata
	 */
	protected function partsAndChapters( $book, $book_contents, $metadata ) {

		$i = 1;
		$j = 1;
		foreach ( $book_contents['part'] as $part ) {

			$my_part = new Part();

			$invisibility = ( get_post_meta( $part['ID'], 'pb_part_invisible', true ) === 'on' ) ? 'invisible' : '';
			$slug = "part-{$part['post_name']}";
			$title = $part['post_title'];
			$part_content = trim( $part['post_content'] );

			// Inject introduction class?
			$inject_introduction_class = false;
			if ( 'invisible' !== $invisibility ) { // visible
				if ( count( $book_contents['part'] ) === 1 ) { // only part
					if ( $part_content ) { // has content
						if ( ! $this->hasIntroduction ) {
							$inject_introduction_class = true;
							$this->hasIntroduction = true;
						}
					}
				} elseif ( count( $book_contents['part'] ) > 1 ) { // multiple parts
					if ( ! $this->hasIntroduction ) {
						$inject_introduction_class = true;
						$this->hasIntroduction = true;
					}
				}
			}

			if ( $inject_introduction_class ) {
				$my_part->setAttributes(
					[
						'class' => "part introduction {$invisibility}",
					]
				);
			} else {
				$my_part->setAttributes(
					[
						'class' => "part {$invisibility}",
					]
				);
			}
			$my_part->appendAttributes(
				[
					'id' => $slug,
				]
			);

			$h1 = new H1();
			$h1->setAttributes(
				[
					'class' => 'part-title',
				]
			);
			$h1->setContent( Sanitize\decode( $title ) );

			$p = new Paragraph();
			$p->setDataType( 'subtitle' );
			$p->setAttributes(
				[
					'class' => 'part-number',
				]
			);
			$m = ( 'invisible' === $invisibility ) ? '' : $i;
			$p->setContent( \Pressbooks\L10n\romanize( $m ) );

			$header = new Header();
			$header->setContent(
				[
					$h1,
					$p,
				]
			);

			$my_part->setContent(
				[
					$header,
					$part_content,
				]
			);

			$my_chapters = [];
			foreach ( $part['chapters'] as $chapter ) {

				if ( ! $chapter['export'] ) {
					continue; // Skip
				}

				$chapter_id = $chapter['ID'];
				$subclass = $this->taxonomy->getChapterType( $chapter_id );
				$slug = "chapter-{$chapter['post_name']}";
				$title = ( get_post_meta( $chapter_id, 'pb_show_title', true ) ? $chapter['post_title'] : '<span class="display-none">' . $chapter['post_title'] . '</span>' ); // Preserve auto-indexing in Prince using hidden span
				$content = $chapter['post_content'];
				$append_chapter_content = apply_filters( 'pb_append_chapter_content', '', $chapter_id );
				$short_title = trim( get_post_meta( $chapter_id, 'pb_short_title', true ) );
				$subtitle = trim( get_post_meta( $chapter_id, 'pb_subtitle', true ) );
				$author = $this->contributors->get( $chapter_id, 'pb_authors' );

				if ( \Pressbooks\Modules\Export\Export::shouldParseSubsections() === true ) {
					if ( \Pressbooks\Book::getSubsections( $chapter_id ) !== false ) {
						$content = \Pressbooks\Book::tagSubsections( $content, $chapter_id );
					}
				}

				if ( $author ) {
					$content = '<h2 class="chapter-author">' . Sanitize\decode( $author ) . '</h2>' . $content;
				}
				if ( $subtitle ) {
					$content = '<h2 class="chapter-subtitle">' . Sanitize\decode( $subtitle ) . '</h2>' . $content;
				}

				// Inject introduction class?
				if ( ! $this->hasIntroduction ) {
					$inject_introduction_class = true;
					$this->hasIntroduction = true;
				}

				$append_chapter_content .= $this->removeAttributionLink( $this->doSectionLevelLicense( $metadata, $chapter_id ) );

				$my_chapter = new Chapter();
				if ( $inject_introduction_class ) {
					$my_chapter->setAttributes(
						[
							'class' => "chapter introduction {$invisibility}",
						]
					);
				} else {
					$my_chapter->setAttributes(
						[
							'class' => "chapter {$invisibility}",
						]
					);
				}
				$my_chapter->appendAttributes(
					[
						'id' => $slug,
						'title' => $short_title ? $short_title : wp_strip_all_tags( Sanitize\decode( $chapter['post_title'] ) ),
					]
				);

				$h1 = new H1();
				$h1->setAttributes(
					[
						'class' => 'chapter-title',
					]
				);
				$h1->setContent( Sanitize\decode( $title ) );

				$p = new Paragraph();
				$p->setDataType( 'subtitle' );
				$p->setAttributes(
					[
						'class' => 'chapter-number',
					]
				);
				$my_chapter_number = ( strpos( $subclass, 'numberless' ) === false ) ? $j : '';
				$p->setContent( $my_chapter_number );

				$header = new Header();
				$header->setContent(
					[
						$h1,
						$p,
					]
				);

				$my_chapter->setContent(
					[
						$header,
						$content,
						$append_chapter_content,
					]
				);

				$my_part->appendContent( $my_chapter );
				$my_chapters[] = $my_chapter;

				if ( $my_chapter_number !== '' ) {
					++$j;
				}
			}

			// Echo with parts?

			if ( 'invisible' !== $invisibility ) { // visible
				if ( count( $book_contents['part'] ) === 1 ) { // only part
					if ( $part_content ) { // has content
						$book->appendContent( $my_part );
					} else { // no content
						if ( ! empty( $my_chapters ) ) {
							foreach ( $my_chapters as $c ) {
								$book->appendContent( $c );
							}
						}
					}
				} elseif ( count( $book_contents['part'] ) > 1 ) { // multiple parts
					$book->appendContent( $my_part );
				}
				++$i;
			} elseif ( 'invisible' === $invisibility ) { // invisible
				if ( ! empty( $my_chapters ) ) {
					foreach ( $my_chapters as $c ) {
						$book->appendContent( $c );
					}
				}
			}
		}

	}


	/**
	 * @param Book $book
	 * @param array $book_contents
	 * @param array $metadata
	 */
	protected function backMatter( $book, $book_contents, $metadata ) {

		$i = 1;
		foreach ( $book_contents['back-matter'] as $back_matter ) {

			if ( ! $back_matter['export'] ) {
				continue;
			}

			$back_matter_id = $back_matter['ID'];
			$subclass = $this->taxonomy->getBackMatterType( $back_matter_id );
			$content = $back_matter['post_content'];
			$append_back_matter_content = apply_filters( 'pb_append_back_matter_content', '', $back_matter_id );
			$short_title = trim( get_post_meta( $back_matter_id, 'pb_short_title', true ) );
			$subtitle = trim( get_post_meta( $back_matter_id, 'pb_subtitle', true ) );
			$author = $this->contributors->get( $back_matter_id, 'pb_authors' );

			if ( \Pressbooks\Modules\Export\Export::shouldParseSubsections() === true ) {
				if ( \Pressbooks\Book::getSubsections( $back_matter_id ) !== false ) {
					$content = \Pressbooks\Book::tagSubsections( $content, $back_matter_id );
				}
			}
			if ( $author ) {
				$content = '<h2 class="chapter-author">' . Sanitize\decode( $author ) . '</h2>' . $content;
			}
			if ( $subtitle ) {
				$content = '<h2 class="chapter-subtitle">' . Sanitize\decode( $subtitle ) . '</h2>' . $content;
			}

			$append_back_matter_content .= $this->removeAttributionLink( $this->doSectionLevelLicense( $metadata, $back_matter_id ) );

			$show_title = get_post_meta( $back_matter_id, 'pb_show_title', true );

			// HTML

			$bm = $this->guessBackMatterObj( $subclass );
			$bm->setAttributes(
				[
					'class' => "back-matter {$subclass}",
					'id' => "back-matter-{$back_matter['post_name']}",
					'title' => $short_title ? $short_title : wp_strip_all_tags( Sanitize\decode( $back_matter['post_title'] ) ),
				]
			);

			$header = new Header();

			$h1 = new H1();
			$h1->setAttributes(
				[
					'class' => 'back-matter-title',
				]
			);
			if ( ! $show_title ) {
				$h1->appendAttributes(
					[
						'class' => 'display-none',
					]
				);
			}
			$h1->setContent( Sanitize\decode( $back_matter['post_title'] ) );

			$p = new Paragraph();
			$p->setDataType( 'subtitle' );
			$p->setAttributes(
				[
					'class' => 'back-matter-number',
				]
			);
			if ( ! $show_title ) {
				$p->appendAttributes(
					[
						'class' => 'display-none',
					]
				);
			}
			$p->setContent( $i );

			$header->setContent(
				[
					$h1,
					$p,
				]
			);

			$bm->setContent(
				[
					$header,
					$content,
					$append_back_matter_content,
				]
			);

			$book->appendContent( $bm );

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
