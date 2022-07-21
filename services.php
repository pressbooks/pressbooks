<?php

// If you add stuff here, don't forget to also edit .phpstorm.meta.php

$container = new \Illuminate\Container\Container();

$container->singleton( 'Sass', fn () => new \Pressbooks\Sass() );

$container->singleton(
	'GlobalTypography',
	fn ( \Illuminate\Container\Container $c ) => new \Pressbooks\GlobalTypography( $c->make( 'Sass' ) )
);

$container->singleton(
	'Styles',
	fn ( \Illuminate\Container\Container $c ) => new \Pressbooks\Styles( $c->make( 'Sass' ) )
);

$container->singleton(
	'Blade', function ( \Illuminate\Container\Container $c ) {
		$views = __DIR__ . '/templates';
		$cache = \Pressbooks\Utility\get_cache_path();

		return new \Jenssegers\Blade\Blade( $views, $cache, $c );
	}
);

return $container;
