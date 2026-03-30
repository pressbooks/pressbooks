{!! wp_kses_post( $notices ?? '' ) !!}

<div class="wrap">
    <h1>{{ __('Upload Custom Font', 'pressbooks') }}</h1>
    <p>{{ __('Upload custom font files for any additional font families you want to make available for books on your network. Uploaded fonts will be available for selection in theme options.', 'pressbooks') }}</p>
    <p>{{ __('Permitted file types: .otf, .ttf, .woff, .woff2.', 'pressbooks') }}</p>

    @if (isset($_GET['updated']) && sanitize_text_field( wp_unslash( $_GET['updated'] ) ) === 'true')
        <div class="notice notice-success is-dismissible">
            <p>{{ __('Font uploaded successfully.', 'pressbooks') }}</p>
        </div>
    @endif

    @if (isset($_GET['deleted']) && sanitize_text_field( wp_unslash( $_GET['deleted'] ) ) === 'true')
        <div class="notice notice-success is-dismissible">
            <p>{{ __('Font deleted successfully.', 'pressbooks') }}</p>
        </div>
    @endif

    @if (isset($_GET['delete_error']) && sanitize_text_field( wp_unslash( $_GET['delete_error'] ) ) === 'not_found')
        <div class="notice notice-error is-dismissible">
            <p>{{ __('Font not found. It may have already been deleted.', 'pressbooks') }}</p>
        </div>
    @endif

    <form method="post" enctype="multipart/form-data" action="{{ esc_url( admin_url('admin-post.php') ) }}">
        <input type="hidden" name="action" value="pb_save_custom_fonts">
        <input type="hidden" name="_wpnonce" value="{{ esc_attr( $nonce ) }}">

        <table class="form-table">
            <tr>
                <th><label for="font_family_name">{{ __('Font Family Name', 'pressbooks') }}</label></th>
                <td><input type="text" name="font_name" id="font_name" required></td>
            </tr>

            <!-- Font Files for Different Variations -->
            <tr>
                <th><label for="font_file_regular">{{ __('Regular Font File', 'pressbooks') }}</label></th>
                <td><input type="file" name="font_file_regular" id="font_file_regular" accept=".woff,.woff2,.ttf,.otf"></td>
            </tr>
            <tr>
                <th><label for="font_file_bold">{{ __('Bold Font File', 'pressbooks') }}</label></th>
                <td><input type="file" name="font_file_bold" id="font_file_bold" accept=".woff,.woff2,.ttf,.otf"></td>
            </tr>
            <tr>
                <th><label for="font_file_italic">{{ __('Italic Font File', 'pressbooks') }}</label></th>
                <td><input type="file" name="font_file_italic" id="font_file_italic" accept=".woff,.woff2,.ttf,.otf"></td>
            </tr>
            <tr>
                <th><label for="font_file_bold_italic">{{ __('Bold Italic Font File', 'pressbooks') }}</label></th>
                <td><input type="file" name="font_file_bold_italic" id="font_file_bold_italic" accept=".woff,.woff2,.ttf,.otf"></td>
            </tr>

            <!-- Font Fallback Options -->
           <tr>
            <th scope="row"><?php esc_html_e( 'Font Fallback', 'pressbooks' ); ?></th>
            <td><fieldset id="font_fallback">
                <label for="font_fallback_sans">
                    <input type="radio" name="font_fallback" id="font_fallback_sans" value="sans-serif" required<?php checked( $font_fallback ?? '', 'sans-serif' ); ?>>
                    <?php esc_html_e( 'Sans-serif', 'pressbooks' ); ?>
                </label><br>
                <label for="font_fallback_serif">
                    <input type="radio" name="font_fallback" id="font_fallback_serif" value="serif" required<?php checked( $font_fallback ?? '', 'serif' ); ?>>
                    <?php esc_html_e( 'Serif', 'pressbooks' ); ?>
                </label>
            </fieldset>
            </td>
            </tr>
        </table>

        <?php submit_button( __('Upload Font', 'pressbooks') ); ?>
    </form>

    @if (!empty($fonts))
        <h2>{{ __('Registered Fonts', 'pressbooks') }}</h2>
        <div class="pb-table-scroll-container">
        <table class="wp-list-table widefat striped pb-table custom-fonts">
            <thead>
            <tr>
                <th class="font-family">{{ __('Font Family Name', 'pressbooks') }}</th>
                <th class="font-variants">{{ __('Font Variants', 'pressbooks') }}</th>
                <th class="font-fallback">{{ __('Font Fallback', 'pressbooks') }}</th>
                <th class="font-actions">{{ __('Actions', 'pressbooks') }}</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($fonts as $slug => $font)
                <tr>
                    <td>{{ esc_html( $font['name'] ?? '' ) }}</td>
                    <td>
                        @foreach (($font['files'] ?? []) as $variant => $file)
                            <div class="font-variant">
                                <span>{{ esc_html( ucwords(str_replace('_', ' ', $variant)) ) }} (<a href="{{ esc_url( $file['file'] ?? '' ) }}">{{ __('Download font file', 'pressbooks') }}</a>)</span>
                                <form class="delete-font-variant" method="post" action="{{ esc_url( admin_url('admin-post.php') ) }}" onsubmit="return confirm('{{ esc_js( __('Are you sure you want to remove this font variant?', 'pressbooks') ) }}')">
                                    <input type="hidden" name="action" value="pb_delete_custom_font_variant">
                                    <input type="hidden" name="_wpnonce" value="{{ esc_attr( wp_create_nonce('pb_delete_custom_font_variant') ) }}">
                                    <input type="hidden" name="font_slug" value="{{ esc_attr( $slug ) }}">
                                    <input type="hidden" name="variant" value="{{ esc_attr( $variant ) }}">
                                    <button type="submit" class="button button-link-delete">{{ __('Remove Variant', 'pressbooks') }}</button>
                                </form>
                            </div>
                        @endforeach
                    </td>
                    <td>{{ esc_html( $font['fallback'] ?? '' ) }}</td>
                    <td>
                        <form class="delete-font-family" method="post" action="{{ esc_url( admin_url('admin-post.php') ) }}" onsubmit="return confirm('{{ esc_js( __('Are you sure you want to delete this font family? Any books currently using it will need to have their theme settings updated to replace this font with a desired replacement.', 'pressbooks') ) }}')">
                            <input type="hidden" name="action" value="pb_delete_custom_font">
                            <input type="hidden" name="_wpnonce" value="{{ esc_attr( wp_create_nonce('pb_delete_custom_font') ) }}">
                            <input type="hidden" name="font_slug" value="{{ esc_attr( $slug ) }}">
                            <button type="submit" class="button button-link-delete">{{ __('Delete Font Family', 'pressbooks') }}</button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        </div>
    @endif
</div>
