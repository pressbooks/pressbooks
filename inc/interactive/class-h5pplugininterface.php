<?php

namespace Pressbooks\Interactive;

/**
 * Interface for H5P Plugin functionality
 * This allows for dependency injection and testing
 */
interface H5PPluginInterface {

	/**
	 * Check if H5P plugin fetch method exists
	 *
	 * @return bool
	 */
	public function canFetchH5P(): bool;

	/**
	 * Initialize REST API
	 *
	 * @return void
	 */
	public function restApiInit(): void;

	/**
	 * Fetch H5P content from URL
	 *
	 * @param string $url
	 * @return int
	 */
	public function fetchH5P( string $url ): int;

	/**
	 * Get H5P core instance
	 *
	 * @param string $type
	 * @return mixed
	 */
	public function getH5PInstance( string $type );

	/**
	 * Get H5P content by ID
	 *
	 * @param int $h5p_id
	 * @return array|null
	 */
	public function getContent( int $h5p_id ): ?array;
}
