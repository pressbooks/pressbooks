<fieldset>
	<legend class="screen-reader-text">{{ __( 'Pressbooks Directory', 'pressbooks' ) }}</legend>
	<input type="radio" id="include-in-directory" name="pb_book_directory_excluded" value="0" {{ checked( $pb_book_directory_excluded, 0, false ) }} />
	<label for="include-in-directory">{{ __( 'Yes. I want this book to be listed in the Pressbooks directory.', 'pressbooks' ) }}</label><br />
	<input type="radio" id="exclude-from-directory" name="pb_book_directory_excluded" value="1" {{ checked( $pb_book_directory_excluded, 1, false ) }} />
	<label for="exclude-from-directory">{{ __( 'No. Exclude this book from the Pressbooks directory.', 'pressbooks' ) }}</label>
</fieldset>
