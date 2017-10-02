<?php /** @var \Pressbooks\Modules\Export\Xhtml\Blade $s */ ?>

@php
    if ( empty( $metadata['pb_book_license'] ) ) {
        $all_rights_reserved = true;
    } elseif ( $metadata['pb_book_license'] === 'all-rights-reserved' ) {
        $all_rights_reserved = true;
    } else {
        $all_rights_reserved = false;
    }
    if ( ! empty( $metadata['pb_custom_copyright'] ) ) {
        $has_custom_copyright = true;
    } else {
        $has_custom_copyright = false;
    }
@endphp

<div id="copyright-page">
    <div class="ugc">
        @if( ! $has_custom_copyright || ( $has_custom_copyright && ! $all_rights_reserved ) )
            @php
                $license = $s->doCopyrightLicense( $metadata );
                if ( $license ) {
                    echo $s->removeAttributionLink( $license );
                }
            @endphp
        @endif

        @if($has_custom_copyright)
            @php echo $s->tidy( $metadata['pb_custom_copyright'] ); @endphp;
        @endif

        {{-- default, so something is displayed --}}
        @if ( empty( $metadata['pb_custom_copyright'] ) && empty( $license ) )
            <p>
                @php
                    echo get_bloginfo( 'name' ) . ' ' . __( 'Copyright', 'pressbooks' ) . ' &#169; ';
                    if ( ! empty( $meta['pb_copyright_year'] ) ) {
                        echo $meta['pb_copyright_year'] . ' ';
                    } elseif ( ! empty( $meta['pb_publication_date'] ) ) {
                        echo strftime( '%Y', $meta['pb_publication_date'] );
                    } else {
                        echo date( 'Y' );
                    }
                    if ( ! empty( $metadata['pb_copyright_holder'] ) ) {
                        echo ' ' . __( 'by', 'pressbooks' ) . ' ' . $metadata['pb_copyright_holder'] . '. ';
                    }
                @endphp
            </p>
        @endif

    </div>
</div>

