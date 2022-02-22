<div id="title-page">
	@if( isset( $content ) )
		{!! $content !!}
	@else
		<h1 class="title">{{ $title }}</h1>
		<h2 class="subtitle">{{ $subtitle }}</h2>
		@if( $authors )
			<p class="author">{{ $authors }}</p>
		@endif
		@if( $editors )
			<p class="author">{{ $editors }}</p>
		@endif
		@if( $translators )
			<p class="author">{{ $translators }}</p>
		@endif
		@if( $illustrators )
			<p class="author">{{ $illustrators }}</p>
		@endif
		@if( $contributors )
			<p class="author">{{ $contributors }}</p>
		@endif
		@if( $logo )
			<div class="publisher-logo">
				<img src="{{ $logo }}" alt="{{ __( 'Publisher Logo', 'pressbooks' ) }}" />
			</div>
		@endif
		<p class="publisher">{{ $publisher }}</p>
		<p class="publisher-city">{{ $publisher_city }}</p>
	@endif
</div>
