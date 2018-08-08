<?php

use function \Pressbooks\PostType\{
	list_post_types,
	register_post_types,
	register_meta,
	register_post_statii,
	add_post_types_rss,
	add_posttypes_to_hypothesis,
	can_export,
	get_post_type_label
};

class PostTypeTest extends \WP_UnitTestCase {

	function test_list_post_types() {
		$v = list_post_types();
		$this->assertTrue( is_array( $v ) );
	}

	function test_register_post_types() {
		global $wp_post_types;
		$wp_post_types_old = $wp_post_types;
		$wp_post_types = [];

		register_post_types();
		$this->assertArrayHasKey( 'front-matter', $wp_post_types );
		$this->assertArrayHasKey( 'part', $wp_post_types );
		$this->assertArrayHasKey( 'chapter', $wp_post_types );
		$this->assertArrayHasKey( 'back-matter', $wp_post_types );
		$this->assertArrayHasKey( 'metadata', $wp_post_types );

		$wp_post_types = $wp_post_types_old;
	}

	function test_register_meta() {
		global $wp_meta_keys;
		$wp_meta_keys_old = $wp_meta_keys;
		$wp_meta_keys = [];

		register_meta();
		$this->assertArrayHasKey( 'post', $wp_meta_keys );
		$this->assertArrayHasKey( 'pb_show_title', $wp_meta_keys['post']['chapter'] );
		$this->assertArrayHasKey( 'pb_short_title', $wp_meta_keys['post']['chapter'] );

		$wp_meta_keys = $wp_meta_keys_old;
	}

	function test_register_post_statii() {
		global $wp_post_statuses;
		$wp_post_statuses_old = $wp_post_statuses;
		$wp_post_statuses = [];

		register_post_statii();
		$this->assertArrayHasKey( 'web-only', $wp_post_statuses );

		$wp_post_statuses = $wp_post_statuses_old;
	}

	function test_add_post_types_rss() {
		$args['feed'] = true;
		$args = add_post_types_rss( $args );
		$this->assertArrayHasKey( 'feed', $args );
		$this->assertArrayHasKey( 'post_type', $args );
		$this->assertTrue( is_array( $args['post_type'] ) );
	}

	function test_add_posttypes_to_hypothesis() {
		$posttypes = add_posttypes_to_hypothesis(
			[
				'post' => 'posts',
				'page' => 'pages',
			]
		);
		$this->assertEquals( false, in_array( 'posts', $posttypes ) );
		$this->assertTrue( array_key_exists( 'chapter', $posttypes ) );
		$this->assertEquals( 'chapters', $posttypes['chapter'] );
	}

	function test_can_export() {
		\Pressbooks\PostType\register_post_statii();
		$pid = $this->factory()->post->create();

		$this->assertTrue( is_bool( can_export() ) );
		wp_update_post( [ 'ID' => $pid, 'post_status' => 'draft' ] );
		$this->assertFalse( can_export( $pid ) );
		update_post_meta( $pid, 'pb_export', 'on' );
		$this->assertTrue( can_export( $pid ) );
		delete_post_meta( $pid, 'pb_export' );
		$this->assertFalse( can_export( $pid ) );
		wp_update_post( [ 'ID' => $pid, 'post_status' => 'publish' ] );
		$this->assertTrue( can_export( $pid ) );
		wp_update_post( [ 'ID' => $pid, 'post_status' => 'private' ] );
		$this->assertTrue( can_export( $pid ) );
		wp_update_post( [ 'ID' => $pid, 'post_status' => 'web-only' ] );
		$this->assertFalse( can_export( $pid ) );
	}

	function test_get_post_type_label() {
		$this->assertFalse( get_post_type_label( 'junk-post-type' ) );
		$this->assertEquals( get_post_type_label( 'metadata' ), 'Book Information' );
		$this->assertEquals( get_post_type_label( 'part' ), 'Part' );
		$this->assertEquals( get_post_type_label( 'front-matter' ), 'Front Matter' );
		$this->assertEquals( get_post_type_label( 'back-matter' ), 'Back Matter' );
		$this->assertEquals( get_post_type_label( 'chapter' ), 'Chapter' );
	}
}
