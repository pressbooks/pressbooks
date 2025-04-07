{!! $notices ?? '' !!}

<div class="wrap">
    <h1>{{ __('Upload Custom Font', 'pressbooks') }}</h1>

    @if (isset($_GET['updated']) && $_GET['updated'] === 'true')
        <div class="notice notice-success is-dismissible">
            <p>{{ __('Font uploaded successfully.', 'pressbooks') }}</p>
        </div>
    @endif

    <form method="post" enctype="multipart/form-data" action="{{ admin_url('admin-post.php' ) }}">
        <input type="hidden" name="action" value="pb_save_custom_fonts">
        <input type="hidden" name="_wpnonce" value="{{ $nonce }}">

        <table class="form-table">
            <tr>
                <th><label for="font_name">{{ __('Font Name', 'pressbooks') }}</label></th>
                <td><input type="text" name="font_name" id="font_name" required></td>
            </tr>
            <tr>
                <th><label for="font_file">{{ __('Font File (.woff2, .woff, .ttf, .otf)', 'pressbooks') }}</label></th>
                <td><input type="file" name="font_file" id="font_file" accept=".woff,.woff2,.ttf,.otf" required></td>
            </tr>
            <tr>
                <th><label for="font_weight">{{ __('Font Weight', 'pressbooks') }}</label></th>
                <td><input type="text" name="font_weight" id="font_weight" value="normal"></td>
            </tr>
            <tr>
                <th><label for="font_style">{{ __('Font Style', 'pressbooks') }}</label></th>
                <td><input type="text" name="font_style" id="font_style" value="normal"></td>
            </tr>
            <tr>
                <th><label for="font_fallback">{{ __('Fallback Stack', 'pressbooks') }}</label></th>
                <td><input type="text" name="font_fallback" id="font_fallback" value="sans-serif"></td>
            </tr>
        </table>

        <?php submit_button( __('Upload Font', 'pressbooks') ); ?>
    </form>

    @if (!empty($fonts))
        <h2>{{ __('Registered Fonts', 'pressbooks') }}</h2>
        <table class="widefat fixed striped">
            <thead>
            <tr>
                <th>{{ __('Font Name', 'pressbooks') }}</th>
                <th>{{ __('Preview Link', 'pressbooks') }}</th>
                <th>{{ __('Weight', 'pressbooks') }}</th>
                <th>{{ __('Style', 'pressbooks') }}</th>
                <th>{{ __('Fallback', 'pressbooks') }}</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($fonts as $font)
                <tr>
                    <td>{{ $font['name'] }}</td>
                    <td><a href="{{ $font['file'] }}" target="_blank">{{ basename($font['file']) }}</a></td>
                    <td>{{ $font['weight'] ?? 'normal' }}</td>
                    <td>{{ $font['style'] ?? 'normal' }}</td>
                    <td>{{ $font['fallback'] ?? 'sans-serif' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>
