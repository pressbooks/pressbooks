<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Modules\Export;

/**
 * @return array
 */
function dependency_errors() {
	$dependency_errors = [];

	if ( false === (bool) get_site_transient( 'pb_pdf_compatible' ) && false === (bool) \Pressbooks\Modules\Export\Prince\Filters::hasDependencies() ) {
		$dependency_errors['pdf'] = 'PDF';
	} else {
		set_site_transient( 'pb_pdf_compatible', true );
	}

	if ( false === (bool) get_site_transient( 'pb_print_pdf_compatible' ) && false === (bool) \Pressbooks\Modules\Export\Prince\Filters::hasDependencies() ) {
		$dependency_errors['print_pdf'] = 'Print PDF';
	} else {
		set_site_transient( 'pb_print_pdf_compatible', true );
	}

	if ( false === (bool) get_site_transient( 'pb_epub_compatible' ) && false === (bool) \Pressbooks\Modules\Export\Epub\Epub201::hasDependencies() ) {
		$dependency_errors['epub'] = 'EPUB';
	} else {
		set_site_transient( 'pb_epub_compatible', true );
	}

	if ( false === (bool) get_site_transient( 'pb_mobi_compatible' ) && false === (bool) \Pressbooks\Modules\Export\Mobi\Kindlegen::hasDependencies() ) {
		$dependency_errors['mobi'] = 'MOBI';
	} else {
		set_site_transient( 'pb_mobi_compatible', true );
	}

	if ( false === (bool) get_site_transient( 'pb_epub3_compatible' ) && false === (bool) \Pressbooks\Modules\Export\Epub\Epub3::hasDependencies() ) {
		$dependency_errors['epub3'] = 'EPUB3';
	} else {
		set_site_transient( 'pb_epub3_compatible', true );
	}

	if ( false === (bool) get_site_transient( 'pb_xhtml_compatible' ) && false === (bool) \Pressbooks\Modules\Export\Xhtml\Xhtml11::hasDependencies() ) {
		$dependency_errors['xhtml'] = 'XHTML';
	} else {
		set_site_transient( 'pb_xhtml_compatible', true );
	}

	if ( false === (bool) get_site_transient( 'pb_icml_compatible' ) && false === (bool) \Pressbooks\Modules\Export\InDesign\Icml::hasDependencies() ) {
		$dependency_errors['icml'] = 'ICML';
	} else {
		set_site_transient( 'pb_icml_compatible', true );
	}

	if ( false === (bool) get_site_transient( 'pb_odt_compatible' ) && false === (bool) \Pressbooks\Modules\Export\Odt\Odt::hasDependencies() ) {
		$dependency_errors['odt'] = 'OpenDocument';
	} else {
		set_site_transient( 'pb_odt_compatible', true );
	}

	if ( false === (bool) get_site_transient( 'pb_htmlbook_compatible' ) && false === (bool) \Pressbooks\Modules\Export\HTMLBook\HTMLBook::hasDependencies() ) {
		$dependency_errors['htmlbook'] = 'HTMLBook';
	} else {
		set_site_transient( 'pb_htmlbook_compatible', true );
	}
	/**
	 * Filter the array of dependency errors, remove unwanted formats.
	 *
	 * @since 3.9.8
	 *
	 * @param array $dependency_errors
	 */
	$dependency_errors = apply_filters( 'pb_dependency_errors', $dependency_errors );

	return $dependency_errors;
}

/**
 * @return string
 */
function dependency_errors_msg() {
	$dependency_errors = dependency_errors();
	if ( empty( $dependency_errors ) ) {
		return '';
	}

	$formats = implode( ', ', $dependency_errors );
	$pos = strrpos( $formats, ', ' );
	$dependency_errors_msg = sprintf(
		'<div class="error"><p>%s</p></div>',
		sprintf(
			__( 'Some dependencies for %1$s exports could not be found. Please verify that you have completed the <a href="%2$s">installation instructions</a>.', 'pressbooks' ),
			( $pos ) ? substr_replace( $formats, ', ' . __( 'and', 'pressbooks' ) . ' ', $pos, strlen( ', ' ) ) : $formats,
			'http://docs.pressbooks.org/installation'
		)
	);
	return $dependency_errors_msg;
}

/**
 * @return array
 */
