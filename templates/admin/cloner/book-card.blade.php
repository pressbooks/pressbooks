<div class="book-card">
	<div class="book-cover">
		<img src="@{{ thumbnailUrl }}" />
	</div>
	<div class="book-info">
		<h2><a href="@{{ url }}" target="_blank">@{{ name }}</a></h2>
		<p>
			<span>@{{ licenseCode }}&nbsp;</span> |
			<span>
				<a href="@{{ url }}/h5p-listing" target="_blank">
					@{{ h5pActivities }} {{ __( 'H5P Activities' , 'pressbooks' ) }}
				</a>
			</span>&nbsp;|
			<span>&nbsp;@{{ languageName }}</span>
		</p>

		<div class="book-extra-info">
			<p>
				<span>{{ __( 'Authors', 'pressbooks' ) }}:</span> @{{ author }}
			</p>
		</div>
		<div class="book-description line-clamp">
			@{{{ description }}}
		</div>
		<div>
			<button
				class="button button-primary"
				onClick="window.selectBookToClone('@{{ url }}')"
			>
				{{ __( 'Select' , 'pressbooks' ) }}
			</button>
		</div>
	</div>
</div>
