<?php

/**
 * @author  PressBooks <code@pressbooks.com>
 * @license GPLv2 (or any later version))
 */

namespace PressBooks\Export\Mpdf;

/**
 * Available filters
 *
 * Overrides the Table of Contents entry;
 *
 *     function my_mpdf_get_toc_entry( $value , $context, $page ) {
 *       $value = sprintf(__('Chapter: %s'), $page['page_title']);
 *     }
 *     add_filter( 'mpdf_get_toc_entry', 'my_mpdf_get_toc_entry', 10, 3 );
 *
 *
 * Overrides the PDF bookmark entry;
 *
 *     function my_mpdf_get_bookmark_entry( $value , $context, $page ) {
 *       $value = sprintf(__('Chapter: %s'), $page['page_title']);
 *     }
 *     add_filter( 'mpdf_get_bookmark_entry', 'my_mpdf_get_bookmark_entry', 10, 3 );
 *
 *
 * Overrides the footer ;
 *
 *     function my_mpdf_footer( $content ) {
 *       return '{PAGENO}';
 *     }
 *     add_filter( 'mpdf_get_footer', 'my_mpdf_footer', 10, 3 );
 *
 *
 *
 * Overrides the header;
 *
 *     function my_mpdf_header( $content ) {
 *       return '{PAGENO}';
 *     }
 *     add_filter( 'mpdf_get_header', 'my_mpdf_header', 10, 3 );
 *
 * Overrides the CSS on every page
 *
 *     function my_mpdf_css_override( $css ) {
 *       return $css . "\n text-align:center;";
 *     }
 *     add_filter( 'mpdf_css_override', 'my_mpdf_css_override, 10, 1 );
 */
require_once( PB_PLUGIN_DIR . 'symbionts/htmLawed/htmLawed.php' );

use \PressBooks\Export\Export;

class Pdf extends Export {

	/**
	 * Fullpath to book CSS file.
	 *
	 * @var string
	 */
	protected $exportStylePath;

	/**
	 * CSS overrides
	 *
	 * @var string
	 */
	protected $cssOverrides;

	/**
	 * mPDF options
	 *
	 * @var array
	 */
	protected $options;

	/**
	 * MPDF Class
	 *
	 * @var object
	 */
	protected $mpdf;

	/**
	 * mPDF uses a lot of memory, this the recommended minimum
	 * 
	 * @see http://mpdf1.com/manual/index.php?tid=408
	 * @var int 
	 */
	protected $memory_needed = 128;

	/**
	 * Tracks when the ToC has been output
	 */
	protected $ToCStatus;
	protected $book_title;

	/**
	 * Define a few constants that should have been defined in MPDF.
	 *
	 * @param array $args
	 */
	function __construct( array $args ) {
		set_time_limit( 600 );
		if ( ! defined( 'MPDF_WRITEHTML_MODE_DOC' ) ) {
			// Define some constants for mPDF::WriteHTML()
			// @see http://mpdf1.com/manual/index.php?tid=121
			define( 'MPDF_WRITEHTML_MODE_DOC', 0 );
			define( 'MPDF_WRITEHTML_MODE_CSS', 1 );
			define( ' MPDF_WRITEHTML_MODE_ELEMENTS', 2 );
		}

		$memory_available = ( int ) ini_get( 'memory_limit' );

		// lives and dies with the instantiation of the object
		if ( $memory_available < $this->memory_needed ) {
			ini_set( 'memory_limit', $this->memory_needed . 'M' );
		}

		$this->options = get_option( 'pressbooks_theme_options_mpdf' );
		$this->book_title = \get_bloginfo( 'name' );
		$this->exportStylePath = $this->getExportStylePath( 'mpdf' );

		$this->themeOptionsOverrides();
	}

