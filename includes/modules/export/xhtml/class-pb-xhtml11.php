<?php
/**
 * @author  PressBooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */
namespace PressBooks\Export\Xhtml;


use PressBooks\Export\Export;
use PressBooks\Sanitize;

require_once( PB_PLUGIN_DIR . 'symbionts/htmLawed/htmLawed.php' );

class Xhtml11 extends Export {


	/**
	 * Timeout in seconds.
	 * Used with wp_remote_get()
	 *
	 * @var int
	 */
	public $timeout = 90;


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
	protected $endnotes = array();


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
	 * @param array $args
	 */
	function __construct( array $args ) {

		// Some defaults

		if ( ! defined( 'PB_XMLLINT_COMMAND' ) )
			define( 'PB_XMLLINT_COMMAND', '/usr/bin/xmllint' );

		$defaults = array(
			'endnotes' => false,
		);
		$r = wp_parse_args( $args, $defaults );

		// Set the access protected "format/xhtml" URL with a valid timestamp and NONCE
		$timestamp = time();
		$md5 = $this->nonce( $timestamp );
		$this->url = home_url() . "/format/xhtml?timestamp={$timestamp}&hashkey={$md5}";

		// Append endnotes to URL?
		if ( $r['endnotes'] )
			$this->url .= '&endnotes=true';

		// HtmLawed: id values not allowed in input
		foreach ( $this->reservedIds as $val ) {
			$fixme[$val] = 1;
		}
		if ( isset( $fixme ) )
			$GLOBALS['hl_Ids'] = $fixme;
	}


