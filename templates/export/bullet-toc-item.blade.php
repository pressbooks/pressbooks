<li class="{{ $post_type }}{{ $subclass }}">
	<a href="{{ $href }}">
		<span class="toc-chapter-title">{!! $title !!}</span>
		@if( $subtitle )
			<span class="chapter-subtitle">{{ $subtitle }}</span>
		@endif
		@if( $author )
			<span class="chapter-author">{{ $author }}</span>
		@endif
		@if( $license )
			<span class="chapter-license">{{ $license }}</span>
		@endif
	</a>
	@if( count($subsections) > 0 )
		<ul class="sections">
			@foreach($subsections as $subsection)
				@include('export/bullet-section', $subsection)
			@endforeach
		</ul>
	@endif
</li>
