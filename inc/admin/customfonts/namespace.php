<?php
/**
 * @author Steel Wagstaff
 * @license  GPLv3 (or any later version)
 */

namespace Pressbooks\Admin\CustomFonts;

use Pressbooks\Container;

/**
 * Render custom fonts page
 */
function render_custom_fonts_page() {
	$blade = Container::get( 'Blade' );
	$fonts = get_site_option( 'pressbooks_custom_fonts', [] );
    // phpcs:disable Pressbooks.Security.EscapeOutput.OutputNotEscaped
	echo $blade->render(
		'admin.custom-fonts', [
			'fonts' => $fonts,
			'nonce' => wp_create_nonce( 'pb_save_custom_fonts' ),
		]
	);
    // phpcs:enable Pressbooks.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Handle form submission for custom fonts page
 */
function handle_form_submission() {
	// Verify the nonce
	if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'pb_save_custom_fonts' ) ) {
		wp_die( 'Permission denied' );
	}

	// Check if the user has the correct permissions
	if ( ! current_user_can( 'manage_network' ) ) {
		wp_die( 'Permission denied' );
	}

	$fonts = get_site_option( 'pressbooks_custom_fonts', [] );
	// Preserve the human-readable font name and derive a slug for storage
	$font_name_raw = sanitize_text_field( wp_unslash( $_POST['font_name'] ?? '' ) );
	$slug = sanitize_title( $font_name_raw );
	$fallback = sanitize_text_field( wp_unslash( $_POST['font_fallback'] ?? '' ) );
	$font_files = [
		'regular'        => isset( $_FILES['font_file_regular'] ) ? map_deep( wp_unslash( $_FILES['font_file_regular'] ), 'sanitize_text_field' ) : null,
		'bold'           => isset( $_FILES['font_file_bold'] ) ? map_deep( wp_unslash( $_FILES['font_file_bold'] ), 'sanitize_text_field' ) : null,
		'italic'         => isset( $_FILES['font_file_italic'] ) ? map_deep( wp_unslash( $_FILES['font_file_italic'] ), 'sanitize_text_field' ) : null,
		'bold_italic'    => isset( $_FILES['font_file_bold_italic'] ) ? map_deep( wp_unslash( $_FILES['font_file_bold_italic'] ), 'sanitize_text_field' ) : null,
	];

	if ( ! isset( $fonts[ $slug ] ) ) {
		$fonts[ $slug ] = [
			// Store the original human-readable name and the slug as the key
			'name'     => $font_name_raw,
			'fallback' => $fallback,
			'files'    => [],
		];
	} else {
		$fonts[ $slug ]['fallback'] = $fallback;
		// Ensure the stored human name remains intact if provided
		if ( ! empty( $font_name_raw ) ) {
			$fonts[ $slug ]['name'] = $font_name_raw;
		}
	}

	if ( array_filter( $font_files ) ) {
		$target_dir = WP_CONTENT_DIR . '/uploads/assets/custom-fonts/';

		// Ensure the directory exists, create if necessary
		if ( ! is_dir( $target_dir ) ) {
			wp_mkdir_p( $target_dir, 0755, true );
		}

		foreach ( $font_files as $key => $file ) {
			if ( ! empty( $file['tmp_name'] ) ) {
				$result = handle_uploaded_font( $file, $key, $target_dir );
				if ( is_wp_error( $result ) ) {
					wp_die( esc_html( $result->get_error_message() ) );
				}
				$fonts[ $slug ]['files'][ $key ] = $result;
			}
		}
	}
	// Update the site option with the new font list
	update_site_option( 'pressbooks_custom_fonts', $fonts );
	generate_custom_font_css();
	// Redirect with success message
	wp_safe_redirect( network_admin_url( 'settings.php?page=pb_custom_fonts&updated=true' ) );
	// Return instead of exit so unit tests don't terminate the PHP process.
	return;
}

/**
 * Handle uploaded font files
 *
 * @param array $file The uploaded data for a font file
 * @param string $key The font variation (e.g., regular, bold, italic)
 * @param string $target_dir The target directory for the uploaded font
 *
 * @return array|WP_Error The URL of the uploaded font file and its variation or an error object
 */
function handle_uploaded_font( array $file, string $key, string $target_dir ) {
	$allowed_types = [ 'woff', 'woff2', 'ttf', 'otf' ];
	$file_name = basename( $file['name'] );
	$file_extension = strtolower( pathinfo( $file_name, PATHINFO_EXTENSION ) );

	if ( ! in_array( $file_extension, $allowed_types, true ) ) {
		return new \WP_Error( 'invalid_type', 'Invalid font file type.' );
	}

	$target_file = $target_dir . $file_name;

	// Only treat as a valid upload if PHP marks it as uploaded. Tests
	// provide a namespaced shim to make is_uploaded_file()/move_uploaded_file
	// work with temp files.
	if ( move_uploaded_file( $file['tmp_name'], $target_file ) ) {
		$url = content_url( '/uploads/assets/custom-fonts/' . $file_name );
		return [
			'file' => esc_url_raw( $url ),
			'variation' => $key,
		];
	}

	return new \WP_Error( 'upload_failed', 'Font upload failed for ' . $key );
}

/**
 * Generate @font-face declarations for custom fonts and write them to a CSS file.
 *
 * return void
 */
function generate_custom_font_css() {
	$fonts = get_site_option( 'pressbooks_custom_fonts', [] );

	if ( empty( $fonts ) ) {
		return;
	}

	$custom_css = '';

	foreach ( $fonts as $slug => $font ) {

		$family = $font['name'];
		$files = $font['files'];

		foreach ( $files as $variation => $data ) {
			$url = $data['file'];
			$style_map = [
				'regular' => [ 'normal', 'normal' ],
				'bold' => [ 'normal', 'bold' ],
				'italic' => [ 'italic', 'normal' ],
				'bold_italic' => [ 'italic', 'bold' ],
			];
			[$style, $weight] = $style_map[ $variation ] ?? [ 'normal', 'normal' ];
			$ext = pathinfo( $url, PATHINFO_EXTENSION );
			$format_map = [
				'woff' => 'woff',
				'woff2' => 'woff2',
				'ttf' => 'truetype',
				'otf' => 'opentype',
			];
			$format = isset( $format_map[ $ext ] ) ? $format_map[ $ext ] : 'truetype';
			$custom_css .= "@font-face {
			font-family: '{$family}';
            font-style: {$style};
            font-weight: {$weight};
            src: url('{$url}') format('{$format}');
        }\n\n";
		}

	}

	$css_file_path = WP_CONTENT_DIR . '/uploads/assets/custom-fonts/custom-fonts.css';
	file_put_contents( $css_file_path, $custom_css ); // @codingStandardsIgnoreLine
}
