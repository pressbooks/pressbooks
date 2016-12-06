<?php

class PostTypeTest extends \WP_UnitTestCase {

	/**
	 * @covers \Pressbooks\PostType\add_posttypes_to_hypothesis
	 */
	function test_add_posttypes_to_hypothesis() {
		$posttypes = \Pressbooks\PostType\add_posttypes_to_hypothesis( array(
			'post' => 'posts',
			'page' => 'pages',
		) );
		$this->assertEquals( false, in_array( 'posts' , $posttypes ) );
		$this->assertTrue( array_key_exists( 'chapter' , $posttypes ) );
		$this->assertEquals( 'chapters', $posttypes['chapter'] );
	}

}
