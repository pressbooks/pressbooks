<div class="back-matter {{ $subclass }}" id="{{ $slug }}">
	<div class="back-matter-title-wrap">
		<p class="back-matter-number">{{ $back_matter_number }}</p>
		<h1 class="back-matter-title">{!! $title !!}</h1>

		@if( $is_new_buckram )
			@if( $output_short_title )
				<p class="short-title">{{ $short_title }}</p>
			@endif
			@if( $subtitle)
				<p class="chapter-subtitle">{{ $subtitle }}</p>
			@endif
			@if( $author )
				<p class="chapter-author">{{ $author }}</p>
			@endif
		@endif
	</div>
	<div class="ugc back-matter-ugc">
		@if( ! $is_new_buckram )
			@if( $output_short_title )
				<p class="short-title">{{ $short_title }}</p>
			@endif
			@if( $subtitle)
				<p class="chapter-subtitle">{{ $subtitle }}</p>
			@endif
			@if( $author )
				<p class="chapter-author">{{ $author }}</p>
			@endif
		@endif
		{!! $content !!}
	</div>
	@if( isset( $append_back_matter_content ) )
		{{ $append_back_matter_content }}
	@endif
	@if( isset( $endnotes ) )
		{{ $endnotes }}
	@endif
	@if( isset ( $footnotes ) )
		{{ $footnotes }}
	@endif
</div>