	/**
	 * Create $this->outputPath
	 *
	 * @return bool
	 */
	function convert() {
		$filename = $this->timestampedFileName( '._oss.pdf' );
		$this->outputPath = $filename;

		require_once( PB_PLUGIN_DIR . 'symbionts/mpdf/mpdf.php' );

		$contents = $this->getOrderedBookContents();

		$this->mpdf = new \mPDF( '' );
		$this->mpdf->SetAnchor2Bookmark( 1 );

		if ( ! empty( $this->options['mpdf_ignore_invalid_utf8'] ) ) {
			$this->mpdf->ignore_invalid_utf8 = true;
		}

		if ( ! empty( $this->options['mpdf_mirror_margins'] ) ) {
			$this->mpdf->mirrorMargins = true;
		}

		$this->mpdf->setBasePath( home_url( '/' ) );

		$this->setCss();

		// all precontent page_options are suppressed and omitted from the TOC 
		$this->addPreContent( $contents );

		// all front matter page numbers are romanized
		$this->addFrontMatter( $contents );

		// change the numbering system to numeric
		// iterate through, parts, chapters, back-matter
		$first_iteration = true;
		foreach ( $contents as $page ) {

			if ( 'front-matter' == $page['post_type'] ) continue; //skip all front-matter

			if ( true == $first_iteration ) {
				$page_options['pagenumstyle'] = 1;
			}

			$this->addPage( $page, $page_options );
			$first_iteration = false;
		}

		$this->mpdf->Output( $this->outputPath, 'F' );

		// TODO trap errors
		return true;
	}

	/**
	 * Add the mpdf Table of Contents.
	 */
	function addToc() {

		$options = array(
		    'paging' => true,
		    'links' => true,
		    'toc-bookmarkText' => 'toc',
//		    'resetpagenum' => 1,
		    'toc-preHTML' => '<h1>Contents</h1>'
		);
		$this->mpdf->TOCpagebreakByArray( $options );
	}

	/**
	 * Merge default page settings.
	 */
	function mergePageOptions( $options ) {
		if ( ! empty( $this->options['mpdf_page_size'] ) ) {
			$options['sheet-size'] = $this->options['mpdf_page_size'];
		}

		if ( isset( $this->options['mpdf_left_margin'] ) && is_numeric( $this->options['mpdf_left_margin'] ) ) {
			$options['margin-left'] = $this->options['mpdf_left_margin'];
		}

		if ( isset( $this->options['mpdf_right_margin'] ) && is_numeric( $this->options['mpdf_right_margin'] ) ) {
			$options['margin-right'] = $this->options['mpdf_right_margin'];
		}

		return $options;
	}

	/**
	 * Add all specially handled content.
	 */
	function addPreContent( &$contents ) {


		$this->addFrontMatterByType( 'before-title', $contents );

		if ( ! empty( $this->options['mpdf_include_cover'] ) ) {
			$this->addCover();
		}

		$this->addFrontMatterByType( 'title-page', $contents );

		$this->addBookInfo();

		$this->addFrontMatterByType( 'dedication', $contents );

		$this->addFrontMatterByType( 'epigraph', $contents );

		$this->addToc();
	}

	/**
	 * Add the cover for the book.
	 */
	function addCover() {
		$metadata = \PressBooks\Book::getBookInformation();
		$page_options['suppress'] = 'on';

		if ( ! empty( $metadata['pb_cover_image'] ) ) {
			$content .= '<div style="text-align:center;"><img src="' . $metadata['pb_cover_image'] . '" alt="book-cover" title="' . bloginfo( 'name' ) . ' book cover" /></div>';
		}
		$page = array(
		    'post_type' => 'cover',
		    'post_content' => $content,
		    'post_title' => '',
		    'mpdf_level' => 1,
		    'mpdf_omit_toc' => true,
		);

		$this->addPage( $page, $page_options, false, false );
	}

