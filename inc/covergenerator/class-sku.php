<?php

namespace Pressbooks\Covergenerator;

/**
 * @see https://en.wikipedia.org/wiki/Code_128
 */
class Sku extends Isbn {

	/**
	 * @var string
	 */
	protected $sku;

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
	 * Create CODE128 png and sideload it into WordPress
	 *
	 * @param string $sku
	 *
	 * @throws \Exception
	 *
	 * @return string
	 */
	public function createBarcode( $sku ) {

		$this->sku = \Pressbooks\Sanitize\force_ascii( $sku );
		if ( $this->sku !== $sku ) {
			$_SESSION['pb_errors'] = __( 'There was a problem creating the barcode: Invalid characters in SKU', 'pressbooks' );
			return false;
		}

		$ps = \Pressbooks\Utility\create_tmp_file();
		$png = \Pressbooks\Utility\create_tmp_file();

		$this->compile( $ps );
		$this->gs( $ps, $png, $this->dpi );
		$this->crop( $png );

		$old_id = \Pressbooks\Image\attachment_id_from_url( get_option( 'pressbooks_cg_sku' ) );
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
				'name' => "{$sku}.png",
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

		update_option( 'pressbooks_cg_sku', $src );

		return $src;
	}


	/**
	 * SKU Invocation Code.
	 *
	 * @see https://github.com/bwipp/postscriptbarcode/wiki/Code-128
	 * @see https://github.com/bwipp/postscriptbarcode/wiki/Symbol-Dimensions
	 * @see https://github.com/bwipp/postscriptbarcode/wiki/Text-Properties
	 *
	 * @param string $sku
	 * @param string $not_used_1
	 * @param float $not_used_2
	 * @param string $text_font
	 * @param float $text_size
	 *
	 * @return string
	 */
	public function invocation( $sku, $not_used_1, $not_used_2, $text_font, $text_size ) {

		$ps[] = "50 50 moveto ({$sku}) (includetext textfont={$text_font} textsize={$text_size} height=0.5)";
		$ps[] = '/code128 /uk.co.terryburton.bwipp findresource exec';

		return implode( "\n", $ps ) . "\n";
	}


	/**
	 * Compile a SKU Postscript file
	 *
	 * @param string $path_to_ps
	 *
	 * @throws \LogicException
	 */
	public function compile( $path_to_ps ) {

		if ( empty( $this->sku ) ) {
			throw new \LogicException( '$this->sku is not set' );
		}

		$sku = \Pressbooks\Utility\get_contents( PB_PLUGIN_DIR . 'symbionts/postscriptbarcode/code128.ps' );

		$invocation = $this->invocation(
			$this->sku,
			null,
			null,
			$this->textFont,
			$this->textSize
		);

		// @codingStandardsIgnoreStart
		file_put_contents( $path_to_ps, $sku );
		file_put_contents( $path_to_ps, $invocation, FILE_APPEND | LOCK_EX );
		file_put_contents( $path_to_ps, ( "\n" . 'showpage' ), FILE_APPEND | LOCK_EX );
		// @codingStandardsIgnoreEnd
	}

}
