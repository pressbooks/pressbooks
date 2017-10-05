<div class="part {{ $subclass }}" id="{{ $slug }}">
    <div class="part-title-wrap">
        <h3 class="part-number">{{ $i }}</h3>
        <h1 class="part-title">{!! $title !!}</h1>
    </div>
    @if(!empty($content))
        <div class="ugc part-ugc">{!! $content !!}</div>
    @else
        <div></div>
    @endif
</div>
