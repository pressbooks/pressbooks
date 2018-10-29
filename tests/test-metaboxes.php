<?php

require_once( PB_PLUGIN_DIR . 'inc/admin/metaboxes/namespace.php' );


class MetaboxesTest extends \WP_UnitTestCase {

	public function test_title_update() {

		$title = get_option( 'blogname' );
		\Pressbooks\Admin\Metaboxes\title_update( null, null, 'pb_some_key', 'Nothing should happen' );
		$option = get_option( 'blogname' );
		$this->assertEquals( $option, $title );

		$title = 'Hello World!';
		\Pressbooks\Admin\Metaboxes\title_update( null, null, 'pb_title', $title );
		$option = get_option( 'blogname' );
		$this->assertEquals( $option, $title );
	}

	public function test_add_meta_boxes() {

		global $wp_meta_boxes;
		$c = custom_metadata_manager::instance();

		update_option( 'pressbooks_show_expanded_metadata', 1 );

		\Pressbooks\Admin\Metaboxes\add_meta_boxes();

		$this->assertArrayHasKey( 'chapter', $wp_meta_boxes );
		$this->assertArrayHasKey( 'part', $wp_meta_boxes );
		$this->assertArrayHasKey( 'metadata', $c->metadata );
		$this->assertArrayHasKey( 'general-book-information', $c->metadata['metadata'] );
		$this->assertArrayHasKey( 'additional-catalog-information', $c->metadata['metadata'] );
	}

	public function test_status_visibility_box() {
		\Pressbooks\Metadata\init_book_data_models();

		// Create front-matter post
		$new_post = [
			'post_title' => 'Test Chapter: ' . rand(),
			'post_type' => 'front-matter',
			'post_status' => 'draft',
			'post_content' => 'Hello World',
		];
		$pid = $this->factory()->post->create_object( $new_post );
		$post = get_post( $pid );

		// Mock Screen for front-matter
		global $current_screen;
		$current_screen = WP_Screen::get( 'front-matter' );
		ob_start();
		\Pressbooks\Admin\Metaboxes\status_visibility_box( $post );
		$buffer = ob_get_clean();
		$this->assertContains( '<div id="misc-publishing-actions">', $buffer );
		$this->assertContains( '<input type="checkbox" name="export_visibility"', $buffer );
		$this->assertNotContains( '<input type="checkbox" name="glossary_visibility" id="glossary_visibility"', $buffer );

		// Create glossary post
		$new_post = [
			'post_title' => 'Test Glossary: ' . rand(),
			'post_type' => 'glossary',
			'post_status' => 'private',
			'post_content' => 'Hello World',
		];
		$pid = $this->factory()->post->create_object( $new_post );
		$post = get_post( $pid );

		// Mock Screen for glossary
		$current_screen = WP_Screen::get( 'glossary' );
		ob_start();
		\Pressbooks\Admin\Metaboxes\status_visibility_box( $post );
		$buffer = ob_get_clean();
		$this->assertContains( '<div id="misc-publishing-actions">', $buffer );
		$this->assertNotContains( '<input type="checkbox" name="export_visibility"', $buffer );
		$this->assertContains( '<input type="checkbox" name="glossary_visibility" id="glossary_visibility"', $buffer );
	}

	public function test_publish_fields_save() {

		\Pressbooks\Metadata\init_book_data_models();

		global $pagenow;
		$pagenow = 'post.php';

		$_POST['web_visibility'] = '1';
		$_POST['export_visibility'] = '1';
		$_POST['pb_show_title'] = 'on';
		$_POST['require_password'] = '0';

		// Create front-matter post
		$new_post = [
			'post_title' => 'Test Chapter: ' . rand(),
			'post_type' => 'front-matter',
			'post_status' => 'draft',
			'post_content' => 'Hello World',
		];
		$pid = $this->factory()->post->create_object( $new_post );
		$post = get_post( $pid );

		\Pressbooks\Admin\Metaboxes\publish_fields_save( $pid, $post, true );
		$post = get_post( $pid );
		$this->assertEquals( 'publish', $post->post_status );
		$this->assertEquals( 'on', get_post_meta( $pid, 'pb_show_title', true ) );

		// Web-Only

		$_POST['web_visibility'] = 1;
		$_POST['export_visibility'] = 0;
		$_POST['pb_show_title'] = 'off';

		\Pressbooks\Admin\Metaboxes\publish_fields_save( $pid, $post, true );
		$post = get_post( $pid );
		$this->assertEquals( 'web-only', $post->post_status );
		$this->assertEmpty( get_post_meta( $pid, 'pb_show_title', true ) );

		// Private

		$_POST['web_visibility'] = 0;
		$_POST['export_visibility'] = 1;

		\Pressbooks\Admin\Metaboxes\publish_fields_save( $pid, $post, true );
		$post = get_post( $pid );
		$this->assertEquals( 'private', $post->post_status );

		// Private again, (when content is set to show in exports only, multiple saves can unpublish it.)

		$_POST['web_visibility'] = 0;
		$_POST['export_visibility'] = 1;

		\Pressbooks\Admin\Metaboxes\publish_fields_save( $pid, $post, true );
		$post = get_post( $pid );
		$this->assertEquals( 'private', $post->post_status );

		// Draft

		$_POST['web_visibility'] = 0;
		$_POST['export_visibility'] = 0;

		\Pressbooks\Admin\Metaboxes\publish_fields_save( $pid, $post, true );
		$post = get_post( $pid );
		$this->assertEquals( 'draft', $post->post_status );

		// Password

		$_POST['web_visibility'] = '1';
		$_POST['require_password'] = '1';
		$post->post_password = 'hello';

		\Pressbooks\Admin\Metaboxes\publish_fields_save( $pid, $post, true );
		$this->assertEquals( 'hello', $post->post_password );

		// Clear Password

		$_POST['web_visibility'] = 0;
		$_POST['require_password'] = '0';

		\Pressbooks\Admin\Metaboxes\publish_fields_save( $pid, $post, true );
		$post = get_post( $pid );
		$this->assertEmpty( $post->post_password );

		// Create glossary post
		$new_post = [
			'post_title' => 'Test Glossary: ' . rand(),
			'post_type' => 'glossary',
			'post_status' => 'private',
			'post_content' => 'Hello World',
		];
		$pid = $this->factory()->post->create_object( $new_post );
		$post = get_post( $pid );

		$_POST['glossary_visibility'] = 1;
		\Pressbooks\Admin\Metaboxes\publish_fields_save( $pid, $post, true );
		$post = get_post( $pid );
		$this->assertEquals( 'publish', $post->post_status );

		$_POST['glossary_visibility'] = 0;
		\Pressbooks\Admin\Metaboxes\publish_fields_save( $pid, $post, true );
		$post = get_post( $pid );
		$this->assertEquals( 'private', $post->post_status );
	}

}