	/**
	 * Create $this->outputPath
	 *
	 * @return bool
	 */
	function convert() {

		// Get XHTML

		$output = $this->queryXhtml();

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
		$output = array();
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
	 */
	function transform() {

		// Check permissions

		if ( ! current_user_can( 'manage_options' ) ) {
			$timestamp = absint( @$_REQUEST['timestamp'] );
			$hashkey = @$_REQUEST['hashkey'];
			if ( ! $this->verifyNonce( $timestamp, $hashkey ) ) {
				wp_die( __( 'Invalid permission error', 'pressbooks' ) );
			}
		}

		// Override footnote shortcode
		if ( ! empty( $_GET['endnotes'] ) ) {
			add_shortcode( 'footnote', array( $this, 'endnoteShortcode' ) );
		} else {
			add_shortcode( 'footnote', array( $this, 'footnoteShortcode' ) );
		}


		// ------------------------------------------------------------------------------------------------------------
		// XHTML, Start!

		$metadata = \PressBooks\Book::getBookInformation();
		$book_contents = $this->preProcessBookContents( \PressBooks\Book::getBookContents() );

		$this->echoDocType( $book_contents, $metadata );

		echo "<head>\n";
		echo '<meta content="text/html; charset=UTF-8" http-equiv="content-type" />' . "\n";
		echo '<base href="' . trailingslashit( site_url( '', 'http' ) ) . '" />' . "\n";

		$this->echoMetaData( $book_contents, $metadata );

		echo '<title>' . get_bloginfo( 'name' ) . "</title>\n";
		echo "</head>\n<body>\n";

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

		// XHTML, Stop!
		echo "</body>\n</html>";
	}


	/**
	 * Add $this->url as additional log info, fallback to parent.
	 *
	 * @param $message
	 * @param array $more_info (unused, overridden)
	 */
	function logError( $message, array $more_info = array() ) {

		$more_info = array(
			'url' => $this->url,
		);

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
	 * @param null  $content
	 *
	 * @return string
	 */
	function endnoteShortcode( $atts, $content = null ) {

		global $id;

		if ( ! $content ) {
			return '';
		}

		$this->endnotes[$id][] = trim( $content );

		return '<sup class="endnote">' . count( $this->endnotes[$id] ) . '</sup>';
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

		if ( ! isset( $this->endnotes[$id] ) || ! count( $this->endnotes[$id] ) )
			return '';

		$e = '<div class="endnotes">';
		$e .= '<hr />';
		$e .= '<h3>' . __( 'Notes', 'pressbooks' ) . '</h3>';
		$e .= '<ol>';
		foreach ( $this->endnotes[$id] as $endnote ) {
			$e .= "<li><span>$endnote</span></li>";
		}
		$e .= '</ol></div>';

		return $e;
	}


	/**
	 * Query the access protected "format/xhtml" URL, return the results.
	 *
	 * @return bool|string
	 */
	protected function queryXhtml() {

		$args = array( 'timeout' => $this->timeout );
		$response = wp_remote_get( $this->url, $args );

		// WordPress error?
		if ( is_wp_error( $response ) ) {
			$this->logError( $response->get_error_message() );

			return false;
		}

		// Server error?
		if ( 200 != wp_remote_retrieve_response_code( $response ) ) {
			$this->logError( wp_remote_retrieve_response_message( $response ) );

			return false;
		}

		return wp_remote_retrieve_body( $response );
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

			if ( preg_match( '/^__/', $type ) )
				continue; // Skip __magic keys

			foreach ( $struct as $i => $val ) {

				if ( isset( $val['post_content'] ) ) {
					$id = $val['ID'];
					$book_contents[$type][$i]['post_content'] = $this->preProcessPostContent( $val['post_content'] );
				}
				if ( isset( $val['post_title'] ) ) {
					$book_contents[$type][$i]['post_title'] = Sanitize\sanitize_xml_attribute( $val['post_title'] );
				}
				if ( isset( $val['post_name'] ) ) {
					$book_contents[$type][$i]['post_name'] = $this->preProcessPostName( $val['post_name'] );
				}

				if ( 'part' == $type ) {

					// Do chapters, which are embedded in part structure
					foreach ( $book_contents[$type][$i]['chapters'] as $j => $val2 ) {

						if ( isset( $val2['post_content'] ) ) {
							$id = $val2['ID'];
							$book_contents[$type][$i]['chapters'][$j]['post_content'] = $this->preProcessPostContent( $val2['post_content'] );
						}
						if ( isset( $val2['post_title'] ) ) {
							$book_contents[$type][$i]['chapters'][$j]['post_title'] = Sanitize\sanitize_xml_attribute( $val2['post_title'] );
						}
						if ( isset( $val2['post_name'] ) ) {
							$book_contents[$type][$i]['chapters'][$j]['post_name'] = $this->preProcessPostName( $val2['post_name'] );
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
		$content = $this->fixAnnoyingCharacters( $content );
		$content = $this->tidy( $content );

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

		$config = array(
			'valid_xhtml' => 1,
			'no_deprecated_attr' => 2,
			'unique_ids' => 'fixme-',
			'hook' => '\PressBooks\Sanitize\html5_to_xhtml11',
			'tidy' => -1,
		);

		return htmLawed( $html, $config );
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
		echo '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">' . "\n";
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

		$front_matter_printf = '<div class="front-matter %s" id="%s">';
		$front_matter_printf .= '<div class="front-matter-title-wrap"><h3 class="front-matter-number">%s</h3><h1 class="front-matter-title">%s</h1></div>';
		$front_matter_printf .= '<div class="ugc front-matter-ugc">%s</div>%s';
		$front_matter_printf .= '</div>';

		$i = $this->frontMatterPos;
		foreach ( array( 'before-title' ) as $compare ) {
			foreach ( $book_contents['front-matter'] as $front_matter ) {

				if ( ! $front_matter['export'] )
					continue; // Skip

				$id = $front_matter['ID'];
				$subclass = \PressBooks\Taxonomy\front_matter_type( $id );

				if ( $compare != $subclass )
					continue; //Skip

				$slug = $front_matter['post_name'];
				$title = ( get_post_meta( $id, 'pb_show_title', true ) ? $front_matter['post_title'] : '<span class="display-none">' . $front_matter['post_title'] . '</span>' ); // Preserve auto-indexing in Prince using hidden span
				$content = $front_matter['post_content'];

				printf( $front_matter_printf,
					$subclass,
					$slug,
					$i,
					Sanitize\decode( $title ),
					$content,
					$this->doEndnotes( $id ) );

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

			if ( ! $front_matter['export'] )
				continue; // Skip

			$id = $front_matter['ID'];
			$subclass = \PressBooks\Taxonomy\front_matter_type( $id );

			if ( 'title-page' != $subclass )
				continue; // Skip

			$content = $front_matter['post_content'];
			break;
		}

		// HTML

		echo '<div id="title-page">';
		if ( $content ) {
			echo $content;
		} else {
			printf( '<h1 class="title">%s</h1>', get_bloginfo( 'name' ) );
			printf( '<h2 class="subtitle">%s</h2>', @$metadata['pb_subtitle'] );
			printf( '<div class="logo"></div>' );
			printf( '<h3 class="author">%s</h3>', @$metadata['pb_author'] );
			printf( '<h4 class="publisher">%s</h4>', @$metadata['pb_publisher'] );
			printf( '<h5 class="publisher-city">%s</h5>', @$metadata['pb_publisher_city'] );
		}
		echo "</div>\n";
	}


	/**
	 * @param array $book_contents
	 * @param array $metadata
	 */
	protected function echoCopyright( $book_contents, $metadata ) {

		echo '<div id="copyright-page"><div class="ugc">';

		if ( ! empty( $metadata['pb_custom_copyright'] ) ) {
			echo $this->tidy( $metadata['pb_custom_copyright'] );
		} else {
			echo '<p>';
			echo get_bloginfo( 'name' ) . ' ' . __( 'Copyright', 'pressbooks' ) . ' &#169; ';
			echo ( ! empty( $metadata['pb_copyright_year'] ) ) ? $metadata['pb_copyright_year'] : date( 'Y' );
			if ( ! empty( $metadata['pb_copyright_holder'] ) ) echo ' ' . __( 'by', 'pressbooks' ) . ' ' . $metadata['pb_copyright_holder'] . '. ';
			echo '</p>';
		}

		echo "</div></div>\n";
	}


	/**
	 * @param array $book_contents
	 * @param array $metadata
	 */
	protected function echoDedicationAndEpigraph( $book_contents, $metadata ) {

		$front_matter_printf = '<div class="front-matter %s" id="%s">';
		$front_matter_printf .= '<div class="front-matter-title-wrap"><h3 class="front-matter-number">%s</h3><h1 class="front-matter-title">%s</h1></div>';
		$front_matter_printf .= '<div class="ugc front-matter-ugc">%s</div>%s';
		$front_matter_printf .= '</div>';

		$i = $this->frontMatterPos;
		foreach ( array( 'dedication', 'epigraph' ) as $compare ) {
			foreach ( $book_contents['front-matter'] as $front_matter ) {

				if ( ! $front_matter['export'] )
					continue; // Skip

				$id = $front_matter['ID'];
				$subclass = \PressBooks\Taxonomy\front_matter_type( $id );

				if ( $compare != $subclass )
					continue; //Skip

				$slug = $front_matter['post_name'];
				$title = ( get_post_meta( $id, 'pb_show_title', true ) ? $front_matter['post_title'] : '<span class="display-none">' . $front_matter['post_title'] . '</span>' ); // Preserve auto-indexing in Prince using hidden span
				$content = $front_matter['post_content'];

				printf( $front_matter_printf,
					$subclass,
					$slug,
					$i,
					Sanitize\decode( $title ),
					$content,
					$this->doEndnotes( $id ) );

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

			if ( preg_match( '/^__/', $type ) )
				continue; // Skip __magic keys

			if ( 'part' == $type ) {
				foreach ( $struct as $part ) {
					$slug = $part['post_name'];
					$title = Sanitize\strip_br( $part['post_title'] );
					if ( count( $book_contents['part'] ) > 1 && $this->atLeastOneExport( $part['chapters'] ) && get_post_meta( $part['ID'], 'pb_part_invisible', true ) !== 'on' ) {
						printf( '<li class="part"><a href="#%s">%s</a></li>',
							$slug,
							Sanitize\decode( $title ) );
					} else {
						printf( '<li class="part display-none"><a href="#%s">%s</a></li>',
							$slug,
							Sanitize\decode( $title ) );
					}
					foreach ( $part['chapters'] as $j => $chapter ) {

						if ( ! $chapter['export'] )
							continue;

						$subclass = \PressBooks\Taxonomy\chapter_type( $chapter['ID'] );
						$slug = $chapter['post_name'];
						$title = Sanitize\strip_br( $chapter['post_title'] );
						$subtitle = trim( get_post_meta( $chapter['ID'], 'pb_subtitle', true ) );
						$author = trim( get_post_meta( $chapter['ID'], 'pb_section_author', true ) );

						printf( '<li class="chapter %s"><a href="#%s"><span class="toc-chapter-title">%s</span>', $subclass, $slug, Sanitize\decode( $title ) );

						if ( $subtitle )
							echo ' <span class="chapter-subtitle">' . Sanitize\decode( $subtitle ) . '</span>';

						if ( $author )
							echo ' <span class="chapter-author">' . Sanitize\decode( $author ) . '</span>';

						echo '</a></li>';
					}
				}
			} else {
				foreach ( $struct as $val ) {

					if ( ! $val['export'] )
						continue;

					$typetype = '';
					$subtitle = '';
					$author = '';
					$slug = $val['post_name'];
					$title = Sanitize\strip_br( $val['post_title'] );

					if ( 'front-matter' == $type ) {
						$subclass = \PressBooks\Taxonomy\front_matter_type( $val['ID'] );
						if ( 'dedication' == $subclass || 'epigraph' == $subclass || 'title-page' == $subclass || 'before-title' == $subclass ) {
							continue; // Skip
						} else {
							$typetype = $type . ' ' . $subclass;
							$subtitle = trim( get_post_meta( $val['ID'], 'pb_subtitle', true ) );
							$author = trim( get_post_meta( $val['ID'], 'pb_section_author', true ) );
						}
					} elseif ( 'back-matter' == $type ) {
						$typetype = $type . ' ' . \PressBooks\Taxonomy\back_matter_type( $val['ID'] );
						$subtitle = trim( get_post_meta( $val['ID'], 'pb_subtitle', true ) );
						$author = trim( get_post_meta( $val['ID'], 'pb_section_author', true ) );
					}

					printf( '<li class="%s"><a href="#%s"><span class="toc-chapter-title">%s</span>', $typetype, $slug, Sanitize\decode( $title ) );

					if ( $subtitle )
						echo ' <span class="chapter-subtitle">' . Sanitize\decode( $subtitle ) . '</span>';

					if ( $author )
						echo ' <span class="chapter-author">' . Sanitize\decode( $author ) . '</span>';

					echo '</a></li>';
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

		$front_matter_printf = '<div class="front-matter %s" id="%s">';
		$front_matter_printf .= '<div class="front-matter-title-wrap"><h3 class="front-matter-number">%s</h3><h1 class="front-matter-title">%s</h1></div>';
		$front_matter_printf .= '<div class="ugc front-matter-ugc">%s</div>%s';
		$front_matter_printf .= '</div>';

		$i = $this->frontMatterPos;
		foreach ( $book_contents['front-matter'] as $front_matter ) {

			if ( ! $front_matter['export'] )
				continue; // Skip

			$id = $front_matter['ID'];
			$subclass = \PressBooks\Taxonomy\front_matter_type( $id );

			if ( 'dedication' == $subclass || 'epigraph' == $subclass || 'title-page' == $subclass || 'before-title' == $subclass )
				continue; // Skip

			if ( 'introduction' == $subclass )
				$this->hasIntroduction = true;

			$slug = $front_matter['post_name'];
			$title = ( get_post_meta( $id, 'pb_show_title', true ) ? $front_matter['post_title'] : '<span class="display-none">' . $front_matter['post_title'] . '</span>' ); // Preserve auto-indexing in Prince using hidden span
			$content = $front_matter['post_content'];

			$short_title = trim( get_post_meta( $id, 'pb_short_title', true ) );
			$subtitle = trim( get_post_meta( $id, 'pb_subtitle', true ) );
			$author = trim( get_post_meta( $id, 'pb_section_author', true ) );

			if ( $author ) {
				$content = '<h2 class="chapter-author">' . Sanitize\decode( $author ) . '</h2>' . $content;
			}

			if ( $subtitle ) {
				$content = '<h2 class="chapter-subtitle">' . Sanitize\decode( $subtitle ) . '</h2>' . $content;
			}

			if ( $short_title ) {
				$content = '<h6 class="short-title">' . Sanitize\decode( $short_title ) . '</h6>' . $content;
			}

			printf( $front_matter_printf,
				$subclass,
				$slug,
				$i,
				Sanitize\decode( $title ),
				$content,
				$this->doEndnotes( $id ) );

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

		$part_printf = '<div class="part %s" id="%s">';
		$part_printf .= '<div class="part-title-wrap"><h3 class="part-number">%s</h3><h1 class="part-title">%s</h1></div>';
		$part_printf .= '</div>';

		$chapter_printf = '<div class="chapter %s" id="%s">';
		$chapter_printf .= '<div class="chapter-title-wrap"><h3 class="chapter-number">%s</h3><h2 class="chapter-title">%s</h2></div>';
		$chapter_printf .= '<div class="ugc chapter-ugc">%s</div>%s';
		$chapter_printf .= '</div>';

		$i = $j = 1;
		foreach ( $book_contents['part'] as $part ) {

			$invisibility = ( get_post_meta( $part['ID'], 'pb_part_invisible', true ) == 'on' ) ? 'invisible' : '';

			$part_printf_changed = '';
			$slug = $part['post_name'];
			$title = $part['post_title'];

			// Inject introduction class?
			if ( ! $this->hasIntroduction && count( $book_contents['part'] ) > 1 ) {
				$part_printf_changed = str_replace( '<div class="part %s" id=', '<div class="part introduction %s" id=', $part_printf );
				$this->hasIntroduction = true;
			}

			// Inject part content?
			$part_content = trim( get_post_meta( $part['ID'], 'pb_part_content', true ) );
			if ( $part_content ) {
				$part_content = $this->preProcessPostContent( $part_content );
				$part_printf_changed = str_replace( '</h1></div></div>', "</h1></div><div class=\"ugc part-ugc\">{$part_content}</div></div>", $part_printf );
			}

			$m = ( $invisibility == 'invisible' ) ? '' : $i;
			$my_part = sprintf(
				( $part_printf_changed ? $part_printf_changed : $part_printf ),
				$invisibility,
				$slug,
				$m,
				Sanitize\decode( $title ) ) . "\n";

			$my_chapters = '';

			foreach ( $part['chapters'] as $chapter ) {

				if ( ! $chapter['export'] )
					continue; // Skip

				$chapter_printf_changed = '';
				$id = $chapter['ID'];
				$subclass = \PressBooks\Taxonomy\chapter_type( $id );
				$slug = $chapter['post_name'];
				$title = ( get_post_meta( $id, 'pb_show_title', true ) ? $chapter['post_title'] : '<span class="display-none">' . $chapter['post_title'] . '</span>' ); // Preserve auto-indexing in Prince using hidden span
				$content = $chapter['post_content'];

				$short_title = trim( get_post_meta( $id, 'pb_short_title', true ) );
				$subtitle = trim( get_post_meta( $id, 'pb_subtitle', true ) );
				$author = trim( get_post_meta( $id, 'pb_section_author', true ) );

				if ( $author ) {
					$content = '<h2 class="chapter-author">' . Sanitize\decode( $author ) . '</h2>' . $content;
				}

				if ( $subtitle ) {
					$content = '<h2 class="chapter-subtitle">' . Sanitize\decode( $subtitle ) . '</h2>' . $content;
				}

				if ( $short_title ) {
					$content = '<h6 class="short-title">' . Sanitize\decode( $short_title ) . '</h6>' . $content;
				}

				// Inject introduction class?
				if ( ! $this->hasIntroduction ) {
					$chapter_printf_changed = str_replace( '<div class="chapter %s" id=', '<div class="chapter introduction %s" id=', $chapter_printf );
					$this->hasIntroduction = true;
				}

				$n = ( $subclass == 'numberless' ) ? '' : $j;
				$my_chapters .= sprintf(
					( $chapter_printf_changed ? $chapter_printf_changed : $chapter_printf ),
					$subclass,
					$slug,
					$n,
					Sanitize\decode( $title ),
					$content,
					$this->doEndnotes( $id ) ) . "\n";

				if ( $subclass !== 'numberless' ) ++$j;
			}

			// Echo with parts?
			if ( $my_chapters ) {
				if ( count( $book_contents['part'] ) > 1 ) {
					echo $my_part . $my_chapters;
					if ( $invisibility !== 'invisible' ) ++$i;
				} else {
					echo $my_chapters;
				}
			}

			// Did we actually inject the introduction class?
			if ( $part_printf_changed && empty( $my_chapters ) ) {
				$this->hasIntroduction = false;
			}

		}

	}


	/**
	 * @param array $book_contents
	 * @param array $metadata
	 */
	protected function echoBackMatter( $book_contents, $metadata ) {

		$back_matter_printf = '<div class="back-matter %s" id="%s">';
		$back_matter_printf .= '<div class="back-matter-title-wrap"><h3 class="back-matter-number">%s</h3><h1 class="back-matter-title">%s</h1></div>';
		$back_matter_printf .= '<div class="ugc back-matter-ugc">%s</div>%s';
		$back_matter_printf .= '</div>';

		$i = 1;
		foreach ( $book_contents['back-matter'] as $back_matter ) {

			if ( ! $back_matter['export'] ) continue;

			$id = $back_matter['ID'];
			$subclass = \PressBooks\Taxonomy\back_matter_type( $id );
			$slug = $back_matter['post_name'];
			$title = ( get_post_meta( $id, 'pb_show_title', true ) ? $back_matter['post_title'] : '<span class="display-none">' . $back_matter['post_title'] . '</span>' ); // Preserve auto-indexing in Prince using hidden span
			$content = $back_matter['post_content'];

			$short_title = trim( get_post_meta( $id, 'pb_short_title', true ) );
			$subtitle = trim( get_post_meta( $id, 'pb_subtitle', true ) );
			$author = trim( get_post_meta( $id, 'pb_section_author', true ) );

			if ( $author ) {
				$content = '<h2 class="chapter-author">' . Sanitize\decode( $author ) . '</h2>' . $content;
			}

			if ( $subtitle ) {
				$content = '<h2 class="chapter-subtitle">' . Sanitize\decode( $subtitle ) . '</h2>' . $content;
			}

			if ( $short_title ) {
				$content = '<h6 class="short-title">' . Sanitize\decode( $short_title ) . '</h6>' . $content;
			}

			printf( $back_matter_printf,
				$subclass,
				$slug,
				$i,
				Sanitize\decode( $title ),
				$content,
				$this->doEndnotes( $id ) );

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
				if ( $found ) return true;
				else continue;
			} elseif ( 'export' == (string) $key && $val ) {
				return true;
			}
		}

		return false;
	}

}
