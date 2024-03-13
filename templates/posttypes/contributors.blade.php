<section class="contributors book-contributors">
	@foreach($contributors as $contributor_type)
		<h2 class="contributor__type">{{__( $contributor_type['title'], 'pressbooks' ) }} </h2>
		@foreach($contributor_type['records'] as $contributor)
			@include('posttypes.contributor',[ 'contributor' => $contributor, 'key' => str_random(), 'exporting' => $exporting ])
		@endforeach
	@endforeach
</section>
