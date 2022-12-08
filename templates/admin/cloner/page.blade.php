<div class="pb-dashboard-row pb-cloner">
	<div class="pb-dashboard-panel">
		<h1>{{ __( 'Clone a Book', 'pressbooks' ) }}</h1>
		<form id="pb-cloner-form" action="" method="post">
			<?php wp_nonce_field('pb-cloner'); ?>
			<div class="pb-dashboard-row">
				<p><label class="pb-label" for="source-book-url">{{ __( 'Source Book URL', 'pressbooks' ) }}</label></p>
				<input class="regular-text code" id="source-book-url" name="source_book_url" type="url" required/>
				<p class="description"
				   id="source_book_url_description">{{ __( 'Enter the URL to a Pressbooks book with an open license which permits cloning.', 'pressbooks' ) }}</p>
			</div>
			<div class="pb-dashboard-row">
				<p><label class="pb-label" for="target_book_url">{{ __( 'New Book URL', 'pressbooks' ) }}</label></p>
				@if( is_subdomain_install() )
					<span><input class="regular-text code" id="target-book-url" name="target_book_url" type="text" required/>.{{ $domain }}</span>
				@else
					<span>{{ $base_url }} <input class="regular-text code" id="target_book_url" name="target_book_url" type="text" required/></span>
				@endif
			</div>
			<p><input id="pb-cloner-button" class="button button-primary" type="submit"
					  value="<?php _e( 'Clone This Book', 'pressbooks' ); ?>"/></p>
			<progress id="pb-sse-progressbar" max="100"></progress>
			<p><b><span id="pb-sse-minutes"></span><span id="pb-sse-seconds"></span></b> <span id="pb-sse-info"
																							   aria-live="polite"></span>
			</p>
		</form>
	</div>
</div>
<div class="pb-dashboard-row">
	<div class="pb-dashboard-panel">
		<div class="pb-directory-logo">
			<img src="https://pressbooks.directory/assets/logo-pressbooks-directory.svg" alt="Pressbooks Directory"/>
		</div>
		<label class="pb-label" for="searchbox">{{ __( 'Search', 'pressbooks' ) }}</label> <span id="stats" aria-live="polite"></span>
		<div id="searchbox" class="pb-directory-search"></div>
		<div id="book-cards" class="book-cards"></div>
	</div>
</div>
