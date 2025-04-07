<fieldset>
	<legend class="screen-reader-text">{{ __( 'Disable Comments', 'pressbooks' ) }}</legend>
	<input type="radio" id="disable-comments" name="pressbooks_sharingandprivacy_options[disable_comments]" value="1" {{ checked( $disable_comments, 1, false ) }} />
	<label for="disable-comments">{{ __( 'Yes. I want to automatically disable comments, trackbacks and pingbacks on all front matter, chapters and back matter.', 'pressbooks' ) }}</label><br />
	<input type="radio" id="enable-comments" name="pressbooks_sharingandprivacy_options[disable_comments]" value="0" {{ checked( $disable_comments, 0, false ) }} />
	<label for="enable-comments">{{ __( 'No. I want to leave comments, trackbacks and pingbacks enabled on all front matter, chapters and back matter unless I disable them manually.', 'pressbooks' ) }}</label>
</fieldset>
