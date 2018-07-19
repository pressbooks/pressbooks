<?php

class Shortcodes_Attributions_Attachments extends \WP_UnitTestCase {

	use utilsTrait;

	protected $att;

	public function setUp() {
		parent::setUp();

		$this->att = $this->getMockBuilder( '\Pressbooks\Shortcodes\Attributions\Attachments' )
		                  ->setMethods( null )
		                  ->disableOriginalConstructor()
		                  ->getMock();
	}

	private function _createAttachment() {

		$pid = $this->factory()->attachment->create_upload_object( __DIR__ . '/data/skates.jpg' );
		update_post_meta( $pid, 'pb_media_attribution_title_url', 'https://sourceoforiginal.com' );
		update_post_meta( $pid, 'pb_media_attribution_figure', '2.1' );
		update_post_meta( $pid, 'pb_media_attribution_author', 'Original Author' );
		update_post_meta( $pid, 'pb_media_attribution_author_url', 'https://authorurl.ca' );
		update_post_meta( $pid, 'pb_media_attribution_adapted', 'Adapting Author' );
		update_post_meta( $pid, 'pb_media_attribution_adapted_url', 'https://adaptingauthorurl.ca' );
		update_post_meta( $pid, 'pb_media_attribution_license', 'cc-by' );

		return $pid;
	}

	public function test_getInstance() {

		$val = $this->att->init();

		$this->assertTrue( $val instanceof \Pressbooks\Shortcodes\Attributions\Attachments );

		global $shortcode_tags;
		$this->assertArrayHasKey( 'media_attributions', $shortcode_tags );
	}


	public function test_getAttributions() {

		$pid          = $this->_createAttachment();
		$url          = get_post_meta( $pid, 'pb_media_attribution_title_url', true );
		$license_meta = get_post_meta( $pid, 'pb_media_attribution_license', true );
		$author       = get_post_meta( $pid, 'pb_media_attribution_author', true );
		$this->assertEquals( 'https://sourceoforiginal.com', $url );
		$this->assertEquals( 'cc-by', $license_meta );
		$this->assertEquals( 'Original Author', $author );

		$result = $this->att->getAttributions( 'I have no <b>images</b>' );
		$this->assertEquals( 'I have no <b>images</b>', $result );

	}

	public function test_attributionsContent() {
		$attributions = [
			33 => [
				'title'       => 'skates',
				'title_url'   => 'https://sourceoforiginal.com',
				'figure'      => '2.1',
				'author'      => 'Original Author',
				'author_url'  => 'https://authorurl.ca',
				'adapted'     => 'Adapted Author',
				'adapted_url' => 'https://adaptingauthorurl.ca',
				'license'     => 'cc-by'
			],
			78 => [
				'title'       => 'running downhills a lot',
				'title_url'   => 'https://source.com',
				'figure'      => 'Figure 2.2',
				'author'      => 'amanda c',
				'author_url'  => '',
				'adapted'     => '',
				'adapted_url' => '',
				'license'     => 'cc-by-nc'
			]
		];

		$html = $this->att->attributionsContent( $attributions );
		$this->assertContains( '<div class="media-atttributions license-attribution" prefix:cc="http://creativecommons.org/ns#" prefix:dc="http://purl.org/dc/terms/"><h3>Media Attributions</h3><ul><li about="https://sourceoforiginal.com">2.1 <a rel="cc:attributionURL" href="https://sourceoforiginal.com" property="dc:title">skates</a>  by  <a rel="dc:creator" href="https://authorurl.ca" property="cc:attributionName">Original Author</a>  adapted by  <a rel="dc:source" href="https://adaptingauthorurl.ca">Adapted Author</a>  &copy;  <a rel="license" href="https://creativecommons.org/licenses/by/4.0/">CC BY (Attribution)</a></li><li about="https://source.com">Figure 2.2 <a rel="cc:attributionURL" href="https://source.com" property="dc:title">running downhills a lot</a>  by  amanda c    &copy;  <a rel="license" href="https://creativecommons.org/licenses/by-nc/4.0/">CC BY-NC (Attribution NonCommercial)</a></li></ul></div>', $html );

		$html = $this->att->attributionsContent( [] );
		$this->assertEquals( '', $html );
	}
}
