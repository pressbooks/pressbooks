<div id="toc">
	<h1>{{ $title }}</h1>
	<ul>
		@foreach($toc as $bullet)
			{!! $bullet !!}
		@endforeach
	</ul>
</div>
