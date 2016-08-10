<?php

/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version))
 */

namespace Pressbooks\Modules\Export\Mpdf;

/**
 * Available filters
 *
 * Overrides the Table of Contents entry;
 *
 *     function my_mpdf_get_toc_entry( $value ) {
 *       return sprintf(__('Chapter: %s'), $value['post_title']);
 *     }
 *     add_filter( 'mpdf_get_toc_entry', 'my_mpdf_get_toc_entry', 10, 1 );
 *
 * Overrides the footer ;
 *
 *     function my_mpdf_footer( $content ) {
 *       return 'left content | center content | {PAGENO}';
 *     }
 *     add_filter( 'mpdf_get_footer', 'my_mpdf_footer', 10, 1 );
 *
 * Overrides the header;
 *
 *     function my_mpdf_header( $content ) {
 *       return 'left content | center content | {PAGENO}';
 *     }
 *     add_filter( 'mpdf_get_header', 'my_mpdf_header', 10, 1 );
 *
 */

use \Pressbooks\Modules\Export\Export;

class Pdf extends Export {

	/**
	 * Fullpath to book CSS file.
	 *
	 * @var string
	 */
	protected $exportStylePath;

	/**
	 * mPDF theme options, set by the user
	 *
	 * @var array
	 */
	protected $options;

	/**
	 * Global theme options, set by the user
	 *
	 * @var array
	 */
	protected $globalOptions;

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
	protected $memoryNeeded = 128;

	/**
	 * Holds the title of the book being published
	 * @var string
	 */
	protected $bookTitle;

	/**
	 * Book Metadata
	 *
	 * @var array
	 */
	protected $bookMeta;

	/**
	 * Number the chapters
	 *
	 * @var boolean
	 */
	protected $numbered = false;

	/**
	 * Parses the html as styles and stylesheets only
	 * @see http://mpdf1.com/manual/index.php?tid=121
	 *
	 */
	const MODE_CSS = 1;

	/**
	 *
	 */
	function __construct() {
		// don't know who would actually wait for 10 minutes, but it's here
		set_time_limit( 600 );

		$memory_available = ( int ) ini_get( 'memory_limit' );

		// lives and dies with the instantiation of the object
		if ( $memory_available < $this->memoryNeeded ) {
			ini_set( 'memory_limit', $this->memoryNeeded . 'M' );
		}

		$this->options = get_option( 'pressbooks_theme_options_mpdf' );
		$this->globalOptions = get_option( 'pressbooks_theme_options_global' );
		$this->bookTitle = get_bloginfo( 'name' );
		$this->exportStylePath = $this->getExportStylePath( 'mpdf' );
		$this->bookMeta = \Pressbooks\Book::getBookInformation();
		$this->numbered = ( 1 == $this->globalOptions['chapter_numbers'] ) ? true : false;
	}

	/**
	 * Book Assembly. Create $this->outputPath
	 *
	 * @return bool
	 */
	function convert() {

		$filename = $this->timestampedFileName( '._oss.pdf' );
		$this->outputPath = $filename;
		$contents = $this->getOrderedBookContents();

		// set up mPDF
		if ( ! $this->isInstalled() ) {
			return false; // mPDF is not installed
		}
		require_once( PB_MPDF_DIR . 'symbionts/mpdf/mpdf.php' );
		$this->mpdf = new \mPDF( '' );
		$this->mpdf->SetAnchor2Bookmark( 1 );
		$this->mpdf->ignore_invalid_utf8 = true;
		if ( 1 == $this->options['mpdf_mirror_margins'] ) {
			$this->mpdf->mirrorMargins = true;
		}
		$this->mpdf->setBasePath( home_url( '/' ) );

		$this->setCss();

		// all precontent page_options are suppressed and omitted from the TOC
		$this->addPreContent( $contents );

		// all front matter page numbers are romanized
		$this->addFrontMatter( $contents );
		// all parts, chapters, back-matter
		$this->addPartsandChapters( $contents );

		$this->mpdf->Output( $this->outputPath, 'F' );

		// TODO trap errors
		return true;
	}

	/**
	 * Add the mpdf Table of Contents.
	 * Note, the functionality of the TOC is limited: its behavior varies
	 * according mirrored margin settings, and will always generate blank pages
	 * after.
	 * http://mpdf1.com/forum/discussion/comment/6417#Comment_6417
	 *
	 */
	function addToc() {

		$options = array(
		    'paging' => true,
		    'links' => true,
		    'toc-bookmarkText' => 'toc',
		    'toc-preHTML' => '<h1 class="toc">Contents</h1>',
		    'toc-margin-left' => 15,
		    'toc-margin-right' => 15,
		);
		$this->mpdf->TOCpagebreakByArray( $options );
	}

