<fieldset>
	<legend class="screen-reader-text">{{ __( 'Private Content', 'pressbooks' ) }}</legend>
	<p id="permissive_private_content-description">{{ __( 'Who can see private front matter, chapters and back matter?', 'pressbooks' ) }}</p>
	<input type="radio" id="standard-private-content" name="permissive_private_content" aria-describedby="permissive_private_content-description" value="0" {{ checked( get_option( 'permissive_private_content' ), 0, false ) }} />
	<label for="standard-private-content">{{ __( 'Only logged in editors and administrators.', 'pressbooks' ) }}</label><br />
	<input type="radio" id="permissive-private-content" name="permissive_private_content" aria-describedby="permissive_private_content-description" value="1" {{ checked( get_option( 'permissive_private_content' ), 1, false ) }} />
	<label for="permissive-private-content">{{ __( 'All logged in users including subscribers.', 'pressbooks' ) }}</label>
</fieldset>
