<?php
require_once( PB_PLUGIN_DIR . 'inc/admin/attachments/namespace.php' );

class Admin_AttachmentTest extends \WP_UnitTestCase {

	function test_validate_attachment_metadata() {
		$good       = 'https://metamorphosiskafka.pressbooks.com/wp-content/uploads/sites/26642/2014/04/themetamorphosis_1200x1600.jpg';
		$not_good   = 'ftp://upload.file';
		$pb_url_key = 'pb_title_attribution_url';

		$valid_url     = \Pressbooks\Admin\Attachments\validate_attachment_metadata( $pb_url_key, $not_good );
		$not_valid_url = \Pressbooks\Admin\Attachments\validate_attachment_metadata( $pb_url_key, $good );

		$this->assertEmpty( $not_valid_url );
		$this->assertContains( $good, $valid_url );

	}
}