	/**
	 * Add book information page.
	 */
	function addBookInfo() {
		$meta = \PressBooks\Book::getBookInformation();
		$options = get_option( 'pressbooks_theme_options_global' );
		$page_options['suppress'] = 'on';

		$content = '<h1 class="title">' . $this->book_title . '</h1>';

		if ( ! empty( $meta['pb_subtitle'] ) ) {
			$content .= '<h2 class="subtitle">' . $meta['pb_subtitle'] . '</h2>';
		}

		if ( isset( $meta['pb_author'] ) ) {
			$content .= '<h3 class="book-author">' . $meta['pb_author'] . '</h3>';
		}

		if ( isset( $meta['pb_contributing_authors'] ) ) {
			$content .= '<h4 class="contributing-author">' . $meta['pb_contributing_authors'] . '</h4>';
		}

		$content .= '<div>';

		if ( isset( $meta['pb_print_isbn'] ) ) {
			$content .= '<p class="isbn"><strong>' . __( 'ISBN', 'pressbooks' ) . '</strong>: ' . $meta['pb_print_isbn'] . '</p>';
		}

		if ( isset( $meta['pb_publisher'] ) ) {
			$content .= '<p class="publisher"><strong>' . __( 'Publisher', 'pressbooks' ) . '</strong>: ' . $meta['pb_publisher'] . '</p>';
		}

		if ( isset( $meta['pb_publisher_city'] ) ) {
			$content .= '<p class="publisher_city"><strong>' . __( 'Publisher City', 'pressbooks' ) . '</strong>: ' . $meta['pb_publisher_city'] . '</p>';
		}

		if ( isset( $meta['pb_copyright_year'] ) || isset( $meta['pb_copyright_holder'] ) ) {
			$content .= '<div class="copyright_notice">';

			$content .= '<p class="copyright_notice"><strong>' . __( 'Copyright', 'pressbooks' ) . '</strong>:';
			if ( ! empty( $meta['pb_copyright_year'] ) ) {
				$content .= $meta['pb_copyright_year'] . ' ';
			}

			if ( ! empty( $meta['pb_copyright_holder'] ) ) {
				$content .= ' ' . __( 'by', 'pressbooks' ) . ' ' . $meta['pb_copyright_holder'] . '. ';
			}
			$content .= '</p>';

			if ( ! empty( $meta['pb_custom_copyright'] ) ) {
				$content .= '<p class="custom_copyright">' . $meta['pb_custom_copyright'] . '</p>';
			}

			$content .= '</div>';
		}

		$content .= '</div>';

		if ( 1 == $options['copyright_license'] ) {
			$content .= '<p class="copyright_license">';
			$content .= $this->doCopyrightLicense( $meta );
			$content .= '</p>';
		}

		$page = array(
		    'post_title' => '',
		    'post_content' => $content,
		    'post_type' => 'bookinfo',
		    'mpdf_level' => 1,
		    'mpdf_omit_toc' => true,
		);

		return $this->addPage( $page, $page_options, false, false );
	}

	/**
	 * Add front matter of a specific type.
	 */
	function addFrontMatterByType( $type, $contents ) {
		$page_options['suppress'] = 'on';

		foreach ( $contents as $index => $page ) {
			// If we hit non front-matter post types we won't see anymore front-matter
			if ( $page['post_type'] != 'front-matter' ) {
				return;
			}

			if ( $type == \PressBooks\Taxonomy\front_matter_type( $page['ID'] ) ) {
				$page['mpdf_omit_toc'] = true;
				$this->addPage( $page, $page_options, false, false );
			}
		}
	}

	/**
	 * Adds front matter, resets the page numbering on the first loop and 
	 * and romanizes the numeric style
	 * 
	 * @param array $contents
	 */
	function addFrontMatter( array $contents ) {
		$first_iteration = true;
		$page_options['pagenumstyle'] = 'i';

		foreach ( $contents as $index => $front_matter ) {
			// safety
			$type = \PressBooks\Taxonomy\front_matter_type( $front_matter['ID'] );
			if ( 'dedication' == $type || 'epigraph' == $type || 'title-page' == $type || 'before-title' == $type )
					continue; // Skip

				
// only reset the page number on first iteration
			( true == $first_iteration ) ? $page_options['resetpagenum'] = 1 : $page_options['resetpagenum'] = 0;

			// assumes the array of book contents is in order 
			if ( 'front-matter' != $front_matter['post_type'] ) {
				return;
			}
			if ( ! empty( $front_matter['post_content'] ) ) {
				$this->addPage( $front_matter, $page_options, true, true );
				$first_iteration = false;
			}
		}
	}

	/**
	 * Add a page to the pdf.
	 */
	function addPage( $page, $page_options = array(), $display_footer = true, $display_header = true ) {
		// defaults
		$defaults = array(
		    'suppress' => 'off',
		    'resetpagenum' => 0,
		    'pagenumstyle' => 1,
		);

		$options = \wp_parse_args( $page_options, $defaults );

		$class = $page['post_type'] . ' type-' . $page['post_type'];

		if ( ! empty( $page['post_content'] ) || 'part' == $page['post_type'] ) {

			$this->mpdf->SetFooter( $this->getFooter( $display_footer, '' ) );
			$this->mpdf->SetHeader( $this->getHeader( $display_header, $this->book_title . '| | {PAGENO}' ) );

			$this->mpdf->AddPageByArray( $this->mergePageOptions( $options ) );

			if ( empty( $page['mpdf_omit_toc'] ) ) {
				$this->mpdf->TOC_Entry( $this->getTocEntry( $page ), $page['mpdf_level'] );
				$this->mpdf->Bookmark( $this->getBookmarkEntry( $page ), $page['mpdf_level'] );
			}

			$content = '<h2 class="entry-title">' . $page['post_title'] . '</h2>';
			$content .= '<div class="' . $class . '">' . $this->getFilteredContent( $page['post_content'] ) . '</div>';

			// TODO Make this hookable.
			$this->mpdf->WriteHTML( $content );
			return true;
		}

		return false;
	}

