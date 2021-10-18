<div class="front-matter {{ $subclass }}" id="{{ $slug }}">
	<div class="front-matter-title-wrap">
		<p class="front-matter-number">{{ $front_matter_number }}</p>
		<h1 class="front-matter-title">{!! $title !!}</h1>
	</div>
	<div class="ugc front-matter-ugc">
		{!! $content !!}
	</div>
	@if( isset( $endnotes ) )
		{{ $endnotes }}
	@endif
	@if( isset ( $footnotes ) )
		{{ $footnotes }}
	@endif
</div>
