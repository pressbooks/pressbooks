<?php

namespace Pressbooks\Interactive;

/**
 * Adapter for H5P Core utility functions
 * Wraps H5PCore static methods for dependency injection
 */
class H5PCoreAdapter implements H5PCoreInterface {

	/**
	 * Generate a slug from a title
	 *
	 * @param string $title Title to slugify
	 * @return string Slugified string
	 */
	public function slugify( string $title ): string {
		return \H5PCore::slugify( $title );
	}

	/**
	 * Convert library array to string representation
	 *
	 * @param array $library Library information array
	 * @return string Library string representation
	 */
	public function libraryToString( array $library ): string {
		return \H5PCore::libraryToString( $library );
	}
}
