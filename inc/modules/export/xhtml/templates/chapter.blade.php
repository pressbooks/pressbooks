<?php /** @var \Pressbooks\Modules\Export\Xhtml\Blade $s */ ?>
@inject('s', '\Pressbooks\Modules\Export\Xhtml\Blade')
@php
if ( ! $s::$hasIntroduction ) {
    $class = 'chapter introduction';
    $s::$hasIntroduction = true;
} else {
    $class = 'chapter';
}
@endphp
<div class="{{ $class }} {{ $subclass }}" id="{{ $slug }}">
    <div class="chapter-title-wrap">
        <h3 class="chapter-number">{{ $i }}</h3>
        <h2 class="chapter-title">{!! $title !!}</h2>
    </div>
    <div class="ugc chapter-ugc">
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
    {!! $append_chapter_content !!}
    @isset($s::$endnotes[$post_id])
        @include('export.xhtml.endnotes', ['endnote_id' => $post_id])
    @endisset
</div>
