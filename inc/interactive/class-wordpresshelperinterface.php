<?php

namespace Pressbooks\Interactive;

/**
 * Interface for WordPress functionality
 * This allows for dependency injection and testing
 */
interface WordPressHelperInterface {

	/**
	 * Check if a plugin is active
	 *
	 * @param string $plugin Plugin path
	 * @return bool
	 */
	public function isPluginActive( string $plugin ): bool;

	/**
	 * Check if a plugin is active for network
	 *
	 * @param string $plugin Plugin path
	 * @return bool
	 */
	public function isPluginActiveForNetwork( string $plugin ): bool;

	/**
	 * Check if a filter has been added
	 *
	 * @param string $hook_name Filter name
	 * @return bool
	 */
	public function hasFilter( string $hook_name ): bool;

	/**
	 * Apply filters
	 *
	 * @param string $hook_name Filter name
	 * @param mixed $value Value to filter
	 * @return mixed
	 */
	public function applyFilters( string $hook_name, $value );

	/**
	 * Get option value
	 *
	 * @param string $option Option name
	 * @return mixed
	 */
	public function getOption( string $option );

	/**
	 * Add a filter
	 *
	 * @param string $hook_name Filter name
	 * @param callable $callback Callback function
	 * @param int $priority Priority
	 * @param int $accepted_args Number of accepted arguments
	 * @return void
	 */
	public function addFilter( string $hook_name, callable $callback, int $priority = 10, int $accepted_args = 1 ): void;

	/**
	 * Check if a file exists
	 *
	 * @param string $filename Path to the file
	 * @return bool
	 */
	public function isFile( string $filename ): bool;

	/**
	 * Activate a plugin
	 *
	 * @param string $plugin Plugin path
	 * @return mixed Result of activation (null on success, WP_Error on failure)
	 */
	public function activatePlugin( string $plugin );
}
