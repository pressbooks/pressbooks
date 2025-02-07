<?php

namespace Pressbooks\Providers;

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as DB;
use Pressbooks\Contracts\ServiceProviderInterface;
use Pressbooks\Support\Config;

class DatabaseServiceProvider implements ServiceProviderInterface
{
    /**
     * Register the Database service without booting it.
     */
    public static function register(Container $container): void
    {
        $container->singleton('db', fn () => new static);
    }

    /**
     * Boot Database when first accessed.
     */
    public static function boot(Container $container): void
    {
        if ($container->bound('db')) {
            return;
        }

        global $wpdb;
        $db = new DB;
        $config = Config::get('database');

        $db->addConnection([
            'driver'    => $config['driver'],
            'host'      => $config['host'],
            'database'  => $config['database'],
            'username'  => $config['username'],
            'password'  => $config['password'],
            'charset'   => $wpdb->charset,
            'collation' => $wpdb->collate,
            'prefix'    => $wpdb->base_prefix,
        ]);

        $db->setAsGlobal();
        $db->bootEloquent();

        $container->instance('db', $db);
    }
}
