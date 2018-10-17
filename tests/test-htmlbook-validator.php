<?php

class HTMLBook_ValidatorTest extends \WP_UnitTestCase {


	public function test_validate() {
		$v = new \Pressbooks\HTMLBook\Validator();
		$this->assertFalse( $v->validate( __DIR__ . '/data/template.php' ) );
		$this->assertNotEmpty( $v->getErrors() );

		$this->assertTrue( $v->validate( __DIR__ . '/data/htmlbook.html' ) );
		$this->assertEmpty( $v->getErrors() );
	}
}
