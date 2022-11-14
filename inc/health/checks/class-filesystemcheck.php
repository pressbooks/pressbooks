<?php

namespace Pressbooks\Health\Checks;

use Pressbooks\Health\Check;
use Pressbooks\Health\Result;
use Symfony\Component\Process\Process;

class FilesystemCheck extends Check {
	public function __construct() {
		$this->name = 'filesystem';
	}

	public function run(): Result {
		global $wp_filesystem;

		$result = Result::make();

		if ( ! WP_Filesystem() || ! $wp_filesystem->connect() ) {
			return $result->failed( 'Failed to obtain filesystem write access.' );
		}

		if ( ! $wp_filesystem->is_writable( WP_CONTENT_DIR ) ) {
			return $result->failed( 'The filesystem is not writable.' );
		}

		if ( ! $wp_filesystem->is_readable( WP_CONTENT_DIR ) ) {
			return $result->failed( 'The filesystem is not readable.' );
		}

		$disk_usage = $this->getDiskUsagePercentage();

		// TODO: allow users to customise the failure threshold
		if ( $disk_usage > 90 ) {
			return $result->failed( "The disk is almost full ({$disk_usage}% used)." );
		}

		return $result->ok();
	}

	protected function getDiskUsagePercentage(): string {
		$process = Process::fromShellCommandline( 'df -P .' );

		$process->run();
		$output = $process->getOutput();

		$matches = [];
		preg_match( '/(\d*)%/', $output, $matches, PREG_UNMATCHED_AS_NULL );

		return (int) $matches[1] ?? 0;
	}
}
