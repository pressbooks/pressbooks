<?php

class Shortcodes_Glossary extends \WP_UnitTestCase {

	use utilsTrait;

	/**
	 * @var \Pressbooks\Shortcodes\Attributions\Attachments
	 */
	protected $glossary;

	public function setUp() {
		parent::setUp();

		$this->glossary = $this->getMockBuilder( '\Pressbooks\Shortcodes\Glossary\Glossary' )
		                  ->setMethods( NULL )
		                  ->disableOriginalConstructor()
		                  ->getMock();
	}

	public function test_getInstance() {

		$val = $this->glossary->init();

		$this->assertTrue( $val instanceof \Pressbooks\Shortcodes\Glossary\Glossary );

		global $shortcode_tags;

		$this->assertArrayHasKey( 'pb_glossary', $shortcode_tags );

	}

}
