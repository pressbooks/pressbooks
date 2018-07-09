<?php

class GdprTest extends \WP_UnitTestCase {


	/**
	 * @var \Pressbooks\Privacy
	 */
	protected $privacy;


	public function setUp() {
		parent::setUp();
		$this->privacy = new \Pressbooks\Privacy();
	}

	public function test_reschedulePrivacyDeleteOldExportFiles() {
		$nochange = (object) [ 'hook' => 'cant_touch_this', 'interval' => 3600 ];
		$event = $this->privacy->reschedulePrivacyCron( $nochange );
		$this->assertEquals( $nochange, $event );

		$fixme = (object) [ 'hook' => 'wp_privacy_delete_old_export_files', 'interval' => 3600 ];
		$event = $this->privacy->reschedulePrivacyCron( $fixme );
		$this->assertEquals( $event->schedule, 'twicedaily' );
		$this->assertEquals( $event->interval, 43200 );
	}

	public function test_addPrivacyPolicyContent() {
		// Doing it right
		global $current_screen;
		$current_screen = WP_Screen::get( 'front-matter' ); // is_admin
		global $wp_current_filter;
		$wp_current_filter = [ 'admin_init' ]; // doing_action

		$this->privacy->addPrivacyPolicyContent();
		$policies = WP_Privacy_Policy_Content::get_suggested_policy_text();
		$result = false;
		foreach ( $policies as $policy ) {
			if ( $policy['plugin_name'] === 'Pressbooks' ) {
				$result = true;
				break;
			}
		}
		$this->assertTrue( $result );
	}

}