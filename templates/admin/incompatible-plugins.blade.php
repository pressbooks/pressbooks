<div class="{{ $div_class ?? '' }}">
    @if ( ! empty($h2) )
        <h2>{{ $h2 }}</h2>
    @endif
    <p>{{ sprintf( __( "Uh oh! The versions of the following plugins you're running haven't been tested with Pressbooks %s. Please update them or confirm compatibility before updating Pressbooks, or you may experience issues:", 'pressbooks' ), $version ) }}</p>
    <table class="{{ $table_class ?? '' }}" cellspacing="0">
        <thead>
        <tr>
            <th><?php _e( 'Plugin', 'pressbooks' ) ?></th>
            <th><?php _e( 'Tested up to Pressbooks version', 'pressbooks' ) ?></th>
        </tr>
        </thead>
        <tbody>
        @foreach ( $plugins as $plugin )
            <tr>
                <td>{{ $plugin['Name'] }}</td>
                <td>{{ ! empty( $plugin[\Pressbooks\Updates::VERSION_TESTED_HEADER] ) ? $plugin[\Pressbooks\Updates::VERSION_TESTED_HEADER] : __( 'Unknown', 'pressbooks' ) ?></td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
