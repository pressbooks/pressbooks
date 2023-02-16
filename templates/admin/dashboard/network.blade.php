<div class="wrap">
	<div class="pb-dashboard-row">
		<div class="pb-dashboard-panel">
			<div class="pb-dashboard-content">
				<h2>
					{{ __( 'Welcome to', 'pressbooks' ) }}
					<span class="network-title">{!! $network_name !!}</span>
				</h2>
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
			</div>
		</div>
	</div>

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
									{{ __( 'Customize network appearance', 'pressbooks' ) }}
								</a>
							</li>
							<li>
								<a
									href="{!! admin_url( 'edit.php?post_type=page' ) !!}"
								>
									{{ __( 'Create or edit pages', 'pressbooks' ) }}
								</a>
							</li>
							@if( $koko_analytics_active )
								<li>
									<a
										href="{!! admin_url( 'index.php?page=koko-analytics' ) !!}"
									>{{ __( 'View home page analytics', 'pressbooks' ) }}</a>
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
									{{ __( 'Adjust network settings', 'pressbooks' ) }}
								</a>
							</li>
							<li>
								<a
									href="{!! network_admin_url( $network_analytics_active ? 'sites.php?page=pb_network_analytics_booklist' : 'sites.php' ) !!}"
								>
									{{ __( 'View book list', 'pressbooks' ) }}
								</a>
							</li>
							<li>
								<a
									href="{!! network_admin_url( $network_analytics_active ? 'users.php?page=pb_network_analytics_userlist' : 'users.php' ) !!}"
								>
									{{ __( 'View user list', 'pressbooks' ) }}
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
