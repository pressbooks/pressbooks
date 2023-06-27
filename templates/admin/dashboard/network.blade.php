<div class="wrap">
	<div class="pb-dashboard-row">
		<div class="pb-dashboard-panel">
			<div class="pb-dashboard-content">
				<h1 class="screen-reader-text">{{ __('Network Manager Dashboard', 'pressbooks')  }}</h1>
				<h2>
					{{ __( 'Welcome to', 'pressbooks' ) }}
					<span class="network-title">{!! $network_name !!}</span>
				</h2>
				<a class="visit-homepage" href="{{ $network_url }}">
					{{ __( 'Visit network homepage', 'pressbooks' ) }}
				</a>
			</div>
		</div>
	</div>
	<div class="pb-dashboard-row">
		<div class="pb-dashboard-panel">
			<div class="pb-dashboard-content">
				<div class="flex-wide">
					<p>
						{!! sprintf( __( 'Your network has %s books and %s users. ', 'pressbooks' ), "<strong>{$total_books}</strong>", "<strong>{$total_users}</strong>" ) !!}
					</p>
					@if( $network_analytics_active )
						<a
							class="button button-primary"
							href="{!! network_admin_url( 'admin.php?page=pb_network_analytics_admin' ) !!}"
						>
							{{ __( 'Explore stats', 'pressbooks' ) }}
						</a>
					@endif
				</div>
				<div class="wp-header-end"></div>
			</div>
		</div>
	</div>
	<div class="pb-dashboard-row">
		<div class="pb-dashboard-panel">
			<div class="pb-dashboard-content network-checklist">
				<h2>{{ __( 'Ready to Launch Checklist', 'pressbooks' ) }}</h2>
				@if($network_checklist)
					<ul>
						@foreach($network_checklist as $item)
							<li x-data="{ isChecked: false }" :class="{ 'checked': isChecked }">
								<label>
									<input
										type="checkbox"
										x-model="isChecked"
										@if($item['checked'])
											checked
										@endif
									/>
									<div>
										<a href="{{ $item['link'] }}">{{ $item['title'] }}</a>
										<span>
											{{ $item['description'] }}
										</span>
									</div>
								</label>
							</li>
						@endforeach
					</ul>
				@endif
			</div>
		</div>
	</div>
	<!-- temp div this will be shown when the list is completed -->
	<div class="pb-dashboard-row">
		<div class="pb-dashboard-panel">
			<div class="pb-dashboard-content network-checklist">
				<h2>{{ __( 'Ready to Launch Checklist', 'pressbooks' ) }}</h2>
				<div>
					<svg width="136" height="136" viewBox="0 0 136 136" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M68 136C30.4779 136 0 105.522 0 68C0 30.4779 30.4779 0 68 0C105.522 0 136 30.4779 136 68C136 105.522 105.522 136 68 136ZM68 10.4615C36.2667 10.4615 10.4615 36.2667 10.4615 68C10.4615 99.7333 36.2667 125.538 68 125.538C99.7333 125.538 125.538 99.7333 125.538 68C125.538 36.2667 99.7333 10.4615 68 10.4615ZM62.7692 94.1538C61.3744 94.1538 60.0492 93.5959 59.0728 92.6195L43.3805 76.9272C41.358 74.9046 41.358 71.5569 43.3805 69.5344C45.4031 67.5118 48.7508 67.5118 50.7733 69.5344L62.0718 80.8328L84.6687 49.239C86.3426 46.8677 89.6205 46.3097 91.9918 48.0533C94.3631 49.7272 94.921 53.0051 93.1774 55.3764L67.0236 91.9918C66.1169 93.2472 64.7221 94.0144 63.1877 94.1538C63.0482 94.1538 62.9087 94.1538 62.7692 94.1538Z" fill="#27AE60"/>
					</svg>
				</div>
				<h2>{{ __( 'Congratulations', 'pressbooks' ) }}</h2>
				<p>{{ __( 'You are ready to launch and can invite users to your network. Please take this survey about your onboarding experience.', 'pressbooks' ) }}</p>
				<a href="#">{{ __( 'Return to checklist', 'pressbooks' ) }}</a>
			</div>
		</div>
	</div>
	<!-- temp div -->
	<div class="pb-dashboard-row">
		<div class="pb-dashboard-grid">
			<div class="pb-dashboard-panel">
				<div class="pb-dashboard-content">
					<h2>{{ __( 'Update homepage', 'pressbooks' ) }}</h2>

					<div class="pb-dashboard-flex">
						<img
							class="pb-dashboard-flex-image"
							src="{{ PB_PLUGIN_URL . "assets/dist/images/pb-root-site.png" }}"
							alt="{{ __( 'Update homepage art', 'pressbooks' ) }}"
						/>

						<ul class="actions">
							<li>
								<a
									href="{!! admin_url( 'customize.php?return=' . network_admin_url() ) !!}"
								>
									<i class="pb-heroicons pb-heroicons-sparkles"></i>
									<span>{{ __( 'Customize network appearance', 'pressbooks' ) }}</span>
								</a>
							</li>
							<li>
								<a
									href="{!! admin_url( 'edit.php?post_type=page' ) !!}"
								>
									<i class="pb-heroicons pb-heroicons-pencil-square"></i>
									<span>{{ __( 'Create or edit pages', 'pressbooks' ) }}</span>
								</a>
							</li>
							@if( $koko_analytics_active )
								<li>
									<a
										href="{!! admin_url( 'index.php?page=koko-analytics' ) !!}"
									>
										<i class="pb-heroicons pb-heroicons-presentation-chart-bar"></i>
										<span>{{ __( 'View homepage analytics', 'pressbooks' ) }}</span>
									</a>
								</li>
							@endif
						</ul>
					</div>
				</div>
			</div>

			<div class="pb-dashboard-panel">
				<div class="pb-dashboard-content">
					<h2>{{ __( 'Administer network', 'pressbooks' ) }}</h2>

					<div class="pb-dashboard-flex">
						<img
							class="pb-dashboard-flex-image"
							src="{{ PB_PLUGIN_URL . "assets/dist/images/pb-network-settings.png" }}"
							alt="{{ __( 'Administer network art', 'pressbooks' ) }}"
						/>

						<ul class="actions">
							<li>
								<a
									href="{!! network_admin_url( $network_analytics_active ? 'settings.php?page=pb_network_analytics_options' : 'settings.php' ) !!}"
								>
									<i class="pb-heroicons pb-heroicons-cog-8-tooth"></i>
									<span>{{ __( 'Adjust network settings', 'pressbooks' ) }}</span>
								</a>
							</li>
							<li>
								<a
									href="{!! network_admin_url( $network_analytics_active ? 'sites.php?page=pb_network_analytics_booklist' : 'sites.php' ) !!}"
								>
									<i class="pb-heroicons pb-heroicons-book-open"></i>
									<span>{{ __( 'View book list', 'pressbooks' ) }}</span>
								</a>
							</li>
							<li>
								<a
									href="{!! network_admin_url( $network_analytics_active ? 'users.php?page=pb_network_analytics_userlist' : 'users.php' ) !!}"
								>
									<i class="pb-heroicons pb-heroicons-users"></i>
									<span>{{ __( 'View user list', 'pressbooks' ) }}</span>
								</a>
							</li>
						</ul>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="pb-dashboard-row">
		<div class="pb-dashboard-panel">
			<div class="pb-dashboard-content">
				<h2>{{ __('Support resources', 'pressbooks') }}</h2>
				<ul class="horizontal">
					<li class="resources" id="pressbooks-guide">
						<a href="https://networkmanagerguide.pressbooks.com/" target="_blank">
							<img
								src="{{ PB_PLUGIN_URL . "assets/dist/images/pb-network-guide.png" }}"
								alt=""
							/>
							{{ __('Network manager guide', 'pressbooks' )}}
						</a>
						<p>{{ __( 'Learn how to administer your Pressbooks network from our comprehensive how-to guide.', 'pressbooks' ) }}</p>
					</li>
					<li class="resources" id="forum">
						<a href="https://pressbooks.community" target="_blank">
							<img
								src="{{ PB_PLUGIN_URL . "assets/dist/images/pb-forum.png" }}"
								alt=""
							/>
							{{ __('Pressbooks community forum', 'pressbooks' ) }}
						</a>
						<p>{{ __( 'Discuss issues of interest with other network managers and Pressbooks support staff.', 'pressbooks' ) }}</p>
					</li>
					<li class="resources" id="spotlight">
						<a href="https://pressbooks.com/webinars" target="_blank">
							<img
								src="{{ PB_PLUGIN_URL . "assets/dist/images/pb-spotlight-series.png" }}"
								alt=""
							/>
							{{ __('Pressbooks webinars', 'pressbooks') }}
						</a>
						<p>{{ __( 'Become a confident Pressbooks user by attending a free, live webinar.', 'pressbooks' ) }}</p>
					</li>
					@if( $network_analytics_active )
						<li class="resources" id="spotlight">
							<a href="mailto:premiumsupport@pressbooks.com" target="_blank">
								<img
									src="{{ PB_PLUGIN_URL . "assets/dist/images/pb-contact-support.png" }}"
									alt=""
								/>
								{{ __('Contact Pressbooks Support', 'pressbooks') }}
							</a>
							<p>{{ __( 'Email Pressbooks’ Premium Support team to report bugs or get personalized help.', 'pressbooks' ) }}</p>
						</li>
					@endif
				</ul>
			</div>
		</div>
	</div>
</div>
