<?php

class HTMLBookValidatorTest extends \WP_UnitTestCase {


	public function test_validate() {
		$v = new \Pressbooks\HTMLBook\Validator();
		$this->assertTrue( $v->validate( __DIR__ . '/data/htmlbook.html' ) );
	}
}