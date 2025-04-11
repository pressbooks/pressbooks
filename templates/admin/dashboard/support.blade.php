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
