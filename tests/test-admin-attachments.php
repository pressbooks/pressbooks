<?php
require_once( PB_PLUGIN_DIR . 'inc/admin/attachments/namespace.php' );

class Admin_AttachmentTest extends \WP_UnitTestCase {
	/**
	 * @group media
	 */
	function test_validate_attachment_metadata() {
		$good_url   = 'https://metamorphosiskafka.pressbooks.com/wp-content/uploads/sites/26642/2014/04/themetamorphosis_1200x1600.jpg';
		$bad_url    = 'ftp://upload.file';
		$url_key    = 'pb_media_attribution_title_url';
		$string_key = 'pb_media_attribution_figure';

		$result = \Pressbooks\Admin\Attachments\validate_attachment_metadata( $url_key, $bad_url );
		$this->assertEmpty( $result );
		$result = \Pressbooks\Admin\Attachments\validate_attachment_metadata( $url_key, $good_url );
		$this->assertStringContainsString( $good_url, $result );
		$result = \Pressbooks\Admin\Attachments\validate_attachment_metadata( $string_key, '<b>Figure 2.1</b>' );
		$this->assertEquals( 'Figure 2.1', $result );
	}
}
