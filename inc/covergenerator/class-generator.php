<?php

namespace Pressbooks\Covergenerator;

use function Pressbooks\Utility\create_tmp_file;
use function Pressbooks\Utility\template;

abstract class Generator {



	/**
	 * @var Input
	 */
	protected $input;

	/**
	 * Required HTML variables
	 *
	 * @var array
	 */
	protected $requiredHtmlVars = [];

	/**
	 * Optional HTML variables
	 *
	 * @var array
	 */
	protected $optionalHtmlVars = [];

	/**
	 * Required SASS variables (no dollar sign)
	 *
	 * @var array
	 */
	protected $requiredSassVars = [];

	/**
	 * Optional SASS variables (no dollar sign)
	 *
	 * @var array
	 */
	protected $optionalSassVars = [];


	/**
	 * Constructor
	 *
	 * @param Input $input
	 */
	public function __construct( Input $input ) {

		$this->input = $input;
	}

	/**
	 * @return void
	 */
	abstract public function generate();


	/**
	 * Convert dashed string to Getter method
	 *
	 * @param string $str
	 *
	 * @return string
	 */
	public function varToGetter( $str ) {

		return 'get' . str_replace( ' ', '', ucwords( str_replace( [ '-', '_' ], ' ', $str ) ) );
	}


	/**
	 * Get the fullpath to the Covers folder.
	 * Create if not there. Create .htaccess protection if missing.
	 *
	 * @return string fullpath
	 */
	public static function getCoversFolder() {

		$path = \Pressbooks\Utility\get_media_prefix() . 'covers/';
		if ( ! file_exists( $path ) ) {
			mkdir( $path, 0775, true );
		}

		$path_to_htaccess = $path . '.htaccess';
		if ( ! file_exists( $path_to_htaccess ) ) {
			// Restrict access
			\Pressbooks\Utility\put_contents( $path_to_htaccess, "deny from all\n" );
		}

		return $path;
	}


	/**
	 * Get the fullpath to the Covers folder.
	 * Create if not there. Create .htaccess protection if missing.
	 *
	 * @return string fullpath
	 */
	public static function getCoversFolderUri() {
		$wp_upload_dir = wp_upload_dir();
		$path = $wp_upload_dir['baseurl'] . '/covers/';

		return $path;
	}


	/**
	 * Generate SCSS vars based on Input object
	 *
	 * @throws \LogicException
	 * @throws \InvalidArgumentException
	 *
	 * @return string
	 */
	protected function getScssVars() {

		$sass = '';

		// Required
		foreach ( $this->requiredSassVars as $var ) {
			$method = $this->varToGetter( $var );

			if ( ! method_exists( $this->input, $method ) ) {
				throw new \LogicException( "Input::{$method}() not found." );
			}
			if ( empty( $this->input->{$method}() ) ) {
				throw new \InvalidArgumentException( "Input::{$method}() cannot be empty." );
			}

			$sass .= "\${$var}: " . $this->input->{$method}() . ";\n";
		}

		// Optional
		foreach ( $this->optionalSassVars as $var ) {
			$method = $this->varToGetter( $var );

			if ( method_exists( $this->input, $method ) && ! empty( $this->input->{$method}() ) ) {
				$sass .= "\${$var}: " . $this->input->{$method}() . ";\n";
			}
		}

		return $sass;
	}


	/**
	 * @return array
	 */
	protected function getHtmlTemplateVars() {

		$html_vars = [];

		// Required
		foreach ( $this->requiredHtmlVars as $var ) {
			$method = $this->varToGetter( $var );

			if ( ! method_exists( $this->input, $method ) ) {
				throw new \LogicException( "Input::{$method}() not found." );
			}
			if ( empty( $this->input->{$method}() ) ) {
				throw new \InvalidArgumentException( "Input::{$method}() cannot be empty." );
			}

			$var = str_replace( '-', '_', $var );
			$html_vars[ $var ] = $this->input->{$method}();
		}

		// Optional
		foreach ( $this->optionalHtmlVars as $var ) {
			$method = $this->varToGetter( $var );

			if ( method_exists( $this->input, $method ) && ! empty( $this->input->{$method}() ) ) {
				$var = str_replace( '-', '_', $var );
				$html_vars[ $var ] = $this->input->{$method}();
			}
		}

		return $html_vars;
	}

