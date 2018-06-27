<?php

class GdprTest extends \WP_UnitTestCase {


	/**
	 * @var \Pressbooks\Gdpr
	 */
	protected $gdpr;


	public function setUp() {
		parent::setUp();
		$this->gdpr = new \Pressbooks\Gdpr();
	}

	public function test_reschedulePrivacyDeleteOldExportFiles() {
		$nochange = (object) [ 'hook' => 'cant_touch_this', 'interval' => 3600 ];
		$event = $this->gdpr->reschedulePrivacyDeleteOldExportFiles( $nochange );
		$this->assertEquals( $nochange, $event );

		$fixme = (object) [ 'hook' => 'wp_privacy_delete_old_export_files', 'interval' => 3600 ];
		$event = $this->gdpr->reschedulePrivacyDeleteOldExportFiles( $fixme );
		$this->assertEquals( $event->schedule, 'twicedaily' );
		$this->assertEquals( $event->interval, 43200 );
	}

}