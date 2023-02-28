<div class="book-dash wrap">
	<div class="pb-dashboard-row">
		<div class="pb-dashboard-panel">
			<div class="pb-dashboard-content">
				<h2 class="site-title">{!! $site_name !!}</h2>
				<a class="visit-book" href="{{ $book_url }}">
					{{ __( 'Visit book', 'pressbooks' ) }}
				</a>
			</div>
		</div>
	</div>
	@if ( ! $is_current_user_subscriber )
		<div class="pb-dashboard-row">
			<div class="pb-dashboard-grid">
				<div class="pb-dashboard-panel pb-dashboard-grid">
					<div class="pb-book-cover">
						<img src="{{ $book_cover }}" alt="{!! $site_name !!} cover"/>
					</div>
					<div class="pb-dashboard-content">
						<ul class="pb-dashboard-action">
							@if( $book_info_url )
								<li id="book_info">
									<a href="{!! $book_info_url !!}">
										<i class="pb-heroicons pb-heroicons-pencil-square"></i>
										<span>{{ __( 'Edit book info', 'pressbooks' ) }}</span>
									</a>
								</li>
							@endif
							@if( $organize_url )
								<li id="organize">
									<a href="{{ $organize_url }}">
										<i class="pb-heroicons pb-heroicons-book-open"></i>
										<span>{{ __( 'Organize book', 'pressbooks' ) }}</span>
									</a>
								</li>
							@endif
							@if( $themes_url )
								<li id="theme">
									<a href="{{ $themes_url }}">
										<i class="pb-heroicons pb-heroicons-sparkles"></i>
										<span>{{ __( 'Change theme', 'pressbooks' ) }}</span>
									</a>
								</li>
							@endif
							@if( $users_url )
							<li id="users">
								<a href="{{ $users_url }}">
									<i class="pb-heroicons pb-heroicons-users"></i>
									<span>{{ __( 'Manage users', 'pressbooks' ) }}</span>
								</a>
							</li>
							@endif
							@if( $analytics_url )
								<li id="analytics">
									<a href="{{ $analytics_url }}">
										<i class="pb-heroicons pb-heroicons-presentation-chart-bar"></i>
										<span>{{ __( 'View Analytics', 'pressbooks' ) }}</span>
									</a>
								</li>
							@endif
							@if( $delete_book_url )
								<li id="delete">
									<a href="{{ $delete_book_url }}">
										<i class="pb-heroicons pb-heroicons-trash"></i>
										<span>{{ __( 'Delete book', 'pressbooks' ) }}</span>
									</a>
								</li>
							@endif
						</ul>
					</div>
				</div>
				@if( $write_chapter_url || $import_content_url )
					<div class="pb-dashboard-panel">
						<div class="pb-dashboard-image">
							<img
									src="{{ PB_PLUGIN_URL . "assets/dist/images/pb-write.png" }}"
									alt="{{ __( 'Write a new chapter art', 'pressbooks' ) }}"
							/>
						</div>
						<div class="pb-dashboard-content">
							<div class="pb-dashboard-action">
								@if( $write_chapter_url )
									<a class="button button-hero button-primary" href="{{ $write_chapter_url }}">
										{{ __( 'Write a new chapter', 'pressbooks' ) }}
									</a>
								@endif
								@if( $import_content_url )
									<a class="button button-hero" href="{{ $import_content_url }}">
										{{ __( 'Import Content', 'pressbooks' ) }}
									</a>
								@endif
							</div>
						</div>
					</div>
				@endif
			</div>
		</div>
	@endif
	<div class="pb-dashboard-row">
		<div class="pb-dashboard-panel">
			<div class="pb-dashboard-content">
				<h2>{{ __('Support resources', 'pressbooks') }}</h2>
				{{-- TODO: add link to new YouTube playlist. --}}
				<ul class="horizontal">
					<li class="resources" id="getting-started">
						<a href="https://youtube.com/playlist?list=PLMFmJu3NJhevTbp5XAbdif8OloNhqOw5n" target="_blank">
							<img
								src="{{ PB_PLUGIN_URL . "assets/dist/images/pb-getting-started.png" }}"
								alt=""
							/>
							{{ __('Getting started with Pressbooks', 'pressbooks' )}}
						</a>
						<p>{{ __( 'Watch a short video series on how to get started with Pressbooks.', 'pressbooks' ) }}</p>
					</li>
					<li class="resources" id="pressbooks-guide">
						<a href="https://guide.pressbooks.com" target="_blank">
							<img
								src="{{ PB_PLUGIN_URL . "assets/dist/images/pb-guide.png" }}"
								alt=""
							/>
							{{ __('Pressbooks user guide', 'pressbooks' )}}
						</a>
						<p>{{ __( 'Find help and how-tos for your publishing project in this detailed handbook.', 'pressbooks' ) }}</p>
					</li>
					<li class="resources" id="forum">
						<a href="https://pressbooks.community" target="_blank">
							<img
								src="{{ PB_PLUGIN_URL . "assets/dist/images/pb-forum.png" }}"
								alt=""
							/>
							{{ __('Pressbooks community forum', 'pressbooks' ) }}
						</a>
						<p>{{ __( 'Discuss Pressbooks related questions with other users in our public forum.', 'pressbooks' ) }}</p>
					</li>
					<li class="resources" id="webinars">
						<a href="https://pressbooks.com/webinars" target="_blank">
							<img
								src="{{ PB_PLUGIN_URL . "assets/dist/images/pb-webinars.png" }}"
								alt=""
							/>
							{{ __('Pressbooks training webinars', 'pressbooks') }}
						</a>
						<p>{{ __( 'Register for free webinars to learn about Pressbooks features and best practices.', 'pressbooks' ) }}</p>
					</li>
				</ul>
			</div>
		</div>
	</div>
</div>
