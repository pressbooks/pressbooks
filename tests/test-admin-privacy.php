<?php

require_once( PB_PLUGIN_DIR . 'inc/admin/privacy/namespace.php' );

class Admin_PrivacyTest extends \WP_UnitTestCase {

	public function test_add_privacy_policy_content() {
		\Pressbooks\Admin\Privacy\add_privacy_policy_content();
		$policies = WP_Privacy_Policy_Content::get_suggested_policy_text();
		foreach ( $policies as $policy ) {
			if ( $policy['plugin_name'] === 'Pressbooks' ) {
				$result = true;
			}
		}
		$this->assertTrue( $result );
	}

}
