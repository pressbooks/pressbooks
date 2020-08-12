<?php

use Pressbooks\DataCollector\Book as DataCollector;

class GdprTest extends \WP_UnitTestCase {


	/**
	 * @var \Pressbooks\Privacy
	 * @group privacy
	 */
	protected $privacy;

	/**
	 * @group privacy
	 */
	public function setUp() {
		parent::setUp();
		$this->privacy = new \Pressbooks\Privacy();
	}
	/**
	 * @group privacy
	 */
	public function test_reschedulePrivacyDeleteOldExportFiles() {
		$nochange = (object) [ 'hook' => 'cant_touch_this', 'interval' => 3600 ];
		$event = $this->privacy->reschedulePrivacyCron( $nochange );
		$this->assertEquals( $nochange, $event );

		$fixme = (object) [ 'hook' => 'wp_privacy_delete_old_export_files', 'interval' => 3600 ];
		$event = $this->privacy->reschedulePrivacyCron( $fixme );
		$this->assertEquals( $event->schedule, 'twicedaily' );
		$this->assertEquals( $event->interval, 43200 );
	}
	/**
	 * @group privacy
	 */
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

	/**
	 * @group privacy
	 */
	public function test_namespace() {
		$last_updated_before = get_blog_details()->last_updated;
		update_site_option( 'pressbooks_sharingandprivacy_options', [ 'network_directory_excluded' => 0 ] );
		add_action( 'admin_init', '\Pressbooks\Admin\Laf\privacy_settings_init' );
		@do_action( 'admin_init' );
		do_action( 'update_option_pb_book_directory_excluded', '0', '1' );
		$last_updated_after = get_blog_details()->last_updated;

		$this->assertEquals( get_site_meta( get_current_blog_id(), DataCollector::BOOK_DIRECTORY_EXCLUDED, true ), '1' );
		$this->assertNotEquals( $last_updated_before, $last_updated_after );
	}
}