function formats() {
	/**
	 * @since 3.9.8
	 * Add custom export formats to the export page format list.
	 *
	 * For example, here's how one might add a hypothetical Word export format:
	 *
	 * add_filter( 'pb_export_formats', function ( $formats ) {
	 *    $formats['exotic']['docx'] = __( 'Word (Beta)', 'pressbooks' );
	 *    return $formats;
	 * } );
	 *
	 */
	$formats = apply_filters(
		'pb_export_formats', [
			'standard' => [
				'print_pdf' => __( 'PDF (for print)', 'pressbooks' ),
				'pdf' => __( 'PDF (for digital distribution)', 'pressbooks' ),
				'epub' => __( 'EPUB (for Nook, Apple Books, Kobo etc.)', 'pressbooks' ),
				'mobi' => __( 'MOBI (for Kindle)', 'pressbooks' ),
			],
			'exotic' => [
				'epub3' => __( 'EPUB 3', 'pressbooks' ),
				'xhtml' => __( 'XHTML', 'pressbooks' ),
				'htmlbook' => __( 'HTMLBook', 'pressbooks' ),
				'odt' => __( 'OpenDocument', 'pressbooks' ),
				'wxr' => __( 'Pressbooks XML', 'pressbooks' ),
				'vanillawxr' => __( 'WordPress XML', 'pressbooks' ),
			],
		]
	);
	return $formats;
}

/**
 * @return array
 */
function filetypes() {
	/**
	 * Add custom export formats to the latest exports filetype mapping array.
	 *
	 * For example, here's how one might add a hypothetical Word export format:
	 *
	 * add_filter( 'pb_latest_export_filetypes', function ( $filetypes ) {
	 *    $filetypes['word'] = '.docx';
	 *    return $filetypes;
	 * } );
	 *
	 * @since 3.9.8
	 *
	 * @param array $value
	 */
	$filetypes = apply_filters(
		'pb_latest_export_filetypes', [
			'epub3' => '._3.epub',
			'epub' => '.epub',
			'pdf' => '.pdf',
			'print_pdf' => '._print.pdf',
			'mobi' => '.mobi',
			'icml' => '.icml',
			'htmlbook' => '.-htmlbook.html',
			'xhtml' => '.html',
			'wxr' => '.xml',
			'vanillawxr' => '._vanilla.xml',
			'mpdf' => '._oss.pdf',
			'odf' => '.odt',
		]
	);
	return $filetypes;
}

/**
 * Return a human-readable filetype for a given filetype slug.
 *
 * @since 5.7.0
 *
 * @param string $filetype The filetype slug.
 *
 * @return string A human-readable filetype.
 */
function get_name_for_filetype( $filetype ) {
	/**
	 * Add custom export file types to the array of human-readable file types.
	 * @since 5.7.0
	 */
	$formats = apply_filters(
		'pb_export_filetype_names', [
			'print-pdf' => __( 'Print PDF', 'pressbooks-book' ),
			'pdf' => __( 'Digital PDF', 'pressbooks-book' ),
			'mpdf' => __( 'Digital PDF', 'pressbooks-book' ),
			'htmlbook' => __( 'HTMLBook', 'pressbooks-book' ),
			'epub' => __( 'EPUB', 'pressbooks-book' ),
			'mobi' => __( 'MOBI', 'pressbooks-book' ),
			'epub3' => __( 'EPUB3', 'pressbooks-book' ),
			'xhtml' => __( 'XHTML', 'presbooks-book' ),
			'odf' => __( 'OpenDocument', 'pressbooks-book' ),
			'wxr' => __( 'Pressbooks XML', 'pressbooks-book' ),
			'vanillawxr' => __( 'WordPress XML', 'pressbooks' ),
		]
	);
	return isset( $formats[ $filetype ] ) ? $formats[ $filetype ] : ucfirst( $filetype );
}

/**
 * @return array
 */
function template_data() {
	$export_form_url = wp_nonce_url( get_admin_url( get_current_blog_id(), '/admin.php?page=pb_export&export=yes' ), 'pb-export' );

	$theme_name = wp_get_theme()->display( 'Name' ) . ' ' . wp_get_theme()->display( 'Version' );
	if ( \Pressbooks\Theme\Lock::init()->isLocked() ) {
		$theme_name .= '<span class="dashicons dashicons-lock" style="vertical-align: text-bottom;"></span>';
	}

	return [
		'export_form_url' => $export_form_url,
		'dependency_errors' => dependency_errors(),
		'dependency_errors_msg' => dependency_errors_msg(),
		'formats' => formats(),
		'theme_name' => $theme_name,
	];
}

/**
 * WP_Ajax
 */
function update_pins() {
	check_ajax_referer( 'pb-export-pins' );
	$pins = json_decode( stripcslashes( $_POST['pins'] ), true );
	if ( is_array( $pins ) ) {
		set_transient( Table::PIN, $pins );
		$data = [
			'message' => sprintf(
				__( 'The file %1$s has been %2$s successfully.', 'pressbooks' ),
				$_POST['file'],
				$_POST['pinned'] ? __( 'pinned', 'pressbooks' ) : __( 'unpinned', 'pressbooks' )
			),
		];
		wp_send_json_success( $data );
	}
}
