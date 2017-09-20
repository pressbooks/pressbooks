<?php

class UpdaterTest extends \WP_UnitTestCase {

	public function test_Updater() {
		$updater = new \Pressbooks\Updater( 'https://github.com/pressbooks/pressbooks/' );
		$release = $updater->getLatestRelease();

		$this->assertNotEmpty( $release->downloadUrl );
		$this->assertNotContains( 'zipball', $release->downloadUrl );
		$this->assertNotContains( 'tarball', $release->downloadUrl );
	}

}