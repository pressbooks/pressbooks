<div class="wrap">
	<div class="pb-dashboard-row">
		<div class="pb-dashboard-panel">
			<div class="pb-dashboard-content banner">
				<h2>
					{{ __( 'Welcome to', 'pressbooks' ) }}
					<span class="network-title">{!! $site_name !!}</span>
				</h2>

				<div class="pb-dashboard-image">
					<img
						src="{{ PB_PLUGIN_URL . "assets/dist/images/pb-network-welcome.png" }}"
						alt="{{ __( 'Network welcome art', 'pressbooks' ) }}"
					/>
				</div>
			</div>
		</div>
	</div>
	<div class="pb-dashboard-row">
		@if($invitations->isNotEmpty())
			<div class="pb-dashboard-panel pb-dashboard-invitations">
				<div class="pb-dashboard-content">
					<h2>{{ __( 'Book Invitations', 'pressbooks' ) }}</h2>

					@foreach($invitations as $invitation)
						<div class="invitation">
							<p>{!! sprintf( __( 'You have been invited to join %1$s as %2$s', 'pressbooks' ), $invitation['book_url'], $invitation['role'] ) !!}</p>
							<a class="button button-primary" href="{{ $invitation['accept_link'] }}">{{ __( 'Accept', 'pressbooks' ) }}</a>
						</div>
					@endforeach
				</div>
			</div>
		@endif
	</div>

	<div class="pb-dashboard-row">
		<div class="pb-dashboard-grid">
			<div class="pb-dashboard-panel">
				<div class="pb-dashboard-image">
					<img
						src="{{ PB_PLUGIN_URL . "assets/dist/images/pb-create-book.png" }}"
						alt="{{ __( 'Create a new book art', 'pressbooks' ) }}"
					/>
				</div>

				<div class="pb-dashboard-content">
					<h2>{{ __( 'Create a book', 'pressbooks' ) }}</h2>

					<p>{{ __( 'Create a new book full of engaging content: words, images, audio, video, footnotes, glossary terms, mathematical formula, interactive quizzes, and more.', 'pressbooks' ) }}</p>
				</div>

				<div class="pb-dashboard-action">
					<a class="button button-hero button-primary" href="{{ network_home_url( 'wp-signup.php' ) }}">
						{{ __( 'Create a book', 'pressbooks' ) }}
					</a>
				</div>
			</div>

			<div class="pb-dashboard-panel">
				<div class="pb-dashboard-image">
					<img
						src="{{ PB_PLUGIN_URL . "assets/dist/images/pb-adapt-book.png" }}"
						alt="{{ __( 'Adapt a book art', 'pressbooks' ) }}"
					/>
				</div>

				<div class="pb-dashboard-content">
					<h2>{{ __( 'Adapt a book', 'pressbooks' ) }}</h2>

					<p>{{ __( 'Use our cloning tool to make your own personalized copy of any of the thousands of openly licensed educational books already published with Pressbooks.', 'pressbooks' ) }}</p>
				</div>

				<div class="pb-dashboard-action">
					<a class="button button-hero button-primary" href="{{ admin_url( 'admin.php?page=pb_cloner' ) }}">
						{{ __( 'Adapt a book', 'pressbooks' ) }}
					</a>
				</div>
			</div>
		</div>
	</div>
</div>
