<?php
use Pressbooks\Metadata;
use function Pressbooks\Image\thumbnail_from_url;
?>

<div class="wrap">
	<div class="pb-dashboard-row">
		<div class="pb-dashboard-panel">
			<div class="pb-dashboard-content banner">
				<h2 class="site-title">{!! $site_name !!} <span>Dashboard</span></h2>
			</div>
		</div>
	</div>
	<div class="pb-dashboard-row">
		<div class="pb-dashboard-grid">
			<div class="pb-dashboard-panel pb-dashboard-grid">
				<div class="pb-dashboard-image book-cover">
					{{-- TODO: replace with better method for getting/showing book cover? --}}
					<img src="{{ thumbnail_from_url( get_post_meta( ( new Metadata )->getMetaPostId(), 'pb_cover_image', true ), 'pb_cover_medium' ) }}"/>
				</div>
				<div class="pb-dashboard-content">
					<ul class="pb-dashboard-action">
						<li id="view">
							<a href="{{ e( get_home_url() ) }}">
								<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
									<path stroke-linecap="round" stroke-linejoin="round" d="M9 8.25H7.5a2.25 2.25 0 00-2.25 2.25v9a2.25 2.25 0 002.25 2.25h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25H15m0-3l-3-3m0 0l-3 3m3-3V15" />
								</svg>
								{{ __( 'View book', 'pressbooks' ) }}</a></li>
						<li id="book_info"><a href="{!! $edit_book_link !!}">
								<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
									<path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
								</svg>
								{{ __( 'Edit book info', 'pressbooks' ) }}</a></li>
						<li id="organize"><a href="?page=pb_organize">
								<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
									<path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
								</svg>
								{{ __( 'Organize book', 'pressbooks' ) }}</a></li>
						<li id="theme"><a href="themes.php">
								<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
									<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 7.125C2.25 6.504 2.754 6 3.375 6h6c.621 0 1.125.504 1.125 1.125v3.75c0 .621-.504 1.125-1.125 1.125h-6a1.125 1.125 0 01-1.125-1.125v-3.75zM14.25 8.625c0-.621.504-1.125 1.125-1.125h5.25c.621 0 1.125.504 1.125 1.125v8.25c0 .621-.504 1.125-1.125 1.125h-5.25a1.125 1.125 0 01-1.125-1.125v-8.25zM3.75 16.125c0-.621.504-1.125 1.125-1.125h5.25c.621 0 1.125.504 1.125 1.125v2.25c0 .621-.504 1.125-1.125 1.125h-5.25a1.125 1.125 0 01-1.125-1.125v-2.25z" />
								</svg>
								{{ __( 'Change theme', 'pressbooks' ) }}</a></li>
						<li id="users"><a href="users.php">
								<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
									<path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
								</svg>
								{{ __( 'Add collaborators', 'pressbooks' ) }}</a></li>
						<li id="analytics"><a href="?page=koko-analytics">
								<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
									<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5m.75-9l3-3 2.148 2.148A12.061 12.061 0 0116.5 7.605" />
								</svg>
								{{ __( 'View Analytics', 'pressbooks' ) }}</a></li>
						<li id="delete"><a href="ms-delete-site.php">
								<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
									<path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
								</svg>
								{{ __( 'Delete book', 'pressbooks' ) }}</a></li>
					</ul>
				</div>
			</div>
			<div class="pb-dashboard-panel">
				<div class="pb-dashboard-image">
					<img
							src="{{ PB_PLUGIN_URL . "assets/dist/images/pb-write.png" }}"
							alt="{{ __( 'Write a new chapter art', 'pressbooks' ) }}"
					/>
				</div>
				<div class="pb-dashboard-content">
					<div class="pb-dashboard-action">
						<a class="button button-hero button-primary" href="{{ admin_url( 'post-new.php?post_type=chapter' ) }}">
							{{ __( 'Write a new chapter', 'pressbooks' ) }}
						</a>
						<a class="button button-hero" href="{{ admin_url( 'admin.php?page=pb_import' ) }}">
							{{ __( 'Import Content', 'pressbooks' ) }}
						</a>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div x-data="{showResources: false}">
		<div class="pb-dashboard-row">
			<div class="pb-dashboard-panel pb-dashboard-grid">
				<div class="pb-dashboard-image">
					<img
						src="{{ PB_PLUGIN_URL . "assets/dist/images/pb-help.png" }}"
						alt="{{ __( 'Want help? art', 'pressbooks' ) }}"
					/>
				</div>
				<div class="pb-dashboard-content">
					<h2>{{ __( 'Want help?', 'pressbooks' ) }}</h2>
					<p>{{ __( 'We have resources designed to help you at every stage of the writing and publishing process.', 'pressbooks' ) }}</p>
					<button class="button button-hero" x-on:click="showResources = !showResources">
						<span x-show="!showResources">{{ __( 'Show resources', 'pressbooks' ) }}</span>
						<span x-show="showResources">{{ __( 'Hide resources', 'pressbooks' ) }}</span>
					</button>
				</div>
			</div>
		</div>
		<div class="pb-dashboard-row" x-bind:class="showResources ? '' : 'hidden'">
			<div class="pb-dashboard-panel">
				<div class="pb-dashboard-content">
					<h2>{{ __('Guides & Video tutorials', 'pressbooks') }}</h2>
					{{-- TODO: add links to youtube videos. --}}
					<ul>
						<li><a href="https://guide.pressbooks.com" target="_blank">{{ __('Pressbooks User Guide', 'pressbooks' )}}</a></li>
						<li><a href="#" target="_blank">{{ __('Edit your profile', 'pressbooks' )}}</a></li>
						<li><a href="#" target="_blank">{{ __('Create a book', 'pressbooks' )}}</a></li>
						<li><a href="#" target="_blank">{{ __('Clone a book', 'pressbooks' )}}</a></li>
						<li><a href="#" target="_blank">{{ __('Create & edit a chapter', 'pressbooks' )}}</a></li>
					</ul>
				</div>
			</div>
		</div>
		<div class="pb-dashboard-row" x-bind:class="showResources ? '' : 'hidden'">
			<div class="pb-dashboard-grid">
				<div class="pb-dashboard-panel">
					<div class="pb-dashboard-content">
						<h2>{{ __('Attend a live training webinar', 'pressbooks') }}</h2>
						{!! $rss !!}
						<p>{{ __('All webinars are recorded and uploaded to the Pressbooks YouTube channel', 'pressbooks' ) }}</p>
					</div>
				</div>
				<div class="pb-dashboard-panel">
					<div class="pb-dashboard-content">
						<h2>{{ __('Participate in the community forum', 'pressbooks' ) }} </h2>
						{{-- TODO: add links to desired sample forum topics--}}
						<p>
							<a href='https://pressbooks.community' target="_blank">{{ __( 'Visit forum', 'pressbooks' ) }}</a>
						</p>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
