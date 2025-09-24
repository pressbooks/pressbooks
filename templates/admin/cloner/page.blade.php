<div class="pb-cloner wrap">
	<div class="pb-cloner-section main">
		<h1 class="page-title">{{ __( 'Clone a Book', 'pressbooks' ) }}</h1>
		<p>{{ __( 'This tool allows you to clone openly licensed books from one Pressbooks network to another. The cloning process makes a copy of the original book for you to revise and redistribute as desired. You can enter the source book URL if you already know it, or find and select a suitable book from the Pressbooks Directory.', 'pressbooks' ) }}</p>
		<p id="form-instructions"><span class="required" aria-hidden="true">*</span> {{ __( 'Required fields', 'pressbooks' ) }}</p>
		<form id="pb-cloner-form" action="" method="post" aria-describedby="form-instructions">
			<?php wp_nonce_field('pb-cloner'); ?>
			<h2><label class="pb-label" for="source-book-url">{{ __( 'Source Book URL', 'pressbooks' ) }} <span class="required" aria-hidden="true">*</span><span class="screen-reader-text">{{ __( 'required', 'pressbooks' ) }}</span></label></h2>
			<input class="regular-text code" id="source-book-url" name="source_book_url" type="url" aria-describedby="source_book_url_description" required aria-required="true"/>
			<p class="description" id="source_book_url_description">{{ __( 'Enter the URL of a Pressbooks book with an open license which permits cloning.', 'pressbooks' ) }}</p>
			<h2><label class="pb-label" for="target_book_url">{{ __( 'New Book URL', 'pressbooks' ) }} <span class="required" aria-hidden="true">*</span><span class="screen-reader-text">{{ __( 'required', 'pressbooks' ) }}</span></label></h2>
			@if( is_subdomain_install() )
				<span class="url-input"><input class="regular-text code" id="target-book-url" name="target_book_url" type="text" aria-describedby="target_book_url_description" required aria-required="true"/></span><span class="subdomain-target-url">.{{ $domain }}</span>
			@else
				<span class="subdir-target-url">{{ $base_url }}</span><span class="url-input"><input class="regular-text code" id="target-book-url" name="target_book_url" type="text" aria-describedby="target_book_url_description" required aria-required="true"/></span>
			@endif
			<p class="description" id="target_book_url_description">{{ __( 'Enter the URL where you want this book to be cloned. This URL cannot be changed later, so choose carefully.', 'pressbooks' ) }}</p>
			<input id="pb-cloner-button" class="button button-hero button-primary" type="submit" value="{{ __( 'Clone book', 'pressbooks' ) }}">
			<progress id="pb-sse-progressbar" max="100" aria-label="{{ __( 'Cloning progress', 'pressbooks' ) }}"></progress>
			<strong><span id="pb-sse-minutes"></span><span id="pb-sse-seconds"></span></strong> <span id="pb-sse-info" aria-live="polite"></span>
		</form>
	</div>
	@if( \Pressbooks\Utility\is_algolia_search_enabled() )
		<div class="pb-cloner-section search">
			<h2 class="section-title">{{ __( 'Search the Pressbooks Directory', 'pressbooks' ) }}</h2>
			<a class="pb-directory-logo" href="https://pressbooks.directory" rel="noopener" target="_blank"><img src="https://pressbooks.directory/assets/logo-pressbooks-directory.svg" alt="Pressbooks Directory"/></a>
			<label class="pb-label screen-reader-text" for="searchbox">{{ __( 'Search', 'pressbooks' ) }}</label> <span id="stats" aria-live="polite"></span>
			<div id="searchbox" class="pb-directory-search"></div>
			<div id="book-cards" class="book-cards"></div>
		</div>
	@endif
</div>
