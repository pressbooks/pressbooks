<?php

use Pressbooks\Admin\Fields\Date;
use Pressbooks\Admin\Fields\Text;
use Pressbooks\Admin\Fields\TextArea;
use Pressbooks\Admin\Fields\Url;

/**
 * @group metaboxes
 */
class Admin_Fields extends \WP_UnitTestCase {
	use utilsTrait;

	public function set_up()
	{
		parent::set_up();

		$new_post['post_type'] = 'chapter';

		$GLOBALS['post'] = get_post( $this->factory()->post->create_object( [
			'post_type' => 'chapter'
		] ) );
	}

	public function test_sanitize_date() {
		$field = new Date( 'test', 'Test' );

		$this->assertEquals( 1639699200, $field->sanitize( '2021-12-17' ) );
	}

	public function test_sanitize_text() {
		$field = new Text( 'test', 'Test' );

		$this->assertEquals( 'Title', $field->sanitize( "<h2>Title</h2>" ) );
	}

	public function test_sanitize_text_with_html() {
		$field = new TextArea( 'test', 'Test' );

		$this->assertEquals( "<h2>Title</h2>", $field->sanitize( "<h2>Title</h2>" ) );
	}

	public function test_sanitize_url() {
		$field = new Url( 'test', 'Test' );

		$this->assertEquals( 'http://pressbooks.com', $field->sanitize( 'pressbooks.com' ) );
	}

}
