<?php

require_once( PB_PLUGIN_DIR . 'inc/admin/metaboxes/namespace.php' );

class MetaboxesTest extends \WP_UnitTestCase {
	use utilsTrait;

	/**
	 * @group metaboxes
	 */
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

	/**
	 * @group metaboxes
	 */
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
		$this->assertStringContainsString( '<div id="misc-publishing-actions">', $buffer );
		$this->assertStringContainsString( '<input type="checkbox" name="export_visibility"', $buffer );
		$this->assertStringNotContainsString( '<input type="checkbox" name="glossary_visibility" id="glossary_visibility"', $buffer );

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
		$this->assertStringContainsString( '<div id="misc-publishing-actions">', $buffer );
		$this->assertStringNotContainsString( '<input type="checkbox" name="export_visibility"', $buffer );
		$this->assertStringContainsString( '<input type="checkbox" name="glossary_visibility" id="glossary_visibility"', $buffer );
	}

	/**
	 * @group metaboxes
	 */
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
		$_POST['post_password'] = 'goodbye';
		$post->post_password = 'hello';

		\Pressbooks\Admin\Metaboxes\publish_fields_save( $pid, $post, true );
		$post = get_post( $pid );
		$this->assertEquals( 'hello', $post->post_password ); // Defaults to $post->post_password
		$post->post_password = '';
		\Pressbooks\Admin\Metaboxes\publish_fields_save( $pid, $post, true );
		$post = get_post( $pid );
		$this->assertEquals( 'goodbye', $post->post_password );
		unset( $_POST['post_password'] );

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

	/**
	 * @group metaboxes
	 */
	public function test_part_save_box() {
		$post = [ 'post_status' => 'draft'];
		ob_start();
		\Pressbooks\Admin\Metaboxes\part_save_box( (object) $post );
		$buffer = ob_get_clean();
		$this->assertStringContainsString( '<div class="submitbox" id="submitpost">', $buffer );
		$this->assertStringContainsString( '<input name="publish" id="publish" type="submit"', $buffer );

		$post = [ 'post_status' => 'publish'];
		ob_start();
		\Pressbooks\Admin\Metaboxes\part_save_box( (object) $post );
		$buffer = ob_get_clean();
		$this->assertStringContainsString( '<div class="submitbox" id="submitpost">', $buffer );
		$this->assertStringContainsString( '<input name="save" id="publish" type="submit"', $buffer );
	}

	/**
	 * @group metaboxes
	 */
	public function test_metadata_save_box() {
		$post = [ 'post_status' => 'draft'];
		ob_start();
		\Pressbooks\Admin\Metaboxes\metadata_save_box( (object) $post );
		$buffer = ob_get_clean();
		$this->assertStringContainsString( '<div class="submitbox" id="submitpost">', $buffer );
		$this->assertStringContainsString( '<input name="publish" id="publish" type="submit"', $buffer );

		$post = [ 'post_status' => 'publish'];
		ob_start();
		\Pressbooks\Admin\Metaboxes\metadata_save_box( (object) $post );
		$buffer = ob_get_clean();
		$this->assertStringContainsString( '<div class="submitbox" id="submitpost">', $buffer );
		$this->assertStringContainsString( '<input name="save" id="publish" type="submit"', $buffer );
	}

	/**
	 * @group metaboxes
	 */
	public function test_a11y_contributor_tweaks() {
		// Mock Screen for taxonomy editor
		global $current_screen;
		$current_screen = WP_Screen::get( 'edit-contributor' );

		ob_start();
		\Pressbooks\Admin\Metaboxes\a11y_contributor_tweaks();
		$buffer = ob_get_clean();
		$this->assertStringContainsString( 'setCustomValidity(', $buffer );
	}

	/**
	 * @group metaboxes
	 */
	public function test_contributor_metaboxes() {
		$user_id = $this->factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user_id );
		ob_start();
		\Pressbooks\Admin\Metaboxes\contributor_add_form();
		$buffer = ob_get_clean();

		$this->assertStringContainsString( '<img style="display: none" src="" id="contributor-picture-thumbnail" width="120" />', $buffer );
		$this->assertStringContainsString( '<input type="hidden" name="contributor_picture" id="contributor-picture">', $buffer );
		$this->assertStringContainsString( '<label for="contributor-first-name">', $buffer );
	}

	/**
	 * @group metaboxes
	 */
	public function test_contributor_editor_settings() {
		$editor_settings = \Pressbooks\Admin\Metaboxes\get_editor_settings();
		$this->assertStringContainsString( \Pressbooks\Contributors::TAXONOMY . '_description', $editor_settings['textarea_name'] );
		$this->assertStringContainsString( 'bold,italic,|,link,unlink,|,undo,redo', $editor_settings['tinymce']['toolbar1'] );
	}

	/**
	 * @group metaboxes
	 */
	public function test_contributor_table_columns() {
		$contributor_columns = \Pressbooks\Admin\Metaboxes\contributor_table_columns( [] );
		$this->assertArrayHasKey( \Pressbooks\Contributors::TAXONOMY . '_description', $contributor_columns );
		$this->assertArrayHasKey( \Pressbooks\Contributors::TAXONOMY . '_institution', $contributor_columns );
		$this->assertArrayHasKey( \Pressbooks\Contributors::TAXONOMY . '_picture', $contributor_columns );
	}

	/**
	 * @group metaboxes
	 */
	public function test_contributor_sortable_columns() {
		$contributor_sortable_columns = \Pressbooks\Admin\Metaboxes\contributor_sortable_columns( [] );
		$this->assertArrayHasKey( 'name', $contributor_sortable_columns );
	}

	/**
	 * @group metaboxes
	 */
	public function test_contributor_custom_columns() {
		$contributor = new \Pressbooks\Contributors();
		$taxonomy = new \Pressbooks\Taxonomy(
			$this->getMockBuilder( '\Pressbooks\Licensing' )->getMock(),
			$contributor
		);
		$taxonomy->registerTaxonomies();
		$post_id = $this->_createChapter();

		$person = $contributor->insert( 'Pat Metheny', $post_id, 'contributors' );

		$term = get_term_by( 'term_id', $person['term_id'], 'contributor' );
		add_term_meta( $term->term_id,
			\Pressbooks\Contributors::TAXONOMY . '_description',
			'<strong>I am a description</strong>'
		);
		add_term_meta( $term->term_id,
			\Pressbooks\Contributors::TAXONOMY . '_institution',
			'Pressbooks University'
		);
		add_term_meta( $term->term_id,
			\Pressbooks\Contributors::TAXONOMY . '_picture',
			'Sorry, there is not picture! :/'
		);
		ob_start();
		\Pressbooks\Admin\Metaboxes\contributor_custom_columns(
			'',
			\Pressbooks\Contributors::TAXONOMY . '_description',
			$term->term_id
		);
		$buffer = ob_get_clean();
		$this->assertStringContainsString( 'I am a description', $buffer );
		$this->assertStringNotContainsString( 'strong', $buffer );

		ob_start();
		\Pressbooks\Admin\Metaboxes\contributor_custom_columns(
			'',
			\Pressbooks\Contributors::TAXONOMY . '_institution',
			$term->term_id
		);
		$buffer = ob_get_clean();
		$this->assertStringContainsString( 'Pressbooks University', $buffer );

		ob_start();
		\Pressbooks\Admin\Metaboxes\contributor_custom_columns(
			'',
			\Pressbooks\Contributors::TAXONOMY . '_picture',
			$term->term_id
		);
		$buffer = ob_get_clean();
		$this->assertStringContainsString( 'Sorry, there is not picture! :/', $buffer );
	}
}
