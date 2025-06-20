<?php

namespace Pressbooks\Interactive;

use H5PExtractor\H5PExtractor;

/**
 * Adapter for H5P Extractor functionality
 * Wraps the external H5PExtractor class for dependency injection
 */
class H5PExtractorAdapter implements H5PExtractorInterface {

	/**
	 * @var H5PExtractor|null
	 */
	private ?H5PExtractor $extractor = null;

	/**
	 * @var array
	 */
	private array $config;

	/**
	 * @param array $config Configuration for H5PExtractor
	 */
	public function __construct( array $config = [] ) {
		$this->config = $config;
	}

	/**
	 * Initialize the H5PExtractor instance if not already done
	 *
	 * @return bool Success of initialization
	 */
	private function initializeExtractor(): bool {
		if ( $this->extractor !== null ) {
			return true;
		}

		$vendor_path = $this->getVendorPath( __DIR__ );
		if ( ! $vendor_path ) {
			return false;
		}

		$h5p_extractor_path = $vendor_path .
			DIRECTORY_SEPARATOR . 'snordian' .
			DIRECTORY_SEPARATOR . 'h5p-extractor' .
			DIRECTORY_SEPARATOR . 'app' .
			DIRECTORY_SEPARATOR . 'H5PExtractor.php';

		try {
			require_once $h5p_extractor_path;
			$this->extractor = new H5PExtractor( $this->config );
			return true;
		} catch ( \Throwable $e ) {
			return false;
		}
	}

	/**
	 * Get composer vendor path.
	 *
	 * @param string $start_dir The directory to start the search from.
	 * @return string|null The vendor path or null if not found.
	 */
	private function getVendorPath( string $start_dir ): string|null {
		while ( ! file_exists( $start_dir . DIRECTORY_SEPARATOR . 'vendor' ) ) {
			$start_dir = dirname( $start_dir );
			if ( DIRECTORY_SEPARATOR === $start_dir ) {
				return null;
			}
		}

		return $start_dir . DIRECTORY_SEPARATOR . 'vendor';
	}

	/**
	 * Extract H5P content to HTML representation
	 *
	 * @param array $options Extraction options (file, format, etc.)
	 * @return array Result with HTML content or error
	 */
	public function extract( array $options ): array {
		if ( ! $this->initializeExtractor() ) {
			return [ 'error' => 'Could not initialize H5PExtractor' ];
		}

		try {
			return $this->extractor->extract( $options );
		} catch ( \Exception $e ) {
			return [ 'error' => $e->getMessage() ];
		}
	}
}
