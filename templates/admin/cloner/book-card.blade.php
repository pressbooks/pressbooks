<div class="book-card">
	<div class="book-cover">
		<img src="@{{ thumbnailUrl }}" alt=""/>
	</div>
	<div class="book-info">
		<h2><a href="@{{ url }}" target="_blank">@{{ name }}</a></h2>
		<p class="book-details">
			<span class="license">@{{ licenseCode }}&nbsp;</span> |
			<span class="h5p-count">
				<a href="@{{ url }}/h5p-listing" target="_blank">
					@{{ h5pActivities }} {{ __( 'H5P Activities' , 'pressbooks' ) }}
				</a>
			</span>&nbsp;|
			<span class="language">&nbsp;@{{ languageName }}</span>
		</p>
			{{-- TODO: add conditional which only prints if a book has an author --}}
		<p class="author">
			<span class="author">{{ __( 'By', 'pressbooks' ) }} </span> @{{ author }}
		</p>
		{{-- TODO: add conditional which only prints if a book has a long or short description --}}
		<div class="book-description line-clamp">
			@{{{ description }}}
		</div>
		<button
				class="button button-primary select-book"
				onClick="window.selectBookToClone('@{{ url }}')"
			>
				{{ __( 'Select this book' , 'pressbooks' ) }}
		</button>
	</div>
</div>
