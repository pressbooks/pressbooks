<div class="book-card">
	<div class="book-cover">
		<img src="@{{ thumbnailUrl }}" alt="@{{{ name }}} cover"/>
	</div>
	<div class="book-info">
		<h2><a href="@{{ url }}" target="_blank">@{{{ name }}}</a></h2>
		<p class="book-details">
			<span class="license">@{{ licenseCode }}</span>
			<span class="h5p-count"><a href="@{{ url }}/h5p-listing" target="_blank">@{{ h5pActivities }} {{ __( 'H5P Activities', 'pressbooks' ) }}</a></span>
			<span class="language">@{{ languageName }}</span>
		</p>
		@{{#author.length}}
			<p class="author">
				<span>{{ __( 'By', 'pressbooks' ) }} </span>
				@{{#author}}
					<span class="author-name">@{{{.}}}</span>
				@{{/author}}
			</p>
		@{{/author.length}}
		@{{#description}}
			<div class="book-description line-clamp">
				@{{{ description }}}
			</div>
		@{{/description}}
		<button
			class="button button-primary select-book"
			onClick="window.selectBookToClone('@{{ url }}')"
		>
			{{ __( 'Select this book' , 'pressbooks' ) }}
		</button>
	</div>
</div>
