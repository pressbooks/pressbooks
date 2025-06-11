<fieldset>
	<legend class="screen-reader-text">{{ __( 'Share Latest Export Files', 'pressbooks' ) }}</legend>
	<input type="radio" id="latest_files_public" name="pbt_redistribute_settings[latest_files_public]" value="1" {{ checked( $latest_files_public, 1, false ) }} />
	<label for="latest_files_public">{{ __( 'Yes. I would like the latest export files to be available on the homepage for free, to everyone.', 'pressbooks' ) }}</label><br />
	<input type="radio" id="latest_files_private" name="pbt_redistribute_settings[latest_files_public]" value="0" {{ checked( $latest_files_public, 0, false ) }} />
	<label for="latest_files_private">{{ __( 'No. I would like the latest export files to only be available to administrators.', 'pressbooks' ) }}</label>
</fieldset>