	/**
	 * Return the Table of Contents entry for this page.
	 */
	function getTocEntry( $page ) {
		$entry = $page['post_title'];

		$entry = apply_filters( 'mpdf_get_toc_entry', $entry, $page );
		return $entry;
	}

	/**
	 * Return the PDF bookmark entry for this page
	 */
	function getBookmarkEntry( $page ) {
		$entry = $page['post_title'];

		$entry = apply_filters( 'mpdf_get_bookmark_entry', $entry, $page );
		return $entry;
	}

	function getFilteredContent( $content ) {
		$filtered = apply_filters( 'the_content', $content );

		$filtered = $this->fixAnnoyingCharacters( $filtered );

		$config = array(
		    'valid_xhtml' => 1,
		    'no_deprecated_attr' => 2,
		    'unique_ids' => 'fixme-',
		    'hook' => '\PressBooks\Sanitize\html5_to_xhtml11',
		    'tidy' => -1,
		);

		return htmLawed( $filtered, $config );
	}

	/**
	 * This function prevents mPDF from completely aborting the export routine, or replacing each non-breaking space with a '?'
	 * if ignore_invalid_utf8 is true. Important to leave this in.
	 *  
	 * @param string $html
	 * @return string
	 */
	function fixAnnoyingCharacters( $html ) {

		// Replace Non-breaking spaces with normal spaces
		$html = preg_replace( '/\xC2\xA0/', ' ', $html );

		return $html;
	}

	/**
	 * Return formatted footers.
	 *
	 * @param string $context
	 *   The post type being added to the page.
	 */
	function getFooter( $display = true, $content = '' ) {
		// bail early
		if ( false == $display ) {
			return '';
		}

		// default is to print page number
		if ( empty( $content ) ) {
			$footer = '';
		} else {
			// @TODO - sanitize
			$footer = $content;
		}

		// override
		//$footer = apply_filters( 'mpdf_get_footer', $footer, true, $content );
		// @TODO - sanitize user input

		return $footer;
	}

	/**
	 * Return formatted headers.
	 *
	 * @param string $context
	 *  The post type being added to the page
	 *
	 * @param array $page
	 *  The "page" content
	 */
	function getHeader( $display = true, $content = '' ) {
		// bail early
		if ( false == $display ) {
			return '';
		}

		// default is to print page number
		if ( empty( $content ) ) {
			$header = '{PAGENO}';
		} else {
			// @TODO - sanitize
			$header = $content;
		}

		// override
		//$header = apply_filters( 'mpdf_get_header', $content );
		// @TODO - sanitize user input
		return $header;
	}

	/**
	 * Restructures \PressBooks\Book::getBookContents() into a format more useful
	 * for direct iteration, and tracks a nesting level for Bookmark and ToC
	 * entries.
	 */
	function getOrderedBookContents() {
		$book_contents = \PressBooks\Book::getBookContents();

		$ordered = array();

		foreach ( $book_contents as $type => $struct ) {
			if ( strpos( $type, '__' ) === 0 ) {
				continue; // Skip __magic keys
			}

			switch ( $type ) {
				case 'part':
					foreach ( $struct as $part ) {
						$part_content = trim( get_post_meta( $part['ID'], 'pb_part_content', true ) );
						if ( $part_content || $this->atLeastOneExport( $part['chapters'] ) ) {
							if ( ! empty( $part['post_content'] ) ) {
								$part['mpdf_level'] = 1;
								$part['post_content'] .= $part_content;
							} else {
								$part['post_content'] = $part_content;
								$part['mpdf_level'] = 0;
							}
							$ordered[] = $part;

							foreach ( $part['chapters'] as $chapter ) {
								if ( ! $chapter['export'] ) {
									continue;
								}

								$chapter['mpdf_level'] = $part['mpdf_level'] + 1;
								$ordered[] = $chapter;

								if ( \PressBooks\Export\Export::shouldParseSections() == true ) {
									$sections = \PressBooks\Book::getSubsections( $chapter['ID'] );
									if ( $sections ) {
										foreach ( $sections as $section ) {
											$section['mpdf_level'] = $part['mpdf_level'] + 2;
											$ordered[] = $section;
										}
									}
								}
							}
						}
					}
					break;
				default:
					foreach ( $struct as $item ) {
						if ( ! $item['export'] ) {
							continue;
						}

						$item['mpdf_level'] = 1;
						$ordered[] = $item;

						if ( \PressBooks\Export\Export::shouldParseSections() == true ) {
							$sections = \PressBooks\Book::getSubsections( $item['ID'] );
							if ( $sections ) {
								foreach ( $sections as $section ) {
									$section['mpdf_level'] = 2;
									$ordered[] = $section;
								}
							}
						}
					}
					break;
			}
		}

		return $ordered;
	}

