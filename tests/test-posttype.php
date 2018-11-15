<?php

use function \Pressbooks\PostType\{
	list_post_types,
	register_post_types,
	row_actions,
	disable_months_dropdown,
	after_title,
	wp_editor_settings,
	display_post_states,
	register_meta,
	register_post_statii,
	add_post_types_rss,
	add_posttypes_to_hypothesis,
	can_export,
	get_post_type_label,
	filter_post_type_label
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
		$this->assertArrayHasKey( 'glossary', $wp_post_types );

		$wp_post_types = $wp_post_types_old;
	}

	function test_row_actions() {
		$actions['do-not-touch'] = 1;
		$actions['view'] = 1;
		$actions['inline hide-if-no-js'] = 1;

		$x = new \StdClass();
		$x->post_type = 'imaginary-post-type';
		$actions2 = row_actions( $actions, $x );
		$this->assertEquals( $actions, $actions2 );

		$x->post_type = 'glossary';
		$actions2 = row_actions( $actions, $x );
		$this->assertNotEquals( $actions, $actions2 );
		$this->assertArrayNotHasKey( 'view', $actions2 );
		$this->assertArrayNotHasKey( 'inline hide-if-no-js', $actions2 );
	}

	function test_disable_months_dropdown() {
		$this->assertTrue( disable_months_dropdown( false, 'glossary' ) );

		$this->assertTrue( disable_months_dropdown( true, 'imaginary-post-type' ) );
		$this->assertFalse( disable_months_dropdown( false, 'imaginary-post-type' ) );
	}

	function test_after_title() {
		$x = new \StdClass();

		$x->post_type = 'imaginary-post-type';
		ob_start();
		after_title( $x );
		$buffer = ob_get_clean();
		$this->assertEmpty( $buffer );

		$x->post_type = 'glossary';
		ob_start();
		after_title( $x );
		$buffer = ob_get_clean();
		$this->assertContains( 'not supported', $buffer );

		$x->post_type = 'back-matter';
		$x->ID = 0;
		ob_start();
		after_title( $x );
		$buffer = ob_get_clean();
		$this->assertContains( 'id="pb-post-type-notice"', $buffer );
	}

	function test_wp_editor_settings() {

		global $post;
		$settings['tinymce'] = true;

		$settings2 = wp_editor_settings( $settings );
		$this->assertEquals( $settings, $settings2 );

		$x = new \StdClass();
		$x->post_type = 'glossary';
		$post = $x;

		$settings2 = wp_editor_settings( $settings );
		$this->assertNotEquals( $settings, $settings2 );
		$this->assertTrue( $settings2['tinymce'] === false );
	}

	function test_display_post_states() {
		$x = new \StdClass();

		$post_states['private'] = 'Private';
		$x->post_type = 'imaginary-post-type';
		$post_states = display_post_states( $post_states, $x );
		$this->assertEquals( 'Private', $post_states['private'] );

		$x->post_type = 'glossary';
		$post_states = display_post_states( $post_states, $x );
		$this->assertEquals( 'Unlisted', $post_states['private'] );
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
		$this->assertEquals( get_post_type_label( 'glossary' ), 'Glossary' );
	}

	function test_filter_post_type_label() {
		$this->assertEquals( filter_post_type_label( 'Chapter', [ 'post_type' => 'chapter' ] ), 'Chapter' );
		update_option( 'pressbooks_theme_options_global', [ 'chapter_label' => 'Section' ] );
		$this->assertEquals( filter_post_type_label( 'Chapter', [ 'post_type' => 'chapter' ] ), 'Section' );
		update_option( 'pressbooks_theme_options_global', [ 'part_label' => 'Unit' ] );
		$this->assertEquals( filter_post_type_label( 'Chapter', [ 'post_type' => 'chapter' ] ), 'Chapter' );
	}
}
