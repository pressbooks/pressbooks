<?php

namespace Pressbooks;

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;

/**
 * Service Provider for Pressbooks
 */
class ServiceProvider {
	/**
	 * If you add services, don't forget to also edit config/.phpstorm.meta.php
	 *
	 */
	public static function init(): void {

		$container = Container::getInstance();

		$container->singleton(
			'Sass', function () {
				return new Sass();
			}
		);

		$container->singleton(
			'GlobalTypography', function ( Container $container ) {
				return new GlobalTypography( $container->make( 'Sass' ) );
			}
		);

		$container->singleton(
			'Styles', function ( Container $container ) {
				return new Styles( $container->make( 'Sass' ) );
			}
		);

		$container->singleton(
			'Blade', function ( Container $container ) {
				// Configuration
				// Note that you can set several directories where your templates are located
				$path_to_templates = [ dirname( __DIR__ ) . '/templates' ];
				$path_to_compiled_templates = \Pressbooks\Utility\get_cache_path();

				// Dependencies
				$filesystem = new Filesystem;
				$event_dispatcher = new Dispatcher( new Container );

				// Create View Factory capable of rendering PHP and Blade templates
				$view_resolver = new EngineResolver;
				$blade_compiler = new BladeCompiler( $filesystem, $path_to_compiled_templates );

				$view_resolver->register('blade', function () use ( $blade_compiler ) {
					return new CompilerEngine( $blade_compiler );
				});

				$view_finder = new FileViewFinder( $filesystem, $path_to_templates );

				return new class(new Factory( $view_resolver, $view_finder, $event_dispatcher )) {
					protected Factory $factory;

					public function __construct( Factory $factory ) {
						$this->factory = $factory;
					}

					public function render( $view, $data = [] ): string {
						return $this->factory->make( $view, $data )->render();
					}

					public function addNamespace( $namespace, $hints ): self {
						$this->factory->addNamespace( $namespace, $hints );

						return $this;
					}
				};
			}
		);

		global $wpdb;

		$db = new Manager;

		/**
		 * TODO: how to fetch environment variables from a config class,
		 * roots config won't be accessible in the pipeline
		 * Mantle POC
		 * This would only create one connection that would be able in other plugins that tries to use Eloquent ORM
		 */
		$db->addConnection( [
			'driver' => 'mysql',
			'host' => env( 'DB_HOST', DB_HOST ),
			'database' => env( 'DB_NAME', DB_NAME ),
			'username' => env( 'DB_USER', DB_USER ),
			'password' => env( 'DB_PASSWORD', DB_PASSWORD ),
			'charset' => $wpdb->charset,
			'collation' => $wpdb->collate,
			'prefix' => $wpdb->base_prefix,
		] );

		$db->setAsGlobal();
		$db->bootEloquent();

		$container->bind( 'db', fn () => $db );

	}
}
