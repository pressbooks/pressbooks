<?php

class CoverGenerator_InputTest extends \WP_UnitTestCase {

	/**
	 * @var \Pressbooks\Covergenerator\Input
	 */
	public $input;

	/**
	 *
	 */
	public function setUp() {
		parent::setUp();
		$this->input = new \Pressbooks\Covergenerator\Input();
	}

	public function test_mutatorMethods() {

		$this->assertEmpty( $this->input->getTitle() );
		$this->assertEmpty( $this->input->getSpineTitle() );
		$this->assertEmpty( $this->input->getSubtitle() );
		$this->assertEmpty( $this->input->getAuthor() );
		$this->assertEmpty( $this->input->getSpineAuthor() );
		$this->assertEmpty( $this->input->getAbout() );
		$this->assertEmpty( $this->input->getIsbnImage() );
		$this->assertEmpty( $this->input->getTextTransform() );
		$this->assertEmpty( $this->input->getTrimWidth() );
		$this->assertEmpty( $this->input->getTrimHeight() );
		$this->assertEmpty( $this->input->getTrimBleed() );
		$this->assertEmpty( $this->input->getSpineWidth() );
		$this->assertEmpty( $this->input->getSpineBackgroundColor() );
		$this->assertEmpty( $this->input->getSpineFontColor() );
		$this->assertEmpty( $this->input->getBackBackgroundColor() );
		$this->assertEmpty( $this->input->getBackFontColor() );
		$this->assertEmpty( $this->input->getFrontBackgroundImage() );
		$this->assertEmpty( $this->input->getFrontBackgroundColor() );
		$this->assertEmpty( $this->input->getFrontFontColor() );

		// Make sure setters are all fluent
		$this->input
			->setTitle( 'a' )
			->setSpineTitle( 'b' )
			->setSubtitle( 'c' )
			->setAuthor( 'd' )
			->setSpineAuthor( 'e' )
			->setAbout( 'f' )
			->setIsbnImage( 'g' )
			->setTextTransform( 'h' )
			->setTrimWidth( 'i' )
			->setTrimHeight( 'j' )
			->setTrimBleed( 'k' )
			->setSpineWidth( 'l' )
			->setSpineBackgroundColor( 'm' )
			->setSpineFontColor( 'n' )
			->setBackBackgroundColor( 'o' )
			->setBackFontColor( 'p' )
			->setFrontBackgroundImage( 'q' )
			->setFrontBackgroundColor( 'r' )
			->setFrontFontColor( 's' );

		$this->assertEquals( 'a', $this->input->getTitle() );
		$this->assertEquals( 'b', $this->input->getSpineTitle() );
		$this->assertEquals( 'c', $this->input->getSubtitle() );
		$this->assertEquals( 'd', $this->input->getAuthor() );
		$this->assertEquals( 'e', $this->input->getSpineAuthor() );
		$this->assertEquals( 'f', $this->input->getAbout() );
		$this->assertEquals( 'g', $this->input->getIsbnImage() );
		$this->assertEquals( 'h', $this->input->getTextTransform() );
		$this->assertEquals( 'i', $this->input->getTrimWidth() );
		$this->assertEquals( 'j', $this->input->getTrimHeight() );
		$this->assertEquals( 'k', $this->input->getTrimBleed() );
		$this->assertEquals( 'l', $this->input->getSpineWidth() );
		$this->assertEquals( 'm', $this->input->getSpineBackgroundColor() );
		$this->assertEquals( 'n', $this->input->getSpineFontColor() );
		$this->assertEquals( 'o', $this->input->getBackBackgroundColor() );
		$this->assertEquals( 'p', $this->input->getBackFontColor() );
		$this->assertEquals( '"q"', $this->input->getFrontBackgroundImage() );
		$this->assertEquals( 'r', $this->input->getFrontBackgroundColor() );
		$this->assertEquals( 's', $this->input->getFrontFontColor() );
	}

}
