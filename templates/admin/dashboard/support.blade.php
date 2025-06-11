<div class="pb-dashboard-panel">
	<div class="pb-dashboard-content">
		<h2>{{ __('Support resources', 'pressbooks') }}</h2>
		<ul class="horizontal two-column">
			<li class="resources" id="pressbooks-guide">
				<a href="https://networkmanagerguide.pressbooks.com/" target="_blank">
					<img src="{{ PB_PLUGIN_URL . "assets/dist/images/pb-network-guide.png" }}" alt="" />
					{{ __('Network manager guide', 'pressbooks' )}}
				</a>
			</li>
			<li class="resources" id="forum">
				<a href="https://pressbooks.community" target="_blank">
					<img src="{{ PB_PLUGIN_URL . "assets/dist/images/pb-forum.png" }}" alt="" />
					{{ __('Pressbooks community forum', 'pressbooks' ) }}
				</a>
			</li>
			<li class="resources" id="spotlight">
				<a href="https://pressbooks.com/webinars" target="_blank">
					<img src="{{ PB_PLUGIN_URL . "assets/dist/images/pb-spotlight-series.png" }}" alt="" />
					{{ __('Pressbooks webinars', 'pressbooks') }}
				</a>
			</li>
			@if( $network_analytics_active )
				<li class="resources" id="spotlight">
					<a href="mailto:premiumsupport@pressbooks.com" target="_blank">
						<img src="{{ PB_PLUGIN_URL . "assets/dist/images/pb-contact-support.png" }}" alt="" />
						{{ __('Contact Pressbooks Support', 'pressbooks') }}
					</a>
				</li>
			@endif
		</ul>
	</div>
</div>
