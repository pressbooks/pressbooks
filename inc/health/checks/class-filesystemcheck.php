<?php

namespace Pressbooks\Health\Checks;

use Pressbooks\Health\Check;
use Pressbooks\Health\Result;
use Symfony\Component\Process\Process;

class FilesystemCheck extends Check {
	public function run(): Result {
		$result = Result::make();

		if ( ! $this->canConnectToFilesystem() ) {
			return $result->failed( 'Failed to obtain filesystem write access.' );
		}

		if ( ! $this->canWriteToFilesystem() ) {
			return $result->failed( 'The filesystem is not writable.' );
		}

		if ( ! $this->canReadFromFilesystem() ) {
			return $result->failed( 'The filesystem is not readable.' );
		}

		$disk_usage = $this->getDiskUsagePercentage();

		// TODO: allow users to customise the failure threshold
		if ( $disk_usage > 90 ) {
			return $result->failed( "The disk is almost full ({$disk_usage}% used)." );
		}

		return $result->ok();
	}

	protected function canConnectToFilesystem(): bool {
		global $wp_filesystem;

		if ( ! WP_Filesystem() ) {
			return false;
		}

		return $wp_filesystem->connect();
	}

	protected function canWriteToFilesystem(): bool {
		global $wp_filesystem;

		return $wp_filesystem->is_writable( WP_CONTENT_DIR );
	}

	protected function canReadFromFilesystem(): bool {
		global $wp_filesystem;

		return $wp_filesystem->is_readable( WP_CONTENT_DIR );
	}

	protected function getDiskUsagePercentage(): int {
		$process = Process::fromShellCommandline( 'df -P .' );

		$process->run();
		$output = $process->getOutput();

		$matches = [];
		preg_match( '/(\d*)%/', $output, $matches, PREG_UNMATCHED_AS_NULL );

		return (int) $matches[1] ?? 0;
	}
}
