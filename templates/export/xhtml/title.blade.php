<?php /** @var \Pressbooks\Modules\Export\Xhtml\Blade $s */ ?>

@foreach($book_contents['front-matter'] as $front_matter)
    @continue(!$front_matter['export'])
    @continue('title-page' !== ($subclass = $s->getFrontMatterType($front_matter['ID'])))
    @php $content = $front_matter['post_content'] @endphp
    @break
@endforeach

<div id="title-page">
    @if(isset($content))
        {!! $content !!}
    @else
        <h1 class="title">{{ $title }}</h1>
        <h2 class="subtitle">{{ $metadata['pb_subtitle'] or '' }}</h2>
        <h3 class="author">{{ $metadata['pb_author'] or '' }}</h3>
        <h4 class="contributing-authors">{{ $metadata['pb_contributing_authors'] or '' }}</h4>
        @if(current_theme_supports( 'pressbooks_publisher_logo' ))
            {{-- // TODO: Support custom publisher logo. --}}
            <div class="publisher-logo"><img src="@php get_theme_support( 'pressbooks_publisher_logo' )[0]['logo_uri']; @endphp"/></div>
        @endif
        <h4 class="publisher">{{ $metadata['pb_publisher'] or '' }}</h4>
        <h5 class="publisher-city">{{ $metadata['pb_publisher_city'] or '' }}</h5>
    @endif
</div>