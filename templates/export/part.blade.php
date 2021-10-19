<div class="part {{ $invisibility }} {{ $introduction_class }}" id="{{ $id }}">
	<div class="part-title-wrap">
		<p class="part-number">{{ $number }}</p>
		<h1 class="part-title">{!! $title !!}</h1>
	</div>
	@if ( $wrap_part )
		<div class="ugc part-ugc">
			{!!  $content !!}
		</div>
	@else
		{!!  $content !!}
	@endif
</div>


