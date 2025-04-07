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

function handle_form_submission() {
    // Verify the nonce
    if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'pb_save_custom_fonts' ) ) {
        die( 'Permission denied' );
    }

    // Check if the user has the correct permissions
    if ( ! current_user_can( 'manage_network' ) ) {
        die( 'Permission denied' );
    }

    // Define the target directory
    $target_dir = WP_CONTENT_DIR . '/uploads/assets/fonts/';

    // Ensure the directory exists, create if necessary
    if ( ! is_dir( $target_dir ) ) {
        wp_mkdir_p( $target_dir, 0755, true);
    }

    // Check and process each font file (regular, bold, italic, bold italic)
    $font_files = [
        'regular'        => $_FILES['font_file_regular'] ?? null,
        'bold'           => $_FILES['font_file_bold'] ?? null,
        'italic'         => $_FILES['font_file_italic'] ?? null,
        'bold_italic'    => $_FILES['font_file_bold_italic'] ?? null,
    ];

    $font_data = [];

    // Loop through each font variation to validate and move the files
    foreach ( $font_files as $key => $file ) {
        if ( isset( $file ) && ! empty( $file['name'] ) ) {
            // Generate a unique file name to avoid conflicts
            $target_file = $target_dir . basename( $file['name'] );

            // Check the file type
            $allowed_types = [ 'woff', 'woff2', 'ttf', 'otf' ];
            $file_extension = pathinfo( $target_file, PATHINFO_EXTENSION );
            if ( ! in_array( $file_extension, $allowed_types ) ) {
                die( 'Invalid font file type.' );
            }

            // Move the uploaded file to the correct location
            if ( move_uploaded_file( $file['tmp_name'], $target_file ) ) {
                // Get the URL of the uploaded file
                $font_url = content_url( '/uploads/assets/fonts/' . basename( $target_file ) );

                // Add the uploaded font info to the font data array
                $font_data[$key] = [
                    'file' => esc_url_raw( $font_url ),
                    'variation' => $key, // Save the variant type
                ];
            } else {
                die( 'Font upload failed for ' . $key );
            }
        }
    }

    // Store font data in the options table
    $fonts = get_site_option('pressbooks_custom_fonts', []);
    $slug = sanitize_title( $_POST['font_name'] );

    if ( isset( $fonts[$slug] ) ) {
        // If the family exists, merge the new variants without overwriting existing ones
        foreach ( $font_data as $key => $data ) {
            // Update the fallback value, if changed
            if ( $data['fallback'] !== $fonts[$slug]['fallback'] ) {
                $fonts[$slug]['fallback'] = $data['fallback'];
            }
            // Only add or update variants that aren't already present in the family
            if ( !isset( $fonts[$slug]['files'][$key] ) ) {
                $fonts[$slug]['files'][$key] = $data;
            } else {
                // If the variant already exists, update the file URL
                if ( !empty($data['file']) ) {
                    $fonts[$slug]['files'][$key]['file'] = $data['file'];
                }
            }
        }
    } else {
        // If font family does not exist, create a new entry
        if ( ! empty( $font_data ) ) {
            $fonts[$slug] = [
                'name'     => sanitize_text_field( $_POST['font_name'] ),
                'fallback' => sanitize_text_field( $_POST['font_fallback'] ),
                'files'    => $font_data, // Each variation (regular, bold, italic, bold_italic)
            ];
        }
    }

    // Update the site option with the new font list
    update_site_option( 'pressbooks_custom_fonts', $fonts );
    // Make font-face declarations available across the network
    generate_custom_font_css();

    // Redirect with success message
    wp_safe_redirect( network_admin_url( 'settings.php?page=pb-custom-fonts&updated=true' ) );
    exit;
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
            $style = 'normal';
            $weight = '400';

            switch ($variation) {
                case 'regular':
                    $style = 'normal';
                    $weight = '400';
                    break;
                case 'bold':
                    $style = 'normal';
                    $weight = '700';
                    break;
                case 'italic':
                    $style = 'italic';
                    $weight = '400';
                    break;
                case 'bold_italic':
                    $style = 'italic';
                    $weight = '700';
                    break;
            }

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