	/**
	 * Generate cover
	 *
	 * @see pressbooks/templates/admin/generator.php
	 */
	public static function formSubmit() {

		if ( empty( current_user_can( 'edit_posts' ) ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', 'pressbooks-cg' ), 403 );
		}

		if ( check_admin_referer( 'pb-generate-cover' ) ) {
			$cg_options = get_option( 'pressbooks_cg_options' );

			if ( isset( $cg_options['pdf_pagecount'] ) ) {
				$pages = $cg_options['pdf_pagecount'];
			} else {
				$spine = new \Pressbooks\Covergenerator\Spine;
				$pages = $spine->countPagesInMostRecentPdf();
			}
			if ( isset( $cg_options['ppi'] ) ) {
				$ppi = $cg_options['ppi'];
			} else {
				$ppi = 444;
			}
			$spine = new \Pressbooks\Covergenerator\Spine();
			$spine_width = $spine->spineWidthCalculator( $pages, $ppi );
			$spine_width = "{$spine_width}in"; // Inches, float to CSS string

			// Either ISBN or SKU, not both
			if ( isset( $cg_options['pb_print_isbn'] ) && '' !== trim( $cg_options['pb_print_isbn'] ) ) {
				$isbn_url = ( new \Pressbooks\Covergenerator\Isbn() )->createBarcode( $cg_options['pb_print_isbn'] );
			} elseif ( isset( $cg_options['pb_print_sku'] ) && '' !== trim( $cg_options['pb_print_sku'] ) ) {
				$isbn_url = ( new \Pressbooks\Covergenerator\Sku() )->createBarcode( $cg_options['pb_print_sku'] );
			}

			$input = new \Pressbooks\Covergenerator\Input();
			$input->setTitle( $cg_options['pb_title'] );
			if ( $pages >= 48 ) {
				if ( isset( $cg_options['pb_title_spine'] ) && '' !== $cg_options['pb_title_spine'] ) {
					$input->setSpineTitle( $cg_options['pb_title_spine'] );
				} else {
					$input->setSpineTitle( $cg_options['pb_title'] );
				}
			}
			if ( isset( $cg_options['pb_subtitle'] ) && '' !== $cg_options['pb_subtitle'] ) {
				$input->setSubtitle( $cg_options['pb_subtitle'] );
			}
			$input->setAuthor( $cg_options['pb_author'] );
			if ( $pages >= 48 ) {
				if ( isset( $cg_options['pb_author_spine'] ) && '' !== $cg_options['pb_author_spine'] ) {
					$input->setSpineAuthor( $cg_options['pb_author_spine'] );
				} else {
					$input->setSpineAuthor( $cg_options['pb_author'] );
				}
			}
			if ( isset( $cg_options['pb_about_unlimited'] ) && '' !== $cg_options['pb_about_unlimited'] ) {
				$input->setAbout( $cg_options['pb_about_unlimited'] );
			}
			if ( isset( $cg_options['text_transform'] ) && '' !== $cg_options['text_transform'] ) {
				$input->setTextTransform( $cg_options['text_transform'] );
			}

			$pdf_options = get_option( 'pressbooks_theme_options_pdf' );

			$input->setTrimHeight( $pdf_options['pdf_page_height'] );
			$input->setTrimWidth( $pdf_options['pdf_page_width'] );
			$input->setSpineWidth( $spine_width );
			if ( isset( $cg_options['front_cover_text'] ) ) {
				$input->setFrontFontColor( $cg_options['front_cover_text'] );
			}
			if ( isset( $cg_options['front_cover_background'] ) ) {
				$input->setFrontBackgroundColor( $cg_options['front_cover_background'] );
			}
			if ( isset( $cg_options['spine_text'] ) ) {
				$input->setSpineFontColor( $cg_options['spine_text'] );
			}
			if ( isset( $cg_options['spine_background'] ) ) {
				$input->setSpineBackgroundColor( $cg_options['spine_background'] );
			}
			if ( isset( $cg_options['back_cover_text'] ) ) {
				$input->setBackFontColor( $cg_options['back_cover_text'] );
			}
			if ( isset( $cg_options['back_cover_background'] ) ) {
				$input->setBackBackgroundColor( $cg_options['back_cover_background'] );
			}
			if ( isset( $cg_options['front_background_image'] ) ) {
				$input->setFrontBackgroundImage( \Pressbooks\Sanitize\maybe_https( $cg_options['front_background_image'] ) );
			}
			if ( isset( $isbn_url ) ) {
				$input->setIsbnImage( $isbn_url );
			}

			try {
				if ( 'pdf' === $_POST['format'] && defined( 'DOCRAPTOR_API_KEY' ) ) {
					$pdf = new \Pressbooks\Covergenerator\DocraptorPdf( $input );
					$pdf->generate();
				} elseif ( 'pdf' === $_POST['format'] ) {
					$pdf = new \Pressbooks\Covergenerator\PrincePdf( $input );
					$pdf->generate();
				} elseif ( 'jpg' === $_POST['format'] && defined( 'DOCRAPTOR_API_KEY' ) ) {
					$jpg = new \Pressbooks\Covergenerator\DocraptorJpg( $input );
					$jpg->generate();
				} elseif ( 'jpg' === $_POST['format'] ) {
					$jpg = new \Pressbooks\Covergenerator\PrinceJpg( $input );
					$jpg->generate();
				}
			} catch ( \Exception $e ) {
				die( '<p>ERROR: ' . $e->getMessage() . "</p>\n" );
			}
		}

		\Pressbooks\Redirect\location( admin_url( 'admin.php?page=pressbooks_cg' ) );
	}


	/**
	 * Delete cover
	 *
	 * @see pressbooks/templates/admin/generator.php
	 */
	public static function formDelete() {

		if ( check_admin_referer( 'pb-delete-cover' ) ) {
			$filename = sanitize_file_name( $_POST['filename'] );
			$path = static::getCoversFolder();
			unlink( $path . $filename );
			delete_transient( 'dirsize_cache' ); /** @see get_dirsize() */
		}

		\Pressbooks\Redirect\location( admin_url( 'admin.php?page=pressbooks_cg' ) );
	}

	/**
	 * Delete all covers
	 *
	 * @see pressbooks/templates/admin/generator.php
	 */
	public static function formDeleteAll() {

		if ( ! empty( $_POST['delete_all_covers'] ) && check_admin_referer( 'pb-delete-all-covers' ) ) {
			\Pressbooks\Utility\truncate_exports( 0, static::getCoversFolder() );
			delete_transient( 'dirsize_cache' ); /** @see get_dirsize() */
		}

		\Pressbooks\Redirect\location( admin_url( 'admin.php?page=pressbooks_cg' ) );
	}

	/**
	 * Download cover
	 *
	 * @see pressbooks/templates/admin/generator.php
	 */
	public static function formDownload() {
		$filename = sanitize_file_name( isset( $_GET['file'] ) ? $_GET['file'] : '' ); // @codingStandardsIgnoreLine
		static::_downloadCoverFile( $filename );
	}


	/**
	 * Download an .htaccess protected file from the exports directory.
	 *
	 * @param string $filename sanitized $_GET['download_export_file']
	 */
	protected static function _downloadCoverFile( $filename ) {

		$filepath = static::getCoversFolder() . $filename;
		if ( ! is_readable( $filepath ) ) {
			// Cannot read file
			wp_die(
				__( 'File not found', 'pressbooks-cg' ) . ": $filename", '', [
					'response' => 404,
				]
			);
		}

		// @codingStandardsIgnoreStart
		// Force download
		set_time_limit( 0 );
		header( 'Content-Description: File Transfer' );
		header( 'Content-Type: ' . \Pressbooks\Modules\Export\Export::mimeType( $filepath ) );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Content-Transfer-Encoding: binary' );
		header( 'Expires: 0' );
		header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
		header( 'Pragma: public' );
		header( 'Content-Length: ' . filesize( $filepath ) );
		@ob_clean();
		flush();

		while ( @ob_end_flush() ) {
			// Fix out-of-memory problem
		}

		readfile( $filepath );
		// @codingStandardsIgnoreEnd

		exit;
	}

	/**
	 * Create a timestamped filename.
	 *
	 * @param string $extension
	 * @param bool $fullpath
	 *
	 * @return string
	 */
	function timestampedFileName( $extension, $fullpath = true ) {
		$book_title = ( get_bloginfo( 'name' ) ) ? get_bloginfo( 'name' ) : __( 'book', 'pressbooks-cg' );
		$book_title_slug = sanitize_file_name( $book_title );
		$book_title_slug = str_replace( [ '+' ], '', $book_title_slug ); // Remove symbols which confuse Apache (Ie. form urlencoded spaces)
		$book_title_slug = sanitize_file_name( $book_title_slug ); // str_replace() may inadvertently create a new bad filename, sanitize again for good measure.

		if ( $fullpath ) {
			$path = static::getCoversFolder();
		} else {
			$path = '';
		}

		$filename = $path . $book_title_slug . '-cover-' . time() . '.' . ltrim( $extension, '.' );

		return $filename;
	}
}
