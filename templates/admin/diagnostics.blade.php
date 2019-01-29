{!! $notices or '' !!}
<div class="wrap">
	<h1>{{ __( 'Diagnostics', 'pressbooks' ) }}</h1>
	<p>{{ __( 'Please submit this information with any bug reports.', 'pressbooks' ) }}</p>
	<textarea style="width: 800px; max-width: 100%; height: 600px; background: #fff; font-family: monospace;" readonly="readonly" onclick="this.focus(); this.select()" title="{{ _e( 'To copy the system info, click below then press Ctrl + C (PC) or Cmd + C (Mac).', 'pressbooks' ) }}">{{ $output }}</textarea>
	@if($is_book)
		<h2>{{ __( 'View Source', 'pressbooks' ) }}</h2>
		<p>{!! sprintf( __( '<a href="%s">View your book&rsquo;s XHTML source</a> to diagnose issues you may be encountering with your PDF exports.', 'pressbooks' ), home_url() . '/format/xhtml?debug=prince' ) !!}</p>
		<h2>{{ __( 'Regenerate Webbook Stylesheet', 'pressbooks' ) }}</h2>
		<p>{{ __( 'If your webbook stylesheet has issues, it may help to regenerate it.', 'pressbooks' ) }}</p>
		<p><form action="{{ $regenerate_webbook_stylesheet_url }}" method="post"><input type="submit" name="submit" id="submit" class="button button-primary" value="{{ __( 'Regenerate Stylesheet', 'pressbooks' ) }}" /></form></p>
	@endif
</div>
