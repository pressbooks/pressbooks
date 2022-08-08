<?php

namespace Pressbooks;
use \Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Engines\PhpEngine;
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
	public static function init(): void
	{
		$c = Container::getInstance();

		$c->singleton(
			'Sass', function () {
			return new Sass();
			}
		);

		$c->singleton(
			'GlobalTypography', function ( Container $c ) {
			return new GlobalTypography( $c->make( 'Sass' ) );
			}
		);

		$c->singleton(
			'Styles', function ( Container $c ) {
			return new Styles( $c->make( 'Sass' ) );
			}
		);

		$c->singleton(
			'Blade', function ( Container $c ) {
			// Configuration
			// Note that you can set several directories where your templates are located
			$pathsToTemplates = [__DIR__ . '/templates'];
			$pathToCompiledTemplates = \Pressbooks\Utility\get_cache_path();

			// Dependencies
			$filesystem = new Filesystem;
			$eventDispatcher = new Dispatcher(new Container);

			// Create View Factory capable of rendering PHP and Blade templates
			$viewResolver = new EngineResolver;
			$bladeCompiler = new BladeCompiler($filesystem, $pathToCompiledTemplates);

			$viewResolver->register('blade', function () use ($bladeCompiler) {
				return new CompilerEngine($bladeCompiler);
			});

			$viewResolver->register('php', function () {
				return new PhpEngine;
			});

			$viewFinder = new FileViewFinder($filesystem, $pathsToTemplates);

			return new class(new Factory($viewResolver, $viewFinder, $eventDispatcher)) {
				/**
				 * @var \Illuminate\View\Factory
				 */
				private Factory $factory;

				public function __construct(Factory $factory) {
					$this->factory = $factory;
				}
				public function render($view, $data = []): string
				{
					return $this->factory->make($view, $data)->render();
				}
			};
			}
		);
	}
}
