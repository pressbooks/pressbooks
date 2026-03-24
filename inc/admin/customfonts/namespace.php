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
		$upload_dir = wp_upload_dir();
		$url = $upload_dir['baseurl'] . '/assets/custom-fonts/' . $file_name;
		return [
			'file' => esc_url_raw( $url ),
			'variation' => $key,
		];
	}

	return new \WP_Error( 'upload_failed', 'Font upload failed for ' . $key );
}

/**
 * Handle form submission to delete a custom font.
 *
 * Removes the font from the network site option, deletes physical font files from disk,
 * regenerates the font CSS, and resets any per-book theme options that referenced the
 * deleted font so those books fall back to their theme default.
 */
function handle_delete_font() {
	if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'pb_delete_custom_font' ) ) {
		wp_die( 'Permission denied' );
	}

	if ( ! current_user_can( 'manage_network' ) ) {
		wp_die( 'Permission denied' );
	}

	$slug = sanitize_title( wp_unslash( $_POST['font_slug'] ?? '' ) );
	$fonts = get_site_option( 'pressbooks_custom_fonts', [] );

	if ( ! isset( $fonts[ $slug ] ) ) {
		wp_safe_redirect( network_admin_url( 'settings.php?page=pb_custom_fonts&delete_error=not_found' ) );
		return;
	}

	$font_name = $fonts[ $slug ]['name'];
	$upload_dir = wp_upload_dir();

	// Delete physical font files from disk
	foreach ( $fonts[ $slug ]['files'] ?? [] as $data ) {
		$file_url = $data['file'] ?? '';
		if ( $file_url ) {
			$relative_path = str_replace( $upload_dir['baseurl'], '', $file_url );
			$abs_path = $upload_dir['basedir'] . $relative_path;
			if ( file_exists( $abs_path ) ) {
				wp_delete_file( $abs_path );
			}
		}
	}

	unset( $fonts[ $slug ] );
	update_site_option( 'pressbooks_custom_fonts', $fonts );
	generate_custom_font_css();

	// Reset theme options for books that were using this font
	reset_books_using_font( $font_name );

	wp_safe_redirect( network_admin_url( 'settings.php?page=pb_custom_fonts&deleted=true' ) );
	return;
}

/**
 * Handle form submission to delete a specific font variant.
 *
 * Removes a single variant from a font family, deletes the physical font file from disk,
 * and regenerates the font CSS.
 */
function handle_delete_font_variant() {
	if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'pb_delete_custom_font_variant' ) ) {
		wp_die( 'Permission denied' );
	}

	if ( ! current_user_can( 'manage_network' ) ) {
		wp_die( 'Permission denied' );
	}

	$slug = sanitize_title( wp_unslash( $_POST['font_slug'] ?? '' ) );
	$variant = sanitize_text_field( wp_unslash( $_POST['variant'] ?? '' ) );
	$fonts = get_site_option( 'pressbooks_custom_fonts', [] );

	if ( ! isset( $fonts[ $slug ] ) || ! isset( $fonts[ $slug ]['files'][ $variant ] ) ) {
		wp_safe_redirect( network_admin_url( 'settings.php?page=pb_custom_fonts&delete_error=not_found' ) );
		return;
	}

	$upload_dir = wp_upload_dir();
	$file_url = $fonts[ $slug ]['files'][ $variant ]['file'] ?? '';

	// Delete physical font file from disk
	if ( $file_url ) {
		$relative_path = str_replace( $upload_dir['baseurl'], '', $file_url );
		$abs_path = $upload_dir['basedir'] . $relative_path;
		if ( file_exists( $abs_path ) ) {
			wp_delete_file( $abs_path );
		}
	}

	// Remove the variant from the font family
	unset( $fonts[ $slug ]['files'][ $variant ] );

	// If no variants remain, remove the entire font family
	if ( empty( $fonts[ $slug ]['files'] ) ) {
		unset( $fonts[ $slug ] );
	}

	update_site_option( 'pressbooks_custom_fonts', $fonts );
	generate_custom_font_css();

	wp_safe_redirect( network_admin_url( 'settings.php?page=pb_custom_fonts&deleted=true' ) );
	return;
}

/**
 * Iterate over all sites in the network and clear any shapeshifter font selections
 * that reference a deleted font, resetting them to the theme default (empty string).
 *
 * @param string $font_name The human‑readable font family name that was deleted.
 */
function reset_books_using_font( string $font_name ) {
	$sites = get_sites( [ 'number' => 0 ] );

	$option_keys = [
		'pressbooks_theme_options_web'   => [ 'webbook_header_font', 'webbook_body_font' ],
		'pressbooks_theme_options_pdf'   => [ 'pdf_header_font', 'pdf_body_font' ],
		'pressbooks_theme_options_ebook' => [ 'ebook_header_font', 'ebook_body_font' ],
	];

	foreach ( $sites as $site ) {
		switch_to_blog( $site->blog_id );

		foreach ( $option_keys as $option_key => $font_fields ) {
			$options = get_option( $option_key, [] );
			$updated = false;

			foreach ( $font_fields as $field ) {
				if ( isset( $options[ $field ] ) && $options[ $field ] === $font_name ) {
					$options[ $field ] = '';
					$updated = true;
				}
			}

			if ( $updated ) {
				update_option( $option_key, $options );
			}
		}

		restore_current_blog();
	}
}

/**
 * Generate @font-face declarations for custom fonts and write them to a CSS file.
 * Uses absolute HTTP URLs so they are resolved correctly by all export engines
 *
 * @return void
 */
function generate_custom_font_css() {
	$fonts = get_site_option( 'pressbooks_custom_fonts', [] );

	if ( empty( $fonts ) ) {
		// Clear any previously generated CSS so stale @font-face rules are not served
		$css_path = WP_CONTENT_DIR . '/uploads/assets/custom-fonts/custom-fonts.css';
		if ( file_exists( $css_path ) ) {
			file_put_contents( $css_path, '' ); // @codingStandardsIgnoreLine
		}
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

	$css_path = WP_CONTENT_DIR . '/uploads/assets/custom-fonts/custom-fonts.css';
	file_put_contents( $css_path, $custom_css ); // @codingStandardsIgnoreLine
}
