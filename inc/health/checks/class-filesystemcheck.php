<?php

namespace Pressbooks\Health\Checks;

use Pressbooks\Health\Check;
use Symfony\Component\Process\Process;

class FilesystemCheck extends Check {
	public function __construct() {
		$this->name = 'filesystem';
	}

	public function run(): array {
		global $wp_filesystem;

		$issues = [];
		$has_issue = false;

		if ( ! WP_Filesystem() || ! $wp_filesystem->connect() ) {
			$has_issue = true;

			$issues[] = 'Failed to obtain filesystem write access.';
		}

		$disk_usage = $this->getDiskUsagePercentage();

		if ( $disk_usage > 90 ) {
			$has_issue = true;

			$issues[] = "The disk is almost full ({$disk_usage}% used).";
		}

		return [
			'status' => $has_issue ? 'Not Accessible' : 'Accessible',
			'writable' => $wp_filesystem->is_writable( WP_CONTENT_DIR ),
			'readable' => $wp_filesystem->is_readable( WP_CONTENT_DIR ),
			'space_used' => $disk_usage,
			'has_issue' => $has_issue,
			'issues' => $issues,
		];
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
