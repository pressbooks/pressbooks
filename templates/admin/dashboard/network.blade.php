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
									<i aria-hidden="true" class="pb-heroicons pb-heroicons-sparkles"></i>
									<span>{{ __( 'Customize network appearance', 'pressbooks' ) }}</span>
								</a>
							</li>
							<li>
								<a
									href="{!! admin_url( 'edit.php?post_type=page' ) !!}"
								>
									<i aria-hidden="true" class="pb-heroicons pb-heroicons-pencil-square"></i>
									<span>{{ __( 'Create or edit pages', 'pressbooks' ) }}</span>
								</a>
							</li>
							@if( $koko_analytics_active )
								<li>
									<a
										href="{!! admin_url( 'index.php?page=koko-analytics' ) !!}"
									>
										<i aria-hidden="true" class="pb-heroicons pb-heroicons-presentation-chart-bar"></i>
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
									<i aria-hidden="true" class="pb-heroicons pb-heroicons-cog-8-tooth"></i>
									<span>{{ __( 'Adjust network settings', 'pressbooks' ) }}</span>
								</a>
							</li>
							<li>
								<a
									href="{!! network_admin_url( $network_analytics_active ? 'sites.php?page=pb_network_analytics_booklist' : 'sites.php' ) !!}"
								>
									<i aria-hidden="true" class="pb-heroicons pb-heroicons-book-open"></i>
									<span>{{ __( 'View book list', 'pressbooks' ) }}</span>
								</a>
							</li>
							<li>
								<a
									href="{!! network_admin_url( $network_analytics_active ? 'users.php?page=pb_network_analytics_userlist' : 'users.php' ) !!}"
								>
									<i aria-hidden="true" class="pb-heroicons pb-heroicons-users"></i>
									<span>{{ __( 'View user list', 'pressbooks' ) }}</span>
								</a>
							</li>
						</ul>
					</div>
				</div>
			</div>
		</div>
	</div>

	@include('admin.dashboard.support')
</div>
