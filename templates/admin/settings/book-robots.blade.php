<fieldset>
	<legend class="screen-reader-text">{{ __( 'Robots', 'pressbooks' ) }}</legend>
	<input type="checkbox" id="robots-ai" name="pressbooks_robots[discourage-ai]" value="1" {{ checked( 1, $robots['discourage-ai'] ?? 0 ) }} />
	<label for="robots-ai">{{ __( 'Discourage AI from ingesting this book.', 'pressbooks' ) }}</label><br>
	<input type="checkbox" id="robots-crawler" name="pressbooks_robots[discourage-index]" value="1" {{ checked( 1, $robots['discourage-index'] ?? 0 ) }} />
	<label for="robots-crawler">{{ __( 'Discourage crawlers and search engines from indexing this book.', 'pressbooks' ) }}</label>
</fieldset>
