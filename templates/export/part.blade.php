<div class="part {{ $invisibility }} {{ $introduction }}" id="{{ $slug }}">
	<div class="part-title-wrap">
		<p class="part-number">{{ $number }}</p>
		<h1 class="part-title">{!! $title !!}</h1>
	</div>
	<div class="ugc part-ugc">
		{!! $content !!}
	</div>
	@if( isset( $endnotes ) )
		{!! $endnotes !!}
	@endif
	@if( isset ( $footnotes ) )
		{!! $footnotes !!}
	@endif
</div>
