<section class="contributors book-contributors">
	@foreach($contributors as $contributor_type)
		<h2 class="contributor__type">{{$contributor_type['title']}}</h2>
		@foreach($contributor_type['records'] as $contributor)
			@include('posttypes.contributor',[ 'contributor' => $contributor, 'exporting' => $exporting ])
		@endforeach
	@endforeach
</section>
