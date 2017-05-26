<?php

class PostTypeTest extends \WP_UnitTestCase {

	function test_add_posttypes_to_hypothesis() {
		$posttypes = \Pressbooks\PostType\add_posttypes_to_hypothesis(
			[
				'post' => 'posts',
				'page' => 'pages',
			]
		);
		$this->assertEquals( false, in_array( 'posts', $posttypes ) );
		$this->assertTrue( array_key_exists( 'chapter', $posttypes ) );
		$this->assertEquals( 'chapters', $posttypes['chapter'] );
	}

}
