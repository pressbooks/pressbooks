<?php
/**
 * @author Steel Wagstaff
 * @license  GPLv3 (or any later version)
 */

namespace Pressbooks\Admin\CustomFonts;

use Pressbooks\Container;

function render_custom_fonts_page() {
    $blade = Container::get( 'Blade' );

    // Get the list of fonts if available
    $fonts = get_site_option('pressbooks_custom_fonts', []);

    // Ensure Blade rendering system is available
    echo $blade->render(
        'admin.custom-fonts', [
            'fonts' => $fonts,
            'nonce' => wp_create_nonce( 'pb_save_custom_fonts' )
        ]
    );
}

function handle_font_file_upload( $slug, $file_key, $file ) {
    // Sanitize and process the uploaded file (e.g., move to appropriate directory)
    if ( empty( $file ) || empty( $file['tmp_name'] ) ) {
        return null; // No file uploaded
    }

    // Define the upload directory
    $upload_dir = wp_upload_dir()['basedir'] . '/custom-fonts/';
    if ( ! file_exists( $upload_dir ) ) {
        wp_mkdir_p( $upload_dir );
    }

    $file_name = $file['name'];
    $file_tmp  = $file['tmp_name'];
    $file_path = $upload_dir . $file_name;

    // Move the file to the upload directory
    if ( move_uploaded_file( $file_tmp, $file_path ) ) {
        return [
            'file' => $file_path,
        ];
    } else {
        error_log( "Failed to upload font file: $file_name" );
        return null; // Return null if the file upload fails
    }
}
function handle_form_submission() {
    // Verify the nonce
    if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'pb_save_custom_fonts' ) ) {
        die( 'Permission denied' );
    }

    // Check if the user has the correct permissions
    if ( ! current_user_can( 'manage_network' ) ) {
        die( 'Permission denied' );
    }

    // Store font data in the options table
    $fonts = get_site_option('pressbooks_custom_fonts', []);

    $slug = sanitize_title( $_POST['font_name'] );
    $fallback = sanitize_text_field( $_POST['font_fallback'] );
    $font_files = [
        'regular'        => $_FILES['font_file_regular'] ?? null,
        'bold'           => $_FILES['font_file_bold'] ?? null,
        'italic'         => $_FILES['font_file_italic'] ?? null,
        'bold_italic'    => $_FILES['font_file_bold_italic'] ?? null,
    ];

    if ( ! isset( $fonts[$slug]) ) {
        $fonts[$slug] = [
            'name'     => $slug,
            'fallback' => $fallback,
            'files'    => [],
        ];
    }
    else {
        $fonts[$slug]['fallback'] = $fallback;
        // If the font does not exist, create a new entry
    }

    if ( array_filter( $font_files ) ) {
        $target_dir = WP_CONTENT_DIR . '/uploads/assets/fonts/';

        // Ensure the directory exists, create if necessary
        if ( ! is_dir( $target_dir ) ) {
            wp_mkdir_p( $target_dir, 0755, true);
        }

        foreach ( $font_files as $key => $file ) {
            if ( ! empty( $file['tmp_name'] ) ) {
                $result = handle_uploaded_font( $file, $key, $target_dir);
                if ( is_wp_error( $result ) ) {
                    die( $result->get_error_message() );
                }
                $fonts[$slug]['files'][$key] = $result;
        }
    }
        // TODO: save generated SCSS to the theme stylesheets (function currently just returns the CSS)
        generate_custom_font_css();
    }

    // Update the site option with the new font list
    update_site_option( 'pressbooks_custom_fonts', $fonts );

    // Redirect with success message
    wp_safe_redirect( network_admin_url( 'settings.php?page=pb-custom-fonts&updated=true' ) );
    exit;
}

function handle_uploaded_font( $file, $key, $target_dir ) {
    $allowed_types = [ 'woff', 'woff2', 'ttf', 'otf' ];
    $file_name = basename( $file['name'] );
    $file_extension = pathinfo( $file_name, PATHINFO_EXTENSION );

    if ( ! in_array( $file_extension, $allowed_types ) ) {
        return new WP_Error( 'invalid_type', 'Invalid font file type.' );
    }

    $target_file = $target_dir . $file_name;

    if ( move_uploaded_file( $file['tmp_name'], $target_file ) ) {
        $url = content_url( '/uploads/assets/fonts/' . $file_name );
        return [
            'file' => esc_url_raw( $url ),
            'variation' => $key,
        ];
    }
    return new WP_Error( 'upload_failed', 'Font upload failed for ' . $key );
}

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
                'regular' => ['normal', '400'],
                'bold' => ['normal', '700'],
                'italic' => ['italic', '400'],
                'bold_italic' => ['italic', '700'],
            ];
            [$style, $weight] = $style_map[$variation] ?? ['normal', '400'];

            // Infer format from file extension
            $ext = pathinfo($url, PATHINFO_EXTENSION);
            $format_map = [
                'woff' => 'woff',
                'woff2' => 'woff2',
                'ttf' => 'truetype',
                'otf' => 'opentype',
            ];
            $format = isset($format_map[$ext]) ? $format_map[$ext] : 'truetype';

            $custom_css .= "@font-face {
                            font-family: '{$family}';
                            font-style: {$style};
                            font-weight: {$weight};
                            src: url('{$url}') format('{$format}');
                            }\n";
        }

        return $custom_css;
    }
}