	/**
	 * Add all specially handled content.
	 *
	 * @param array $contents - the book
	 */
	function addPreContent( $contents ) {
		// Before Title Page (user generated)
		$this->addFrontMatterByType( 'before-title', $contents );
		// Cover
		if ( 1 == $this->options['mpdf_include_cover'] ) {
			$this->addCover();
		}
		// Title (user generated)
		$this->addFrontMatterByType( 'title-page', $contents );
		// Title page
		$this->addBookInfo();
		// Copyright
		$this->addCopyright();
		// Dedication and Epigraph (In that order!)
		$this->addFrontMatterByType( 'dedication', $contents );
		$this->addFrontMatterByType( 'epigraph', $contents );
		// Table of Contents
		if ( 1 == $this->options['mpdf_include_toc'] ) {
			$this->addToc();
		}
	}

	/**
	 * Add the cover for the book.
	 */
	function addCover() {
		$page_options = array(
		    'suppress' => 'on',
		    'margin-left' => 15,
		    'margin-right' => 15,
		);
		$content = '<div id="half-title-page">';
		$content .=  '<h1 class="title">' . $this->bookTitle . '</h1>';
		$content .=  '</div>' . "\n";

		if ( ! empty( $this->bookMeta['pb_cover_image'] ) ) {
			$content .= '<div style="text-align:center;"><img src="' . $this->bookMeta['pb_cover_image'] . '" alt="book-cover" title="' . bloginfo( 'name' ) . ' book cover" /></div>';
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
	 * Add book information page, otherwise known as title page
	 *
	 */
	function addBookInfo() {
		$page_options = array(
		    'suppress' => 'on',
		    'margin-left' => 15,
		    'margin-right' => 15,
		);

		$content = '<div id="title-page">';
		$content .= '<h1 class="title">' . $this->bookTitle . '</h1>';

		if ( ! empty( $this->bookMeta['pb_subtitle'] ) ) {
			$content .= '<h2 class="subtitle">' . $this->bookMeta['pb_subtitle'] . '</h2>';
		}

		if ( isset( $this->bookMeta['pb_author'] ) ) {
			$content .= '<h3 class="author">' . $this->bookMeta['pb_author'] . '</h3>';
		}

		if ( isset( $this->bookMeta['pb_contributing_authors'] ) ) {
			$content .= '<h4 class="contributing-authors">' . $this->bookMeta['pb_contributing_authors'] . '</h4>';
		}


		if ( isset( $this->bookMeta['pb_print_isbn'] ) ) {
			$content .= '<p class="isbn"><strong>' . __( 'ISBN', 'pressbooks' ) . '</strong>: ' . $this->bookMeta['pb_print_isbn'] . '</p>';
		}

		if ( isset( $this->bookMeta['pb_publisher'] ) ) {
			$content .= '<p class="publisher">' . $this->bookMeta['pb_publisher'] . '</p>';
		}

		if ( isset( $this->bookMeta['pb_publisher_city'] ) ) {
			$content .= '<p class="publisher-city">' . $this->bookMeta['pb_publisher_city'] . '</p>';
		}

		$content .= '</div>';

		$page = array(
		    'post_title' => '',
		    'post_content' => $content,
		    'post_type' => 'bookinfo',
		    'mpdf_level' => 1,
		    'mpdf_omit_toc' => true,
		);

		$this->addPage( $page, $page_options, false, false );
	}

	/**
	 * Copyright information on a separate page
	 *
	 */
	function addCopyright() {
		$options = $this->globalOptions;
		$page_options = array(
		    'suppress' => 'on',
		    'margin-left' => 15,
		    'margin-right' => 15,
		);

		$content = '<div id="copyright-page">';

		if ( isset( $this->bookMeta['pb_copyright_year'] ) || isset( $this->bookMeta['pb_copyright_holder'] ) ) {

			$content .= '<p><strong>' . __( 'Copyright', 'pressbooks' ) . '</strong>:';
			if ( ! empty( $this->bookMeta['pb_copyright_year'] ) ) {
				$content .= $this->bookMeta['pb_copyright_year'] . ' ';
			}

			if ( ! empty( $this->bookMeta['pb_copyright_holder'] ) ) {
				$content .= ' ' . __( 'by', 'pressbooks' ) . ' ' . $this->bookMeta['pb_copyright_holder'] . '. ';
			}
			$content .= '</p>';

			if ( ! empty( $this->bookMeta['pb_custom_copyright'] ) ) {
				$content .= '<p class="custom-copyright">' . $this->bookMeta['pb_custom_copyright'] . '</p>';
			}
		}

		if ( 1 == $options['copyright_license'] ) {
			$content .= '<p class="copyright-license">';
			$content .= $this->doCopyrightLicense( $this->bookMeta );
			$content .= '</p>';
		}

		$content .= '</div>';

		$page = array(
		    'post_title' => '',
		    'post_content' => $content,
		    'post_type' => 'bookinfo',
		    'mpdf_level' => 1,
		    'mpdf_omit_toc' => true,
		);

		$this->addPage( $page, $page_options, false, false );
	}

	/**
	 * Add front matter of a specific/special type
	 *
	 * @param string $type - special content placed ahead of everything else
	 * @param array $contents - book contents
	 */
	function addFrontMatterByType( $type, $contents ) {
		$page_options = array(
		    'suppress' => 'on',
		);

		foreach ( $contents as $index => $page ) {
			// If we hit non front-matter post types we won't see anymore front-matter
			if ( $page['post_type'] != 'front-matter' ) {
				return;
			}

			if ( $type == \Pressbooks\Taxonomy::getFrontMatterType( $page['ID'] ) ) {
				$page['mpdf_omit_toc'] = true;
				$this->addPage( $page, $page_options, false, false );
			}
		}
	}

	/**
	 * Adds front matter, resets the page numbering on the first loop,
	 * romanizes the numeric style
	 *
	 * @param array $contents
	 */
	function addFrontMatter( array $contents ) {

		$first_iteration = true;
		$page_options = array(
		    'pagenumstyle' => 'i',
		    'margin-left' => 15,
		    'margin-right' => 15,
		);

		foreach ( $contents as $front_matter ) {
			// safety
			$type = \Pressbooks\Taxonomy::getFrontMatterType( $front_matter['ID'] );
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

	function addPartsAndChapters( $contents ) {
		// change the numbering system to numeric
		// iterate through, parts, chapters, back-matter
		$first_iteration = true;
		$i = 1;
		$page_options = array();
		foreach ( $contents as $page ) {

			if ( 'front-matter' == $page['post_type'] ) continue; //skip all front-matter

			if ( true == $first_iteration ) {
				$page_options['pagenumstyle'] = 1;
			}
			$page['chapter_num'] = $i;
			$this->addPage( $page, $page_options );
			$first_iteration = false;
			if ( 'part' != $page['post_type'] ) {
				$i++;
			}
		}
	}

	/**
	 * Add a page to the pdf
	 *
	 * @param array $page - the content
	 * @param array $page_options - numbering reset, style, suppress adding to TOC
	 * @param boolean $display_footer turn on/off footer display
	 * @param boolean $display_header turn on/off header display
	 * @return boolean
	 */
	function addPage( $page, $page_options = array(), $display_footer = true, $display_header = true ) {
		// defaults
		$defaults = array(
		    'suppress' => 'off',
		    'resetpagenum' => 0,
		    'pagenumstyle' => 1,
		    'margin-right' => $this->options['mpdf_right_margin'],
		    'margin-left' => $this->options['mpdf_left_margin'],
		    'sheet-size' => $this->options['mpdf_page_size'],
		);

		$options = \wp_parse_args( $page_options, $defaults );
		$class = ( $this->numbered ) ? '<div class="' . $page['post_type'] . '">' : '<div class="' . $page['post_type'] . ' numberless">';
		$toc_entry = ( 'chapter' == $page['post_type']  && true === $this->numbered ) ? $page['chapter_num'] . ' ' . $page['post_title'] : $page['post_title'];

		if ( ! empty( $page['post_content'] ) || 'part' == $page['post_type'] ) {

			$this->mpdf->SetFooter( $this->getFooter( $display_footer, $this->bookTitle . '| | {PAGENO}' ) );
			$this->mpdf->SetHeader( $this->getHeader( $display_header, '' ) );

			$this->mpdf->AddPageByArray( $options );

			if ( empty( $page['mpdf_omit_toc'] ) ) {
				$this->mpdf->TOC_Entry( $this->getTocEntry( $toc_entry ), $page['mpdf_level'] );
				$this->mpdf->Bookmark( $this->getBookmarkEntry( $page ), $page['mpdf_level'] );
			}

			if ( 'chapter' == $page['post_type'] ) {
				$title = '<h3 class="chapter-number">' . $page['chapter_num'] . '</h3><h2 class="entry-title">' . $page['post_title'] . '</h2>';
			} else {
				$title = '<h2 class="entry-title">' . $page['post_title'] . '</h2>';
			}
			$content = $class
				. $title
				. $this->getFilteredContent( $page['post_content'] )
				. '</div>';

			// TODO Make this hookable.
			$this->mpdf->WriteHTML( $content );
			return true;
		}

		return false;
	}

	/**
	 * Return the Table of Contents entry for this page.
	 *
	 * @param string $page
	 * @return string
	 */
	function getTocEntry( $page ) {

		// allow override
		$entry = apply_filters( 'mpdf_get_toc_entry', $page );
		// sanitize
		$entry = \Pressbooks\Sanitize\filter_title( $entry );

		return $entry;
	}

	/**
	 * Return the PDF bookmark entry for this page
	 * should be unique, using static variable for cheap cache
	 *
	 * @staticvar int $id - to avoid collisions with identical page titles
	 * @param array $page
	 * @return string
	 */
	function getBookmarkEntry( $page ) {
		static $id = 1;
		$entry = $id . $page['post_title'];
		$id++;

		return $entry;
	}

	/**
	 * Cleans up html
	 *
	 * @param string $content
	 * @return string
	 */
	function getFilteredContent( $content ) {

		$filtered = apply_filters( 'the_content', $content );

		$filtered = $this->fixAnnoyingCharacters( $filtered );

		$config = array(
		    'valid_xhtml' => 1,
		    'no_deprecated_attr' => 2,
		    'unique_ids' => 'fixme-',
		    'hook' => '\Pressbooks\Sanitize\html5_to_xhtml11',
		    'tidy' => -1,
		);

		return \Htmlawed::filter( $filtered, $config );
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
	 * @param bool $display
	 * @param string $content
	 *   The post type being added to the page.
	 *
	 * @return string
	 */
	function getFooter( $display = true, $content = '' ) {
		// bail early
		if ( false == $display ) {
			return '';
		}

		// override
		$footer = apply_filters( 'mpdf_get_footer', $content );
		// sanitize
		$footer = \Pressbooks\Sanitize\filter_title( $footer );

		return $footer;
	}

	/**
	 * Return formatted headers.
	 *
	 * @param bool $display
	 * @param string $content
	 *  The post type being added to the page
	 *
	 * @return string
	 */
	function getHeader( $display = true, $content = '' ) {
		// bail early
		if ( false == $display ) {
			return '';
		}

		// override
		$header = apply_filters( 'mpdf_get_header', $content );
		//sanitize
		$header = \Pressbooks\Sanitize\filter_title( $header );

		return $header;
	}

	/**
	 * Restructures \Pressbooks\Book::getBookContents() into a format more useful
	 * for direct iteration, and tracks a nesting level for Bookmark and ToC
	 * entries.
	 *
	 * @return array
	 */
	function getOrderedBookContents() {

		$book_contents = \Pressbooks\Book::getBookContents();

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

								if ( \Pressbooks\Modules\Export\Export::isParsingSubsections() == true ) {
									$sections = \Pressbooks\Book::getSubsections( $chapter['ID'] );
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

						if ( \Pressbooks\Modules\Export\Export::isParsingSubsections() == true ) {
							$sections = \Pressbooks\Book::getSubsections( $item['ID'] );
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
		$css = '';

		// check for child theme export file
		$cssfile = $this->getExportStylePath( 'mpdf' );

		// if empty, try the parent theme export directory
		if ( empty( $cssfile ) ) {
			$cssfile = realpath( get_template_directory() . "/export/mpdf/style.css" );
		}

		if ( is_string( $cssfile ) && ! empty( $cssfile ) ) {
			$css .= file_get_contents( $cssfile ) . "\n";
		}

		// grab the web theme, ONLY as a backup
		if ( empty( $css ) ) {
			$theme = wp_get_theme();
			$css = $this->getThemeCss( $theme );
		}

		// Theme options override
		$css .= apply_filters( 'pb_mpdf_css_override', $css ) . "\n";

		if ( ! empty( $css ) ) {
			$this->mpdf->WriteHTML( $css, self::MODE_CSS );
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
	 * Does array of chapters have at least one export? Recursive.
	 *
	 * @param array $chapters
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


	/**
	 * Is mPDF installed?
	 *
	 * @return bool
	 */
	static function isInstalled() {

		if ( in_array(  WP_PLUGIN_DIR . '/pressbooks-mpdf/pressbooks-mpdf.php', wp_get_active_network_plugins() ) ) {
			return true;
		} else {
			return false;
		}

	}

}
