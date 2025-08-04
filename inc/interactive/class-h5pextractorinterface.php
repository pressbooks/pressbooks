<?php

namespace Pressbooks\Interactive;

/**
 * Interface for H5P Extractor functionality
 * This allows for dependency injection and testing
 */
interface H5PExtractorInterface {

	/**
	 * Extract H5P content to HTML representation
	 *
	 * @param array $options Extraction options (file, format, etc.)
	 * @return array Result with HTML content or error
	 */
	public function extract( array $options ): array;
}
