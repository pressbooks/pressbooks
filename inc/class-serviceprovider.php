<?php

namespace Pressbooks;

use Pressbooks\Interactive\H5PCoreAdapter;
use Pressbooks\Interactive\H5PExtractorAdapter;
use Pressbooks\Interactive\H5PPluginAdapter;
use Pressbooks\Interactive\WordPressHelperAdapter;
use function Pressbooks\Utility\get_cache_path;
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
	 * If you add services, remember to also edit config/.phpstorm.meta.php
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
			'ScopedStyles', function () {
				return new class {
					public function __construct(
						public string $h5p_css_url = '',
					) {
					}
				};
			}
		);

		$container->singleton(
			'Blade', function () {
				// Configuration
				// Note that you can set several directories where your templates are located
				$path_to_templates = [ dirname( __DIR__ ) . '/templates' ];
				$path_to_compiled_templates = get_cache_path();

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

		// H5P Plugin Autoloader Bootstrap
		// Load H5P plugin autoloader if available (required for H5P classes)
		if ( is_file( WP_PLUGIN_DIR . '/h5p/autoloader.php' ) ) {
			require_once( WP_PLUGIN_DIR . '/h5p/autoloader.php' );
		}

		// H5P Dependencies
		$container->bind(
			'H5PPlugin', function () {
				return new H5PPluginAdapter();
			}
		);

		$container->bind(
			'H5PExtractor', function () {
				// Default configuration for H5PExtractor
				$config = [
					'uploadsPath' => wp_upload_dir()['basedir'],
					'h5pContentUrl' => '',  // Will be set dynamically in H5P class
					'h5pCoreUrl' => plugins_url() . '/h5p/h5p-php-library/',
					'h5pLibrariesUrl' => wp_upload_dir()['baseurl'] . '/h5p/libraries/',
					'baseFontSize' => 10,
					'renderWidth' => 800, // Default width, will be overridden as needed
				];
				return new H5PExtractorAdapter( $config );
			}
		);

		$container->bind(
			'WordPressHelper', function () {
				return new WordPressHelperAdapter();
			}
		);

		$container->bind(
			'H5PCore', function () {
				return new H5PCoreAdapter();
			}
		);

	}
}
