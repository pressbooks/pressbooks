<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */

namespace Pressbooks\Modules\Export\Xhtml;

use Masterminds\HTML5;
use Pressbooks\Container;
use Pressbooks\Modules\Export\Export;
use Pressbooks\Sanitize;
use function Pressbooks\Sanitize\clean_filename;

class Xhtml11 extends Export {

	/**
	 * Service URL
	 *
	 * @var string
	 */
	public $url;

	/**
	 * Main language of document, two letter code
	 *
	 * @var string
	 */
	protected $lang = 'en';


	/**
	 * @param array $args
	 */
	function __construct( array $args ) {

		// Some defaults

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
			$this->url .= '&' . http_build_query( [ 'preview' => $_REQUEST['preview'] ] );
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
		file_put_contents( $filename, $output );
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
		if ( count( $output ) ) {
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

		// Override footnote shortcode
		if ( ! empty( $_GET['endnotes'] ) ) {
			add_shortcode( 'footnote', [ $this, 'endnoteShortcode' ] );
		} else {
			add_shortcode( 'footnote', [ $this, 'footnoteShortcode' ] );
		}

		// ------------------------------------------------------------------------------------------------------------
		// XHTML, Setup

		$metadata = \Pressbooks\Book::getBookInformation();
		$book_contents = $this->preProcessBookContents( \Pressbooks\Book::getBookContents() );

		// Set two letter language code
		if ( isset( $metadata['pb_language'] ) ) {
			list( $this->lang ) = explode( '-', $metadata['pb_language'] );
		}

		$style_url = $script_url = false;
		if ( ! empty( $_GET['style'] ) ) {
			$style_url = Container::get( 'Sass' )->urlToUserGeneratedCss() . '/' . clean_filename( $_GET['style'] ) . '.css';
		}
		if ( ! empty( $_GET['script'] ) ) {
			$script_url = $this->getExportScriptUrl( clean_filename( $_GET['script'] ) ) . '/script.js';
		}

		// ------------------------------------------------------------------------------------------------------------
		// XHTML, Start

		ob_start();

		// Before Title Page
		$this->echoBeforeTitle( $book_contents );

		// Half-title
		$this->echoHalfTitle();

		// Title
		$this->echoTitle( $book_contents, $metadata );

		// Copyright
		$this->echoCopyright( $metadata );

		// Dedication and Epigraph (In that order!)
		$this->echoDedicationAndEpigraph( $book_contents );

		// Table of contents
		$this->echoToc( $book_contents );

		// Front-matter
		$this->echoFrontMatter( $book_contents, $metadata );

		// Promo
		$this->createPromo();

		// Parts, Chapters
		$this->echoPartsAndChapters( $book_contents, $metadata );

		// Back-matter
		$this->echoBackMatter( $book_contents, $metadata );

		$buffer = ob_get_clean();

		// ------------------------------------------------------------------------------------------------------------
		// XHTML, Wrap

		$blade = Container::get( 'Blade' );
		$book = $blade->render( 'export.xhtml.book', [
			'lang' => $this->lang,
			'pb_plugin_version' => PB_PLUGIN_VERSION,
			'style_url' => $style_url,
			'script_url' => $script_url,
			'title' => get_bloginfo( 'name' ),
			'metadata' => $metadata,
			'buffer' => $buffer,
		] );

		if ( $return ) {
			return $book;
		} else {
			echo $book;
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
	 * @see endnotes.blade.php
	 *
	 * @param array $atts
	 * @param null $content
	 *
	 * @return string
	 */
	function endnoteShortcode( $atts, $content = null ) {

		global $id;

		if ( ! $content ) {
			return '';
		}

		Blade::$endnotes[ $id ][] = trim( $content );

		return '<sup class="endnote">' . count( Blade::$endnotes[ $id ] ) . '</sup>';
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
					$book_contents[ $type ][ $i ]['post_content'] = $this->preProcessPostContent( $val['post_content'] );
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
	 * @returns string
	 */
	protected function switchLaTexFormat( $content ) {
		$content = preg_replace( '/(quicklatex.com-[a-f0-9]{32}_l3.)(png)/i', '$1svg', $content );

		return $content;
	}

	/**
	 * @param string $content
	 *
	 * @return string
	 */
	protected function fixInternalLinks( $content ) {
		// takes care of PB subdirectory installations of PB
		$content = preg_replace( '/href\="\/([a-z0-9]*)\/(front\-matter|chapter|back\-matter|part)\/([a-z0-9\-]*)([\/]?)(\#[a-z0-9\-]*)"/', 'href="$5"', $content );
		$content = preg_replace( '/href\="\/([a-z0-9]*)\/(front\-matter|chapter|back\-matter|part)\/([a-z0-9\-]*)([\/]?)"/', 'href="#$3"', $content );

		// takes care of PB subdomain installations of PB
		$content = preg_replace( '/href\="\/(front\-matter|chapter|back\-matter|part)\/([a-z0-9\-]*)([\/]?)(\#[a-z0-9\-]*)"/', 'href="$4"', $content );
		$output = preg_replace( '/href\="\/(front\-matter|chapter|back\-matter|part)\/([a-z0-9\-]*)([\/]?)"/', 'href="#$2"', $content );

		return $output;
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
		$html5 = new HTML5();
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
		$content = \Pressbooks\Sanitize\strip_container_tags( $content );

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
		$html5 = new HTML5();
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
			$content = \Pressbooks\Sanitize\strip_container_tags( $content );
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

		$config = [
			'valid_xhtml' => 1,
			'no_deprecated_attr' => 2,
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
	 */
	protected function echoBeforeTitle( $book_contents ) {
		$blade = Container::get( 'Blade' );
		echo $blade->render( 'export.xhtml.before-title', [
			'book_contents' => $book_contents,
		] );
	}


	/**
	 *
	 */
	protected function echoHalfTitle() {
		$blade = Container::get( 'Blade' );
		echo $blade->render( 'export.xhtml.half-title', [
			'title' => get_bloginfo( 'name' ),
		] );
	}


	/**
	 * @param array $book_contents
	 * @param array $metadata
	 */
	protected function echoTitle( $book_contents, $metadata ) {
		$blade = Container::get( 'Blade' );
		echo $blade->render( 'export.xhtml.title', [
			'title' => get_bloginfo( 'name' ),
			'book_contents' => $book_contents,
			'metadata' => $metadata,
		] );
	}


	/**
	 * @param array $metadata
	 */
	protected function echoCopyright( $metadata ) {

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
		$html = '';
		if ( ! $has_custom_copyright || ( $has_custom_copyright && ! $all_rights_reserved ) ) {
			$license = $this->doCopyrightLicense( $metadata );
			if ( $license ) {
				$html .= $this->removeAttributionLink( $license );
			}
		}

		// Custom copyright
		if ( $has_custom_copyright ) {
			$html .= $this->tidy( $metadata['pb_custom_copyright'] );
		}

		// default, so something is displayed
		if ( empty( $metadata['pb_custom_copyright'] ) && empty( $license ) ) {
			$html .= '<p>';
			$html .= get_bloginfo( 'name' ) . ' ' . __( 'Copyright', 'pressbooks' ) . ' &#169; ';
			if ( ! empty( $meta['pb_copyright_year'] ) ) {
				$html .= $meta['pb_copyright_year'] . ' ';
			} elseif ( ! empty( $meta['pb_publication_date'] ) ) {
				$html .= strftime( '%Y', $meta['pb_publication_date'] );
			} else {
				$html .= date( 'Y' );
			}
			if ( ! empty( $metadata['pb_copyright_holder'] ) ) {
				$html .= ' ' . __( 'by', 'pressbooks' ) . ' ' . $metadata['pb_copyright_holder'] . '. ';
			}
			$html .= '</p>';
		}

		$blade = Container::get( 'Blade' );
		echo $blade->render( 'export.xhtml.copyright', [
			'copyright' => $html,
		] );
	}


	/**
	 * @param array $book_contents
	 */
	protected function echoDedicationAndEpigraph( $book_contents ) {
		$blade = Container::get( 'Blade' );
		echo $blade->render( 'export.xhtml.dedication-and-epigraph', [
			'book_contents' => $book_contents,
		] );
	}


	/**
	 * @param array $book_contents
	 */
	protected function echoToc( $book_contents ) {

		echo '<div id="toc"><h1>' . __( 'Contents', 'pressbooks' ) . '</h1><ul>';
		foreach ( $book_contents as $type => $struct ) {

			if ( preg_match( '/^__/', $type ) ) {
				continue; // Skip __magic keys
			}

			if ( 'part' === $type ) {
				foreach ( $struct as $part ) {
					$slug = $part['post_name'];
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

						$subclass = \Pressbooks\Taxonomy::getChapterType( $chapter['ID'] );
						$slug = $chapter['post_name'];
						$title = Sanitize\strip_br( $chapter['post_title'] );
						$subtitle = trim( get_post_meta( $chapter['ID'], 'pb_subtitle', true ) );
						$author = trim( get_post_meta( $chapter['ID'], 'pb_section_author', true ) );
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
				foreach ( $struct as $val ) {

					if ( ! $val['export'] ) {
						continue;
					}

					$typetype = '';
					$subtitle = '';
					$author = '';
					$license = '';
					$slug = $val['post_name'];
					$title = Sanitize\strip_br( $val['post_title'] );

					if ( 'front-matter' === $type ) {
						$subclass = \Pressbooks\Taxonomy::getFrontMatterType( $val['ID'] );
						if ( 'dedication' === $subclass || 'epigraph' === $subclass || 'title-page' === $subclass || 'before-title' === $subclass ) {
							continue; // Skip
						} else {
							$typetype = $type . ' ' . $subclass;
							$subtitle = trim( get_post_meta( $val['ID'], 'pb_subtitle', true ) );
							$author = trim( get_post_meta( $val['ID'], 'pb_section_author', true ) );
							$license = $this->doTocLicense( $val['ID'] );
						}
					} elseif ( 'back-matter' === $type ) {
						$typetype = $type . ' ' . \Pressbooks\Taxonomy::getBackMatterType( $val['ID'] );
						$subtitle = trim( get_post_meta( $val['ID'], 'pb_subtitle', true ) );
						$author = trim( get_post_meta( $val['ID'], 'pb_section_author', true ) );
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

		$blade = Container::get( 'Blade' );
		$i = Blade::$frontMatterPos;
		foreach ( $book_contents['front-matter'] as $front_matter ) {

			if ( ! $front_matter['export'] ) {
				continue; // Skip
			}

			$front_matter_id = $front_matter['ID'];
			$subclass = \Pressbooks\Taxonomy::getFrontMatterType( $front_matter_id );

			if ( 'dedication' === $subclass || 'epigraph' === $subclass || 'title-page' === $subclass || 'before-title' === $subclass ) {
				continue; // Skip
			}

			if ( 'introduction' === $subclass ) {
				Blade::$hasIntroduction = true;
			}

			$slug = $front_matter['post_name'];
			$title = ( get_post_meta( $front_matter_id, 'pb_show_title', true ) ? $front_matter['post_title'] : '<span class="display-none">' . $front_matter['post_title'] . '</span>' ); // Preserve auto-indexing in Prince using hidden span
			$content = $front_matter['post_content'];
			$append_front_matter_content = apply_filters( 'pb_append_front_matter_content', '', $front_matter_id );
			$short_title = trim( get_post_meta( $front_matter_id, 'pb_short_title', true ) );
			$subtitle = trim( get_post_meta( $front_matter_id, 'pb_subtitle', true ) );
			$author = trim( get_post_meta( $front_matter_id, 'pb_section_author', true ) );

			if ( \Pressbooks\Modules\Export\Export::isParsingSubsections() === true ) {
				$sections = \Pressbooks\Book::getSubsections( $front_matter_id );
				if ( $sections ) {
					$content = \Pressbooks\Book::tagSubsections( $content, $front_matter_id );
				}
			}

			if ( $author ) {
				$content = '<h2 class="chapter-author">' . Sanitize\decode( $author ) . '</h2>' . $content;
			}

			if ( $subtitle ) {
				$content = '<h2 class="chapter-subtitle">' . Sanitize\decode( $subtitle ) . '</h2>' . $content;
			}

			if ( $short_title ) {
				$content = '<h6 class="short-title">' . Sanitize\decode( $short_title ) . '</h6>' . $content;
			}

			$append_front_matter_content .= $this->removeAttributionLink( $this->doSectionLevelLicense( $metadata, $front_matter_id ) );

			echo $blade->render( 'export.xhtml.front-matter', [
				'post_id' => $front_matter_id,
				'subclass' => $subclass,
				'slug' => $slug,
				'i' => $i,
				'title' => Sanitize\decode( $title ),
				'content' => $content,
				'append_front_matter_content' => $append_front_matter_content,
			] );

			++$i;
		}
		Blade::$frontMatterPos = $i;
	}


	/**
	 *
	 */
	protected function createPromo() {

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

		$blade = Container::get( 'Blade' );
		$i = $j = 1;
		foreach ( $book_contents['part'] as $part ) {

			$invisibility = ( get_post_meta( $part['ID'], 'pb_part_invisible', true ) === 'on' ) ? 'invisible' : '';

			$slug = $part['post_name'];
			$title = $part['post_title'];
			$part_content = trim( $part['post_content'] );

			// Inject introduction class?
			$inject_introduction_class = false;
			if ( 'invisible' !== $invisibility ) { // visible
				if ( count( $book_contents['part'] ) === 1 ) { // only part
					if ( $part_content ) { // has content
						if ( ! Blade::$hasIntroduction ) {
							$inject_introduction_class = true;
							Blade::$hasIntroduction = true;
						}
					}
				} elseif ( count( $book_contents['part'] ) > 1 ) { // multiple parts
					if ( ! Blade::$hasIntroduction ) {
						$inject_introduction_class = true;
						Blade::$hasIntroduction = true;
					}
				}
			}

			if ( $part_content ) {
				$part_content = $this->preProcessPostContent( $part_content );
			}

			$m = ( 'invisible' === $invisibility ) ? 0 : $i;

			$my_part = $blade->render( 'export.xhtml.part', [
				'post_id' => $part['ID'],
				'subclass' => $inject_introduction_class ? "introduction {$invisibility}": $invisibility,
				'slug' => $slug,
				'i' => \Pressbooks\L10n\romanize( $m ),
				'title' => Sanitize\decode( $title ),
				'content' => $part_content,
			] );

			$my_chapters = '';
			foreach ( $part['chapters'] as $chapter ) {

				if ( ! $chapter['export'] ) {
					continue; // Skip
				}

				$chapter_id = $chapter['ID'];
				$subclass = \Pressbooks\Taxonomy::getChapterType( $chapter_id );
				$slug = $chapter['post_name'];
				$title = ( get_post_meta( $chapter_id, 'pb_show_title', true ) ? $chapter['post_title'] : '<span class="display-none">' . $chapter['post_title'] . '</span>' ); // Preserve auto-indexing in Prince using hidden span
				$content = $chapter['post_content'];
				$append_chapter_content = apply_filters( 'pb_append_chapter_content', '', $chapter_id );
				$short_title = trim( get_post_meta( $chapter_id, 'pb_short_title', true ) );
				$subtitle = trim( get_post_meta( $chapter_id, 'pb_subtitle', true ) );
				$author = trim( get_post_meta( $chapter_id, 'pb_section_author', true ) );

				if ( \Pressbooks\Modules\Export\Export::isParsingSubsections() === true ) {
					$sections = \Pressbooks\Book::getSubsections( $chapter_id );
					if ( $sections ) {
						$content = \Pressbooks\Book::tagSubsections( $content, $chapter_id );
					}
				}

				if ( $author ) {
					$content = '<h2 class="chapter-author">' . Sanitize\decode( $author ) . '</h2>' . $content;
				}

				if ( $subtitle ) {
					$content = '<h2 class="chapter-subtitle">' . Sanitize\decode( $subtitle ) . '</h2>' . $content;
				}

				if ( $short_title ) {
					$content = '<h6 class="short-title">' . Sanitize\decode( $short_title ) . '</h6>' . $content;
				}

				$append_chapter_content .= $this->removeAttributionLink( $this->doSectionLevelLicense( $metadata, $chapter_id ) );

				$my_chapters .= $blade->render( 'export.xhtml.chapter', [
					'post_id' => $chapter_id,
					'subclass' => $subclass,
					'slug' => $slug,
					'i' => ( 'numberless' === $subclass ) ? '' : $j,
					'title' => Sanitize\decode( $title ),
					'content' => $content,
					'append_chapter_content' => $append_chapter_content,
				] );

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

		$blade = Container::get( 'Blade' );
		$i = 1;
		foreach ( $book_contents['back-matter'] as $back_matter ) {

			if ( ! $back_matter['export'] ) {
				continue;
			}

			$back_matter_id = $back_matter['ID'];
			$subclass = \Pressbooks\Taxonomy::getBackMatterType( $back_matter_id );
			$slug = $back_matter['post_name'];
			$title = ( get_post_meta( $back_matter_id, 'pb_show_title', true ) ? $back_matter['post_title'] : '<span class="display-none">' . $back_matter['post_title'] . '</span>' ); // Preserve auto-indexing in Prince using hidden span
			$content = $back_matter['post_content'];
			$append_back_matter_content = apply_filters( 'pb_append_back_matter_content', '', $back_matter_id );
			$short_title = trim( get_post_meta( $back_matter_id, 'pb_short_title', true ) );
			$subtitle = trim( get_post_meta( $back_matter_id, 'pb_subtitle', true ) );
			$author = trim( get_post_meta( $back_matter_id, 'pb_section_author', true ) );

			if ( \Pressbooks\Modules\Export\Export::isParsingSubsections() === true ) {
				$sections = \Pressbooks\Book::getSubsections( $back_matter_id );
				if ( $sections ) {
					$content = \Pressbooks\Book::tagSubsections( $content, $back_matter_id );
				}
			}

			if ( $author ) {
				$content = '<h2 class="chapter-author">' . Sanitize\decode( $author ) . '</h2>' . $content;
			}

			if ( $subtitle ) {
				$content = '<h2 class="chapter-subtitle">' . Sanitize\decode( $subtitle ) . '</h2>' . $content;
			}

			if ( $short_title ) {
				$content = '<h6 class="short-title">' . Sanitize\decode( $short_title ) . '</h6>' . $content;
			}

			$append_back_matter_content .= $this->removeAttributionLink( $this->doSectionLevelLicense( $metadata, $back_matter_id ) );

			echo $blade->render( 'export.xhtml.back-matter', [
				'post_id' => $back_matter_id,
				'subclass' => $subclass,
				'slug' => $slug,
				'i' => $i,
				'title' => Sanitize\decode( $title ),
				'content' => $content,
				'append_back_matter_content' => $append_back_matter_content,
			] );

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
