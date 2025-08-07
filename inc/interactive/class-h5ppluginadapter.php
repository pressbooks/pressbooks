<?php

namespace Pressbooks\Interactive;

/**
 * Adapter for H5P Plugin functionality
 * Wraps the external H5P_Plugin class for dependency injection
 */
class H5PPluginAdapter implements H5PPluginInterface {

	/**
	 * Check if H5P plugin fetch method exists
	 *
	 * @return bool
	 */
	public function canFetchH5P(): bool {
		return method_exists( '\H5P_Plugin', 'fetch_h5p' );
	}

	/**
	 * Initialize REST API
	 *
	 * @return void
	 */
	public function restApiInit(): void {
		\H5P_Plugin::get_instance()->rest_api_init();
	}

	/**
	 * Fetch H5P content from URL
	 *
	 * @param string $url
	 * @return int
	 */
	public function fetchH5P( string $url ): int {
		try {
			return \H5P_Plugin::get_instance()->fetch_h5p( $url );
		} catch ( \Throwable $e ) {
			return 0;
		}
	}

	/**
	 * Get H5P core instance
	 *
	 * @param string $type
	 * @return mixed
	 */
	public function getH5PInstance( string $type ) {
		return \H5P_Plugin::get_instance()->get_h5p_instance( $type );
	}

	/**
	 * Get H5P content by ID
	 *
	 * @param int $h5p_id
	 * @return array|null
	 */
	public function getContent( int $h5p_id ): ?array {
		try {
			return \H5P_Plugin::get_instance()->get_content( $h5p_id );
		} catch ( \Throwable $e ) {
			return null;
		}
	}
}
