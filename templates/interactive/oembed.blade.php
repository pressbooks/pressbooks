<div class="pb-interactive-content oembed">
    @if ($img_src)
        <img src="{{ $img_src }}"/>
    @else
        <span class="dashicons dashicons-media-interactive"></span>
    @endif
    <p>
        @if ($provider_name)
            {{  sprintf( __( 'A %s element has been excluded from this version of the text. You can view it online at', 'pressbooks' ), $provider_name ) }}
        @else
            {{ __( 'An interactive or media element has been excluded from this version of the text. You can view it online at', 'pressbooks' ) }}
        @endif
        <a href="{{ $url }}{{ \Pressbooks\Interactive\Content::ANCHOR }}">{{ $title }}</a>.
    </p>
</div>