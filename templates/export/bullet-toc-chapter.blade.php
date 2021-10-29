<li class="{{ $post_type }} {{ $subclass }}">
	<a href="#{{ $slug }}">
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
	@if( $subsections )
		<ul class="sections">
			@foreach($subsections as $subsection)
				@include('export/bullet-row', $subsection)
			@endforeach
		</ul>
	@endif
</li>
