<div class="pb-cloner wrap">
	<div class="pb-cloner-section main">
		<h1 class="page-title">{{ __( 'Book Info', 'pressbooks' ) }}</h1>
		<ul>
			<li>{{ __( 'Title', 'pressbooks' ) }}: {{ $book->blogname }}</li>
			<li>{{ __( 'URL', 'pressbooks' ) }}: {{ $book->siteurl }}</li>
			<li>{{ __( 'Created', 'pressbooks' ) }}: {{ $book->registered }}</li>
			<li>{{ __( 'Total # of clones', 'pressbooks' ) }}: {{ count($cloning_stats) }}</li>
		</ul>
	</div>
</div>
@if($cloning_stats)
<div class="pb-cloner stats wrap">
	<h2 class="section-title">{{ __( 'Clones made from this book:', 'pressbooks' ) }}</h2>
	<ol>
		@foreach($cloning_stats as $clone)
			<li>
				<strong>{{ $clone->target_book_name}}</strong>
				<ul>
					<li>{{ __( 'URL', 'pressbooks' ) }}: {{ $clone->target_book_url }}</li>
					<li>{{ __( 'Cloned at', 'pressbooks' ) }}: {{ $clone->created_at }}</li>
				</ul>
			</li>
		@endforeach
	</ol>
</div>
@endif
