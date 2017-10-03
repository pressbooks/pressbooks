<?php /** @var \Pressbooks\Modules\Export\Xhtml\Blade $s */ ?>
@inject('s', '\Pressbooks\Modules\Export\Xhtml\Blade')
@foreach(['dedication', 'epigraph'] as $compare)
    @foreach($book_contents['front-matter'] as $front_matter)
        @continue(!$front_matter['export'])
        @continue($compare !== ($subclass = $s->getFrontMatterType($front_matter['ID'])))
        <div class="front-matter {!! $subclass !!}" id="{!! $front_matter['post_name'] !!}">
            <div class="front-matter-title-wrap">
                <h3 class="front-matter-number">{{ $s::$frontMatterPos }}</h3>
                <h1 class="front-matter-title">
                    @if($s->showTitle($front_matter['ID']))
                        {!! $front_matter['post_title'] !!}
                    @else
                        <span class="display-none">{!! $front_matter['post_title'] !!}</span> {{-- Preserve auto-indexing in Prince using hidden span --}}
                    @endif
                </h1>
            </div>
            <div class="ugc front-matter-ugc">{!! $front_matter['post_content'] !!}</div>
            @isset($s::$endnotes[$front_matter['ID']])
                @include('export.xhtml.endnotes', ['endnote_id' => $front_matter['ID']])
            @endisset
            @php $s::$frontMatterPos++; @endphp
        </div>
    @endforeach
@endforeach