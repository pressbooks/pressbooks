<?php /** @var \Pressbooks\Modules\Export\Xhtml\Blade $s */ ?>
@inject('s', '\Pressbooks\Modules\Export\Xhtml\Blade')
<div class="front-matter {{ $subclass }}" id="{{ $slug }}">
    <div class="front-matter-title-wrap">
        <h3 class="front-matter-number">{{ $i }}</h3>
        <h1 class="front-matter-title">{!! $title !!}</h1>
    </div>
    <div class="ugc front-matter-ugc">
        @if ( $author )
            <h2 class="chapter-author">{!! $author !!}</h2>
        @endif
        @if ( $subtitle )
            <h2 class="chapter-subtitle">{!! $subtitle !!}</h2>
        @endif
        @if ( $short_title )
            <h6 class="short-title">{!! $short_title !!}</h6>
        @endif
        {!! $content !!}
    </div>
    {!! $append_front_matter_content !!}
    @isset($s::$endnotes[$post_id])
        @include('export.xhtml.endnotes', ['endnote_id' => $post_id])
    @endisset
</div>
