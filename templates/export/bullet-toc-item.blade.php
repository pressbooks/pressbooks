<li class="{{ $post_type }} {{ $subclass }}">
	<a href="{{ $href }}">
		<span class="toc-chapter-title">{{ $title }}</span>
	</a>
	@if( $subtitle )
		<span class="chapter-title">{{ $subtitle }}</span>
	@endif
	@if( $author )
		<span class="chapter-author">{{ $author }}</span>
	@endif
	@if( $author )
		<span class="chapter-license">{{ $license }}</span>
	@endif
	@if( count($subsections) > 0 )
		<ul class="sections">
			@foreach($subsections as $subsection)
				@include('export/bullet-section', $subsection)
			@endforeach
		</ul>
	@endif
</li>
