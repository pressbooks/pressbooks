<div class="chapter {{ $subclass }}" id="{{ $id }}">
	<div class="chapter-title-wrap">
		<p class="chapter-number">{{ $number }}</p>
		<h1 class="chapter-title">{!! $title !!}</h1>
		@if( $is_new_buckram )
			@if( $authors )
				<p class="chapter-author">{{ $authors }}</p>
			@endif
			@if( $subtitle )
				<p class="chapter-subtitle">{!! $subtitle !!}</p>
			@endif
		@endif
		{!! $after_title !!}
	</div>
	<div class="ugc chapter-ugc">
		@if( ! $is_new_buckram )
			@if( $authors )
				<p class="chapter-author">{{ $authors }}</p>
			@endif
			@if( $subtitle )
				<p class="chapter-subtitle">{!! $subtitle !!}</p>
			@endif
		@endif
			{!! $content !!}
	</div>
	{!! $append_content !!}
</div>
