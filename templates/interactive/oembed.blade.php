<div class="textbox interactive-content interactive-content--oembed">
    @if ($img_src)
		<img src="{{ $img_src }}" alt="{{ sprintf( __( 'Thumbnail for the embedded element "%s"', 'pressbooks' ), $title ) }}" title="{{ $title }}" />
    @else
    	<span class="interactive-content__icon"></span>
    @endif
    <p>
        @if ($provider_name)
            {{  sprintf( __( 'A %s element has been excluded from this version of the text. You can view it online here:', 'pressbooks' ), $provider_name ) }}
        @else
            <?php _e( 'An interactive or media element has been excluded from this version of the text. You can view it online here:', 'pressbooks' ) ?>
		@endif
		<a href="{{ $url }}{{ \Pressbooks\Interactive\Content::ANCHOR }}" title="{{ $title }}">{{ $url }}</a>
    </p>
</div>
