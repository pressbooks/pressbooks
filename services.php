<?php

// If you add stuff here, don't forget to also edit .phpstorm.meta.php

$c = new \Illuminate\Container\Container();

$c->singleton(
	'Sass', function () {
		return new \Pressbooks\Sass();
	}
);

$c->singleton(
	'GlobalTypography', function ( \Illuminate\Container\Container $c ) {
		return new \Pressbooks\GlobalTypography( $c->make( 'Sass' ) );
	}
);

$c->singleton(
	'Styles', function ( \Illuminate\Container\Container $c ) {
		return new \Pressbooks\Styles( $c->make( 'Sass' ) );
	}
);

$c->singleton(
	'Blade', function ( \Illuminate\Container\Container $c ) {
		$views = __DIR__ . '/templates';
		$cache = \Pressbooks\Utility\get_cache_path();
		return new \Jenssegers\Blade\Blade( $views, $cache, $c );
	}
);

return $c;
