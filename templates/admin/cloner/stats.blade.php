<div class="pb-cloner wrap">
	<div class="pb-cloner-section main">
		<h1 class="page-title">{{ __( 'Book Info', 'pressbooks' ) }}</h1>
		<p><strong>{{ __( 'Title', 'pressbooks' ) }}:</strong> {{ $book->blogname }}</p>
		<p><strong>{{ __( 'URL', 'pressbooks' ) }}:</strong> {{ $book->siteurl }}</p>
		<p><strong>{{ __( 'Created', 'pressbooks' ) }}:</strong> {{ $book->registered }}</p>
		<p><strong>{{ __( 'Total # of clones', 'pressbooks' ) }}:</strong> {{ count($cloning_stats) }}</p>
	</div>
@if($cloning_stats)
<div class="pb-cloner-section stats">
	<h2 class="section-title">{{ __( 'List of clones', 'pressbooks' ) }}</h2>
	<p>{{ __('Pressbooks added the ability to reliably track successful clones in early 2023. This list may not include all clones made before that time.', 'pressbooks' ) }}</p>
	<p>{{ __('The URLs here refer to external web addresses which may no longer be active or which may be hosting other content. Please use caution if you choose to visit any of the URLs listed below.', 'pressbooks' ) }}</p>
	<ol>
		@foreach($cloning_stats as $clone)
			<li class="clone-info">
				<strong>{{ $clone->target_book_name}}</strong><br/>
				<strong>{{ __( 'URL', 'pressbooks' ) }}:</strong> {{ $clone->target_book_url }}<br/>
				<strong>{{ __( 'Date of clone', 'pressbooks' ) }}:</strong> {{ $clone->created_at }}
			</li>
		@endforeach
	</ol>
</div>
@endif
</div>
