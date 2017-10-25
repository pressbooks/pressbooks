<?php

class HTMLBookTest extends \WP_UnitTestCase {

	public function test_isHeading() {

		$element = new \Pressbooks\HTMLBook\Element();

		$this->assertTrue( $element->isHeading( '<h1>Hello World!</h1>' ) );
		$this->assertTrue( $element->isHeading( '<h2>Hello World!</h2>' ) );
		$this->assertTrue( $element->isHeading( '<h3>Hello World!</h3>' ) );
		$this->assertTrue( $element->isHeading( '<h4>Hello World!</h4>' ) );
		$this->assertTrue( $element->isHeading( '<h5>Hello World!</h5>' ) );
		$this->assertTrue( $element->isHeading( '<h6>Hello World!</h6>' ) );
		$this->assertFalse( $element->isHeading( '<header>Hello World!</header>' ) );
		$this->assertFalse( $element->isHeading( '<p>Hello World!</p>' ) );

		$h1 = new \Pressbooks\HTMLBook\Heading\H1();
		$h2 = new \Pressbooks\HTMLBook\Heading\H2();
		$h3 = new \Pressbooks\HTMLBook\Heading\H3();
		$h4 = new \Pressbooks\HTMLBook\Heading\H4();
		$h5 = new \Pressbooks\HTMLBook\Heading\H5();
		$h6 = new \Pressbooks\HTMLBook\Heading\H6();
		$header = new \Pressbooks\HTMLBook\Heading\Header();
		$p = new \Pressbooks\HTMLBook\Block\Paragraph();

		$this->assertTrue( $element->isHeading( $h1 ) );
		$this->assertTrue( $element->isHeading( $h2 ) );
		$this->assertTrue( $element->isHeading( $h3 ) );
		$this->assertTrue( $element->isHeading( $h4 ) );
		$this->assertTrue( $element->isHeading( $h5 ) );
		$this->assertTrue( $element->isHeading( $h6 ) );
		$this->assertFalse( $element->isHeading( $header ) );
		$this->assertFalse( $element->isHeading( $p ) );
	}
}
