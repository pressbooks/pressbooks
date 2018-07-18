<?php

class Shortcodes_Attributions_Attachments extends \WP_UnitTestCase {

	use utilsTrait;

	protected $att;

	public function setUp() {
		parent::setUp();

		$this->att = $this->getMockBuilder( '\Pressbooks\Shortcodes\Attribution\Attachments' )
		                  ->setMethods( NULL )
		                  ->disableOriginalConstructor()
		                  ->getMock();
	}

	public function test_getInstance() {

		$val = $this->att->init();

		$this->assertTrue( $val instanceof \Pressbooks\Shortcodes\Attributions\Attachments );

		global $shortcode_tags;
		$this->assertArrayHasKey( 'media_attributions', $shortcode_tags );
	}

}
