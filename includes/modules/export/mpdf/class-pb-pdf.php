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
	 * Service URL
	 *
	 * @var string
	 */
	public $url;

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
		if ( ! defined( 'MPDF_WRITEHTML_MODE_DOC' ) ) {
			// Define some constants for mPDF::WriteHTML()
			// @see http://mpdf1.com/manual/index.php?tid=121
			define( 'MPDF_WRITEHTML_MODE_DOC', 0);
			define( 'MPDF_WRITEHTML_MODE_CSS', 1);
			define(' MPDF_WRITEHTML_MODE_ELEMENTS', 2);
		}

		$this->exportStylePath = $this->getExportStylePath( 'mpdf' );

		// Set the access protected "format/xhtml" URL with a valid timestamp and NONCE
		// @todo is this necessary?
		$timestamp = time();
		$md5 = $this->nonce( $timestamp );
		$this->url = home_url() . "/format/xhtml?timestamp={$timestamp}&hashkey={$md5}";

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
		$this->mpdf->setBasePath( $this->url);
		$this->mpdf->setFooter( $this->getFooter() );
		$this->setCss();

		$this->addPreContent();

		foreach ( $contents as $page ) {
			$this->addPage( $page );
			$pageno++;
		}
		$this->mpdf->Output( $this->outputPath, 'F' );

		// TODO trap errors
		return TRUE;
	}

	/**
	 * Add the mpdf Table of Contents.
	 */
	function addToc() {
		$this->mpdf->AddPageByArray( array( 'suppress' => 'on' ) );

		$options = array(
			'paging' => TRUE,
			'links' => TRUE,
			'toc-bookmarkText' => __( 'Table of Contents', 'pressbooks' ),
		);
		$this->mpdf->TOCpagebreakByArray( $options );

	}

	/**
	 * Add all content that is not included via
	 * PressBooks\PressBooks\Book::getBookContents()
	 */
	function addPreContent() {
		$this->addCover();
		$this->addCopyright();
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
			$this->mpdf->addPageByArray( $pageoptions );
			$this->mpdf->WriteHTML( $content );
		}
	}

	/**
	 * Add copyright information for book.
	 */
	function addCopyright() {
		$metadata = \PressBooks\Book::getBookInformation();
		$options = get_option( 'pressbooks_theme_options_global' );

		$content = '<h2 class="entry-title">' . get_bloginfo( 'name' ) . ' ' . __( 'Copyright', 'pressbooks' ) . ' &#169; </h2>';
		$content .= '<div class="copyright">';

		if ( ! empty( $metadata['pb_custom_copyright'] ) ) {
			$content .= $metadata['pb_custom_copyright'];
		}

		if ( 1 == $options['copyright_license'] ){
			$content .= $this->doCopyrightLicense( $metadata );
		}
		// default, so something is displayed
		if ( empty( $metadata['pb_custom_copyright'] ) && 0 == $options['copyright_license'] ) {
			$content .= '<p>';
			if ( ! empty( $metadata['pb_copyright_year'] ) ) {
				$content .= $metadata['pb_copyright_year'];
			}
			else {
				$content .= date( 'Y' );
			}


			if ( ! empty( $metadata['pb_copyright_holder'] ) ) {
				$content .= ' ' . __( 'by', 'pressbooks' ) . ' ' . $metadata['pb_copyright_holder'] . '. ';
			}

			$content .= '</p>';
		}

		$content .= "</div>\n";

		$pageoptions = array(
			'suppress' => 'on',
			'resetpagenum' => 1,
		);

		$this->mpdf->SetFooter( $this->getFooter( 'copyright' ) );
		$this->mpdf->addPageByArray( $pageoptions );
		$this->mpdf->WriteHTML( $content );
	}

	/**
	 * Add a page to the pdf.
	 */
	function addPage( $page ) {
		static $previous;
		static $firstChapter;

		if ( ! isset( $firstChapter ) ) {
			$firstChapter = TRUE;
		}

		// If this is our first page set the previous to this one.
		if ( empty( $previous) ) {
			$previous = $page;
		}

		$changed = FALSE;
		if ($previous['post_type'] != $page['post_type'] ) {
			$changed = TRUE;
		}

		$pageoptions = array();
		switch ( $page['post_type'] ) {
			case 'chapter':
				$class = "chapter type-chapter";
				$pageoptions['suppress'] = 'off';
				$pageoptions['pagenumstyle'] = 1;
				$bookmark = TRUE;
				// If this is our first chapter add the ToC.
				if ( $firstChapter ) {
					$this->addToc();
					$firstChapter = FALSE;
				}
				break;
			default:
				$class = $page['post_type'] . ' type-' . $page['post_type'];
				$pageoptions['suppress'] = 'on';
				$pageoptions['pagenumstyle'] = 'i';
				$bookmark = FALSE;
				break;
		}

		if ( ! empty( $page['post_content'] ) ) {
			$this->mpdf->SetFooter( $this->getFooter( $page['post_type'] ) );
			$this->mpdf->AddPageByArray( $pageoptions );

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
			    			$part['post_content'] .= trim( get_post_meta( $part['ID'], 'pb_part_content', true ) );
			    		}
			    		else {
			    			$part['post_content'] = trim( get_post_meta( $part['ID'], 'pb_part_content', true ) );
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
	 * Add all css files
	 */
	function setCss() {

		$css = '';

		$theme = wp_get_theme();
		$themefiles = $theme->get_files( 'css' );
		if ( ! empty( $themefiles ) ) {
			foreach ($themefiles as $file ) {
				$css .= file_get_contents( $file ) . "\n";
			}
		}

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
