<?php

class ClonerTest extends \WP_UnitTestCase {

	use utilsTrait;

	public function test_getSubdomainOrSubDirectory() {
		$result = \Pressbooks\Cloner::getSubdomainOrSubDirectory( 'https://sub.domain.com/path/' );
		$this->assertEquals( $result, 'path' );
		$result = \Pressbooks\Cloner::getSubdomainOrSubDirectory( 'https://sub.domain.com/path' );
		$this->assertEquals( $result, 'path' );
		$result = \Pressbooks\Cloner::getSubdomainOrSubDirectory( 'https://sub.domain.com/' );
		$this->assertEquals( $result, 'sub' );
		$result = \Pressbooks\Cloner::getSubdomainOrSubDirectory( 'https://sub.domain.com' );
		$this->assertEquals( $result, 'sub' );
	}

}