	/**
	 * Get current child and parent theme css files. Child themes only have one parent 
	 * theme, and 99% of the time this is 'Luther' or /pressbooks-book/ whose stylesheet is 
	 * named 'style.css'
	 * 
	 * @param object $theme
	 * @return string $css
	 */
	function getThemeCss( $theme ) {
		$css = '';

		// get parent theme files
		if ( is_object( $theme->parent() ) ) {
			$parent_files = $theme->parent()->get_files( 'css' );

			// exclude admin files
			$parents = $this->stripUnwantedStyles( $parent_files );

			// hopefully there is something left for us to grab
			if ( ! empty( $parents ) ) {
				foreach ( $parents as $parent ) {
					$css .= file_get_contents( $parent ) . "\n";
				}
			}
		}
		// get child theme files
		$child_files = $theme->get_files( 'css' );
		// exclude admin files
		$children = $this->stripUnwantedStyles( $child_files );

		if ( ! empty( $children ) ) {
			foreach ( $children as $child ) {
				$css .= file_get_contents( $child ) . "\n";
			}
		}

		return $css;
	}

	/**
	 * Helper function to omit unwanted stylesheets in the output
	 * 
	 * @param array $styles
	 * @return array $sytles
	 */
	private function stripUnwantedStyles( array $styles ) {
		$unwanted = array(
		    'editor-style.css',
		);

		foreach ( $unwanted as $key ) {
			if ( array_key_exists( $key, $styles ) ) {
				unset( $styles[$key] );
			}
		}
		return $styles;
	}

	/**
	 * Add all css files
	 */
	function setCss() {

		$theme = wp_get_theme();

		$css = $this->getThemeCss( $theme );

		// check for child theme export file
		$cssfile = $this->getExportStylePath( 'mpdf' );

		// if empty, try the parent theme export directory
		if ( empty( $cssfile ) ) {
			$cssfile = realpath( get_template_directory() . "/export/mpdf/style.css" );
		}

		if ( is_string( $cssfile ) && ! empty( $cssfile ) ) {
			$css .= file_get_contents( $cssfile ) . "\n";
		}

		if ( ! empty( $this->cssOverrides ) ) {
			$css .= $this->cssOverrides . "\n";
		}

		if ( ! empty( $this->options['mpdf_indent_paragraphs'] ) ) {
			$css .= "p {text-indent: 2.0 em; }\n";
		}

		if ( ! empty( $css ) ) {
			$this->mpdf->WriteHTML( $css, MPDF_WRITEHTML_MODE_CSS );
		}
	}

	/**
	 * Check the sanity of $this->outputPath
	 *
	 * @return bool
	 */
	function validate() {

		if ( ! $this->isPdf( $this->outputPath ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Verify if file has 'application/pdf' mimeType.
	 *
	 * @param string $file
	 *
	 * @return bool
	 */
	protected function isPdf( $file ) {

		$mime = static::mimeType( $file );

		return ( strpos( $mime, 'application/pdf' ) !== false );
	}

	/**
	 * Override based on Theme Options
	 */
	protected function themeOptionsOverrides() {
		$css = '';
		$this->cssOverrides = apply_filters( 'pb_mpdf_css_override', $css );
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
				if ( $this->atLeastOneExport( $val ) ) {
					return true;
				}
			} elseif ( 'export' == ( string ) $key && $val ) {
				return true;
			}
		}

		return false;
	}

}
