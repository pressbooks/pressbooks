<?php

class Shortcodes_Attributions_Attachments extends \WP_UnitTestCase {

	use utilsTrait;

	/**
	 * @var \Pressbooks\Shortcodes\Attributions\Attachments
	 * @group attributions
	 */
	protected $att;

	/**
	 * @group attributions
	 */
	public function set_up() {
		parent::set_up();

		$this->att = $this->getMockBuilder( '\Pressbooks\Shortcodes\Attributions\Attachments' )
						->setMethods( null )
						->disableOriginalConstructor()
						->getMock();
	}

	/**
	 * @group attributions
	 */
	public function test_getInstance() {

		$val = $this->att->init();

		$this->assertTrue( $val instanceof \Pressbooks\Shortcodes\Attributions\Attachments );

		global $shortcode_tags;
		$this->assertArrayHasKey( 'media_attributions', $shortcode_tags );
	}

	/**
	 * @group attributions
	 */
	public function test_getAttributions() {

		$result = $this->att->getAttributions( 'I have no <b>images</b>' );
		$this->assertEquals( 'I have no <b>images</b>', $result );

	}

	/**
	 * @group attributions
	 */
	public function test_getAttributionsMeta() {
		$pid = $this->_createAttachment();
		$url = get_post_meta( $pid, 'pb_media_attribution_title_url', true );
		$license_meta = get_post_meta( $pid, 'pb_media_attribution_license', true );
		$author = get_post_meta( $pid, 'pb_media_attribution_author', true );

		$this->assertEquals( 'https://sourceoforiginal.com', $url );
		$this->assertEquals( 'cc-by', $license_meta );
		$this->assertEquals( 'Original Author', $author );

		$id = [ $pid ];
		$result = $this->att->getAttributionsMeta( $id );

		$this->assertArrayHasKey( $pid, $result );
		$this->assertNonEmptyMultidimensionalArray( $result );
		$this->assertEquals( 'https://sourceoforiginal.com', $result[ $pid ]['title_url'] );
		$this->assertEquals( 'cc-by', $result[ $pid ]['license'] );
		$this->assertEquals( 'Original Author', $result[ $pid ]['author'] );

	}

	/**
	 * @group attributions
	 */
	private function _createAttachment() {

		$pid = $this->factory()->attachment->create_upload_object( __DIR__ . '/data/skates.jpg' );
		update_post_meta( $pid, 'pb_media_attribution_title_url', 'https://sourceoforiginal.com' );
		update_post_meta( $pid, 'pb_media_attribution_author', 'Original Author' );
		update_post_meta( $pid, 'pb_media_attribution_author_url', 'https://authorurl.ca' );
		update_post_meta( $pid, 'pb_media_attribution_adapted', 'Adapting Author' );
		update_post_meta( $pid, 'pb_media_attribution_adapted_url', 'https://adaptingauthorurl.ca' );
		update_post_meta( $pid, 'pb_media_attribution_license', 'cc-by' );

		return $pid;
	}

	/**
	 * @group attributions
	 */
	public function test_attributionsContent() {
		$attributions = [
			33 => [
				'title'       => 'skates',
				'title_url'   => 'https://sourceoforiginal.com',
				'author'      => 'Original Author',
				'author_url'  => 'https://authorurl.ca',
				'adapted'     => 'Adapted Author',
				'adapted_url' => 'https://adaptingauthorurl.ca',
				'license'     => 'cc-by',
			],
			78 => [
				'title'       => 'running downhills a lot',
				'title_url'   => 'https://source.com',
				'author'      => 'amanda c',
				'author_url'  => '',
				'adapted'     => '',
				'adapted_url' => '',
				'license'     => 'cc-by-nc',
			],
		];

		$html = $this->att->attributionsContent( $attributions );
		$this->assertStringContainsString( '<div class="media-attributions clear" prefix:cc="http://creativecommons.org/ns#" prefix:dc="http://purl.org/dc/terms/"><h3>Media Attributions</h3><ul><li about="https://sourceoforiginal.com"><a rel="cc:attributionURL" href="https://sourceoforiginal.com" property="dc:title">skates</a>  &copy;  <a rel="dc:creator" href="https://authorurl.ca" property="cc:attributionName">Original Author</a>  adapted by  <a rel="dc:source" href="https://adaptingauthorurl.ca">Adapted Author</a>  is licensed under a  <a rel="license" href="https://creativecommons.org/licenses/by/4.0/">CC BY (Attribution)</a> license</li><li about="https://source.com"><a rel="cc:attributionURL" href="https://source.com" property="dc:title">running downhills a lot</a>  &copy;  amanda c    is licensed under a  <a rel="license" href="https://creativecommons.org/licenses/by-nc/4.0/">CC BY-NC (Attribution NonCommercial)</a> license</li></ul></div>', $html );

		$html = $this->att->attributionsContent( [] );
		$this->assertEquals( '', $html );
	}

	/**
	 * @group attributions
	 */
	public function test_getBookMedia() {
		$this->assertEmpty( $this->att->getBookMedia() );
		$pid = $this->_createAttachment();
		$this->assertEmpty( $this->att->getBookMedia() ); // Once per PHP invocation, there should be no change on second call
		$result = $this->att->getBookMedia( true ); // Or, use the reset switch
		$this->assertEquals( 1, is_countable($result) ? count( $result ) : 0 );
		$this->assertArrayHasKey( $pid, $result );
		$this->assertStringContainsString( 'http://example.org/wp-content/uploads', $result[ $pid ] );
	}

}
