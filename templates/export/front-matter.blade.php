<div class="front-matter {{ $subclass }}" id="{{ $slug }}">
	<div class="front-matter-title-wrap">
		<p class="front-matter-number">{{ $front_matter_number }}</p>
		<h1 class="front-matter-title">{!! $title !!}</h1>
		@if( isset( $is_new_buckram ) && $is_new_buckram )
			@if( $output_short_title )
				<p class="short_title">{{ $short_title  }}</p>
			@endif
			@if( $subtitle )
				<p class="chapter-subtitle">{{ $subtitle }}</p>
			@endif
			@if( $author )
				<p class="chapter-author">{{ $author }}</p>
			@endif
		@endif
	</div>
	<div class="ugc front-matter-ugc">
		@if( isset( $is_new_buckram ) && ! $is_new_buckram )
			@if( $output_short_title )
				<p class="short_title">{{ $short_title  }}</p>
			@endif
			@if( $subtitle )
				<p class="chapter-subtitle">{{ $subtitle }}</p>
			@endif
			@if( $author )
				<p class="chapter-author">{{ $author }}</p>
			@endif
		@endif
		{!! $content !!}
	</div>
	@if( isset( $endnotes ) )
		{{ $endnotes }}
	@endif
	@if( isset ( $footnotes ) )
		{{ $footnotes }}
	@endif
</div>
