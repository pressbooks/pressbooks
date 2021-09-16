<section class="contributors book-contributors">
@foreach($contributors as $contributor_type)
	<h2 class="contributor__type">{{$contributor_type['title']}}</h2>
		@foreach($contributor_type['records'] as $contributor)
			<div class="contributor">
				<div class="contributor__name__and__links">
					@if ( $contributor['contributor_picture'] )
						<img class="contributor__profile__picture" alt="Contributor photo" title="Photo"
							 src="{{ $contributor['contributor_picture'] }}"/>
					@endif
					<p class="contributor__name">{{ $contributor['name'] }}</p>
					@if ( $contributor['contributor_institution'] )
						<p class="contributor__institution"> {{ $contributor['contributor_institution'] }}</p>
					@endif
					@if ( $contributor['contributor_user_url'] )
						<p class="contributor__website"><a href="{{ $contributor['contributor_user_url'] }}"
															 target="_blank">{{ $contributor['contributor_user_url'] }}</a></p>
					@endif
					<div class="contributor__links">
						@if ( $contributor['contributor_twitter'] )
							<a class="contributor__twitter" href="{{$contributor['contributor_twitter']}}" target="_blank">
								<svg role="img" aria-labelledby="twitter-logo" class="contributor icon-svg">
									<title id="twitter-title-logo">Twitter logo</title>
									<use href="#twitter-icon"/>
								</svg>
							</a>
						@endif
						@if ( $contributor['contributor_linkedin'] )
							<a class="contributor__linkedin" href="{{$contributor['contributor_linkedin']}}" target="_blank">
								<svg role="img" aria-labelledby="linkedin-logo" class="contributor icon-svg">
									<title id="linkedin-title-logo">LinkedIn logo</title>
									<use href="#linkedin-icon"/>
								</svg>
							</a>
						@endif
						@if ( $contributor['contributor_github'] )
							<a class="contributor__github" href="{{$contributor['contributor_github']}}" target="_blank">
								<svg role="img" aria-labelledby="github-logo" class="contributor icon-svg">
									<title id="github-title-logo">GitHub logo</title>
									<use href="#github-icon"/>
								</svg>
							</a>
						@endif
					</div>
				</div>
				<div class="contributor__bio">{!! wp_kses( $contributor['contributor_description'], true ) !!}</div>
			</div>
		@endforeach
@endforeach
</section>
