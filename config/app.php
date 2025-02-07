<?php

return [
	'app' => [
		'name' => 'Pressbooks',
		'version' => '8.0.0',
	],
	/*
	 * Sass configuration.
	 */
	'sass' => [],
	/*
	 * The environment in which the application is running.
	 */
	'global_typography' => [],
	/*
	 * The environment in which the application is running.
	 */
	'styles' => [],
	/*
	 * Database configuration for Eloquent and DB facade.
	 */
	'database' => [
		'driver' => 'mysql',
		'host' => env('DB_HOST', '127.0.0.1'),
		'database' => env('DB_NAME', 'pressbooks'),
		'username' => env('DB_USER', 'root'),
		'password' => env('DB_PASSWORD', ''),
		'charset' => 'utf8mb4',
		'collation' => 'utf8mb4_unicode_ci',
		'prefix' => '',
	],
	/*
	 * Blade configuration.
	 */
	'blade' => [
		'templates_path' => dirname(__DIR__) . '/resources/views',
		'cache_path' => dirname(__DIR__) . '/storage/cache',
	],
];
