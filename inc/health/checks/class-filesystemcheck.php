<?php

namespace Pressbooks\Health\Checks;

use Pressbooks\Health\Check;

class FilesystemCheck extends Check {
	public function __construct() {
		$this->name = 'filesystem';
	}

	public function run(): array {
		global $wp_filesystem;

		$has_issue = false;

		if ( ! WP_Filesystem() ) {
			$has_issue = true;
		}

		if ( ! $wp_filesystem->connect() ) {
			$has_issue = true;
		}

		return [
			'status' => $has_issue ? 'Not Accessible' : 'Accessible',
			'writable' => $wp_filesystem->is_writable( WP_CONTENT_DIR ),
			'readable' => $wp_filesystem->is_readable( WP_CONTENT_DIR ),
			'free_space' => $this->calculateFreeSpace(),
			'total_space' => $this->calculateTotalSpace(),
			'has_issue' => $has_issue,
		];
	}

	protected function calculateFreeSpace(): string {
		return $this->calculateSpace( disk_free_space( '.' ) );
	}

	protected function calculateTotalSpace():string {
		return $this->calculateSpace( disk_total_space( '.' ) );
	}

	protected function calculateSpace( float $bytes ): string {
		$base = 1024;
		$suffixes = [ 'B', 'KB', 'MB', 'GB', 'TB' ];

		$index = min( (int) log( $bytes, $base ), count( $suffixes ) - 1 );

		return sprintf( '%1.2f', $bytes / pow( $base, $index ) ) . $suffixes[ $index ];
	}
}
