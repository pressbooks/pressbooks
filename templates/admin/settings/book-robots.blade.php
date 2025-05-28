<fieldset>
	<legend class="screen-reader-text">{{ __( 'Robots', 'pressbooks' ) }}</legend>
	<input type="checkbox" id="robots-ai" name="pressbooks_robots[discourage-ai]" value="1" @if ($robots['discourage-ai']) checked @endif />
	<label for="robots-ai">{{ __( 'Discourage AI from ingesting this book.', 'pressbooks' ) }}</label><br>
	<input type="checkbox" id="robots-crawler" name="pressbooks_robots[discourage-index]" value="1" @if($robots['discourage-index']) checked @endif />
	<label for="robots-crawler">{{ __( 'Discourage crawlers and search engines from indexing this book.', 'pressbooks' ) }}</label>
</fieldset>
