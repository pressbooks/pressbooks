<?php

namespace Pressbooks\Providers;

use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;
use Pressbooks\Support\Config;
use Pressbooks\Contracts\ServiceProviderInterface;

class BladeServiceProvider implements ServiceProviderInterface
{
    /**
     * Register the Blade service but do not boot it yet.
     */
    public static function register(Container $container): void
    {
        $container->singleton('Blade', fn () => new static);
    }

    /**
     * Boot Blade when it's first used.
     */
    public static function boot(Container $container): void
    {
        if ($container->bound('Blade')) {
            return;
        }

        $config = Config::get('blade');

        $filesystem = new Filesystem;
        $eventDispatcher = new Dispatcher($container);
        $viewResolver = new EngineResolver;
        $bladeCompiler = new BladeCompiler($filesystem, $config['cache_path']);

        $viewResolver->register('blade', fn () => new CompilerEngine($bladeCompiler));
        $viewFinder = new FileViewFinder($filesystem, [$config['templates_path']]);

        $container->instance('Blade', new class (new Factory($viewResolver, $viewFinder, $eventDispatcher)) {
            protected Factory $factory;

            public function __construct(Factory $factory)
            {
                $this->factory = $factory;
            }

            public function render($view, $data = []): string
            {
                return $this->factory->make($view, $data)->render();
            }

            public function addNamespace($namespace, $hints): self
            {
                $this->factory->addNamespace($namespace, $hints);
                return $this;
            }
        });
    }
}
