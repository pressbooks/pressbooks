<div>
  <div style="border: 1px solid #eee;margin:0;padding:0;width: 100%;">
   {!! $representation !!}
  </div>
</div>
<div class="textbox interactive-content">
	<span class="interactive-content__icon"></span>
	<p>{{ __('The interactive version of this H5P content is available at:', 'pressbooks' ) }}
		<a href="{{ $url.$id }}" title="{{ $title }}">{{ $url.$id }}</a>
	</p>
</div>
