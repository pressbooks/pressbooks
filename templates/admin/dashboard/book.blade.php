<?php
use function Pressbooks\Admin\Laf\book_info_slug;
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
			<div class="pb-dashboard-panel">
				<div class="pb-dashboard-book-cover">
					<img src=""/>
				</div>
				<div class="pb-dashboard-content">
					<ul class="pb-dashboard-action">
						<li><a href="{{ get_home_url() }}">{{ __( 'View book', 'pressbooks' ) }}</a></li>
						<li><a href="{{ book_info_slug() }}">{{ __( 'Edit book info', 'pressbooks' ) }}</a></li>
						<li><a href="?page=pb_organize">{{ __( 'Organize book', 'pressbooks' ) }}</a></li>
						<li><a href="themes.php">{{ __( 'Change theme', 'pressbooks' ) }}</a></li>
						<li><a href="users.php">{{ __( 'Add collaborators', 'pressbooks' ) }}</a></li>
						<li><a href="?page=koko-analytics">{{ __( 'View Analytics', 'pressbooks' ) }}</a></li>
						<li><a href="ms-delete-site.php">{{ __( 'Delete book', 'pressbooks' ) }}</a></li>
					</ul>
				</div>
			</div>
			<div class="pb-dashboard-panel">
				<div class="pb-dashboard-image">
					<img
							src="{{ PB_PLUGIN_URL . "assets/dist/images/pb-adapt-book.png" }}"
							alt="{{ __( 'Write a new chapter art', 'pressbooks' ) }}"
					/>
				</div>
				<div class="pb-dashboard-content">
					<div class="pb-dashboard-action">
						<a class="button button-hero button-primary" href="{{ admin_url( 'admin.php?page=pb_cloner' ) }}">
							{{ __( 'Write a new chapter', 'pressbooks' ) }}
						</a>
						<a class="button button-hero" href="{{ admin_url( 'admin.php?page=pb_cloner' ) }}">
							{{ __( 'Import Content', 'pressbooks' ) }}
						</a>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="pb-dashboard-row">
		<div class="pb-dashboard-panel">
			<div class="pb-dashboard-image">
				<img
						src="{{ PB_PLUGIN_URL . "assets/dist/images/pb-adapt-book.png" }}"
						alt="{{ __( 'Write a new chapter art', 'pressbooks' ) }}"
				/>
			</div>
			<div class="pb-dashboard-content">
				<h2>{{ __( 'Want help?', 'pressbooks' ) }}</h2>
				<p>{{ __( 'We have resources designed to help you at every stage of the writing and publishing process.', 'pressbooks' ) }}</p>
				<a class="button button-hero" href="{{ admin_url( 'admin.php?page=pb_cloner' ) }}">
					{{ __( 'Show resources', 'pressbooks' ) }}
				</a>
			</div>
		</div>
	</div>
</div>
