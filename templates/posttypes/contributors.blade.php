@foreach($contributors as $contributor_type)
	<div class="contributors page">
		<h3>{{$contributor_type['title']}}</h3>
		@foreach($contributor_type['records'] as $contributor)
			<div class="contributor">
				<div class="contributor_name_and_links">
					@if ( $contributor['contributor_picture'] )
						<img class="contributor_profile_picture" alt="Contributor photo" title="Photo"
							 src="{{ $contributor['contributor_picture'] }}"/>
					@endif
					<span class="contributor_name">{{ $contributor['name'] }}</span>
					@if ( $contributor['contributor_institution'] )
						<span class="contributor_institution"> {{ $contributor['contributor_institution'] }}</span>
					@endif
					@if ( $contributor['contributor_user_url'] )
						<span class="contributor_website"><a href="{{ $contributor['contributor_user_url'] }}"
															 target="_blank">{{ $contributor['contributor_user_url'] }}</a></span>
					@endif
					<div class="contributor_links">
						@if ( $contributor['contributor_twitter'] )
							<a class="contributor_twitter" href="{{$contributor['contributor_twitter']}}" target="_blank">
								<svg role="img" aria-labelledby="twitter-logo" class="contributor icon-svg">
									<title id="twitter-title-logo">Twitter logo</title>
									<use href="#twitter-icon"/>
								</svg>
							</a>
						@endif
						@if ( $contributor['contributor_linkedin'] )
							<a class="contributor_linkedin" href="{{$contributor['contributor_linkedin']}}" target="_blank">
								<svg role="img" aria-labelledby="linkedin-logo" class="contributor icon-svg">
									<title id="linkedin-title-logo">LinkedIn logo</title>
									<use href="#linkedin-icon"/>
								</svg>
							</a>
						@endif
						@if ( $contributor['contributor_github'] )
							<a class="contributor_github" href="{{$contributor['contributor_github']}}" target="_blank">
								<svg role="img" aria-labelledby="github-logo" class="contributor icon-svg">
									<title id="github-title-logo">GitHub logo</title>
									<use href="#github-icon"/>
								</svg>
							</a>
						@endif
					</div>
				</div>
				<div class="contributor_bio">{!! wp_kses( $contributor['contributor_description'], true ) !!}</div>
			</div>
		@endforeach
	</div>
@endforeach
