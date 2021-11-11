<div class="contributor">
	<div class="contributor__name__and__links">
		@if ( $contributor['contributor_picture'] )
			<img class="contributor__profile__picture" alt="Contributor photo" src="{{ $contributor['contributor_picture'] }}"/>
		@endif
		<div class="{{ $contributor['contributor_picture']  ? 'contributor__info__small' : 'contributor__info__large' }}">
			<p class="contributor__name">
				@if ( !isset( $exporting ) )
					<span class="screen-reader-text">name: </span>
				@endif
				{{ $contributor['name'] }}
			</p>
			@if ( $contributor['contributor_institution'] )
				<p class="contributor__institution">
					@if ( !isset( $exporting ) )
						<span class="screen-reader-text">institution: </span>
					@endif
					{{ $contributor['contributor_institution'] }}
				</p>
			@endif
			@if ( $contributor['contributor_user_url'] )
				<p class="contributor__website">
					@if ( !isset( $exporting ) )
						<span class="screen-reader-text">website: </span>
					@endif
					<a href="{{ $contributor['contributor_user_url'] }}" target="_blank">{{ $contributor['contributor_user_url'] }}</a>
				</p>
			@endif
			@if ( $contributor['contributor_twitter'] || $contributor['contributor_linkedin'] || $contributor['contributor_github'])
				<div class="contributor__links">
					@if ( $contributor['contributor_twitter'] )
						@if ( isset( $exporting ) )
							<p class="contributor__link">
								<a href="{{$contributor['contributor_twitter']}}" target="_blank">{{$contributor['contributor_twitter']}}</a>
							</p>
						@else
							<a href="{{$contributor['contributor_twitter']}}" target="_blank">
								<svg role="img" aria-labelledby="twitter-logo-{{ $key }}" class="contributor__icon-svg">
									<title id="twitter-logo-{{ $key }}">Twitter logo</title>
									<use href="#twitter-icon"/>
								</svg>
							</a>
						@endif
					@endif
					@if ( $contributor['contributor_linkedin'] )
						@if ( isset( $exporting ) )
							<p class="contributor__link">
								<a href="{{$contributor['contributor_linkedin']}}" target="_blank">{{$contributor['contributor_linkedin']}}</a>
							</p>
						@else
							<a href="{{$contributor['contributor_linkedin']}}" target="_blank">
								<svg role="img" aria-labelledby="linkedin-logo-{{ $key }}" class="contributor__icon-svg">
									<title id="linkedin-logo-{{ $key }}">LinkedIn logo</title>
									<use href="#linkedin-icon"/>
								</svg>
							</a>
						@endif
					@endif
					@if ( $contributor['contributor_github'] )
						@if ( isset( $exporting ) )
							<p class="contributor__link">
								<a href="{{$contributor['contributor_github']}}" target="_blank">{{$contributor['contributor_github']}}</a>
							</p>
						@else
							<a href="{{$contributor['contributor_github']}}" target="_blank">
								<svg role="img" aria-labelledby="github-logo-{{ $key }}" class="contributor__icon-svg">
									<title id="github-logo-{{ $key }}">GitHub logo</title>
									<use href="#github-icon"/>
								</svg>
							</a>
						@endif
					@endif
				</div>
			@endif
		</div>
	</div>
	@if ( $contributor['contributor_description'] )
		<div class="contributor__bio">{!! apply_filters( 'the_content', $contributor['contributor_description'] ) !!}</div>
	@endif
</div>
