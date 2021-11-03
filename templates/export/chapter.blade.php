<div class="chapter {{ $subclass }}" id="{{ $slug }}" title="{{ $sanitized_title }}">
	<div class="chapter-title-wrap">
		<p class="chapter-number">{{ $number }}</p>
		<h2 class="chapter-title">{!! $title !!}</h2>
		@if(  isset( $is_new_buckram ) && $is_new_buckram )
			@include('export/sub-author-partial')
		@endif
	</div>
	<div class="ugc chapter-ugc">
		@if(  isset( $is_new_buckram ) && ! $is_new_buckram )
			@include('export/sub-author-partial')
		@endif
		{!! $content !!}
	</div>
	@if( isset( $append_content ) )
		{!! $append_content !!}
	@endif
	@if( isset( $endnotes ) )
		{!! $endnotes !!}
	@endif
	@if( isset ( $footnotes ) )
		{!! $footnotes !!}
	@endif
</div>
