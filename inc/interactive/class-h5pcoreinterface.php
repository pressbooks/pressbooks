<?php

namespace Pressbooks\Interactive;

/**
 * Interface for H5P Core utility functions
 * This allows for dependency injection and testing
 */
interface H5PCoreInterface {

	/**
	 * Generate a slug from a title
	 *
	 * @param string $title Title to slugify
	 * @return string Slugified string
	 */
	public function slugify( string $title ): string;

	/**
	 * Convert library array to string representation
	 *
	 * @param array $library Library information array
	 * @return string Library string representation
	 */
	public function libraryToString( array $library ): string;
}
