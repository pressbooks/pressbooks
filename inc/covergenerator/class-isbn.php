<?php

namespace Pressbooks\Covergenerator;

use function Pressbooks\Utility\debug_error_log;

/**
 * @see https://github.com/bwipp/postscriptbarcode/wiki/ISBN
 */
class Isbn {

	/**
	 * @var string
	 */
	protected $isbnNumber;

	/**
	 * @var string
	 */
	protected $isbnTextFont = 'Courier';

	/**
	 * @var int
	 */
	protected $isbnTextSize = 9;

	/**
	 * @var string
	 */
	protected $textFont = 'Helvetica';

	/**
	 * @var int
	 */
	protected $textSize = 12;

	/**
	 * @var int
	 */
	protected $dpi = 300;

	/**
	 * Constructor
	 */
	function __construct() {

	}


	/**
	 * Create an ISBN png and sideload it into WordPress
	 *
	 * @param string $isbn_number
	 *
	 * @throws \Exception
	 *
	 * @return string
	 */
	public function createBarcode( $isbn_number ) {

		if ( ! $this->validateIsbnNumber( $isbn_number ) ) {
			\Pressbooks\add_error( __( 'There was a problem creating the barcode: Invalid ISBN number.', 'pressbooks' ) );
			return false;
		}

		$this->isbnNumber = $this->fixIsbnNumber( $isbn_number );

		$ps = \Pressbooks\Utility\create_tmp_file();
		$png = \Pressbooks\Utility\create_tmp_file();

		$this->compile( $ps );
		$this->gs( $ps, $png, $this->dpi );
		$this->crop( $png );

		$old_id = \Pressbooks\Image\attachment_id_from_url( get_option( 'pressbooks_cg_isbn' ) );
		if ( $old_id ) {
			wp_delete_attachment( $old_id, true );
		}

		if ( ! function_exists( 'media_handle_sideload' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/image.php' );
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
			require_once( ABSPATH . 'wp-admin/includes/media.php' );
		}

		$pid = media_handle_sideload(
			[
				'name' => "{$isbn_number}.png",
				'tmp_name' => $png,
			], 0
		);
		if ( is_wp_error( $pid ) ) {
			throw new \Exception(
				$pid->get_error_message(),
				$pid->get_error_code()
			);
		}

		$src = wp_get_attachment_url( $pid );

		if ( false === $src ) {
			throw new \Exception( 'No attachment url.' );
		}

		update_option( 'pressbooks_cg_isbn', $src );

		return $src;
	}


	/**
	 * Validate an ISBN string
	 *
	 * @param $isbn_number
	 *
	 * @return bool
	 */
	public function validateIsbnNumber( $isbn_number ) {

		// Regex to split a string only by the last whitespace character
		@list( $isbn_number, $addon ) = preg_split( '/\s+(?=\S*+$)/', trim( $isbn_number ) ); // @codingStandardsIgnoreLine

		$is_valid_isbn = ( new \Isbn\Isbn() )->validation->isbn( $isbn_number );
		$is_valid_addon = true;

		if ( $addon ) {
			if ( ! preg_match( '/^([0-9]{2}|[0-9]{5})$/', $addon ) ) {
				$is_valid_addon = false;
			}
		}

		return $is_valid_isbn && $is_valid_addon;
	}

	/**
	 * Fix an ISBN string
	 *
	 * @param $isbn_number
	 *
	 * @return string
	 */
	public function fixIsbnNumber( $isbn_number ) {

		// Regex to split a string only by the last whitespace character
		@list( $isbn_number, $addon ) = preg_split( '/\s+(?=\S*+$)/', trim( $isbn_number ) ); // @codingStandardsIgnoreLine

		$isbn_number = ( new \Isbn\Isbn() )->hyphens->fixHyphens( $isbn_number );

		if ( $addon ) {
			$isbn_number .= " $addon";
		}

		return $isbn_number;
	}


	/**
	 * ISBN Invocation Code.
	 *
	 * @see https://github.com/bwipp/postscriptbarcode/wiki/ISBN
	 * @see https://github.com/bwipp/postscriptbarcode/wiki/Symbol-Dimensions
	 * @see https://github.com/bwipp/postscriptbarcode/wiki/Text-Properties
	 *
	 * @param string $isbn
	 * @param string $isbn_text_font
	 * @param float $isbn_text_size
	 * @param string $text_font
	 * @param float $text_size
	 *
	 * @return string
	 */
	public function invocation( $isbn, $isbn_text_font, $isbn_text_size, $text_font, $text_size ) {

		$ps[] = "50 50 moveto ({$isbn}) (includetext isbntextfont={$isbn_text_font} isbntextsize={$isbn_text_size} textfont={$text_font} textsize={$text_size})";
		$ps[] = '/isbn /uk.co.terryburton.bwipp findresource exec';

		return implode( "\n", $ps ) . "\n";
	}


	/**
	 * Compile an ISBN Postscript file
	 *
	 * @param string $path_to_ps
	 *
	 * @throws \LogicException
	 */
	public function compile( $path_to_ps ) {

		if ( empty( $this->isbnNumber ) ) {
			throw new \LogicException( '$this->isbnNumber is not set' );
		}

		$isbn = \Pressbooks\Utility\get_contents( PB_PLUGIN_DIR . 'symbionts/postscriptbarcode/isbn.ps' );

		$invocation = $this->invocation(
			$this->isbnNumber,
			$this->isbnTextFont,
			$this->isbnTextSize,
			$this->textFont,
			$this->textSize
		);

		// @codingStandardsIgnoreStart
		file_put_contents( $path_to_ps, $isbn );
		file_put_contents( $path_to_ps, $invocation, FILE_APPEND | LOCK_EX );
		file_put_contents( $path_to_ps, ( "\n" . 'showpage' ), FILE_APPEND | LOCK_EX );
		// @codingStandardsIgnoreEnd
	}


	/**
	 * Use Ghostscript to convert a PostScript file into a grayscale PNG file
	 *
	 * @param string $input_path_to_ps
	 * @param string $output_path_to_png
	 * @param int $dpi
	 */
	public function gs( $input_path_to_ps, $output_path_to_png, $dpi ) {

		$dpi = (int) $dpi;
		$command = PB_GS_COMMAND . " -dQUIET -dNOPAUSE -dSAFER -dBATCH -sDEVICE=pnggray -r{$dpi} -sOutputFile=" . escapeshellarg( $output_path_to_png ) . ' ' . escapeshellarg( $input_path_to_ps );

		// Execute command
		$output = [];
		$return_var = 0;
		exec( $command, $output, $return_var ); // @codingStandardsIgnoreLine

		if ( ! empty( $output ) ) {
			// @codingStandardsIgnoreStart
			debug_error_log( $command );
			debug_error_log( print_r( $output, true ) );
			// @codingStandardsIgnoreEnd
		}
	}


	/**
	 * Use ImageMagick to automatically crop & pad a PNG file with a white border
	 *
	 * @param string $path_to_png
	 * @param string $border
	 */
	public function crop( $path_to_png, $border = '20x20' ) {

		$command = PB_CONVERT_COMMAND . ' ' . escapeshellarg( $path_to_png ) . " -trim +repage -bordercolor white -border {$border} " . escapeshellarg( $path_to_png );

		// Execute command
		$output = [];
		$return_var = 0;
		exec( $command, $output, $return_var ); // @codingStandardsIgnoreLine

		if ( ! empty( $output ) ) {
			// @codingStandardsIgnoreStart
			debug_error_log( $command );
			debug_error_log( print_r( $output, true ) );
			// @codingStandardsIgnoreEnd
		}
	}
}
