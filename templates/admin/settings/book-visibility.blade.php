<fieldset>
	<legend class="screen-reader-text">{{ __( 'Book Visibility', 'pressbooks' ) }}</legend>
	<input type="radio" id="blog-public" name="blog_public" value="1" {{ checked( $blog_public, 1, false ) }} />
	<label for="blog-public">{{ __( 'Public. I would like this book to be visible to everyone.', 'pressbooks' ) }}</label><br />
	<input type="radio" id="blog-norobots" name="blog_public" value="0" {{ checked( $blog_public, 0, false ) }} />
	<label for="blog-norobots">{{ __( 'Private. I would like this book to be accessible only to people I invite.', 'pressbooks' ) }}</label>
</fieldset>
