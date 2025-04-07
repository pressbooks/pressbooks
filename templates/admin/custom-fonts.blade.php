{!! $notices ?? '' !!}

<div class="wrap">
    <h1>{{ __('Upload Custom Font', 'pressbooks') }}</h1>
    <p>Use the form below to upload up to four variants from a font family and declare whether the fallback font used should be serif or sans-serif.</p>

    @if (isset($_GET['updated']) && $_GET['updated'] === 'true')
        <div class="notice notice-success is-dismissible">
            <p>{{ __('Font uploaded successfully.', 'pressbooks') }}</p>
        </div>
    @endif

    <form method="post" enctype="multipart/form-data" action="{{ admin_url('admin-post.php') }}">
        <input type="hidden" name="action" value="pb_save_custom_fonts">
        <input type="hidden" name="_wpnonce" value="{{ $nonce }}">

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
                <th><label for="font_fallback">{{ __('Fallback Stack', 'pressbooks') }}</label></th>
                <td>
                    <select name="font_fallback" id="font_fallback" required>
                        <option value="sans-serif">{{ __('Sans-serif', 'pressbooks') }}</option>
                        <option value="serif">{{ __('Serif', 'pressbooks') }}</option>
                    </select>
                </td>
            </tr>
        </table>

        <?php submit_button( __('Upload Font', 'pressbooks') ); ?>
    </form>

    @if (!empty($fonts))
        <h2>{{ __('Registered Fonts', 'pressbooks') }}</h2>
        <table class="widefat fixed striped">
            <thead>
            <tr>
                <th>{{ __('Font Family Name', 'pressbooks') }}</th>
                <th>{{ __('Font Variant(s)', 'pressbooks') }}</th>
                <th>{{ __('Fallback', 'pressbooks') }}</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($fonts as $font)
                <tr>
                    <td>{{ $font['name'] }}</td> <!-- Font Family Name -->
                    <td>
                        @foreach ($font['files'] as $variant => $file)
                            <a href="{{ $file['file'] }}" target="_blank">
                                {{ ucwords(str_replace('_', ' ', $variant)) }}
                            </a><br> <!-- Links for each variant -->
                        @endforeach
                    </td>
                    <td>{{ $font['fallback'] ?? 'sans-serif' }}</td> <!-- Fallback stack -->
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>
