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
									<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
										<image xlink:href="{{ $icons->getIcon('pencil-square') }}"  height="24" width="24"/>
									</svg>
									<a href="{!! $book_info_url !!}">
										{{ __( 'Edit book info', 'pressbooks' ) }}
									</a>
								</li>
							@endif
							@if( $organize_url )
								<li id="organize">
									<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
										<image xlink:href="{{ $icons->getIcon('book-open') }}"  height="24" width="24"/>
									</svg>
									<a href="{{ $organize_url }}">
										{{ __( 'Organize book', 'pressbooks' ) }}
									</a>
								</li>
							@endif
							@if( $themes_url )
								<li id="theme">
									<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
										<image xlink:href="{{ $icons->getIcon('rectangle-group') }}"  height="24" width="24"/>
									</svg>
									<a href="{{ $themes_url }}">
										{{ __( 'Change theme', 'pressbooks' ) }}
									</a>
								</li>
							@endif
							@if( $users_url )
							<li id="users">
								<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
									<image xlink:href="{{ $icons->getIcon('users') }}"  height="24" width="24"/>
								</svg>
								<a href="{{ $users_url }}">
									{{ __( 'Manage users', 'pressbooks' ) }}
								</a>
							</li>
							@endif
							@if( $analytics_url )
								<li id="analytics">
									<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
										<image xlink:href="{{ $icons->getIcon('presentation-chart-bar') }}"  height="24" width="24"/>
									</svg>
									<a href="{{ $analytics_url }}">
										{{ __( 'View Analytics', 'pressbooks' ) }}
									</a>
								</li>
							@endif
							@if( $delete_book_url )
								<li id="delete">
									<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
										<image xlink:href="{{ $icons->getIcon('trash') }}"  height="24" width="24"/>
									</svg>
									<a href="{{ $delete_book_url }}">
										{{ __( 'Delete book', 'pressbooks' ) }}
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
				<h2>{{ __('Support Resources', 'pressbooks') }}</h2>
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
