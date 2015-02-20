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
 * Overrides the footer for the given context and page;
 *
 *     function my_mpdf_footer( $value, $context, $page ) {
 *       return '{PAGENO}';
 *     }
 *     add_filter( 'mpdf_get_footer', 'my_mpdf_footer', 10, 3 );
 *
 *
 *
 * Overrides the header for the given context and page;
 *
 *     function my_mpdf_header( $value, $context, $page ) {
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
	 * Tracks when the ToC has been output
	 */
	protected $ToCStatus;

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
			define( 'MPDF_WRITEHTML_MODE_DOC', 0);
			define( 'MPDF_WRITEHTML_MODE_CSS', 1);
			define(' MPDF_WRITEHTML_MODE_ELEMENTS', 2);
		}

		// Define a few constants to help track status of ToC
		define( 'MPDF_TOC_NOT_OUTPUT', 0);
		define( 'MPDF_TOC_OUTPUT_PAGENO_NOT_RESET', 1);
		define( 'MPDF_TOC_OUTPUT_PAGENO_RESET', 2);

		$this->options = get_option( 'pressbooks_theme_options_mpdf' );
		$this->ToCStatus = MPDF_TOC_NOT_OUTPUT;
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

		$this->mpdf = new \mPDF('');

		if ( ! empty ( $this->options['mpdf_ignore_invalid_utf8'] ) ) {
			$this->mpdf->ignore_invalid_utf8 = true;
		}

		if ( ! empty ( $this->options['mpdf_mirror_margins'] ) ) {
			$this->mpdf->mirrorMargins = true;
		}

		$this->mpdf->setBasePath( home_url( '/' ) );
		$this->mpdf->setFooter( $this->getFooter( 'default' ) );
		$this->mpdf->setHeader( $this->getHeader( 'default' ) );
		$this->setCss();

		$this->addPreContent( $contents );

		foreach ( $contents as $page ) {
			$this->addPage( $page );
		}
		$this->mpdf->Output( $this->outputPath, 'F' );

		// TODO trap errors
		return true;
	}

	/**
	 * Add the mpdf Table of Contents.
	 */
	function addToc() {
		$this->mpdf->AddPageByArray( $this->mergePageOptions( array( 'suppress' => 'on' ) ) );

		$options = array(
			'paging' => true,
			'links' => true,
			'toc-bookmarkText' => __( 'Table of Contents', 'pressbooks' ),
		);
		$this->mpdf->TOCpagebreakByArray( $options );
		$this->ToCOutput = MPDF_TOC_OUTPUT_PAGENO_NOT_RESET;
	}

	/**
	 * Merge default page settings.
	 */
	function mergePageOptions( $options ) {
		if ( ! empty( $this->options['mpdf_page_size'])) {
			$options['sheet-size'] = $this->options['mpdf_page_size'];
		}

		if ( isset( $this->options['mpdf_left_margin'] ) && is_numeric( $this->options['mpdf_left_margin'] ) ){
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
		$pageoptions = array( 'resetpagenum' => 1);

		if ( ! empty ( $this->options['mpdf_include_cover'] ) ) {
			$this->addCover();
		}

		$output = $this->addFrontMatterByType( 'before-title', $contents, $pageoptions );

		// Check to see if we output a page (if so reset pageoptions to empty array)
		// This ensures that regardless of whether or not these pages have content
		// the page numbering will be reset.
		// @TODO: Refactor this.
		if ( ! empty( $output ) ) { $pageoptions = array(); }
		$output = $this->addFrontMatterByType( 'title-page', $contents, $pageoptions );

		if ( ! empty( $output ) ) { $pageoptions = array(); }
		$output = $this->addBookInfo();

		if ( ! empty( $output ) ) { $pageoptions = array(); }
		$output = $this->addFrontMatterByType( 'dedication', $contents );

		if ( ! empty( $output ) ) { $pageoptions = array(); }
		$this->addFrontMatterByType( 'epigraph', $contents );

		$this->addToc();
	}

	/**
	 * Add the cover for the book.
	 */
	function addCover() {
		$metadata = \PressBooks\Book::getBookInformation();

		if ( ! empty($metadata['pb_cover_image'] ) ) {

			$content = '<div style="text-align:center;"><img src="' . $metadata['pb_cover_image'] . '" alt="book-cover" title="' . bloginfo( 'name' ) . ' book cover" /></div>';

			$page = array(
				'post_type' => 'cover',
				'post_content' => $content,
				'post_title' => '',
				'mpdf_level' => 1,
				'mpdf_omit_toc' => TRUE,
			);

			$this->mpdf->SetFooter( $this->getFooter( 'cover', $page ) );
			$this->mpdf->SetHeader( $this->getHeader( 'cover', $page ) );

			$pageoptions['suppress'] = 'on';

			$this->addPage( $page, $pageoptions );
		}
	}

	/**
	 * Add book information page.
	 */
	function addBookInfo() {
		$meta = \PressBooks\Book::getBookInformation();
		$options = get_option( 'pressbooks_theme_options_global' );

		$content = '<h1>' . get_bloginfo( 'name' ) . '</h1>';

		if (! empty ( $meta['pb_subtitle'] ) ) {
			$content .= '<h2>' . $meta['pb_subtitle'] . '</h2>';
		}

		if ( isset( $meta['pb_author'] ) ) {
			$content .= '<h2>' . __('by', 'pressbooks') . '</h2>';
			$content .= '<h2>' . $meta['pb_author'] . '</h2>';
		}

		if ( isset( $meta['pb_contributing_authors'] ) ) {
			$content .= '<h3>' . $meta['pb_contributing_authors'] . '</h3>';
		}

		$content .= '<div>';

		if ( isset( $meta['pb_print_isbn'] ) ) {
			$content .= '<p class="isbn"><strong>' . __('ISBN', 'pressbooks' ) . '</strong>: ' .$meta['pb_print_isbn'] . '</p>';
		}

		if ( isset( $meta['pb_publisher'] ) ) {
			$content .= '<p class="publisher"><strong>' . __('Publisher', 'pressbooks' ) . '</strong>: ' . $meta['pb_publisher'] . '</p>';
		}

		if ( isset( $meta['pb_publisher_city'] ) ) {
			$content .= '<p class="publisher_city"><strong>' . __('Publisher City', 'pressbooks' ) . '</strong>: ' . $meta['pb_publisher_city'] . '</p>';
		}

		if ( isset( $meta['pb_copyright_year'] ) || isset( $meta['pb_copyright_holder'] )  ) {
			$content .= '<div class="copyright_notice">';

			$content .= '<p class="copyright_notice"><strong>' . __('Copyright', 'pressbooks') . '</strong>:';
			if ( ! empty( $meta['pb_copyright_year'] ) ) {
				$content .= $meta['pb_copyright_year'] . ' ';
			}

			if ( ! empty( $meta['pb_copyright_holder'] ) ) {
				$content .= ' ' . __('by', 'pressbooks') . ' ' . $meta['pb_copyright_holder'] . '. ';
			}
			$content .= '</p>';

			if ( ! empty ( $meta['pb_custom_copyright'] ) ) {
				$content .= '<p class="custom_copyright">' . $meta['pb_custom_copyright'] .'</p>';
			}

			$content .= '</div>';

		}

		$content .= '</div>';

		if ( 1 == $options['copyright_license'] ){
			$content .= '<p class="copyright_license">';
			$content .= $this->doCopyrightLicense( $meta );
			$content .= '</p>';
		}

		$page = array(
			'post_title' => __('Metadata', 'pressbooks'),
			'post_content' => $content,
			'post_type' => 'bookinfo',
			'mpdf_level' => 1,
		);

		return $this->addPage( $page );
	}


	/**
	 * Add front matter of a specific type.
	 */
	function addFrontMatterByType( $type, &$contents, $pageoptions = array() ) {
		foreach ( $contents as $index => $page ) {
			// If we hit non front-matter post types we won't see anymore front-matter
			if ( $page['post_type'] != 'front-matter' ) {
				return;
			}

			if ( $type == \PressBooks\Taxonomy\front_matter_type( $page['ID'] ) ) {
				$this->addPage( $page, $pageoptions );
				unset( $contents[$index] );
			}
		}
	}

	/**
	 * Add a page to the pdf.
	 */
	function addPage( $page, $pageoptions = array() ) {
		if ( empty ( $pageoptions['suppress'] ) ) {
			$pageoptions['suppress'] = 'off';
		}

		switch ( $this->ToCOutput ) {
			case MPDF_TOC_NOT_OUTPUT:
				$pageoptions['pagenumstyle'] = 'i';
				break;
			case MPDF_TOC_OUTPUT_PAGENO_NOT_RESET:
				$pageoptions['pagenumstyle'] = 1;
				$pageoptions['resetpagenum'] = 1;
				$this->ToCOutput = MPDF_TOC_OUTPUT_PAGENO_RESET;
				break;
			default:
				$pageoptions['pagenumstyle'] = 1;
				break;
		}

		$class = $page['post_type'] . ' type-' . $page['post_type'];

		if ( ! empty( $page['post_content'] ) ) {
			$this->mpdf->SetFooter( $this->getFooter( $page['post_type'], $page ) );
			$this->mpdf->SetHeader( $this->getHeader( $page['post_type'], $page ) );

			$this->mpdf->AddPageByArray( $this->mergePageOptions( $pageoptions ) );

			if ( empty($page['mpdf_omit_toc'] ) ) {
				$this->mpdf->TOC_Entry( $this->getTocEntry( $page ) , $page['mpdf_level'] );
				$this->mpdf->Bookmark( $this->getBookmarkEntry( $page ) , $page['mpdf_level'] );
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
	function getFooter( $context = '', $page = NULL ) {
		switch ( $context ) {
			case 'cover':
				$footer = '';
				break;
			default:
				$footer = '{PAGENO}';
				break;
		}

		$footer = apply_filters('mpdf_get_footer', $footer, $context, $page );

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
	function getHeader( $context = '', $page = NULL ) {
		$header = '';

		$header = apply_filters('mpdf_get_header', $header, $context, $page );
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
		  if ( strpos($type, '__') === 0 ) {
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
			    		}
			    		else {
			    			$part['post_content'] = $part_content;
			    			$part['mpdf_level' ] = 0;
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
	 * Get current theme and all (grand)parent theme css.
	 */
	function getCssRecursive( $theme ) {
		$css = '';
		if ( ! empty( $theme->parent() ) ) {
			$css .= $this->getCssRecursive ( $theme->parent() );
		}
		$themefiles = $theme->get_files( 'css' );
		if ( ! empty( $themefiles ) ) {
			foreach ( $themefiles as $file ) {
				$css .= file_get_contents( $file ) . "\n";
			}
		}

		return $css;
	}

	/**
	 * Add all css files
	 */
	function setCss() {

		$theme = wp_get_theme();

		$css = $this->getCssRecursive( $theme );

		$cssfile = $this->getExportStylePath( 'mpdf' );
		if ( ! empty($cssfile) ) {
			$css .= file_get_contents( $cssfile ) . "\n";
		}

		if ( ! empty( $this->cssOverrides ) ) {
			$css .= $this->cssOverrides . "\n";
		}

		if ( ! empty ( $css  ) ) {
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
			}
			elseif ( 'export' == (string) $key && $val ) {
				return true;
			}
		}

		return false;
	}



}
