<?php
/**
 * @author  PressBooks <code@pressbooks.com>
 * @license GPLv2 (or any later version))
 */
namespace PressBooks\Export\Mpdf;

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
		$this->options = get_option( 'pressbooks_theme_options_mpdf' );

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

		$contents = $this->getOrdererdBookContents();

		$this->mpdf = new \mPDF('');

		if ( ! empty ( $this->options['mpdf_ignore_invalid_utf8'] ) ) {
			$this->mpdf->ignore_invalid_utf8 = true;
		}

		if ( ! empty ( $this->options['mpdf_mirror_margins'] ) ) {
			$this->mpdf->mirrorMargins = true;
		}

		$this->mpdf->setBasePath( home_url( '/' ) );
		$this->mpdf->setFooter( $this->getFooter() );
		$this->setCss();

		$this->addPreContent();

		foreach ( $contents as $page ) {
			$this->addPage( $page );
			$pageno++;
		}
		$this->mpdf->Output( $this->outputPath, 'F' );

		// TODO trap errors
		return true;
	}

	/**
	 * Add the mpdf Table of Contents.
	 */
	function addToc() {
		$this->mpdf->AddPageByArray(  $this->mergePageOptions( array( 'suppress' => 'on' ) ) );

		$options = array(
			'paging' => true,
			'links' => true,
			'toc-bookmarkText' => __( 'Table of Contents', 'pressbooks' ),
		);
		$this->mpdf->TOCpagebreakByArray( $options );

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
	 * Add all content that is not included via
	 * PressBooks\PressBooks\Book::getBookContents()
	 */
	function addPreContent() {
		$this->addCover();
		$this->addBookInfo();
	}

	/**
	 * Add the cover for the book.
	 */
	function addCover() {
		$metadata = \PressBooks\Book::getBookInformation();

		if ( ! empty($metadata['pb_cover_image'] ) ) {
			$pageoptions = array(
				'suppress' => 'on',
				'resetpagenum' => 1,
			);

			$content = '<img src="' . $metadata['pb_cover_image'] . '" alt="book-cover" title="' . bloginfo( 'name' ) . ' book cover" />';
			$this->mpdf->SetFooter( $this->getFooter( 'cover' ) );
			$this->mpdf->addPageByArray( $this->mergePageOptions( $pageoptions ) );
			$this->mpdf->WriteHTML( $content );
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

		if ( isset( $meta['pb_keywords_tags'] ) ) {
			$content .= '<p class="keywords_tags"><strong>' . __('Keywords/Tags', 'pressbooks' ) . '</strong>: ' .  $meta['pb_keywords_tags'] . '</p>';
		}

		$content .= '</div>';


		if ( isset( $meta['pb_about_unlimited'] ) ) {
			$content .= '<h3>' . __( 'About the book', 'pressbooks' ) . '</h3>';
			$content .= '<p class="about">' . $meta['pb_about_unlimited'] . '</p>';
		}

		if ( 1 == $options['copyright_license'] ){
			$content .= '<p class="copyright_license">';
			$content .= $this->doCopyrightLicense( $meta );
			$content .= '</p>';
		}

		$pageoptions = array(
			'suppress' => 'on',
			'resetpagenum' => 1,
		);

		$this->mpdf->SetFooter( $this->getFooter( 'bookinfo' ) );
		$this->mpdf->addPageByArray( $this->mergePageOptions( $pageoptions ) );
		$this->mpdf->WriteHTML( $content );
	}

	/**
	 * Add a page to the pdf.
	 */
	function addPage( $page ) {
		static $previous;
		static $tocAdded;

		// If this is our first page set the previous to this one.
		if ( empty( $previous) ) {
			$previous = $page;
		}

		// Indicate the ToC has not been added yet.
		if ( ! isset( $tocAdded ) ) {
			$tocAdded = false;
		}

		// Add the Table of Contents before the first non front-matter page,
		// and reset page numbers.
		$pageoptions = array();
		if ( ! $tocAdded && $page['post_type'] != 'front-matter' ) {
			$this->addToc();
			$tocAdded = true;
			$pageoptions['resetpagenum'] = 1;
		}

		switch ( $page['post_type'] ) {
			case 'chapter':
			case 'part':
				$pageoptions['suppress'] = 'off';
				$pageoptions['pagenumstyle'] = 1;
				$bookmark = true;
				break;
			default:
				$pageoptions['suppress'] = 'on';
				$pageoptions['pagenumstyle'] = 'i';
				$bookmark = false;
				break;
		}

		$class = $page['post_type'] . ' type-' . $page['post_type'];

		if ( ! empty( $page['post_content'] ) ) {
			$this->mpdf->SetFooter( $this->getFooter( $page['post_type'] ) );
			$this->mpdf->AddPageByArray( $this->mergePageOptions( $pageoptions ) );

			if ( $bookmark ) {
				$this->mpdf->TOC_Entry( $page['post_title'] , $page['mpdf_level'] );
				$this->mpdf->Bookmark( $page['post_title'] , $page['mpdf_level'] );
			}

			$content = '<h2 class="entry-title">' . $page['post_title'] . '</h2>';
			$content .= '<div class="' . $class . '">' . $this->getFilteredContent( $page['post_content'] ) . '</div>';

			// TODO Make this hookable.
			$this->mpdf->WriteHTML( $content );
		}

		$previous = $page;
	}

	function getFilteredContent( $content ) {
		$filtered = apply_filters( 'the_content', $content );

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
	 * Return formatted footers.
	 *
	 * @param string $context
	 *   The post type being added to the page.
	 */
	function getFooter( $context = '' ) {
		switch ( $context ) {
			case 'chapter':
				$footer = '{PAGENO}';
				break;
			default:
				$footer = '';
				break;
		}
		// TODO Make this hookable.

		return $footer;
	}

	/**
	 * Restructures \PressBooks\Book::getBookContents() into a format more useful
	 * for direct iteration, and tracks a nesting level for Bookmark and ToC
	 * entries.
	 */
	function getOrdererdBookContents() {
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
		if ( ! empty( $theme->parent ) ) {
			$css .= $this->getCssRecursive ( $theme->parent );
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
