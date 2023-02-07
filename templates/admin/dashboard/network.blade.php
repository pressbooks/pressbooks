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
		<div class="pb-dashboard-panel pb-dashboard-stats">
			<div class="pb-dashboard-content">
				<div class="stat">
					<p>{!! sprintf( __( 'Your network has %s books and %s users. ', 'pressbooks' ), "<strong>{$total_books}</strong>", "<strong>{$total_users}</strong>" ) !!}</p>
					<a class="button button-primary" href="#">{{ __( 'Explore more stats', 'pressbooks' ) }}</a>
				</div>
			</div>
		</div>
	</div>

	<div class="pb-dashboard-row">
		<div class="pb-dashboard-grid">
			<div class="pb-dashboard-panel">
				<div class="pb-dashboard-content">
					<h2>{{ __( 'Update your home page', 'pressbooks' ) }}</h2>

					<div>
						<img
							src="{{ PB_PLUGIN_URL . "assets/dist/images/pb-customize-page.png" }}"
							alt="{{ __( 'Create a new book art', 'pressbooks' ) }}"
						/>

						<div class="actions">
							<a href="#">{{ __( 'Customize network appearance', 'pressbooks' ) }}</a>
							<a href="#">{{ __( 'Create or edit pages', 'pressbooks' ) }}</a>
							<a href="#">{{ __( 'View web analytics', 'pressbooks' ) }}</a>
						</div>
					</div>
				</div>
			</div>

			<div class="pb-dashboard-panel">
				<div class="pb-dashboard-content">
					<h2>{{ __( 'Administer your network', 'pressbooks' ) }}</h2>

					<div>
						<img
							src="{{ PB_PLUGIN_URL . "assets/dist/images/pb-administer-network.png" }}"
							alt="{{ __( 'Create a new book art', 'pressbooks' ) }}"
						/>

						<div class="actions">
							<a href="#">{{ __( 'Adjust network settings', 'pressbooks' ) }}</a>
							<a href="#">{{ __( 'View book list', 'pressbooks' ) }}</a>
							<a href="#">{{ __( 'View user list', 'pressbooks' ) }}</a>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="pb-dashboard-row">
		<div class="pb-dashboard-panel">
			<div class="pb-dashboard-content">
				<h2>{{ __('Support Resources', 'pressbooks') }}</h2>
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
								src="{{ PB_PLUGIN_URL . "assets/dist/images/pb-webinars.png" }}"
								alt=""
							/>
							{{ __('Pressbooks spotlight series', 'pressbooks') }}
						</a>
						<p>{{ __( 'Learn about doing more with your Pressbooks network from this webinar series.', 'pressbooks' ) }}</p>
					</li>
					<li class="resources" id="spotlight">
						<a href="mailto:premiumsupport@pressbooks.com" target="_blank">
							<img
								src="{{ PB_PLUGIN_URL . "assets/dist/images/pb-premium-support.png" }}"
								alt=""
							/>
							{{ __('Contact Pressbooks Support', 'pressbooks') }}
						</a>
						<p>{{ __( 'Email Pressbooks’ Premium Support team to report bugs or get personalized help.', 'pressbooks' ) }}</p>
					</li>
				</ul>
			</div>
		</div>
	</div>
</div>
